<?php
//manages customer orders
namespace Models\V1;

use Models\BaseModel;

class Order extends BaseModel
{
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    //create order from cart
    public function createOrder($userId, $total, $paymentMethod, $shippingAddress, $notes = null)
    {
        // Generate unique order number
        $orderNumber = $this->generateOrderNumber();
        
        // Create order
        $orderId = $this->create([
            'order_number' => $orderNumber,
            'user_id' => $userId,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => $paymentMethod,
            'payment_status' => 'pending',
            'shipping_address' => $shippingAddress,
            'notes' => $notes
        ]);
        
        return $orderId;
    }

    //generate unique order number
    public function generateOrderNumber()
    {
        $date = date('Ymd');
        $prefix = "ORD-{$date}-";
        
        // Get count of orders (today)
        $sql = "
            SELECT COUNT(*) as count 
            FROM {$this->table} 
            WHERE order_number LIKE '{$prefix}%'
        ";
        
        $result = $this->connection->query($sql);
        
        if ($result) {
            $row = $result->fetch_assoc();
            $count = (int)($row['count'] ?? 0);
            $result->free();
        } else {
            $count = 0;
        }
        
        // Increment and pad with zeros
        // If $count = 42
        // str_pad(43, 5, '0', STR_PAD_LEFT) → "00043"
        $number = str_pad($count + 1, 5, '0', STR_PAD_LEFT);
        
        return $prefix . $number;
    }


    //get order by order number
    public function findByOrderNumber($orderNumber)
    {
        $orderNumber = $this->connection->real_escape_string($orderNumber);
        
        $sql = "SELECT * FROM {$this->table} WHERE order_number = '{$orderNumber}' LIMIT 1";
        $result = $this->connection->query($sql);

        // $result = $this->where('order_number','=',$orderNumber)->findAll(1);
        
        if (!$result) {
            $this->logError("FindByOrderNumber failed", $sql);
            return null;
        }
        
        $order = $result->fetch_assoc();
        $result->free();
        
        return $order ?: null;
    }
    

    //get all orders for a user
    public function getUserOrders($userId, $limit = 20, $offset = 0)
    {
        $userId = $this->connection->real_escape_string($userId);
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "
            SELECT * FROM {$this->table} 
            WHERE user_id = '{$userId}' 
            ORDER BY created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ";
        $result = $this->connection->query($sql);

        // $result = $this->where('user_id','=',$this->table)->orderBy('created_at','DESC')->findAll($limit,$offset);
        
        if (!$result) {
            $this->logError("GetUserOrders failed",$sql);
            return [];
        }
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
        
        return $orders;
    }

    //get all orders for a user
    public function getUsersOrders( $limit = 20, $offset = 0)
    {
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "
            SELECT * FROM {$this->table} 
            ORDER BY created_at DESC 
            LIMIT {$limit} OFFSET {$offset}
        ";
        $result = $this->connection->query($sql);

        // $result = $this->where('user_id','=',$this->table)->orderBy('created_at','DESC')->findAll($limit,$offset);
        
        if (!$result) {
            $this->logError("GetUserOrders failed",$sql);
            return [];
        }
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
        
        return $orders;
    }


    //get order with items
    public function getWithItems($orderId)
    {
        // Get order
        $order = $this->find($orderId);
        
        if (!$order) {
            return null;
        }
        
        // Get order items
        $sql = "
            SELECT 
                oi.*,
                p.main_image as product_image,
                p.is_available as product_available
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = '{$orderId}'
            ORDER BY oi.created_at ASC
        ";
        
        $result = $this->connection->query($sql);
        
        $items = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
            $result->free();
        }
        
        $order['items'] = $items;
        $order['item_count'] = count($items);
        
        return $order;
    }


    //count user's total orders
    public function countUserOrders($userId)
    {
        $userId = $this->connection->real_escape_string($userId);
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = '{$userId}'";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        return (int)($row['total'] ?? 0);
    }

    //count user's total orders
    public function countUsersOrders()
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        return (int)($row['total'] ?? 0);
    }


    //order status managment

    //update order status
    public function updateStatus($orderId, $status)
    {
        // Validate status
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return false;
        }
        
        return $this->update($orderId, ['status' => $status]);
    }

    //update payment status
    public function updatePaymentStatus($orderId, $paymentStatus)
    {
        // Validate payment status
        $validStatuses = ['pending', 'paid', 'failed', 'refunded'];
        
        if (!in_array($paymentStatus, $validStatuses)) {
            return false;
        }
        
        return $this->update($orderId, ['payment_status' => $paymentStatus]);
    }

    //cancel order
    //only allows cancelation if order isn't shipped, or delivered
    public function cancelOrder($orderId)
    {
        $order = $this->find($orderId);
        
        if (!$order) {
            return false;
        }
        
        // Check if order can be cancelled
        if (in_array($order['status'], ['shipped', 'delivered', 'cancelled'])) {
            return false;
        }
        
        return $this->updateStatus($orderId, 'cancelled');
    }


    //check if order can be cancelled
    public function canBeCancelled($order)
    {
        return !in_array($order['status'], ['shipped', 'delivered', 'cancelled']);
    }



    //---------------------------------------
    // Order Statistics (for admin/user)
    //---------------------------------------

    //get orders by status
    public function getByStatus($status, $limit = 20, $offset = 0)
    {
        return $this->where('status', '=', $status)->orderBy('created_at', 'DESC')->findAll($limit, $offset);
    }

    //get recent orders
    public function getRecent($limit = 10)
    {
        return $this->orderBy('created_at', 'DESC')->findAll($limit);
    }

    //get user's order statistics
    public function getUserStats($userId)
    {
        $userId = $this->connection->real_escape_string($userId);
        
        $sql = "
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as delivered_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(total) as total_spent
            FROM {$this->table}
            WHERE user_id = '{$userId}'
        ";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return [
                'total_orders' => 0,
                'delivered_orders' => 0,
                'cancelled_orders' => 0,
                'total_spent' => 0
            ];
        }
        
        $stats = $result->fetch_assoc();
        $result->free();
        
        return $stats;
    }
}