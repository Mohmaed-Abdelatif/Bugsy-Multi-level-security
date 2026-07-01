<?php
// PDO prepared statements throughout — matches V2's trust level
// Same validate()/calculateDiscount() contract as V1 so controllers
// can call it identically across both versions

namespace Models\V2;

use Models\BaseModel;

class PromoCode extends BaseModel
{
    protected $table = 'promo_codes';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    public function findByCode(string $code): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE code = :code LIMIT 1"
        );
        $stmt->execute(['code' => $code]);
        $promo = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $promo ?: null;
    }


    public function codeExists(string $code, ?int $excludeId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE code = :code";
        $params = ['code' => $code];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }


    public function getUserUsageCount(int $promoId, int $userId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) as total FROM promo_code_usage WHERE promo_id = :promo_id AND user_id = :user_id"
        );
        $stmt->execute(['promo_id' => $promoId, 'user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }


    public function recordUsage(int $promoId, int $userId, int $orderId, float $discountAmount): bool
    {
        $stmt = $this->connection->prepare("
            INSERT INTO promo_code_usage (promo_id, user_id, order_id, discount_amount)
            VALUES (:promo_id, :user_id, :order_id, :discount_amount)
        ");

        $success = $stmt->execute([
            'promo_id' => $promoId,
            'user_id' => $userId,
            'order_id' => $orderId,
            'discount_amount' => $discountAmount,
        ]);

        if ($success) {
            $increment = $this->connection->prepare(
                "UPDATE {$this->table} SET times_used = times_used + 1 WHERE id = :id"
            );
            $increment->execute(['id' => $promoId]);
        }

        return $success;
    }


    // Core validation — identical rule order to V1
    // so behavior is consistent across both versions
    // the cart preview endpoint AND checkout
    public function validate(string $code, int $userId, float $orderSubtotal): array
    {
        $promo = $this->findByCode($code);

        if (!$promo) {
            return ['valid' => false, 'message' => 'Promo code not found', 'promo' => null];
        }

        if (!$promo['is_active']) {
            return ['valid' => false, 'message' => 'This promo code is no longer active', 'promo' => null];
        }

        if ($promo['expires_at'] && strtotime($promo['expires_at']) < time()) {
            return ['valid' => false, 'message' => 'This promo code has expired', 'promo' => null];
        }

        if ($orderSubtotal < (float)$promo['min_order_amount']) {
            $min = number_format((float)$promo['min_order_amount'], 2);
            return ['valid' => false, 'message' => "Minimum order amount of {$min} required for this code", 'promo' => null];
        }

        if ($promo['usage_limit_total'] !== null && (int)$promo['times_used'] >= (int)$promo['usage_limit_total']) {
            return ['valid' => false, 'message' => 'This promo code has reached its usage limit', 'promo' => null];
        }

        if ($promo['usage_limit_per_user'] !== null) {
            $userUsage = $this->getUserUsageCount((int)$promo['id'], $userId);
            if ($userUsage >= (int)$promo['usage_limit_per_user']) {
                return ['valid' => false, 'message' => 'You have already used this promo code the maximum number of times', 'promo' => null];
            }
        }

        return ['valid' => true, 'message' => 'Promo code is valid', 'promo' => $promo];
    }


    public function calculateDiscount(array $promo, float $subtotal): float
    {
        if ($promo['discount_type'] === 'percentage') {
            $discount = $subtotal * ((float)$promo['discount_value'] / 100);
        } else {
            $discount = (float)$promo['discount_value'];
        }

        return round(min($discount, $subtotal), 2);
    }
}