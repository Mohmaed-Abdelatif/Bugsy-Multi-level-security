<?php
//requireAdmin() backed by real JWT verification
//Uses Helpers\V2\ImageUpload — strict MIME validation, UUID filenames
//searchByName uses PDO prepared LIKE — SQL injection impossible
//Validator used for basic numeric/required checks

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Product;
use Helpers\V2\ImageUpload;
use Helpers\Validator;

class ProductController extends BaseController
{
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }


    // GET /api/v2/products — public
    public function index(): void
    {
        //get pagination parameters
        $pagination = $this->getPagination(20);
        //get filter parameters
        $categoryId = $this->getQuery('category');
        $brandId    = $this->getQuery('brand');
        $minPrice   = $this->getQuery('min_price');
        $maxPrice   = $this->getQuery('max_price');
        // Get sorting parameters
        $sortField  = $this->getQuery('sort', 'created_at');
        $sortOrder  = $this->getQuery('order', 'desc');

        // Validate sort field (prevent SQL injection)
        $allowedSortFields = ['price', 'rating', 'created_at', 'name'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }

        // Validate sort order
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        
        //Build query with filters
        $query = $this->productModel->where('is_available', '=', 1);
        if ($categoryId) $query->where('category_id', '=', $categoryId);
        if ($brandId)    $query->where('brand_id', '=', $brandId);
        if ($minPrice)   $query->where('price', '>=', $minPrice);
        if ($maxPrice)   $query->where('price', '<=', $maxPrice);

        $query->orderBy($sortField, $sortOrder);

        //get products data and paginate result
        $results = $query->paginate($pagination['perPage'], $pagination['offset']);

        //get products with full details (category and brand names)
        $products = [];
        foreach ($results['data'] as $product) {
            $products[] = $this->productModel->getWithNames($product['id']);
        }

        $this->json([
            'products'   => $products,
            'pagination' => [
                'total'      => $results['total'],
                'perPage'    => $results['perPage'],
                'page'       => $results['page'],
                'totalPages' => $results['totalPages']
            ],
            'filters' => [
                'category' => $categoryId, 'brand' => $brandId,
                'minPrice' => $minPrice,   'maxPrice' => $maxPrice,
                'sort'     => $sortField,  'order' => $sortOrder
            ]
        ]);
    }



    // GET /api/v2/products/{id} — public
    public function show(int $id): void
    {
        $product = $this->productModel->getWithNames($id);

        if (!$product) {
            $this->error('Product not found', 404);
            return;
        }

        if (!$product['is_available']) {
            $this->error('Product is not available', 404);
            return;
        }

        $this->json(['product' => $product]);
    }


    // GET /api/v2/products/search?q=keyword — public
    public function search(): void
    {
        $keyword = $this->getQuery('q', '');

        if (empty($keyword)) {
            $this->error('Search keyword is required', 400);
            return;
        }

        $limit = min(100, max(1, (int)$this->getQuery('limit', 20)));

        //V2: PDO prepared LIKE, V1 had raw string interpolation here
        $results = $this->productModel->searchByName($keyword, $limit);

        if (APP_ENV === 'development') {
            error_log("V2 Product search: keyword='{$keyword}', results=" . count($results));
        }

        $this->json([
            'results' => $results,
            'keyword' => $keyword,
            'total'   => count($results)
        ]);
    }



    // GET /api/v2/categories/{id}/products — public
    public function categoryProducts(int $categoryId): void
    {
        $pagination = $this->getPagination(20);

        $products = $this->productModel->getByCategory($categoryId, $pagination['perPage'], $pagination['offset']);
        $total    = $this->productModel->where('category_id', '=', $categoryId)->where('is_available', '=', 1)->count();

        $this->json([
            'products'    => $products,
            'category_id' => $categoryId,
            'pagination'  => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }


    // GET /api/v2/brands/{id}/products — public
    public function brandProducts(int $brandId): void
    {
        $pagination = $this->getPagination(20);

        $products = $this->productModel->getByBrand($brandId, $pagination['perPage'], $pagination['offset']);
        $total    = $this->productModel->where('brand_id', '=', $brandId)->where('is_available', '=', 1)->count();

        $this->json([
            'products' => $products,
            'brand_id' => $brandId,
            'pagination' => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }


    
    // POST /api/v2/products — admin only

    public function create(): void
    {
        $this->requireAdmin();

        //determine if request is multipart (file upload) or from json (base64)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            // Handle multipart form data
            $data = $_POST;

            // Handle main image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['main_image']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['main_image'] = $uploadResult['filename'];
            }
        } else {
            // Handle JSON data
            $data = $this->getAllInput();

            if (isset($data['main_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['main_image_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }
                $data['main_image'] = $uploadResult['filename'];
                unset($data['main_image_base64']);
            }
        }

        $errors = Validator::make($data)
            ->required(['name', 'price', 'stock', 'category_id', 'brand_id'])
            ->numeric('price')
            ->numeric('stock')
            ->minValue('price', 0.01)
            ->validate();

        if (!empty($errors)) {
            if (isset($data['main_image'])) ImageUpload::delete($data['main_image']);
            $this->error('Validation failed', 422, $errors);
            return;
        }

        $data['is_available'] = $data['is_available'] ?? 1;
        $data['rating']       = $data['rating'] ?? 0.00;

        $productId = $this->productModel->create($data);

        if (!$productId) {
            if (isset($data['main_image'])) ImageUpload::delete($data['main_image']);
            $this->error('Failed to create product', 500);
            return;
        }

        //handle additional images (if uploaded)
        if ($isMultipart && isset($_FILES['additional_images'])) {
            $this->uploadAdditionalImagesInternal($productId, $_FILES['additional_images']);
        }

        $product = $this->productModel->getWithNames($productId);

        $product['main_image_url'] = $product['main_image']
            ? ImageUpload::getUrl($product['main_image'])
            : ImageUpload::getUrl('');

        $this->log('product_created_v2', ['product_id' => $productId, 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], null, 201);
    }


    //--------------------------------------------------
    // POST /api/v2/products/{id} — admin only (update)
    // Supports: main_image replace, delete_images[], additional_images
    //--------------------------------------------------
    public function update(int $id): void
    {
        $this->requireAdmin();

        $existingProduct = $this->productModel->find($id);

        if (!$existingProduct) {
            $this->error('Product not found', 404);
            return;
        }

        // Determine content type and request method
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
        $additionalImagesUploaded = [];

        if ($isMultipart) {
            // Handle multipart form data (works for POST)
            // For PUT with files, client must use POST with _method=PUT
            if ($actualMethod === 'POST' || !empty($_POST)) {
                $data = $_POST;
                // Remove _method field from data
                unset($data['_method']);

                // Handle new main image
                if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = ImageUpload::upload($_FILES['main_image']);

                    if (!$uploadResult['success']) {
                        $this->error($uploadResult['error'], 400);
                        return;
                    }

                    //delete old image
                    if ($existingProduct['main_image']) {
                        ImageUpload::delete($existingProduct['main_image']);
                    }

                    $data['main_image'] = $uploadResult['filename'];
                }

                // Delete specific additional images: delete_images[] = [1, 5, 8]
                if (isset($data['delete_images']) && is_array($data['delete_images'])) {
                    foreach ($data['delete_images'] as $imageId) {
                        $this->deleteProductImageInternal((int)$imageId);
                    }
                    unset($data['delete_images']);
                }

                // Add new additional images
                if (isset($_FILES['additional_images'])) {
                    $additionalImagesUploaded = $this->uploadAdditionalImagesInternal($id, $_FILES['additional_images']);
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

            if (isset($data['main_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['main_image_base64']);

                if (!$uploadResult['success']) {
                    $this->error($uploadResult['error'], 400);
                    return;
                }

                if ($existingProduct['main_image']) {
                    ImageUpload::delete($existingProduct['main_image']);
                }

                $data['main_image'] = $uploadResult['filename'];
                unset($data['main_image_base64']);
            }

            // Handle deleting specific additional images (JSON)
            if (isset($data['delete_images']) && is_array($data['delete_images'])) {
                foreach ($data['delete_images'] as $imageId) {
                    $this->deleteProductImageInternal((int)$imageId);
                }
                unset($data['delete_images']);
            }

            // Handle base64 additional images (JSON)
            if (isset($data['additional_images_base64']) && is_array($data['additional_images_base64'])) {
                foreach ($data['additional_images_base64'] as $base64Image) {
                    $uploadResult = ImageUpload::uploadBase64($base64Image);

                    if ($uploadResult['success']) {
                        $imgId = $this->productModel->addImage($id, $uploadResult['filename']);
                        $additionalImagesUploaded[] = [
                            'id'       => $imgId,
                            'filename' => $uploadResult['filename'],
                            'url'      => ImageUpload::getUrl($uploadResult['filename'])
                        ];
                    }
                }
                unset($data['additional_images_base64']);
            }
        }

        // Check if we have data to update
        if (empty($data)) {
            // If no product data but images were uploaded, that's okay
            if (!empty($additionalImagesUploaded)) {
                $product = $this->productModel->getWithNames($id);
                $this->json([
                    'message'    => 'Additional images uploaded successfully',
                    'product'    => $product,
                    'new_images' => $additionalImagesUploaded
                ]);
                return;
            }
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['price'])) {
            $errors = Validator::make($data)->numeric('price')->minValue('price', 0.01)->validate();
            if (!empty($errors)) { $this->error('Validation failed', 422, $errors); return; }
        }

        if (isset($data['stock'])) {
            $errors = Validator::make($data)->numeric('stock')->validate();
            if (!empty($errors)) { $this->error('Validation failed', 422, $errors); return; }
        }

        // Update product
        $success = $this->productModel->update($id, $data);

        if (!$success) {
            $this->error('Failed to update product', 500);
            return;
        }

        $product = $this->productModel->getWithNames($id);

        $product['main_image_url'] = $product['main_image']
            ? ImageUpload::getUrl($product['main_image'])
            : ImageUpload::getUrl('');

        $product['additional_images'] = $this->getProductImagesWithUrls($id);

        $this->log('product_updated_v2', [
            'product_id' => $id,
            'by'         => $this->getUserId(),
            'fields'     => array_keys($data)
        ]);

        $this->json([
            'message'    => 'Product updated successfully',
            'product'    => $product,
            'new_images' => $additionalImagesUploaded
        ]);
    }


    //DELETE /api/v2/products/{id} — admin only
    public function delete(int $id): void
    {
        $this->requireAdmin();

        $product = $this->productModel->find($id);

        if (!$product) {
            $this->error('Product not found', 404);
            return;
        }

        // Get all additional images before deleting product
        $additionalImages = $this->productModel->getImages($id);

        $deletedFiles = [];
        $failedFiles  = [];

        // Delete main image file
        if ($product['main_image']) {
            if (ImageUpload::delete($product['main_image'])) {
                $deletedFiles[] = $product['main_image'];
            } else {
                $failedFiles[] = $product['main_image'];
                error_log("V2: Failed to delete main image: {$product['main_image']}");
            }
        }

        // Delete all additional image files
        foreach ($additionalImages as $image) {
            if (ImageUpload::delete($image['image_url'])) {
                $deletedFiles[] = $image['image_url'];
            } else {
                $failedFiles[] = $image['image_url'];
                error_log("V2: Failed to delete additional image: {$image['image_url']}");
            }
        }

        //CASCADE deletes product_images rows atomatic, but delete above for insure 
        $success = $this->productModel->delete($id); 

        if (!$success) {
            $this->error('Failed to delete product', 500);
            return;
        }

        $this->log('product_deleted_v2', [
            'product_id'     => $id,
            'product_name'   => $product['name'],
            'images_deleted' => count($deletedFiles),
            'images_failed'  => count($failedFiles),
            'by'             => $this->getUserId()
        ]);

        $message = 'Product deleted successfully';
        if (!empty($failedFiles)) {
            $message .= sprintf(' (Note: %d image file(s) could not be deleted from disk)', count($failedFiles));
        }

        $this->json([
            'message' => $message,
            'details' => [
                'product_id'     => $id,
                'images_deleted' => count($deletedFiles),
                'images_failed'  => count($failedFiles)
            ]
        ]);
    }


    
    // POST /api/v2/products/{id}/images — admin only endpoint
    //must send image in images[] "so important"
    public function uploadAdditionalImages(int $productId): void
    {
        $this->requireAdmin();

        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        if (!isset($_FILES['images'])) {
            $this->error('No images provided', 400);
            return;
        }

        $stored = $this->uploadAdditionalImagesInternal($productId, $_FILES['images']);

        if (empty($stored)) {
            $this->error('Failed to upload images', 400);
            return;
        }

        $this->json([
            'message'        => 'Images uploaded successfully',
            'images'         => $stored,
            'uploaded_count' => count($stored)
        ]);
    }


    // DELETE /api/v2/products/images/{id} — admin only endpoint
    public function deleteProductImage(int $imageId): void
    {
        $this->requireAdmin();

        $success = $this->deleteProductImageInternal($imageId);

        if (!$success) {
            $this->error('Image not found or failed to delete', 404);
            return;
        }

        $this->json([
            'message'  => 'Image deleted successfully',
            'image_id' => $imageId
        ]);
    }


    // GET /api/v2/products/{id}/images — public
    public function getProductImages(int $productId): void
    {
        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        $images = $this->getProductImagesWithUrls($productId);

        $this->json([
            'product_id' => $productId,
            'images'     => $images,
            'count'      => count($images)
        ]);
    }


    // POST /api/v2/products/{id}/images/replace — admin only
    public function replaceProductImages(int $productId): void
    {
        $this->requireAdmin();

        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        if (!isset($_FILES['images'])) {
            $this->error('No images provided', 400);
            return;
        }

        $existingImages = $this->productModel->getImages($productId);

        foreach ($existingImages as $image) {
            ImageUpload::delete($image['image_url']);
            $this->productModel->deleteImage($image['id']);
        }

        $uploadResult = $this->uploadAdditionalImagesInternal($productId, $_FILES['images']);

        $this->log('product_images_replaced_v2', [
            'product_id' => $productId,
            'old_count'  => count($existingImages),
            'new_count'  => count($uploadResult),
            'by'         => $this->getUserId()
        ]);

        $this->json([
            'message'   => 'Images replaced successfully',
            'old_count' => count($existingImages),
            'new_count' => count($uploadResult),
            'images'    => $uploadResult
        ]);
    }



    // Private helpers — used internally by create/update/replace
    
    private function uploadAdditionalImagesInternal(int $productId, array $files): array
    {
        if (empty($files) || (isset($files['error']) && $files['error'] === UPLOAD_ERR_NO_FILE)) {
            return [];
        }

        $uploadResult = ImageUpload::uploadMultiple($files, 5);

        if (!$uploadResult['success']) {
            if (!empty($uploadResult['errors'])) {
                error_log("V2 additional images upload failed for product {$productId}: " . implode(', ', $uploadResult['errors']));
            }
            return [];
        }

        $stored = [];
        foreach ($uploadResult['files'] as $filename) {
            $imageId = $this->productModel->addImage($productId, $filename);

            if ($imageId) {
                $stored[] = ['id' => $imageId, 'filename' => $filename, 'url' => ImageUpload::getUrl($filename)];
            } else {
                error_log("V2: Failed to store image {$filename} for product {$productId}");
            }
        }

        return $stored;
    }


    private function deleteProductImageInternal(int $imageId): bool
    {
        $image = $this->productModel->getImageById($imageId);

        if (!$image) {
            return false;
        }

        $fileDeleted = ImageUpload::delete($image['image_url']);

        if (!$fileDeleted) {
            error_log("V2: Failed to delete image file: {$image['image_url']}");
        }

        $dbDeleted = $this->productModel->deleteImage($imageId);

        if ($dbDeleted) {
            $this->log('product_image_deleted_v2', [
                'image_id'   => $imageId,
                'product_id' => $image['product_id'],
                'filename'   => $image['image_url']
            ]);
        }

        return $dbDeleted;
    }


    private function getProductImagesWithUrls(int $productId): array
    {
        $images = $this->productModel->getImages($productId);

        return array_map(fn($image) => [
            'id'         => $image['id'],
            'filename'   => $image['image_url'],
            'url'        => ImageUpload::getUrl($image['image_url']),
            'created_at' => $image['created_at']
        ], $images);
    }
}