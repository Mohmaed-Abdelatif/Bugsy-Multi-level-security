<?php
//manages order operations: handle checkout, order viewing, tracking, and cancellation

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Order;
use Models\V1\OrderItem;
use Models\V1\Cart;
use Models\V1\CartItem;
use Models\V1\Product;

class OrderController extends BaseController
{
    private $orderModel;
    private $orderItemModel;
    private $cartModel;
    private $cartItemModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
        $this->orderItemModel = new OrderItem();
        $this->cartModel = new Cart();
        $this->cartItemModel = new CartItem();
        $this->productModel = new Product();
    }


    //checkout: create order from cart
    // post /api/v1/checkout
    /*
     * Request Body:
     * {
     *     "payment_method": "cash_on_delivery",
     *     "shipping_address": "123 Main St, Cairo, Egypt",
     *     "notes": "Please call before delivery"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Order placed successfully",
     *     "data": {
     *         "order": {
     *             "id": 1,
     *             "order_number": "ORD-20250125-00001",
     *             "total": 2999.98,
     *             "status": "pending",
     *             "items": [...]
     *         }
     *     }
     * }
    */
    public function checkout()
    {
        $this->requireAuth();
        
        // Get input
        $paymentMethod = $this->getInput('payment_method');
        $shippingAddress = $this->getInput('shipping_address');
        $notes = $this->getInput('notes');
        
        // Validate input
        if (empty($paymentMethod)) {
            return $this->error('Payment method is required', 400);
        }
        
        if (empty($shippingAddress)) {
            return $this->error('Shipping address is required', 400);
        }
        
        // Get user's cart
        $userId = $this->getUserId();
        $cart = $this->cartModel->getUserCartWithItems($userId);
        
        if (!$cart) {
            return $this->error('Cart not found', 404);
        }
        
        // Check if cart is empty
        if (empty($cart['items'])) {
            return $this->error('Cart is empty', 400);
        }
        
        // Validate cart items (stock, availability)
        $validation = $this->cartItemModel->validateCartItems($cart['id']);
        
        if (!$validation['valid']) {
            return $this->error('Cart validation failed', 400, $validation['errors']);
        }
        
        // Create order
        $orderId = $this->orderModel->createOrder(
            $userId,
            $cart['total'],
            $paymentMethod,
            $shippingAddress,
            $notes
        );
        
        if (!$orderId) {
            return $this->error('Failed to create order', 500);
        }
        
        // Create order items from cart items
        $success = $this->orderItemModel->createFromCart($orderId, $cart['items']);
        
        if (!$success) {
            // Rollback: delete order if items creation failed
            $this->orderModel->delete($orderId);
            return $this->error('Failed to create order items', 500);
        }
        
        // Decrease product stock
        foreach ($cart['items'] as $item) {
            $this->productModel->decreaseStock($item['product_id'], $item['quantity']);
        }
        
        // Clear cart
        $this->cartModel->clearItems($cart['id']);
        
        // Get created order with items
        $order = $this->orderModel->getWithItems($orderId);
        
        // Log action
        $this->log('order_created', [
            'user_id' => $userId,
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'total' => $order['total']
        ]);
        
        return $this->json([
            'message' => 'Order placed successfully',
            'order' => $order
        ], null, 201);
    }


    //view orders: get /api/v1/orders
    /*
     * Query params: ?page=1&per_page=10
     * 
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "orders": [...],
     *         "pagination": {...}
     *     }
     * }
    */
    public function index()
    {
        // Require authentication
        $this->requireAuth();
        
        // Get pagination
        $pagination = $this->getPagination(10);
        
        // Get user's orders
        $userId = $this->getUserId();
        $orders = $this->orderModel->getUserOrders(
            $userId,
            $pagination['perPage'],
            $pagination['offset']
        );
        
        // Get total count
        $total = $this->orderModel->countUserOrders($userId);
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        return $this->json([
            'orders' => $orders,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }


    //get single order details: get /api/v1/orders/{id}
    /*
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "order": {
     *             "id": 1,
     *             "order_number": "ORD-20250125-00001",
     *             "status": "pending",
     *             "total": 2999.98,
     *             "items": [...],
     *             "item_count": 3
     *         }
     *     }
     * }
    */
    public function show($id)
    {
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid order ID', 400);
        }
        
        // Get order
        $order = $this->orderModel->getWithItems($id);
        
        if (!$order) {
            return $this->error('Order not found', 404);
        }
        
        // Check ownership
        $this->checkOwnership($order['user_id'], 'You cannot view this order');
        
        return $this->json([
            'order' => $order
        ]);
    }


    //get order items: get /api/v1/orders/{id}/items
    /*
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "items": [...]
     *     }
     * }
    */
    public function items($id)
    {
        $this->requireAuth();
        
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid order ID', 400);
        }
        
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            return $this->error('Order not found', 404);
        }
        
        $this->checkOwnership($order['user_id'], 'You cannot view this order');
        
        // Get items
        $items = $this->orderItemModel->getByOrder($id);
        
        return $this->json([
            'items' => $items
        ]);
    }

    //order tracking
    // get order status: get /api/v1/orders/{id}/status
    /*
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "order_number": "ORD-20250125-00001",
     *         "status": "processing",
     *         "payment_status": "pending",
     *         "created_at": "2025-01-25 10:30:00",
     *         "updated_at": "2025-01-25 11:00:00"
     *     }
     * }
    */
    public function status($id)
    {
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid order ID', 400);
        }
        
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            return $this->error('Order not found', 404);
        }
        
        $this->checkOwnership($order['user_id'], 'You cannot view this order');
        
        return $this->json([
            'order_number' => $order['order_number'],
            'status' => $order['status'],
            'payment_status' => $order['payment_status'],
            'created_at' => $order['created_at'],
            'updated_at' => $order['updated_at']
        ]);
    }


    //cancel order: put /api/v1/orders/{id}/cancel
    /*
     * Response:
     * {
     *     "success": true,
     *     "message": "Order cancelled successfully",
     *     "data": {
     *         "order": {...}
     *     }
     * }
    */
    public function cancel($id)
    {
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid order ID', 400);
        }
        
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            return $this->error('Order not found', 404);
        }
        
        $this->checkOwnership($order['user_id'], 'You cannot cancel this order');
        
        // Check if order can be cancelled
        if (!$this->orderModel->canBeCancelled($order)) {
            return $this->error('Order cannot be cancelled (already shipped/delivered/cancelled)', 400);
        }
        
        // Get order items to restore stock
        $items = $this->orderItemModel->getByOrder($id);
        
        // Cancel order
        $success = $this->orderModel->cancelOrder($id);
        
        if (!$success) {
            return $this->error('Failed to cancel order', 500);
        }
        
        // Restore product stock
        foreach ($items as $item) {
            $product = $this->productModel->find($item['product_id']);
            if ($product) {
                $newStock = $product['stock'] + $item['quantity'];
                $this->productModel->updateStock($item['product_id'], $newStock);
            }
        }
        
        // Get updated order
        $updatedOrder = $this->orderModel->getWithItems($id);
        
        // Log action
        $this->log('order_cancelled', [
            'user_id' => $order['user_id'],
            'order_id' => $id,
            'order_number' => $order['order_number']
        ]);
        
        return $this->json([
            'message' => 'Order cancelled successfully',
            'order' => $updatedOrder
        ]);
    }

    //update order status (for admin): put /api/v1/orders/{id}/status
    /*
     * Request Body:
     * {
     *     "status": "processing"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Order status updated",
     *     "data": {
     *         "order": {...}
     *     }
     * }
    */
    public function updateStatus($id)
    {
        $this->requireAdmin();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid order ID', 400);
        }
        
        $status = $this->getInput('status');
        
        if (empty($status)) {
            return $this->error('Status is required', 400);
        }
        
        $validStatuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return $this->error('Invalid status', 400);
        }
        
        // Get order
        $order = $this->orderModel->find($id);
        
        if (!$order) {
            return $this->error('Order not found', 404);
        }
        
        // Update status
        $success = $this->orderModel->updateStatus($id, $status);
        
        if (!$success) {
            return $this->error('Failed to update order status', 500);
        }
        
        // Get updated order
        $updatedOrder = $this->orderModel->getWithItems($id);
        
        // Log action
        $this->log('order_status_updated', [
            'admin_id' => $this->getUserId(),
            'order_id' => $id,
            'order_number' => $order['order_number'],
            'old_status' => $order['status'],
            'new_status' => $status
        ]);
        
        return $this->json([
            'message' => 'Order status updated',
            'order' => $updatedOrder
        ]);
    }

}
