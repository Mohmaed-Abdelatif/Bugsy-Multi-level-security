<?php
// Admin-only management of promo codes — JWT + admin role required
// Validator used for input checking, matching V2's pattern elsewhere

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\PromoCode;
use Helpers\Validator;

class PromoCodeController extends BaseController
{
    private PromoCode $promoModel;

    public function __construct()
    {
        parent::__construct();
        $this->promoModel = new PromoCode();
    }


    // GET /api/v2/promo-codes — admin only
    public function index(): void
    {
        $this->requireAdmin();

        $promos = $this->promoModel->findAll();

        $this->json(['promo_codes' => $promos]);
    }


    // GET /api/v2/promo-codes/{id} — admin only
    public function show(int $id): void
    {
        $this->requireAdmin();

        $promo = $this->promoModel->find($id);

        if (!$promo) {
            $this->error('Promo code not found', 404);
            return;
        }

        $this->json(['promo_code' => $promo]);
    }


    // POST /api/v2/promo-codes — admin only
    /*
     * Body:
     * {
     *     "code": "WELCOME50",
     *     "description": "50% off for new users",
     *     "discount_type": "percentage",
     *     "discount_value": 50,
     *     "min_order_amount": 100,
     *     "usage_limit_total": 500,
     *     "usage_limit_per_user": 2,
     *     "expires_at": "2026-12-31 23:59:59"
     * }
    */
    public function create(): void
    {
        $this->requireAdmin();

        $data = $this->getAllInput();

        $errors = Validator::make($data)
            ->required(['code', 'discount_type', 'discount_value'])
            ->in('discount_type', ['percentage', 'fixed'])
            ->numeric('discount_value')
            ->minValue('discount_value', 0.01)
            ->validate();

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        if ($data['discount_type'] === 'percentage' && (float)$data['discount_value'] > 100) {
            $this->error('Validation failed', 422, ['discount_value' => 'Percentage discount cannot exceed 100']);
            return;
        }

        $code = strtoupper(trim($data['code']));

        if ($this->promoModel->codeExists($code)) {
            $this->error('This promo code already exists', 409);
            return;
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
            $this->error('Failed to create promo code', 500);
            return;
        }

        $this->log('promo_code_created_v2', ['promo_id' => $promoId, 'code' => $code, 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Promo code created successfully',
            'promo_code' => $this->promoModel->find($promoId)
        ], null, 201);
    }


    // PUT /api/v2/promo-codes/{id} — admin only
    public function update(int $id): void
    {
        $this->requireAdmin();

        $existing = $this->promoModel->find($id);

        if (!$existing) {
            $this->error('Promo code not found', 404);
            return;
        }

        $data = $this->getAllInput();

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['code'])) {
            $newCode = strtoupper(trim($data['code']));
            if ($this->promoModel->codeExists($newCode, $id)) {
                $this->error('This promo code already exists', 409);
                return;
            }
            $data['code'] = $newCode;
        }

        if (isset($data['discount_type'])) {
            $errors = Validator::make($data)->in('discount_type', ['percentage', 'fixed'])->validate();
            if (!empty($errors)) { $this->error('Validation failed', 422, $errors); return; }
        }

        if (isset($data['discount_value'])) {
            $errors = Validator::make($data)->numeric('discount_value')->minValue('discount_value', 0.01)->validate();
            if (!empty($errors)) { $this->error('Validation failed', 422, $errors); return; };
            
            if ($data['discount_type'] === 'percentage' && (float)$data['discount_value'] > 100) {
            $this->error('Validation failed', 422, ['discount_value' => 'Percentage discount cannot exceed 100']);
            return;
            }
        }

        $success = $this->promoModel->update($id, $data);

        if (!$success) {
            $this->error('Failed to update promo code', 500);
            return;
        }

        $this->log('promo_code_updated_v2', ['promo_id' => $id, 'fields' => array_keys($data), 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Promo code updated successfully',
            'promo_code' => $this->promoModel->find($id)
        ]);
    }


    // DELETE /api/v2/promo-codes/{id} — admin only
    public function delete(int $id): void
    {
        $this->requireAdmin();

        $promo = $this->promoModel->find($id);

        if (!$promo) {
            $this->error('Promo code not found', 404);
            return;
        }

        $success = $this->promoModel->delete($id);

        if (!$success) {
            $this->error('Failed to delete promo code', 500);
            return;
        }

        $this->log('promo_code_deleted_v2', ['promo_id' => $id, 'code' => $promo['code'], 'by' => $this->getUserId()]);

        $this->json(['message' => 'Promo code deleted successfully']);
    }
}