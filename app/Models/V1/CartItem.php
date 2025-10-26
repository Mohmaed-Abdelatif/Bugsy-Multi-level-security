<?php
// CartItem Model - Manages individual items in cart
namespace Models\V1;

use Models\BaseModel;

class CartItem extends BaseModel
{
    protected $table = 'cart_items';
    protected $primaryKey = 'id';
    protected $timestamps = true;



    //---------------------------------------
    // Cart Item Operations
    //---------------------------------------

    //add item to cart , if item exists updates quantity
    //no need to check stock her will make it in cart controller
    public function addItem($cartId, $productId, $quantity, $price)
    {
        //check if product has enough stock
        if(!$this->checkStock($productId,$quantity)){
            return false;
        }

        // Check if item already exists in cart
        $existing = $this->findByCartAndProduct($cartId, $productId);

        if ($existing) {
            // Update quantity
            $newQuantity = $existing['quantity'] + $quantity;
            $success = $this->updateQuantity($existing['id'], $newQuantity);
            return $success ? $existing['id'] : false;
        }
        
        // Add new item
        return $this->create([
            'cart_id' => $cartId,
            'product_id' => $productId,
            'quantity' => $quantity,
            'price' => $price
        ]);
    }

    // find cart item by cart and product
    public function findByCartAndProduct($cartId, $productId)
    {
        $cartId = $this->connection->real_escape_string($cartId);
        $productId = $this->connection->real_escape_string($productId);
        
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE cart_id = '{$cartId}' AND product_id = '{$productId}' 
            LIMIT 1
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("FindByCartAndProduct failed", $sql);
            return null;
        }
        
        $item = $result->fetch_assoc();
        $result->free();
        
        return $item ?: null;
    }

    //udate item quantity
    public function updateQuantity($itemId, $quantity)
    {
        if ($quantity <= 0) {
            // If quantity is 0 or negative, remove item
            return $this->delete($itemId);
        }
        
        return $this->update($itemId, [
            'quantity' => $quantity
        ]);
    }


    //remove item from cart
    public function removeItem($itemId)
    {
        return $this->delete($itemId);
    }


    //get cart item with product details
    public function getWithProduct($itemId)
    {
        $itemId = $this->connection->real_escape_string($itemId);

        $sql = "
            SELECT 
                ci.*,
                p.name as product_name,
                p.price as product_price,
                p.main_image as product_image,
                p.stock as product_stock,
                p.is_available as product_available,
                (ci.quantity * ci.price) as subtotal
            FROM {$this->table} ci
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE ci.id = '{$itemId}'
            LIMIT 1
        ";
        
        return $this->fetchOne($sql);
    }


    //get all items for a cart
    public function getByCart($cartId)
    {
        $cartId = $this->connection->real_escape_string($cartId);
        
        $sql = "
            SELECT 
                ci.*,
                p.name as product_name,
                p.price as product_price,
                p.main_image as product_image,
                p.stock as product_stock,
                p.is_available as product_available,
                (ci.quantity * ci.price) as subtotal
            FROM {$this->table} ci
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE ci.cart_id = '{$cartId}'
            ORDER BY ci.created_at DESC
        ";
        
        $items = $this->fetchAll($sql);
        
        return $items;
    }


    //check if product has enough stock
    public function checkStock($productId, $requestedQuantity)
    {
        $productId = $this->connection->real_escape_string($productId);
        
        $sql = "SELECT stock FROM products WHERE id = '{$productId}' LIMIT 1";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return false;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        if (!$row) {
            return false;
        }
        
        return (int)$row['stock'] >= $requestedQuantity;
    }


    //validate cart items before checkout (stocks and availability)
    public function validateCartItems($cartId)
    {
        $items = $this->getByCart($cartId);
        $errors = [];
        
        foreach ($items as $item) {
            // Check if product is available
            if (!$item['product_available']) {
                $errors[] = "{$item['product_name']} is no longer available";
            }
            
            // Check stock
            if ($item['quantity'] > $item['product_stock']) {
                $errors[] = "{$item['product_name']} only has {$item['product_stock']} in stock";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }


    
}