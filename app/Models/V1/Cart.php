<?php
/**
 * Cart Model (v1 intentionally vulnerable)
 * 
 * vulnerablitey:
 * - no user authentication (anyone can access any cart)
 * - user_id passed in request (IDOR)
 * - Direct price manipulation possible
 * - no stock validation
 * - race conditions in concurrent updates
*/

namespace Models\V1;

use Models\BaseModel;

class Cart extends BaseModel
{
    protected $table = 'carts';
    protected $primaryKey = 'id';
    protected $timestamps = true;

    /**
     * get or create cart for user
     * v1: user_id from request ( anyone can fake it)
     * v2: user_id from JWT token
    */
    public function getOrCreate($userId)
    {
        // Find existing cart
        $cart = $this->where('user_id', '=', $userId)
                     ->first();
        
        if ($cart) {
            return $cart;
        }
        
        // Create new cart
        $cartId = $this->create([
            'user_id' => $userId,
        ]);
        
        return $this->find($cartId);
    }


    // get cart with items and product details
    public function getWithItems($userId)
    {
        $cart = $this->getOrCreate($userId);
        
        if (!$cart) {
            return null;
        }
        
        // Get cart items with product details (VULNERABLE query)
        $sql = "SELECT 
                    ci.*,
                    p.name as product_name,
                    p.price as current_price,
                    p.main_image,
                    p.stock as available_stock,
                    (ci.quantity * ci.price) as item_total
                FROM cart_items ci
                JOIN products p ON ci.product_id = p.id
                WHERE ci.cart_id = " . (int)$cart['id'];
        
        $items = $this->fetchAll($sql);
        
        // Calculate totals
        $subtotal = 0;
        foreach ($items as &$item) {
            $subtotal += $item['item_total'];
            
            // Flag if price changed
            $item['price_changed'] = ($item['price'] != $item['current_price']);
        }
        
        $cart['items'] = $items;
        $cart['subtotal'] = $subtotal;
        $cart['item_count'] = count($items);
        
        return $cart;
    }


    
    //add item to cart (VULNERABLE - no stock check, price manipulation)
    //need some edite but good for now
    public function addItem($userId, $productId, $quantity, $price)
    {
        $cart = $this->getOrCreate($userId);
        
        // Check if item already in cart
        $sql = "SELECT * FROM cart_items 
                WHERE cart_id = " . (int)$cart['id'] . " 
                AND product_id = " . (int)$productId;
        
        $existingItem = $this->fetchOne($sql);
        
        if ($existingItem) {
            // Update quantity (VULNERABLE - no max quantity check)
            $newQuantity = $existingItem['quantity'] + $quantity;
            $sql = "UPDATE cart_items 
                    SET quantity = {$newQuantity},
                        price = {$price},
                        updated_at = NOW()
                    WHERE id = " . (int)$existingItem['id'];
            
            $this->connection->query($sql);
            return $existingItem['id'];
        } else {
            // Insert new item (VULNERABLE - accepts any price from client)
            $sql = "INSERT INTO cart_items 
                    (cart_id, product_id, quantity, price, created_at, updated_at)
                    VALUES ({$cart['id']}, {$productId}, {$quantity}, {$price}, NOW(), NOW())";
            
            if ($this->connection->query($sql)) {
                return $this->connection->insert_id;
            }
        }
        
        return false;
    }


    //udate item quantity
    //need some edite but good till now
    public function updateItemQuantity($cartItemId, $quantity)
    {
        // V1: No ownership check (IDOR vulnerability)
        $sql = "UPDATE cart_items 
                SET quantity = " . (int)$quantity . ",
                    updated_at = NOW()
                WHERE id = " . (int)$cartItemId;
        
        return $this->connection->query($sql);
    }


    //remove item from cart
    public function removeItem($cartItemId)
    {
        // V1: No ownership check (anyone can delete any item)
        $sql = "DELETE FROM cart_items WHERE id = " . (int)$cartItemId;
        
        return $this->connection->query($sql);
    }

    //clear entire cart
    public function clearCart($userId)
    {
        $cart = $this->getOrCreate($userId);
        
        $sql = "DELETE FROM cart_items WHERE cart_id = " . (int)$cart['id'];
        
        return $this->connection->query($sql);
    }


    //get cart item count (for header badge)
    public function getItemCount($userId)
    {
        $cart = $this->getOrCreate($userId);
        
        $sql = "SELECT SUM(quantity) as total 
                FROM cart_items 
                WHERE cart_id = " . (int)$cart['id'];
        
        $result = $this->fetchOne($sql);
        
        return (int)($result['total'] ?? 0);
    }

    //get cart total price
    public function getTotal($userId)
    {
        $cart = $this->getWithItems($userId);
        
        return $cart['subtotal'] ?? 0;
    }


    /**
     * Validate cart before checkout
     * Returns: ['valid' => bool, 'errors' => []]
     */
    public function validateForCheckout($userId)
    {
        $cart = $this->getWithItems($userId);
        
        $errors = [];
        
        if (empty($cart['items'])) {
            $errors[] = 'Cart is empty';
        }
        
        foreach ($cart['items'] as $item) {
            // Check stock availability
            if ($item['quantity'] > $item['available_stock']) {
                $errors[] = "{$item['product_name']}: Only {$item['available_stock']} items in stock";
            }
            
            // Check price changes
            if ($item['price_changed']) {
                $errors[] = "{$item['product_name']}: Price has changed";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'cart' => $cart
        ];
    }



    //Convert cart to order (used in checkout)
    public function convertToOrder($userId)
    {
        $validation = $this->validateForCheckout($userId);
        
        if (!$validation['valid']) {
            return [
                'success' => false,
                'errors' => $validation['errors']
            ];
        }
        
        $cart = $validation['cart'];
        
        // Mark cart as converted
        $this->update($cart['id'], ['status' => 'converted']);
        
        return [
            'success' => true,
            'cart' => $cart
        ];
    }

    /**
     * Sync cart prices with current product prices
     * Useful to update old cart items
    */
    public function syncPrices($userId)
    {
        $cart = $this->getOrCreate($userId);
        
        $sql = "UPDATE cart_items ci
                JOIN products p ON ci.product_id = p.id
                SET ci.price = p.price,
                    ci.updated_at = NOW()
                WHERE ci.cart_id = " . (int)$cart['id'];
        
        return $this->connection->query($sql);
    }

    /**
     * Get abandoned carts (for marketing)
     * Carts not updated in 24 hours
    */
    public function getAbandoned($hours = 24)
    {
        $sql = "SELECT c.*, u.email, u.name,
                COUNT(ci.id) as item_count,
                SUM(ci.quantity * ci.price) as total
                FROM {$this->table} c
                JOIN users u ON c.user_id = u.id
                LEFT JOIN cart_items ci ON c.id = ci.cart_id
                AND c.updated_at < DATE_SUB(NOW(), INTERVAL {$hours} HOUR)
                GROUP BY c.id
                HAVING item_count > 0";
        
        return $this->fetchAll($sql);
    }
}