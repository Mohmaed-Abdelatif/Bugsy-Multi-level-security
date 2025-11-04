<?php
//manages individual items in 
namespace Models\V1;

use Models\BaseModel;

class OrderItem extends BaseModel
{
    protected $table = 'order_items';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    //create order item
    public function createItem($orderId, $productId, $productName, $quantity, $price)
    {
        $subtotal = $quantity * $price;
        
        return $this->create([
            'order_id' => $orderId,
            'product_id' => $productId,
            'product_name' => $productName,
            'quantity' => $quantity,
            'price' => $price,
            'subtotal' => $subtotal
        ]);
    }

    //create multiple order items from cart items
    public function createFromCart($orderId, $cartItems)
    {
        foreach ($cartItems as $item) {
            $itemId = $this->createItem(
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['quantity'],
                $item['price']
            );
            
            if (!$itemId) {
                return false;
            }
        }
        
        return true;
    }


    //get all items for an order
    public function getByOrder($orderId)
    {
        $orderId = $this->connection->real_escape_string($orderId);
        
        $sql = "
            SELECT 
                oi.*,
                p.main_image as product_image,
                p.is_available as product_available,
                p.stock as product_stock
            FROM {$this->table} oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = '{$orderId}'
            ORDER BY oi.created_at ASC
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("GetByOrder failed", $sql);
            return [];
        }
        
        $items = [];
        while ($row = $result->fetch_assoc()) {
            $items[] = $row;
        }
        $result->free();
        
        return $items;
    }


    //get order item with product details
    public function getWithProduct($itemId)
    {
        $itemId = $this->connection->real_escape_string($itemId);
        
        $sql = "
            SELECT 
                oi.*,
                p.main_image as product_image,
                p.is_available as product_available,
                p.stock as product_stock
            FROM {$this->table} oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.id = '{$itemId}'
            LIMIT 1
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("GetWithProduct failed", $sql);
            return null;
        }
        
        $item = $result->fetch_assoc();
        $result->free();
        
        return $item ?: null;
    }


    //---------------------------------------
    // Order Item Statistics
    //---------------------------------------

    //get total quantity and subtotal for an order
    public function getOrderSummary($orderId)
    {
        $orderId = $this->connection->real_escape_string($orderId);
        
        $sql = "
            SELECT 
                COUNT(*) as item_count,
                SUM(quantity) as total_quantity,
                SUM(subtotal) as total
            FROM {$this->table}
            WHERE order_id = '{$orderId}'
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return [
                'item_count' => 0,
                'total_quantity' => 0,
                'total' => 0
            ];
        }
        
        $summary = $result->fetch_assoc();
        $result->free();
        
        return $summary;
    }

    //get most purchased products (for admin analytic)
    public function getTopProducts($limit = 10)
    {
        $limit = (int)$limit;
        
        $sql = "
            SELECT 
                oi.product_id,
                oi.product_name,
                SUM(oi.quantity) as total_sold,
                SUM(oi.subtotal) as total_revenue,
                p.main_image as product_image
            FROM {$this->table} oi
            LEFT JOIN products p ON oi.product_id = p.id
            GROUP BY oi.product_id, oi.product_name, p.main_image
            ORDER BY total_sold DESC
            LIMIT {$limit}
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("GetTopProducts failed", $sql);
            return [];
        }
        
        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        $result->free();
        
        return $products;
    }


}