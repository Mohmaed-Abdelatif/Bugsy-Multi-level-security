<?php
// Admin-only management of promo codes
// V1: requireAdmin() weak auth, matching the rest of V1's trust level

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\PromoCode;

class PromoCodeController extends BaseController
{
    private $promoModel;

    public function __construct()
    {
        parent::__construct();
        $this->promoModel = new PromoCode();
    }


    // GET /api/V1/promo-codes (admin only)
    public function index()
    {
        // $this->requireAdmin();

        $promos = $this->promoModel->findAll();

        return $this->json(['promo_codes' => $promos]);
    }


    // GET /api/V1/promo-codes/{id} (admin only)
    public function show($id)
    {
        $this->requireAdmin();

        $promo = $this->promoModel->find($id);

        if (!$promo) {
            return $this->error('Promo code not found', 404);
        }

        return $this->json(['promo_code' => $promo]);
    }


    // POST /api/V1/promo-codes (admin only)
    /*
     * Body:
     * {
     *     "code": "WELCOME20",
     *     "description": "20% off for new users",
     *     "discount_type": "percentage",   // or "fixed"
     *     "discount_value": 20,
     *     "min_order_amount": 100,
     *     "usage_limit_total": 500,        // null/omit = unlimited
     *     "usage_limit_per_user": 1,        // null/omit = unlimited
     *     "expires_at": "2026-12-31 23:59:59"  // null/omit = never expires
     * }
    */
    public function create()
    {
        $this->requireAdmin();

        $data = $this->getAllInput();

        // basic validation
        if (empty($data['code'])) {
            return $this->error('Promo code is required', 400);
        }

        if (empty($data['discount_type']) || !in_array($data['discount_type'], ['percentage', 'fixed'])) {
            return $this->error("discount_type must be 'percentage' or 'fixed'", 400);
        }

        if (!isset($data['discount_value']) || !is_numeric($data['discount_value']) || $data['discount_value'] <= 0) {
            return $this->error('discount_value must be a positive number', 400);
        }

        if ($data['discount_type'] === 'percentage' && $data['discount_value'] > 100) {
            return $this->error('Percentage discount cannot exceed 100', 400);
        }

        $code = strtoupper(trim($data['code']));

        if ($this->promoModel->codeExists($code)) {
            return $this->error('This promo code already exists', 409);
        }

        $promoId = $this->promoModel->create([
            'code' => $code,
            'description' => $data['description'] ?? null,
            'discount_type' => $data['discount_type'],
            'discount_value' => $data['discount_value'],
            'min_order_amount' => $data['min_order_amount'] ?? 0,
            'usage_limit_total' => $data['usage_limit_total'] ?? null,
            'usage_limit_per_user' => $data['usage_limit_per_user'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'is_active' => 1
        ]);

        if (!$promoId) {
            return $this->error('Failed to create promo code', 500);
        }

        $this->log('promo_code_created', ['promo_id' => $promoId, 'code' => $code]);

        return $this->json([
            'message' => 'Promo code created successfully',
            'promo_code' => $this->promoModel->find($promoId)
        ], null, 201);
    }


    // PUT /api/V1/promo-codes/{id} (admin only)
    public function update($id)
    {
        $this->requireAdmin();

        $existing = $this->promoModel->find($id);

        if (!$existing) {
            return $this->error('Promo code not found', 404);
        }

        $data = $this->getAllInput();

        if (empty($data)) {
            return $this->error('No data provided', 400);
        }

        // if changing the code string, re-check uniqueness
        if (isset($data['code'])) {
            $newCode = strtoupper(trim($data['code']));
            if ($this->promoModel->codeExists($newCode, $id)) {
                return $this->error('This promo code already exists', 409);
            }
            $data['code'] = $newCode;
        }

        if (isset($data['discount_type']) && !in_array($data['discount_type'], ['percentage', 'fixed'])) {
            return $this->error("discount_type must be 'percentage' or 'fixed'", 400);
        }

        if (isset($data['discount_value']) && (!is_numeric($data['discount_value']) || $data['discount_value'] <= 0)) {
            return $this->error('discount_value must be a positive number', 400);
        }

        $success = $this->promoModel->update($id, $data);

        if (!$success) {
            return $this->error('Failed to update promo code', 500);
        }

        $this->log('promo_code_updated', ['promo_id' => $id, 'fields' => array_keys($data)]);

        return $this->json([
            'message' => 'Promo code updated successfully',
            'promo_code' => $this->promoModel->find($id)
        ]);
    }


    // DELETE /api/V1/promo-codes/{id} (admin only)
    public function delete($id)
    {
        $this->requireAdmin();

        $promo = $this->promoModel->find($id);

        if (!$promo) {
            return $this->error('Promo code not found', 404);
        }

        $success = $this->promoModel->delete($id);

        if (!$success) {
            return $this->error('Failed to delete promo code', 500);
        }

        $this->log('promo_code_deleted', ['promo_id' => $id, 'code' => $promo['code']]);

        return $this->json(['message' => 'Promo code deleted successfully']);
    }
}