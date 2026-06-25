<?php
namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Brand;
use Models\V2\Product;
use Helpers\V2\ImageUpload;

class BrandController extends BaseController
{
    private Brand   $brandModel;
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->brandModel   = new Brand();
        $this->productModel = new Product();
    }


    // GET /api/v2/brands — public
    public function index(): void
    {
        $brands = $this->brandModel->getAllWithCount();

        foreach ($brands as &$brand) {
            if ($brand && $brand['logo']) {
                $brand['logo_url'] = ImageUpload::getUrl($brand['logo']);
            } else {
                $brand['logo_url'] = ImageUpload::getUrl('');
            }
        }
        unset($brand);

        $this->json(['brands' => $brands]);
    }


    // GET /api/v2/brands/{id}/products — public
    public function products(int $id): void
    {
        $brand = $this->brandModel->getWithCount($id);

        if (!$brand) {
            $this->error('Brand not found', 404);
            return;
        }

        $pagination = $this->getPagination(20);

        $products = $this->productModel->getByBrand($id, $pagination['perPage'], $pagination['offset']);
        $total    = $this->productModel->where('brand_id', '=', $id)->where('is_available', '=', 1)->count();

        $this->json([
            'brand'      => $brand,
            'products'   => $products,
            'pagination' => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }



    // POST /api/v2/brands — admin only
    public function create(): void
    {
        $this->requireAdmin();

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            $data = $_POST;

            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['logo']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['logo'] = $uploadResult['filename'];
            }
        } else {
            $data = $this->getAllInput();

            if (isset($data['logo_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['logo_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['logo'] = $uploadResult['filename'];
                unset($data['logo_base64']);
            }
        }

        if (!isset($data['name']) || empty($data['name'])) {
            if (isset($data['logo'])) ImageUpload::delete($data['logo']);
            $this->error('Field name is required', 422, ['name' => 'Name is required']);
            return;
        }

        if ($this->brandModel->nameExists($data['name'])) {
            if (isset($data['logo'])) ImageUpload::delete($data['logo']);
            $this->error('Brand name already exists', 409);
            return;
        }

        $brandId = $this->brandModel->create($data);

        if (!$brandId) {
            if (isset($data['logo'])) ImageUpload::delete($data['logo']);
            $this->error('Failed to create brand', 500);
            return;
        }

        $brand = $this->brandModel->find($brandId);

        $brand['logo_url'] = $brand['logo']
            ? ImageUpload::getUrl($brand['logo'])
            : ImageUpload::getUrl('');

        $this->log('brand_created_v2', ['brand_id' => $brandId, 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Brand created successfully',
            'brand'   => $brand
        ], null, 201);
    }


    // POST /api/v2/brands/{id} — admin only (update)
    // POST + _method=PUT in request body for multipart file uploads
    public function update(int $id): void
    {
        $this->requireAdmin();

        $existingBrand = $this->brandModel->find($id);

        if (!$existingBrand) {
            $this->error('Brand not found', 404);
            return;
        }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        // Check for method override (POST with _method=PUT)               
        $actualMethod = $this->getMethod();
        if ($actualMethod === 'POST') {
            $methodOverride = $this->getInput('_method') ?: $this->getQuery('_method');
            if (strtoupper((string)$methodOverride) === 'PUT') {
                $actualMethod = 'PUT';
            }
        }

        $data = [];

        if ($isMultipart) {
            // Handle multipart form data (works for POST)
            // For PUT with files, client must use POST with _method=PUT
            if ($actualMethod === 'POST' || !empty($_POST)) {
                $data = $_POST;
                unset($data['_method']);

                // Handle new category image upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = ImageUpload::upload($_FILES['logo']);

                    if (!$uploadResult['success']) {
                        $this->error($uploadResult['error'], 400);
                        return;
                    }

                    if ($existingBrand['logo']) {
                        ImageUpload::delete($existingBrand['logo']);
                    }

                    $data['logo'] = $uploadResult['filename'];
                }
            } else {
                $this->error(
                    'For file uploads with PUT, use POST with _method=PUT parameter',
                    400,
                    ['hint' => 'Add _method=PUT to form data or query string']
                );
                return;
            }
        } else {
            // Handle JSON data (regular PUT)           
            $data = $this->getAllInput();

            if (isset($data['logo_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['logo_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }

                if ($existingBrand['logo']) {
                    ImageUpload::delete($existingBrand['logo']);
                }

                $data['logo'] = $uploadResult['filename'];
                unset($data['logo_base64']);
            }
        }

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['name']) && $this->brandModel->nameExists($data['name'], $id)) {
            $this->error('Brand name already exists', 409);
            return;
        }

        $success = $this->brandModel->update($id, $data);

        if (!$success) {
            $this->error('Failed to update brand', 500);
            return;
        }

        $brand = $this->brandModel->find($id);

        $brand['logo_url'] = $brand['logo']
            ? ImageUpload::getUrl($brand['logo'])
            : ImageUpload::getUrl('');

        $this->log('brand_updated_v2', ['brand_id' => $id, 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Brand updated successfully',
            'brand'   => $brand
        ]);
    }



    // DELETE /api/v2/brands/{id} — admin only
    public function delete(int $id): void
    {
        $this->requireAdmin();

        $brand = $this->brandModel->find($id);

        if (!$brand) {
            $this->error('Brand not found', 404);
            return;
        }

        if ($brand['logo']) {
            ImageUpload::delete($brand['logo']);
        }

        $success = $this->brandModel->delete($id);

        if (!$success) {
            $this->error('Failed to delete brand', 500);
            return;
        }

        $this->log('brand_deleted_v2', [
            'brand_id' => $id,
            'name'     => $brand['name'],
            'by'       => $this->getUserId()
        ]);

        $this->json(['message' => 'Brand deleted successfully']);
    }
}