<?php
/**
 * Cart Model (V1 intentionally vulnerable)
 * 
 * vulnerablitey:
 * - Each user has ONE cart and it is the only allowed for him (can only access their own cart)
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




    //---------------------------------------
    // Cart Management
    //---------------------------------------

    //get or create cart for user (If user doesn't have cart, creates one)
    //V1: user_id from request ( anyone can fake it) so will add authe in controller
    public function getOrCreate($userId)
    {
        // Check if user already has cart
        $cart = $this->getByUserId($userId);
        
        if ($cart) {
            return $cart;
        }
        
        // Create new cart
        $cartId = $this->create([
            'user_id' => $userId,
        ]);
        
       if ($cartId) {
            return $this->find($cartId);
        }

        return null;
    }


    //get cart by user id
    public function getByUserId($userId)
    {
        $userId = $this->connection->real_escape_string($userId);
        $sql = "SELECT * FROM {$this->table} WHERE user_id = '{$userId}' LIMIT 1";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("GetByUserId failed", $sql);
            return null;
        }
        
        $cart = $result->fetch_assoc();
        $result->free();
        
        return $cart ?: null;
    }



    // get cart with all items including product info
    public function getWithItems($cartId)
    {
        //no need to use ger or create coz if didnot exist cart for this user no need to return his new created impty cart
        //so will make method separated if needed to get by userid
        // $cart = $this->getOrCreate($userId);

        // Get cart
        $cart = $this->find($cartId);
        
        if (!$cart) {
            return null;
        }
        
        // Get cart items with product details
        $sql = "
            SELECT 
                ci.*,
                p.name as product_name,
                p.price as product_price,
                p.main_image as product_image,
                p.stock as product_stock,
                p.is_available as product_available,
                (ci.quantity * ci.price) as subtotal
            FROM cart_items ci
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = '{$cartId}'
            ORDER BY ci.created_at DESC
        ";

        
        $result = $this->connection->query($sql);
        
        $items = [];
        $total = 0;
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
                $total += $row['subtotal'];

            }
            $result->free();
        }
        
        $cart['items'] = $items;
        $cart['total'] = $total;
        $cart['item_count'] = count($items);
        
        return $cart;
    }


    //get cart with items by user id if user dont have  => creat one
    public function getUserCartWithItems($userId)
    {
        $cart = $this->getOrCreate($userId);
        
        if (!$cart) {
            return null;
        }
        
        return $this->getWithItems($cart['id']);
    }



    //get cart items count for user
    public function getItemCount($userId)
    {
        $cart = $this->getByUserId($userId);
        
        if (!$cart) {
            return 0;
        }
        
        $cartId = $cart['id'];
        $sql = "SELECT COUNT(*) as total FROM cart_items WHERE cart_id = '{$cartId}'";

        
        $result = $this->fetchOne($sql);
        
        return (int)($result['total'] ?? 0);
    }



    //get cart total price
    public function getTotal($userId)
    {
        $cart = $this->getByUserId($userId);
        
        if (!$cart) {
            return 0;
        }
        
        $cartId = $cart['id'];
        $sql = "
            SELECT SUM(quantity * price) as total 
            FROM cart_items 
            WHERE cart_id = '{$cartId}'
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        return (float)($row['total'] ?? 0);
    }



    //clear all items from cart
    public function clearItems($cartId)
    {
        $cartId = $this->connection->real_escape_string($cartId);
        $sql = "DELETE FROM cart_items WHERE cart_id = '{$cartId}'";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("ClearItems failed", $sql);
            return false;
        }
        
        return true;
    }


    //delete cart and all items
    public function deleteCart($cartId)
    {
        // Delete items first
        $this->clearItems($cartId);
        
        // Delete cart
        return $this->delete($cartId);
    }
    



   


    
    /**
     * Sync cart prices with current product prices
     * Useful to update old cart items
    */
    public function syncPrices($userId)
    {
        $cart = $this->getByUserId($userId);
        
        $sql = "UPDATE cart_items ci
                JOIN products p ON ci.product_id = p.id
                SET ci.price = p.price,
                    ci.updated_at = NOW()
                WHERE ci.cart_id = " . (int)$cart['id'];
        
        return $this->connection->query($sql);
    }

    /**
     * Get abandoned carts (for marketing)
     * Carts not updated in 24 hours: to add in cart to buy
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