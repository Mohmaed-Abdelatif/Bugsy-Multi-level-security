<?php
// getUserId() reads from JWT — cannot be faked via request body
// No user_id accepted from request body or query string

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Cart;
use Models\V2\CartItem;
use Models\V1\Product; // Product reads or find are safe,use pdo in basemodel

class CartController extends BaseController
{
    private Cart     $cartModel;
    private CartItem $cartItemModel;

    public function __construct()
    {
        parent::__construct();
        $this->cartModel     = new Cart();
        $this->cartItemModel = new CartItem();
    }


    // GET /api/v2/cart
    public function show(): void
    {
        $this->requireAuth();

        $userId  = $this->getUserId();
        $rawRows = $this->cartModel->getCartWithItems($userId);

        $items = [];
        $total = 0;

        foreach ($rawRows as $row) {
            if (!$row['item_id']) continue;

            $subtotal = (float)$row['subtotal'];
            $total   += $subtotal;

            $items[] = [
                'id'           => $row['item_id'],
                'product_id'   => $row['product_id'],
                'product_name' => $row['product_name'],
                'quantity'     => (int)$row['quantity'],
                'price'        => (float)$row['price'],
                'subtotal'     => $subtotal,
                'main_image'   => $row['main_image'],
                'stock'        => (int)$row['stock'],
            ];
        }

        $this->json([
            'cart' => [
                'user_id' => $userId,
                'items'   => $items,
                'count'   => count($items),
                'total'   => round($total, 2),
            ]
        ]);
    }


    // GET /api/v2/cart/count
    public function count(): void
    {
        $this->requireAuth();

        $count = $this->cartModel->getItemCount($this->getUserId());

        $this->json(['count' => $count]);
    }


    // GET /api/v2/cart/total
    public function total(): void
    {
        $this->requireAuth();

        $total = $this->cartModel->getTotal($this->getUserId());

        $this->json(['total' => round($total, 2)]);
    }


    // POST /api/v2/cart/add
    public function add(): void
    {
        $this->requireAuth();

        $userId    = $this->getUserId();
        $productId = (int)$this->getInput('product_id', 0);
        $quantity  = (int)$this->getInput('quantity', 1);

        // Validate
        if (!$productId) {
            $this->error('Validation failed', 422, ['product_id' => 'Product ID is required']);
            return;
        }

        if ($quantity < 1) {
            $this->error('Validation failed', 422, ['quantity' => 'Quantity must be at least 1']);
            return;
        }

        // Check product exists and is available
        $productModel = new Product();
        $product      = $productModel->find($productId);

        if (!$product || !$product['is_available']) {
            $this->error('Product not found or not available', 404);
            return;
        }

        // Check stock
        if ($product['stock'] < $quantity) {
            $this->error("Insufficient stock. Available: {$product['stock']}", 400);
            return;
        }

        // Get or create cart for this user
        $cart = $this->cartModel->getOrCreateCart($userId);

        if (!$cart) {
            $this->error('Failed to get cart', 500);
            return;
        }

        // Check if product already in cart
        $existingItem = $this->cartItemModel
            ->where('cart_id',    '=', $cart['id'])
            ->where('product_id', '=', $productId)
            ->first();

        if ($existingItem) {
            // Update quantity
            $newQty = (int)$existingItem['quantity'] + $quantity;

            if ($product['stock'] < $newQty) {
                $this->error("Insufficient stock. Available: {$product['stock']}", 400);
                return;
            }

            $this->cartItemModel->update($existingItem['id'], ['quantity' => $newQty]);
        } else {
            // Add new item
            $this->cartItemModel->create([
                'cart_id'    => $cart['id'],
                'product_id' => $productId,
                'quantity'   => $quantity,
                'price'      => $product['price'],
            ]);
        }

        $this->log('cart_item_added_v2', [
            'user_id'    => $userId,
            'product_id' => $productId,
            'quantity'   => $quantity,
        ]);

        // Return updated cart
        $this->show();
    }


    // PUT /api/v2/cart/items/{id}
    public function updateItem(int $itemId): void
    {
        $this->requireAuth();

        $userId   = $this->getUserId();
        $quantity = (int)$this->getInput('quantity', 0);

        if ($quantity < 1) {
            $this->error('Validation failed', 422, ['quantity' => 'Quantity must be at least 1']);
            return;
        }

        // Verify item belongs to this user's cart — IDOR fix
        if (!$this->itemBelongsToUser($itemId, $userId)) {
            $this->error('You do not have access to this cart item', 403);
            return;
        }

        $item    = $this->cartItemModel->find($itemId);
        $product = (new Product())->find($item['product_id']);

        if ($product['stock'] < $quantity) {
            $this->error("Insufficient stock. Available: {$product['stock']}", 400);
            return;
        }

        $this->cartItemModel->update($itemId, ['quantity' => $quantity]);

        $this->log('cart_item_updated_v2', ['user_id' => $userId, 'item_id' => $itemId]);

        $this->show();
    }


    // DELETE /api/v2/cart/items/{id}
    public function removeItem(int $itemId): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        // Verify ownership — IDOR fix
        if (!$this->itemBelongsToUser($itemId, $userId)) {
            $this->error('You do not have access to this cart item', 403);
            return;
        }

        $this->cartItemModel->delete($itemId);

        $this->log('cart_item_removed_v2', ['user_id' => $userId, 'item_id' => $itemId]);

        $this->show();
    }


    // DELETE /api/v2/cart/clear
    public function clear(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        $this->cartModel->clearCart($userId);

        $this->log('cart_cleared_v2', ['user_id' => $userId]);

        $this->json(['message' => 'Cart cleared successfully']);
    }


    // verify cart item if belongs to this user
    // This is the core IDOR fix for cart items
    private function itemBelongsToUser(int $itemId, int $userId): bool
    {
        $item = $this->cartItemModel->find($itemId);

        if (!$item) {
            return false;
        }

        $cart = $this->cartModel->find($item['cart_id']);

        if (!$cart) {
            return false;
        }

        return (int)$cart['user_id'] === $userId;
    }
}