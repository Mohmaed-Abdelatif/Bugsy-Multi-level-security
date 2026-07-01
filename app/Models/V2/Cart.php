<?php
// extends V1\Cart and overrides only methods that have raw user input in SQL
// all inherited from BaseModel and already use PDO in v2
// BaseModel auto-detects v2 → PDO connection used automatically

namespace Models\V2;

class Cart extends \Models\V1\Cart
{
    // get cart by user id
    // V1: "WHERE user_id = '{$userId}'" — injectable
    // V2: bound parameter — safe
    public function getByUserId($userId): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE user_id = :user_id LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    
    // get or creat cart if not exist    
    public function getOrCreateCart(int $userId): ?array
    {
        // Try to find existing cart
        $cart = $this->getByUserId($userId);

        if ($cart) {
            return $cart;
        }

        // Create new cart for user if not have
        $cartId = $this->create(['user_id' => $userId]);

        if (!$cartId) {
            return null;
        }

        return $this->find($cartId); // BaseModel::find() — already PDO
    }

    
    //get cart with its items
    public function getCartWithItems(int $userId): array
    {
        $stmt = $this->connection->prepare("
            SELECT 
                c.id as cart_id,
                c.user_id,
                ci.id as item_id,
                ci.product_id,
                ci.quantity,
                ci.price,
                ci.quantity * ci.price as subtotal,
                p.name as product_name,
                p.main_image,
                p.stock
            FROM carts c
            LEFT JOIN cart_items ci ON c.id = ci.cart_id
            LEFT JOIN products p ON ci.product_id = p.id
            WHERE c.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    
    //get num of cart items for user caet
    public function getItemCount($userId): int
    {
        $stmt = $this->connection->prepare("
            SELECT COALESCE(SUM(ci.quantity), 0) as total
            FROM carts c
            LEFT JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }


    //get cart total price
    public function getTotal( $userId): float
    {
        $stmt = $this->connection->prepare("
            SELECT COALESCE(SUM(ci.quantity * ci.price), 0) as total
            FROM carts c
            LEFT JOIN cart_items ci ON c.id = ci.cart_id
            WHERE c.user_id = :user_id
        ");
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (float)($row['total'] ?? 0);
    }

    //clear all cartitems from cart_items table
    public function clearCart(int $userId): bool
    {
        $stmt = $this->connection->prepare("
            DELETE ci FROM cart_items ci
            INNER JOIN carts c ON ci.cart_id = c.id
            WHERE c.user_id = :user_id
        ");

        return $stmt->execute(['user_id' => $userId]);
    }


    
    // Promo code attachment — new for V2, PDO from the start

    public function attachPromoCode( $userId,  $code): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE carts SET promo_code = :code WHERE user_id = :user_id"
        );

        return $stmt->execute(['code' => $code, 'user_id' => $userId]);
    }

    public function removePromoCode($userId): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE carts SET promo_code = NULL WHERE user_id = :user_id"
        );

        return $stmt->execute(['user_id' => $userId]);
    }

    public function getAttachedPromoCode($userId): ?string
    {
        $stmt = $this->connection->prepare(
            "SELECT promo_code FROM carts WHERE user_id = :user_id LIMIT 1"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $row['promo_code'] ?? null;
    }
}