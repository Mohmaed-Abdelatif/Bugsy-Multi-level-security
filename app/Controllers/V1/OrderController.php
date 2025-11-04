<?php
//manages order operations: handle checkout, order viewing, tracking, and cancellation
// V1: Simple payment simulation (no real payment gateway)
// V2: Integration with Stripe/PayPal
// V3: Multiple payment gateways + fraud detection

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
     * Complete flow:
     * 1. Get cart items
     * 2. Validate stock availability
     * 3. Calculate total
     * 4. Create order
     * 5. Create order items
     * 6. Process payment
     * 7. Decrease stock
     * 8. Clear cart
     * 
     * Request Body:
     * {
     *     "payment_method": "cash",        // cash, credit_card, paypal
     *     "shipping_address": "...",
     *     "notes": "Please call before delivery",
     *      "card_details": {                // Optional for card payments
     *         "card_number": "4242424242424242",
     *         "cvv": "123",
     *         "expiry": "12/25"
     *     }
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

        // Get user's cart
        $userId = $this->getUserId();
        
        // Get input
        $paymentMethod = $this->getInput('payment_method');
        $shippingAddress = $this->getInput('shipping_address');
        $notes = $this->getInput('notes');
        
        // Validate required fields
        if (!$paymentMethod || !$shippingAddress) {
            return $this->error('payment_method and shipping_address are required', 400);
        }

        // Validate payment method
        $validPaymentMethods = ['cash', 'credit_card', 'debit_card', 'paypal', 'bank_transfer'];
        if (!in_array($paymentMethod, $validPaymentMethods)) {
            return $this->error('Invalid payment method', 400);
        }
        
        
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
        

        // Process payment
        $paymentResult = $this->processPayment($orderId, $paymentMethod, $cart['total']);

        if (!$paymentResult['success']) {
            // Payment failed - mark order as failed
            $this->orderModel->updatePaymentStatus($orderId, 'failed');
            
            return $this->error('Payment failed: ' . $paymentResult['error'], 400, [
                'order_id' => $orderId,
                'order_status' => 'pending',
                'payment_status' => 'failed'
            ]);
        }

        // Payment successful - update order status
        $this->orderModel->updatePaymentStatus($orderId, $paymentResult['payment_status']);
        $this->orderModel->updateStatus($orderId, 'processing');


        // Decrease product stock
        foreach ($cart['items'] as $item) {
            $this->productModel->decreaseStock($item['product_id'], $item['quantity']);
        }
        
        // Clear cart
        $this->cartModel->clearItems($cart['id']);
        
        // Get created order with items
        $order = $this->orderModel->getWithItems($orderId);
        
        // Log action
        $this->log('checkout_completed', [
            'user_id' => $userId,
            'order_id' => $orderId,
            'order_number' => $order['order_number'],
            'total' => $order['total'],
            'payment_method' => $paymentMethod
        ]);
        
        return $this->json([
            'message' => 'Order placed successfully',
            'order' => $order,
            'payment' => [
                'status' => 'paid',
                'method' => $paymentMethod,
                'transaction_id' => $paymentResult['transaction_id'] ?? null
            ]
        ], null, 201);
    }



    //process payment (V1:just simulation)
    /*
     * V1: Simulates payment processing
     * - Cash: Always succeeds
     * - Cards: Simulated validation
     * - PayPal: Simulated API
     * 
     * V2/V3: Real payment gateway integration
    */
        private function processPayment($orderId, $paymentMethod, $amount)
    {
        // V1: Simulated payment processing
        
        switch ($paymentMethod) {
            case 'cash':
                // Cash on delivery - always succeeds
                return [
                    'success' => true,
                    'payment_status'=> 'pending',
                    'transaction_id' => 'CASH-' . time(),
                    'message' => 'Cash on delivery'
                ];

            case 'credit_card':
            case 'debit_card':
                // V1: Simulate card payment
                $cardDetails = $this->getInput('card_details');
                
                if (!$cardDetails) {
                    return [
                        'success' => false,
                        'error' => 'Card details required'
                    ];
                }

                // V1: Basic validation (VULNERABLE - no real processing)
                if (empty($cardDetails['card_number']) || 
                    empty($cardDetails['cvv']) || 
                    empty($cardDetails['expiry'])) {
                    return [
                        'success' => false,
                        'error' => 'Incomplete card details'
                    ];
                }

                // V1: Simulate processing delay
                usleep(500000); // 0.5 seconds

                // V1: 90% success rate (simulate occasional failures)
                if (rand(1, 10) > 1) {
                    return [
                        'success' => true,
                        'payment_status'=> 'paid',
                        'transaction_id' => 'CARD-' . uniqid(),
                        'message' => 'Card payment successful'
                    ];
                } else {
                    return [
                        'success' => false,
                        'error' => 'Card declined - insufficient funds'
                    ];
                }

            case 'paypal':
                // V1: Simulate PayPal payment
                usleep(500000);
                
                return [
                    'success' => true,
                    'payment_status'=> 'paid',
                    'transaction_id' => 'PP-' . uniqid(),
                    'message' => 'PayPal payment successful'
                ];

            case 'bank_transfer':
                // Bank transfer - requires manual verification
                return [
                    'success' => true,
                    'payment_status'=> 'pending',
                    'transaction_id' => 'BANK-' . uniqid(),
                    'message' => 'Bank transfer initiated - awaiting confirmation'
                ];

            default:
                return [
                    'success' => false,
                    'error' => 'Invalid payment method'
                ];
        }

    }




    //view orders: get /api/V1/orders
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


    //get single order details: get /api/V1/orders/{id}
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


    //get order items: get /api/V1/orders/{id}/items
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
    // get order status: get /api/V1/orders/{id}/status
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


    //cancel order: put /api/V1/orders/{id}/cancel
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

    //update order status (for admin): put /api/V1/orders/{id}/status
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
