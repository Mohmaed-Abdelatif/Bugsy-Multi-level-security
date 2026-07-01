<?php
// V1: raw MySQLi string-built queries, matching the rest of V1's trust level
// Intentionally consistent with V1's existing pattern — no prepared statements

namespace Models\V1;

use Models\BaseModel;

class PromoCode extends BaseModel
{
    protected $table = 'promo_codes';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    // find promo by its code string 
    public function findByCode($code)
    {
        $code = $this->connection->real_escape_string($code);
        $sql = "SELECT * FROM {$this->table} WHERE code = '{$code}' LIMIT 1";

        $result = $this->connection->query($sql);

        if (!$result) {
            $this->logError("findByCode failed", $sql);
            return null;
        }

        $promo = $result->fetch_assoc();
        $result->free();

        return $promo ?: null;
    }


    // check if a code string already exists (for admin create uniqueness check)
    public function codeExists($code, $excludeId = null)
    {
        $code = $this->connection->real_escape_string($code);
        $sql = "SELECT id FROM {$this->table} WHERE code = '{$code}'";

        if ($excludeId) {
            $sql .= " AND id != " . (int)$excludeId;
        }

        $sql .= " LIMIT 1";

        $result = $this->connection->query($sql);

        if (!$result) {
            return false;
        }

        $exists = $result->num_rows > 0;
        $result->free();

        return $exists;
    }


    // count how many times a specific user has used this promo code
    public function getUserUsageCount($promoId, $userId)
    {
        $promoId = (int)$promoId;
        $userId = (int)$userId;

        $sql = "SELECT COUNT(*) as total FROM promo_code_usage WHERE promo_id = {$promoId} AND user_id = {$userId}";

        $result = $this->connection->query($sql);

        if (!$result) {
            return 0;
        }

        $row = $result->fetch_assoc();
        $result->free();

        return (int)($row['total'] ?? 0);
    }


    // record a successful promo code use
    public function recordUsage($promoId, $userId, $orderId, $discountAmount)
    {
        $promoId = (int)$promoId;
        $userId = (int)$userId;
        $orderId = (int)$orderId;
        $discountAmount = (float)$discountAmount;

        $sql = "INSERT INTO promo_code_usage (promo_id, user_id, order_id, discount_amount) 
                VALUES ({$promoId}, {$userId}, {$orderId}, {$discountAmount})";

        $result = $this->connection->query($sql);

        if (!$result) {
            $this->logError("recordUsage failed", $sql);
            return false;
        }

        // increment the running total on promo_codes
        $incrementSql = "UPDATE {$this->table} SET times_used = times_used + 1 WHERE id = {$promoId}";
        $this->connection->query($incrementSql);

        return true;
    }


    
    // The core validation function 
    // the cart preview endpoint AND checkout
    // Returns: ['valid' => bool, 'message' => string, 'promo' => array|null]
    public function validate($code, $userId, $orderSubtotal)
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
            $userUsage = $this->getUserUsageCount($promo['id'], $userId);
            if ($userUsage >= (int)$promo['usage_limit_per_user']) {
                return ['valid' => false, 'message' => 'You have already used this promo code the maximum number of times', 'promo' => null];
            }
        }

        return ['valid' => true, 'message' => 'Promo code is valid', 'promo' => $promo];
    }


    
    // Calculate the discount amount given a valid promo + subtotal
    public function calculateDiscount(array $promo, $subtotal)
    {
        $subtotal = (float)$subtotal;

        if ($promo['discount_type'] === 'percentage') {
            $discount = $subtotal * ((float)$promo['discount_value'] / 100);
        } else {
            $discount = (float)$promo['discount_value'];
        }

        // never let the discount exceed the subtotal itself
        return round(min($discount, $subtotal), 2);
    }
}