<?php
//requireAdmin() now backed by real JWT verification (was weak session before)
//Uses Helpers\V2\ImageUpload — strict MIME validation, no .php uploads
//Validator used for basic field checks
//Audit log written via BaseController::log()

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Category;
use Models\V2\Product;
use Helpers\V2\ImageUpload;

class CategoryController extends BaseController
{
    private Category $categoryModel;
    private Product  $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new Category();
        $this->productModel  = new Product();
    }


    // get all categories
    // GET /api/v2/categories — public
    public function index(): void
    {
        $categories = $this->categoryModel->getAllWithCount();

        foreach ($categories as &$category) {
            if ($category && $category['cat_image']) {
                $category['cat_image_url'] = ImageUpload::getUrl($category['cat_image']);
            } else {
                $category['cat_image_url'] = ImageUpload::getUrl('');
            }
        }
        unset($category);

        $this->json(['categories' => $categories]);
    }


    // POST /api/v2/categories — admin only
    public function create(): void
    {
        $this->requireAdmin();

        //determine if request is multipart (file upload) or from json (base64)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            $data = $_POST;

            // Handle main image upload
            if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['cat_image']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['cat_image'] = $uploadResult['filename'];
            }
        } else {
            // Handle JSON data
            $data = $this->getAllInput();

            if (isset($data['cat_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['cat_image_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['cat_image'] = $uploadResult['filename'];
                unset($data['cat_image_base64']);
            }
        }

        if (!isset($data['name']) || empty($data['name'])) {
            //image cleanup on failure.
            if (isset($data['cat_image'])) ImageUpload::delete($data['cat_image']);
            $this->error('Field name is required', 422, ['name' => 'Name is required']);
            return;
        }

        if ($this->categoryModel->nameExists($data['name'])) {
            //image cleanup on failure.
            if (isset($data['cat_image'])) ImageUpload::delete($data['cat_image']);
            $this->error('Category name already exists', 409);
            return;
        }

        $categoryId = $this->categoryModel->create($data);

        if (!$categoryId) {
            //image cleanup on failure.
            if (isset($data['cat_image'])) ImageUpload::delete($data['cat_image']);
            $this->error('Failed to create category', 500);
            return;
        }

        $category = $this->categoryModel->find($categoryId);

        $category['cat_image_url'] = $category['cat_image']
            ? ImageUpload::getUrl($category['cat_image'])
            : ImageUpload::getUrl('');

        $this->log('category_created_v2', ['category_id' => $categoryId, 'by' => $this->getUserId()]);

        $this->json([
            'message'  => 'Category created successfully',
            'category' => $category
        ], null, 201);
    }


    // POST /api/v2/categories/{id} — admin only (update)
    // POST + _method=PUT in request body for multipart file uploads
    public function update(int $id): void
    {
        $this->requireAdmin();

        $existingCategory = $this->categoryModel->find($id);

        if (!$existingCategory) {
            $this->error('Category not found', 404);
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
                if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = ImageUpload::upload($_FILES['cat_image']);

                    if (!$uploadResult['success']) {
                        $this->error($uploadResult['error'], 400);
                        return;
                    }

                    if ($existingCategory['cat_image']) {
                        ImageUpload::delete($existingCategory['cat_image']);
                    }

                    $data['cat_image'] = $uploadResult['filename'];
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

            if (isset($data['cat_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['cat_image_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }

                if ($existingCategory['cat_image']) {
                    ImageUpload::delete($existingCategory['cat_image']);
                }

                $data['cat_image'] = $uploadResult['filename'];
                unset($data['cat_image_base64']);
            }
        }

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['name']) && $this->categoryModel->nameExists($data['name'], $id)) {
            $this->error('Category name already exists', 409);
            return;
        }

        $success = $this->categoryModel->update($id, $data);

        if (!$success) {
            $this->error('Failed to update category', 500);
            return;
        }

        $category = $this->categoryModel->find($id);

        $category['cat_image_url'] = $category['cat_image']
            ? ImageUpload::getUrl($category['cat_image'])
            : ImageUpload::getUrl('');

        $this->log('category_updated_v2', ['category_id' => $id, 'by' => $this->getUserId()]);

        $this->json([
            'message'  => 'Category updated successfully',
            'category' => $category
        ]);
    }


    // DELETE /api/v2/categories/{id} — admin only
    public function delete(int $id): void
    {
        $this->requireAdmin();

        $category = $this->categoryModel->find($id);

        if (!$category) {
            $this->error('Category not found', 404);
            return;
        }

        if ($category['cat_image']) {
            ImageUpload::delete($category['cat_image']);
        }

        $success = $this->categoryModel->delete($id);

        if (!$success) {
            $this->error('Failed to delete category', 500);
            return;
        }

        $this->log('category_deleted_v2', [
            'category_id' => $id,
            'name'        => $category['name'],
            'by'          => $this->getUserId()
        ]);

        $this->json(['message' => 'Category deleted successfully']);
    }
}