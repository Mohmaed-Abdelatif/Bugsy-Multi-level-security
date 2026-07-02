<?php
// CartController manages shopping cart operations
// V1: Session-based authentication
// all methods require authentication
// users can only access their own cart
// but admin can access to all users 's carts

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Cart;
use Models\V1\CartItem;
use Models\V1\Product;

use Models\V1\PromoCode;

class CartController extends BaseController
{
    private $cartModel;
    private $cartItemModel;
    private $productModel;

    private $promoModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel = new Cart();
        $this->cartItemModel = new CartItem();
        $this->productModel = new Product();

        $this->promoModel = new PromoCode();
    }



    // View Cart
    //get current user's cart with items: get /api/V1/cart
    public function show()
    {
        // Require authentication (login)
        $this->requireAuth();
        
        // Get current user ID fom session
        // so user cann't show other user cart (still not secure so use jwt inv2&3)
        $userId = $this->getUserId();
        
        // Get or create cart
        $cart = $this->cartModel->getOrCreate($userId);
        
        if (!$cart) {
            return $this->error('Failed to get cart', 500);
        }
        
        // Get cart with items
        $rawRows = $this->cartModel->getCartWithItems($cart['id']);
        
        $items = [];
        $subtotal  = 0;

        foreach ($rawRows as $row) {
            if (!$row['item_id']) continue;

            $itemSubtotal  = (float)$row['subtotal'];
            $subtotal    += $itemSubtotal;

            $items[] = [
                'id'           => $row['item_id'],
                'product_id'   => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity'     => (int)$row['quantity'],
                'price'        => (float)$row['price'],
                'subtotal'     => $itemSubtotal ,
                'main_image'   => $row['main_image'] ? APP_URL . '/public/uploads/products/' . $row['main_image'] : APP_URL . '/public/uploads/products/no-image.png'  ,
                'stock'        => (int)$row['stock'],
            ];
        }

        // Promo preview — re-validated fresh on every cart view
        $promoCode = $this->cartModel->getAttachedPromoCode($userId);
        $discount = 0.00;
        $promoInfo = null;

        if ($promoCode) {
            $validation = $this->promoModel->validate($promoCode, $userId, $subtotal);

            if ($validation['valid']) {
                $discount = $this->promoModel->calculateDiscount($validation['promo'], $subtotal);
                $promoInfo = [
                    'code' => $promoCode,
                    'discount_type' => $validation['promo']['discount_type'],
                    'discount_value' => (float)$validation['promo']['discount_value'],
                    'valid' => true
                ];
            } else {
                // promo became invalid since attached — surface it, don't crash the cart
                $promoInfo = [
                    'code' => $promoCode,
                    'valid' => false,
                    'message' => $validation['message']
                ];
            }
        }

        $total = round($subtotal - $discount, 2);


        $this->json([
            'cart' => [
                'user_id' => $userId,
                'items'   => $items,
                'count'   => count($items),
                'subtotal'   => round($subtotal , 2),
                'discount' => $discount,
                'total'    => $total,
                'promo'    => $promoInfo,
            ]
        ]);
    }



    //add product to cart: post /api/V1/cart/add
    /*
     * Request Body:
     * {
     *     "product_id": 5,
     *     "quantity": 2
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Product added to cart",
     *     "data": {
     *         "cart": {...}
     *     }
     * }
    */
    public function add()
    {
        // Require authentication
        $this->requireAuth();

        // Get input
        $productId = $this->getInput('product_id');
        $quantity = $this->getInput('quantity', 1);

        // Validate input
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }
        
        if (!is_numeric($quantity) || $quantity < 1) {
            return $this->error('Invalid quantity', 400);
        }

        // Get product
        $product = $this->productModel->find($productId);

        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        // Check if product is available
        if (!$product['is_available']) {
            return $this->error('Product is not available', 400);
        }
        
        // Check stock
        if ($product['stock'] < $quantity) {
            return $this->error("Only {$product['stock']} items available in stock", 400);
        }

        // Get or create cart
        $userId = $this->getUserId();
        $cart = $this->cartModel->getOrCreate($userId);

        if (!$cart) {
            return $this->error('Failed to create cart', 500);
        }

        // Add item to cart or edite quantity if exist
        $itemId = $this->cartItemModel->addItem(
            $cart['id'],
            $productId,
            $quantity,
            $product['price']
        );

        if (!$itemId) {
            return $this->error('Failed to add item to cart', 500);
        }


        //get updated cart (cart that added or edited item)
        $updatedCart = $this->cartModel->getWithItems($cart['id']);
        // Log action
        $this->log('cart_item_added', [
            'user_id' => $userId,
            'product_id' => $productId,
            'quantity' => $quantity
        ]);


        return $this->json([
            'message' => 'Product added to cart',
            'cart' => $updatedCart
        ]);
    }



    //update cart item quantity: put /api/V1/cart/items/{id}
    //{id} is added as a parameter direct as i made it in app.php so no neet to make $this->requestData['params'][$key]
    //this user in his cart and edite quantity , abouve user add product when explore and add if exist quantity increase
    /*
     * Request Body:
     * {
     *     "quantity": 3
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Cart item updated",
     *     "data": {
     *         "cart": {...}
     *     }
     * }
    */
    public function updateItem($itemId)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate item ID
        if (!$itemId || !is_numeric($itemId)) {
            return $this->error('Invalid item ID', 400);
        }
        
        // Get cart item
        $item = $this->cartItemModel->find($itemId);
        
        if (!$item) {
            return $this->error('Cart item not found', 404);
        }
        
        // Get cart and verify ownership
        $cart = $this->cartModel->find($item['cart_id']);
        
        if (!$cart) {
            return $this->error('Cart not found', 404);
        }
        
        // Check ownership
        // $this->checkOwnership($cart['user_id'], 'You cannot modify this cart');
        
        // Get new quantity
        $quantity = $this->getInput('quantity');
        
        if (!is_numeric($quantity) || $quantity < 0) {
            return $this->error('Invalid quantity', 400);
        }
        
        // If quantity is 0, remove item
        if ($quantity == 0) {
            $this->cartItemModel->removeItem($itemId);
            
            // Get updated cart
            $updatedCart = $this->cartModel->getWithItems($cart['id']);
            
            return $this->json([
                'message' => 'Item removed from cart',
                'cart' => $updatedCart
            ]);
        }
        
        // Check stock:-
        // if(!$this->cartItemModel->checkStock($item['product_id'],$quantity)){
        //     $product = $this->productModel->find($item['product_id']);
        //     return $this->error("Only {$product['stock']} items available in stock", 400);
        // }
        //or
        $product = $this->productModel->find($item['product_id']);
        if ($product && $product['stock'] < $quantity) {
            return $this->error("Only {$product['stock']} items available in stock", 400);
        }
        
        // Update quantity
        $success = $this->cartItemModel->updateQuantity($itemId, $quantity);
        
        if (!$success) {
            return $this->error('Failed to update cart item', 500);
        }
        
        // Get updated cart
        $updatedCart = $this->cartModel->getWithItems($cart['id']);
        
        // Log action
        $this->log('cart_item_updated', [
            'user_id' => $cart['user_id'],
            'item_id' => $itemId,
            'quantity' => $quantity
        ]);
        
        return $this->json([
            'message' => 'Cart item updated',
            'cart' => $updatedCart
        ]);
    }


    //remove cart item: delete /api/V1/cart/items/{id}
    public function removeItem($itemId)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate item ID
        if (!$itemId || !is_numeric($itemId)) {
            return $this->error('Invalid item ID', 400);
        }
        
        // Get cart item
        $item = $this->cartItemModel->find($itemId);
        
        if (!$item) {
            return $this->error('Cart item not found', 404);
        }
        
        // Get cart and verify ownership
        $cart = $this->cartModel->find($item['cart_id']);
        
        if (!$cart) {
            return $this->error('Cart not found', 404);
        }
        
        // Check ownership
        // $this->checkOwnership($cart['user_id'], 'You cannot modify this cart');
        
        // Remove item
        $success = $this->cartItemModel->removeItem($itemId);
        
        if (!$success) {
            return $this->error('Failed to remove item', 500);
        }
        
        // Get updated cart
        $updatedCart = $this->cartModel->getWithItems($cart['id']);
        
        // Log action
        $this->log('cart_item_removed', [
            'user_id' => $cart['user_id'],
            'item_id' => $itemId
        ]);
        
        return $this->json([
            'message' => 'Item removed from cart',
            'cart' => $updatedCart
        ]);
    }


    //clear cart 
    public function clear()
    {
        // Require authentication
        $this->requireAuth();
        
        // Get user's cart
        $userId = $this->getUserId();
        $cart = $this->cartModel->getByUserId($userId);
        
        if (!$cart) {
            return $this->error('Cart not found', 404);
        }
        
        // Clear all items
        $success = $this->cartModel->clearItems($cart['id']);

        $this->cartModel->removePromoCode($userId);
        
        if (!$success) {
            return $this->error('Failed to clear cart', 500);
        }
        
        // Log action
        $this->log('cart_cleared', ['user_id' => $userId]);
        
        return $this->json([
            'message' => 'Cart cleared'
        ]);
    }


    //get cart items count: get /api/V1/cart/count
    public function count()
    {
        // Require authentication
        $this->requireAuth();
        
        // Get count
        $userId = $this->getUserId();
        $count = $this->cartModel->getItemCount($userId);
        
        return $this->json([
            'count' => $count
        ]);
    }


    //get cart total price: get /api/V1/cart/total
    public function total()
    {
        // Require authentication
        $this->requireAuth();
        
        // Get total
        $userId = $this->getUserId();
        $total = $this->cartModel->getTotal($userId);
        
        return $this->json([
            'total' => $total
        ]);
    }
    
    // POST /api/V1/cart/promo
    public function applyPromo()
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $code = trim($this->getInput('promo_code', ''));

        if (empty($code)) {
            return $this->error('Promo code is required', 400);
        }

        // need the current subtotal to validate min_order_amount
        $subtotal = $this->cartModel->getTotal($userId);

        $validation = $this->promoModel->validate($code, $userId, $subtotal);

        if (!$validation['valid']) {
            return $this->error($validation['message'], 400);
        }

        $this->cartModel->attachPromoCode($userId, $code);

        $discount = $this->promoModel->calculateDiscount($validation['promo'], $subtotal);

        $this->log('promo_applied', ['user_id' => $userId, 'code' => $code]);

        return $this->json([
            'message' => 'Promo code applied successfully',
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => round($subtotal - $discount, 2)
        ]);
    }

    // DELETE /api/V1/cart/promo
    public function removePromo()
    {
        $this->requireAuth();
        $userId = $this->getUserId();

        $this->cartModel->removePromoCode($userId);

        $this->log('promo_removed', ['user_id' => $userId]);

        return $this->json(['message' => 'Promo code removed']);
    }


}