<?php
// Models/V2/Order.php
// Extends V1\Order — overrides only methods with raw user input in SQL
// BaseModel auto-detects v2 → PDO connection used automatically

namespace Models\V2;

class Order extends \Models\V1\Order
{
    //get all orders for a user
    public function getByUserId(int $userId, int $limit = 20, int $offset = 0): array
    {
        $stmt = $this->connection->prepare("
            SELECT * FROM {$this->table}
            WHERE user_id = :user_id
            ORDER BY created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    //cout orders for a user
    public function countByUserId(int $userId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }

    
    //get order with its items
    public function getWithItems( $orderId): ?array
    {
        // Get order
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            return null;
        }

        // Get order items with product details
        $stmt = $this->connection->prepare("
            SELECT 
                oi.*,
                p.main_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmt->execute(['order_id' => $orderId]);
        $order['items'] = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return $order;
    }

    //get items for order
    public function getItems(int $orderId): array
    {
        $stmt = $this->connection->prepare("
            SELECT oi.*, p.main_image
            FROM order_items oi
            LEFT JOIN products p ON oi.product_id = p.id
            WHERE oi.order_id = :order_id
        ");
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    //checkout
    // Creates order + order_items from cart in a transaction
    // Transaction ensures: either everything saves or nothing does
    // re-validates and applies any attached promo code
    public function checkout(int $userId, array $data): array|false
    {
        $promoModel = new PromoCode();
        try {
            $this->connection->beginTransaction();

            // Get user's cart with items
            $cartModel = new Cart();
            $cartData  = $cartModel->getCartWithItems($userId);

            if (empty($cartData) || !$cartData[0]['item_id']) {
                $this->connection->rollBack();
                return false;
            }

            // Calculate total
            $subtotal = array_sum(array_column($cartData, 'subtotal'));

            // Re-validate promo fresh — never trust what /cart showed earlier.
            // Between viewing the cart and checking out, the code could have expired, been deactivated, or had its usage limit exhausted.
            $promoCode = $cartModel->getAttachedPromoCode($userId);
            $discount = 0.00;
            $appliedPromo = null;

            if ($promoCode) {
                $validation = $promoModel->validate($promoCode, $userId, $subtotal);

                if (!$validation['valid']) {
                    $this->connection->rollBack();
                    throw new \RuntimeException($validation['message']);
                }

                $discount = $promoModel->calculateDiscount($validation['promo'], $subtotal);
                $appliedPromo = $validation['promo'];
            }

            $total = round($subtotal - $discount, 2);


            // Generate unique order number
            $orderNumber = 'ORD-' . strtoupper(bin2hex(random_bytes(5)));

            // Create order
            $stmt = $this->connection->prepare("
                INSERT INTO orders 
                    (order_number, user_id, total, status, payment_method, promo_code, discount_amount,
                     payment_status, shipping_address, notes, created_at, updated_at)
                VALUES 
                    (:order_number, :user_id, :total, 'pending', :payment_method, :promo_code, :discount_amount,
                     'pending', :shipping_address, :notes, NOW(), NOW())
            ");
            $stmt->execute([
                'order_number'     => $orderNumber,
                'user_id'          => $userId,
                'total'            => $total,
                'payment_method'   => $data['payment_method']   ?? 'cash_on_delivery',
                'promo_code'       => $promoCode,
                'discount_amount'  => $discount,
                'shipping_address' => $data['shipping_address'] ?? $data['address'] ?? '',
                'notes'            => $data['notes']            ?? null,
            ]);

            $orderId = (int)$this->connection->lastInsertId();

            // Create order items + decrease stock
            $itemStmt = $this->connection->prepare("
                INSERT INTO order_items
                    (order_id, product_id, product_name, quantity, price, subtotal, created_at, updated_at)
                VALUES
                    (:order_id, :product_id, :product_name, :quantity, :price, :subtotal, NOW(), NOW())
            ");

            $stockStmt = $this->connection->prepare(
                "UPDATE products SET stock = stock - :qty WHERE id = :id AND stock >= :qty2"
            );

            foreach ($cartData as $item) {
                if (!$item['item_id']) continue;

                $itemStmt->execute([
                    'order_id'     => $orderId,
                    'product_id'   => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'quantity'     => $item['quantity'],
                    'price'        => $item['price'],
                    'subtotal'     => $item['subtotal'],
                ]);

                // Decrease stock safely — only if enough stock exists
                $stockStmt->execute([
                    'qty'  => $item['quantity'],
                    'id'   => $item['product_id'],
                    'qty2' => $item['quantity'],
                ]);
            }

            // Record promo usage now that the order is confirmed
            if ($appliedPromo) {
                $promoModel->recordUsage($appliedPromo['id'], $userId, $orderId, $discount);
            }

            // Clear the cart after successful order
            $cartModel->clearCart($userId);

            // also clears the promo attachment
            $cartModel->removePromoCode($userId);

            $this->connection->commit();

            // Return the created order with items
            return $this->getWithItems($orderId);

        } catch (\RuntimeException $e) {
            // promo validation failure — already rolled back above
            throw $e;
        } catch (\PDOException $e) {
            $this->connection->rollBack();
            error_log("V2 Order checkout failed: " . $e->getMessage());
            return false;
        }
    }

    
    // cancel order, verify ownership before cancel
    public function cancel(int $orderId, int $userId): bool
    {
        // Verify the order belongs to this user
        $stmt = $this->connection->prepare(
            "SELECT id, status, user_id FROM {$this->table} WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $orderId]);
        $order = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$order) {
            return false;
        }

        // Ownership check at model level — extra safety layer
        if ((int)$order['user_id'] !== $userId) {
            return false;
        }

        // Can only cancel pending or processing orders
        if (!in_array($order['status'], ['pending', 'processing'])) {
            return false;
        }

        $stmt = $this->connection->prepare(
            "UPDATE {$this->table} SET status = 'cancelled', updated_at = NOW() WHERE id = :id"
        );

        return $stmt->execute(['id' => $orderId]);
    }

    
    // Override: updateStatus — admin only, safe
    public function updateStatus($orderId, $status): bool
    {
        $allowed = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];

        if (!in_array($status, $allowed)) {
            return false;
        }

        $stmt = $this->connection->prepare(
            "UPDATE {$this->table} SET status = :status, updated_at = NOW() WHERE id = :id"
        );

        return $stmt->execute(['status' => $status, 'id' => $orderId]);
    }

    
    // verifyOwnership, used by controller
    // returns true if order belongs to this user
    public function verifyOwnership(int $orderId, int $userId): bool
    {
        $stmt = $this->connection->prepare(
            "SELECT id FROM {$this->table} WHERE id = :id AND user_id = :user_id LIMIT 1"
        );
        $stmt->execute(['id' => $orderId, 'user_id' => $userId]);

        return $stmt->fetch() !== false;
    }
}