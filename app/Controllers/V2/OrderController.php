<?php
// getUserId() from JWT only — cannot be supplied in request body
// index() returns ONLY the authenticated user's orders
// show/items/status/cancel all verify order belongs to auth user
// checkout() uses transaction — atomic, no partial saves
// Admin routes explicitly require admin role

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Order;
use Models\V2\Cart;

class OrderController extends BaseController
{
    private Order $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->orderModel = new Order();
    }


    // Returns ONLY the authenticated user's orders
    // GET /api/v2/orders
    public function index(): void
    {
        $this->requireAuth();

        $userId     = $this->getUserId();
        $pagination = $this->getPagination(10);

        $orders = $this->orderModel->getByUserId(
            $userId,
            $pagination['perPage'],
            $pagination['offset']
        );

        $total = $this->orderModel->countByUserId($userId);

        $this->json([
            'orders'     => $orders,
            'pagination' => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }


    // GET /api/v2/orders/{id}
    public function show(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid order ID', 400);
            return;
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        // ownership enforced — user can only see own orders
        // Admin can see all
        $this->checkOwnership($order['user_id'], 'You do not have access to this order');

        $orderWithItems = $this->orderModel->getWithItems($id);

        $this->json(['order' => $orderWithItems]);
    }


    // GET /api/v2/orders/{id}/items
    public function items(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid order ID', 400);
            return;
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        // V2: ownership check
        $this->checkOwnership($order['user_id'], 'You do not have access to this order');

        $items = $this->orderModel->getItems($id);

        $this->json([
            'order_id' => $id,
            'items'    => $items,
            'total'    => count($items)
        ]);
    }


    // GET /api/v2/orders/{id}/status
    public function status(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid order ID', 400);
            return;
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        // ownership check
        $this->checkOwnership($order['user_id'], 'You do not have access to this order');

        $this->json([
            'order_id'       => $id,
            'order_number'   => $order['order_number'],
            'status'         => $order['status'],
            'payment_status' => $order['payment_status'],
            'updated_at'     => $order['updated_at'],
        ]);
    }


    // POST /api/v2/checkout
    public function checkout(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();

        // Validate required fields
        $shippingAddress = trim($this->getInput('shipping_address', ''));
        $paymentMethod   = trim($this->getInput('payment_method', 'cash_on_delivery'));
        $notes           = trim($this->getInput('notes', ''));

        if (empty($shippingAddress)) {
            $this->error('Validation failed', 422, [
                'shipping_address' => 'Shipping address is required'
            ]);
            return;
        }

        $allowedPayments = ['cash_on_delivery', 'credit_card', 'bank_transfer'];

        if (!in_array($paymentMethod, $allowedPayments)) {
            $this->error('Validation failed', 422, [
                'payment_method' => 'Invalid payment method. Allowed: ' . implode(', ', $allowedPayments)
            ]);
            return;
        }

        // Checkout — uses PDO transaction in model
        // revalidate any attached promo
        try {
            $order = $this->orderModel->checkout($userId, [
                'shipping_address' => $shippingAddress,
                'payment_method'   => $paymentMethod,
                'notes'            => $notes,
            ]);
        } catch (\RuntimeException $e) {
            // promo code became invalid between cart preview and checkout
            $this->error("Your promo code is no longer valid: {$e->getMessage()}", 400);
            return;
        }

        if (!$order) {
            $this->error('Checkout failed. Your cart may be empty or a product is out of stock.', 400);
            return;
        }

        $this->log('order_created_v2', [
            'user_id'  => $userId,
            'order_id' => $order['id'] ?? null,
            'total'    => $order['total'] ?? null,
            'promo_code' => $order['promo_code'] ?? null,
            'discount_amount' => $order['discount_amount'] ?? null,
        ]);

        $this->json([
            'message' => 'Order placed successfully',
            'order'   => $order
        ], null, 201);
    }


    // PUT /api/v2/orders/{id}/cancel
    public function cancel(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid order ID', 400);
            return;
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        //ownership check
        $this->checkOwnership($order['user_id'], 'You do not have access to this order');

        // Model-level cancel: checks status + ownership again
        $success = $this->orderModel->cancel($id, $this->getUserId());

        if (!$success) {
            $this->error(
                'Cannot cancel this order. Only pending or processing orders can be cancelled.',
                400
            );
            return;
        }

        $this->log('order_cancelled_v2', ['user_id' => $this->getUserId(), 'order_id' => $id]);

        $order = $this->orderModel->find($id);

        $this->json([
            'message' => 'Order cancelled successfully',
            'order'   => $order
        ]);
    }


    // GET /api/v2/usersorders  (admin only)
    public function usersOrders(): void
    {
        $this->requireAdmin();

        $pagination = $this->getPagination(20);

        $orders = $this->orderModel->findAll(
            $pagination['perPage'],
            $pagination['offset']
        );

        $total = $this->orderModel->count();

        $this->json([
            'orders'     => $orders,
            'pagination' => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }


    // PUT /api/v2/orders/{id}/status  (admin only)
    public function updateStatus(int $id): void
    {
        $this->requireAdmin();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid order ID', 400);
            return;
        }

        $status = trim($this->getInput('status', ''));

        if (empty($status)) {
            $this->error('Validation failed', 422, ['status' => 'Status is required']);
            return;
        }

        $order = $this->orderModel->find($id);

        if (!$order) {
            $this->error('Order not found', 404);
            return;
        }

        $success = $this->orderModel->updateStatus($id, $status);

        if (!$success) {
            $this->error('Invalid status value or update failed', 400);
            return;
        }

        $this->log('order_status_updated_v2', [
            'admin_id' => $this->getUserId(),
            'order_id' => $id,
            'status'   => $status,
        ]);

        $this->json([
            'message' => 'Order status updated successfully',
            'order'   => $this->orderModel->find($id)
        ]);
    }
}