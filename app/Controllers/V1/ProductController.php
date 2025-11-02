<?php
//handle product endpoints
namespace Controllers\v1;

use Controllers\BaseController;
use Models\v1\Product;
use Helpers\ImageUpload;


class ProductController extends BaseController
{
    //product model instance
    private $productModel;

    //initialize controller with product model
    public function __construct()
    {
        Parent::__construct();
        $this->productModel = new Product();
    }



    //-----------------------------------
    //public endpoints (read operations)
    //-----------------------------------

    //list all products:  get /api/v1/products
    // complex exm:  GET /api/v1/products?page=1&per_page=20&category=1&sort=price&order=desc
    /*
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "products": [...],
     *         "pagination": {
     *             "total": 100,
     *             "perPage": 20,
     *             "page": 1,
     *             "totalPages": 5
     *         }
     *     }
     * }
    */
    public function index()
    {
        //get pagination parameters
        $pagination = $this->getPagination(20); //default 20 per page
        //get filter parameters
        $categoryId = $this->getQuery('category');
        $brandId = $this->getQuery('brand');
        $minPrice = $this->getQuery('min_price');
        $maxPrice = $this->getQuery('max_price');
        // Get sorting parameters
        $sortField = $this->getQuery('sort', 'created_at');
        $sortOrder = $this->getQuery('order', 'desc');

        // Validate sort field (prevent SQL injection)
        $allowedSortFields = ['price', 'rating', 'created_at', 'name'];
        if (!in_array($sortField, $allowedSortFields)) {
            $sortField = 'created_at';
        }
        // Validate sort order
        $sortOrder = strtoupper($sortOrder) === 'ASC' ? 'ASC' : 'DESC';
        


        //Build query with filters
        $query = $this->productModel->where('is_available','=',1);
    
        // Apply category filter
        if ($categoryId) {
            $query->where('category_id', '=', $categoryId);
        }
        // Apply brand filter
        if ($brandId) {
            $query->where('brand_id', '=', $brandId);
        }
        // Apply price range filter
        if ($minPrice) {
            $query->where('price', '>=', $minPrice);
        }
        if ($maxPrice) {
            $query->where('price', '<=', $maxPrice);
        }
        
        // apply sorting
        $query->orderBy($sortField, $sortOrder);

        //get products data and paginate result
        // $productResult = $query->limit($pagination['perPage'])->findAll();
        $results = $query->paginate($pagination['perPage'],$pagination['offset']); //broke query bilder
        //get products with full details (category and brand names)
        $products = [];
        foreach($results['data'] as $product){
            $products[] = $this->productModel->getWithNames($product['id']);
        }

        //return response
        return $this->json([
            'products' => $products,
            'pagination' => [
                'total' => $results['total'],
                'perPage' => $results['perPage'],
                'page' => $results['page'],
                'totalPages' => $results['totalPages']
            ],
            'filters' => [
                'category' => $categoryId,
                'brand' => $brandId,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'sort' => $sortField,
                'order' => $sortOrder
            ]
        ]);
    }


    //get single product details:   get /api/v1/products/{id}
    //exm:  /api/v1/products/5
    /*
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "id": 5,
     *         "name": "Samsung Galaxy S24 Ultra",
     *         "price": 48999,
     *         "category_name": "Phones",
     *         "brand_name": "Samsung",
     *         ...
     *     }
     * }
    */
    public function show($id)
    {
        //validate Id
        if(!$id || !is_numeric($id)){
            return $this->error('Invalid product ID');
        }

        //get product with details
        $product = $this->productModel->getWithNames($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        // Check if product is available
        if (!$product['is_available']) {
            return $this->error('Product is not available', 404);
        }
        
        // Return product data
        return $this->json(['product' => $product]);
    }


    //search products by keyword: get /api/v1/products/search?q=keyword
    public function search()
    {
        // Get search keyword
        $keyword = $this->getQuery('q', '');
        
        if (empty($keyword)) {
            return $this->error('Search keyword is required', 400);
        }
        
        // Get limit
        $limit = min(100, max(1, (int)$this->getQuery('limit', 20)));
        
        // Search products vulnerable in v1
        $results = $this->productModel->searchByName($keyword, $limit);
        
        // Log search in development
        if (APP_ENV === 'development') {
            error_log("Product search: keyword='{$keyword}', results=" . count($results));
        }
        
        // Return results
        return $this->json([
            'results' => $results,
            'keyword' => $keyword,
            'total' => count($results)
        ]);
    }


    //get products by category get /api/v1/categories/{id}/products
    public function categoryProducts($categoryId)
    {
        // Validate category ID
        if (!$categoryId || !is_numeric($categoryId)) {
            return $this->error('Invalid category ID', 400);
        }
        
        // Get pagination
        $pagination = $this->getPagination(20);
        
        // Get products by category
        $products = $this->productModel->getByCategory( $categoryId, $pagination['perPage'], $pagination['offset']);
        
        // Count total products in category
        $total = $this->productModel->where('category_id', '=', $categoryId)->where('is_available', '=', 1)->count();
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        // Return response
        return $this->json([
            'products' => $products,
            'category_id' => (int)$categoryId,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }



    //get products by brand get /api/v1/brands/{id}/products
    public function brandProducts($brandId)
    {
        // Validate brand ID
        if (!$brandId || !is_numeric($brandId)) {
            return $this->error('Invalid brand ID', 400);
        }
        
        // Get pagination
        $pagination = $this->getPagination(20);
        
        // Get products by brand
        $products = $this->productModel->getByBrand( $brandId, $pagination['perPage'], $pagination['offset']);
        
        // Count total products in brand
        $total = $this->productModel->where('brand_id', '=', $brandId)->where('is_available', '=', 1)->count();
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        // Return response
        return $this->json([
            'products' => $products,
            'brand_id' => (int)$brandId,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }




    //-----------------------------------
    // Admin endpints (write operations)
    //-----------------------------------
    //V1: Requires admin role (week auth + need login fist) => current
    //V2: Requires admin role (strong auth)
    //V3: Requires admin role (strong auth)+ 2FA

    //create new product (admin only) : post /api/v1/products
    /*
     * Form Data (multipart/form-data):
     * - name: string
     * - description: string
     * - price: number
     * - stock: number
     * - category_id: number
     * - brand_id: number
     * - main_image: file (image file)
     * - additional_images[]: file[] (multiple images, optional)
     * 
     * OR JSON with base64:
     * {
     *     "name": "Product name",
     *     "price": 999,
     *     "main_image_base64": "data:image/jpeg;base64,/9j/4AAQ..."
     * }
    */
    // if will add aditional_images shoud the key be like this additional_images[]
    public function create()
    {
        $this->requireAdmin();
        
        //determine if request is multipart (file upload) or from json (base64)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

        if ($isMultipart) {
            // Handle multipart form data
            $data = $_POST; // Form fields
            
            // Handle main image upload
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['main_image']);
                
                if ($uploadResult['success']) {
                    $data['main_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }
        } else {
            // Handle JSON data
            $data = $this->getAllInput();
            
            // Handle base64 image if present
            if (isset($data['main_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['main_image_base64']);
                
                if ($uploadResult['success']) {
                    $data['main_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
                
                unset($data['main_image_base64']);
            }
        }

        
        // Basic validation (weak in V1)
        $required = ['name', 'price', 'stock', 'category_id', 'brand_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->error("Field '{$field}' is required", 400);
            }
        }
        
        // Validate data types
        if (!is_numeric($data['price']) || $data['price'] <= 0) {
            return $this->error('Invalid price', 400);
        }
        
        if (!is_numeric($data['stock']) || $data['stock'] < 0) {
            return $this->error('Invalid stock quantity', 400);
        }
        
        // Set default values
        if (!isset($data['is_available'])) {
            $data['is_available'] = 1;
        }
        
        if (!isset($data['rating'])) {
            $data['rating'] = 0.00;
        }
        
        // Create product
        $productId = $this->productModel->create($data);
        
        if (!$productId) {
            if (isset($data['main_image'])) {
                ImageUpload::delete($data['main_image']);
            }
            return $this->error('Failed to create product', 500);// If product creation failed, delete uploaded image
        }

        //handle additional images (if uploaded)
        if ($isMultipart && isset($_FILES['additional_images'])) {
            $this->uploadAdditionalImages($productId, $_FILES['additional_images']);
        }
        
        // Get created product
        $product = $this->productModel->getWithNames($productId);

        // Add full image URL
        if ($product && $product['main_image']) {
            $product['main_image_url'] = ImageUpload::getUrl($product['main_image']);
        }
        
        // Log action (V2/V3 will use audit_logs table)
        $this->log('product_created', ['product_id' => $productId]);
        
        // Return success
        return $this->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }


    //update product (admin only) : put /api/v1/products/{id}
    // For image upload with PUT, use POST with _method=PUT
    /*
     URL: /api/v1/products/5?_method=PUT
     - Body: form-data
       - _method: PUT
       - name: Updated Product Name
       - price: 999
       - main_image: [file]
     
    */
    public function update($id)
    {
        $this->requireAdmin();

        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid product ID', 400);
        }

        // Get existing product
        $existingProduct = $this->productModel->find($id);

        if (!$existingProduct) {
            return $this->error('Product not found', 404);
        }

        // Determine content type and request method
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

        // Check for method override (POST with _method=PUT)
        $actualMethod = $this->getMethod();
        if ($actualMethod === 'POST') {
            $methodOverride = $this->getInput('_method') ?: $this->getQuery('_method');
            if (strtoupper($methodOverride) === 'PUT') {
                $actualMethod = 'PUT';
            }
        }

        $data = [];

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

                    if ($uploadResult['success']) {
                        // Delete old image
                        if ($existingProduct['main_image']) {
                            ImageUpload::delete($existingProduct['main_image']);
                        }

                        $data['main_image'] = $uploadResult['filename'];
                    } else {
                        return $this->error($uploadResult['error'], 400);
                    }
                }

                // Handle deleting specific additional images
                // Expected format: delete_images[] = [1, 5, 8] (image IDs)
                if (isset($data['delete_images']) && is_array($data['delete_images'])) {
                    foreach ($data['delete_images'] as $imageId) {
                        $this->deleteProductImage($imageId);
                    }
                    unset($data['delete_images']);
                }

                // Handle adding new additional images
                if (isset($_FILES['additional_images'])) {
                    $additionalImagesUploaded = $this->uploadAdditionalImages($id, $_FILES['additional_images']);
                }
            } else {
                return $this->error(
                    'For file uploads with PUT, use POST with _method=PUT parameter', 
                    400,
                    ['hint' => 'Add _method=PUT to form data or query string']
                );
            }

        } else {
            // Handle JSON data (regular PUT)
            $data = $this->getAllInput();

            // Handle base64 image
            if (isset($data['main_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['main_image_base64']);

                if ($uploadResult['success']) {
                    // Delete old image
                    if ($existingProduct['main_image']) {
                        ImageUpload::delete($existingProduct['main_image']);
                    }

                    $data['main_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }

                unset($data['main_image_base64']);
            }


            // Handle deleting specific additional images (JSON)
            if (isset($data['delete_images']) && is_array($data['delete_images'])) {
                foreach ($data['delete_images'] as $imageId) {
                    $this->deleteProductImage($imageId);
                }
                unset($data['delete_images']);
            }

            // Handle base64 additional images (JSON)
            if (isset($data['additional_images_base64']) && is_array($data['additional_images_base64'])) {
                foreach ($data['additional_images_base64'] as $base64Image) {
                    $uploadResult = ImageUpload::uploadBase64($base64Image);

                    if ($uploadResult['success']) {
                        $this->productModel->addImage($id, $uploadResult['filename']);
                        $additionalImagesUploaded[] = [
                            'filename' => $uploadResult['filename'],
                            'url' => ImageUpload::getUrl($uploadResult['filename'])
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

                return $this->json([
                    'message' => 'Additional images uploaded successfully',
                    'product' => $product,
                    'new_images' => $additionalImagesUploaded
                ]);
            }
            return $this->error('No data provided', 400);
        }

        // Validate numeric fields if provided
        if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] <= 0)) {
            return $this->error('Invalid price', 400);
        }

        if (isset($data['stock']) && (!is_numeric($data['stock']) || $data['stock'] < 0)) {
            return $this->error('Invalid stock quantity', 400);
        }

        // Update product
        $success = $this->productModel->update($id, $data);

        if (!$success) {
            return $this->error('Failed to update product', 500);
        }

        // Get updated product
        $product = $this->productModel->getWithNames($id);

        // Add full image URL
        if ($product && $product['main_image']) {
            $product['main_image_url'] = ImageUpload::getUrl($product['main_image']);
        }

        // Get all additional images
        $product['additional_images'] = $this->getProductImagesWithUrls($id);


        // Log action
        $this->log('product_updated', [
            'product_id' => $id,
            'fields_updated' => array_keys($data),
        ]);

        return $this->json([
            'message' => 'Product updated successfully',
            'product' => $product,
            'new_images' => $additionalImagesUploaded
        ]);
    }



    //delete product (admin only) : delete /api/v1/products/{id}
    public function delete($id)
    {
        $this->requireAdmin();

        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid product ID', 400);
        }

        // Check if product exists
        $product = $this->productModel->find($id);

        if (!$product) {
            return $this->error('Product not found', 404);
        }

        // Get all additional images before deleting product
        $additionalImages = $this->productModel->getImages($id);

        $deletedFiles = [];
        $failedFiles = [];

        // Delete main image file
        if ($product['main_image']) {
            if (ImageUpload::delete($product['main_image'])) {
                $deletedFiles[] = $product['main_image'];
            } else {
                $failedFiles[] = $product['main_image'];
                error_log("Failed to delete main image: {$product['main_image']}");
            }
        }

        // Delete all additional image files
        foreach ($additionalImages as $image) {
            if (ImageUpload::delete($image['image_url'])) {
                $deletedFiles[] = $image['image_url'];
            } else {
                $failedFiles[] = $image['image_url'];
                error_log("Failed to delete additional image: {$image['image_url']}");
            }
        }

        // Delete product from database
        // This will CASCADE delete product_images records automatically
        $success = $this->productModel->delete($id);

        if (!$success) {
            return $this->error('Failed to delete product', 500);
        }

        // Log action
        $this->log('product_deleted', [
            'product_id' => $id,
            'product_name' => $product['name'],
            'images_deleted' => count($deletedFiles),
            'images_failed' => count($failedFiles)
        ]);

        // Build response message
        $message = 'Product deleted successfully';
        if (!empty($failedFiles)) {
            $message .= sprintf(
                ' (Note: %d image file(s) could not be deleted from disk)',
                count($failedFiles)
            );
        }

        // Return success
        return $this->json([
            'message' => $message,
            'details' => [
                'product_id' => $id,
                'images_deleted' => count($deletedFiles),
                'images_failed' => count($failedFiles)
            ]
        ]);
    }


    //uplad additional images for product
    //post /api/v1/products/{id}/images
    //use images[] key
    // If called from create/update, files are passed directly
    // If called as endpoint, get from $_FILES
    public function uploadAdditionalImages($productId, $files = null)
    {
        // Only require admin if called as endpoint
        $calledAsEndpoint = ($files === null);

        if ($calledAsEndpoint) {
            $this->requireAdmin();

            // Validate product ID
            if (!$productId || !is_numeric($productId)) {
                return $this->error('Invalid product ID', 400);
            }

            // Check if product exists
            if (!$this->productModel->exists($productId)) {
                return $this->error('Product not found', 404);
            }

            // Get files from $_FILES
            if (!isset($_FILES['images'])) {
                return $this->error('No images provided', 400);
            }

            $files = $_FILES['images'];
        }

        // Validate files array
        if (empty($files) || (isset($files['error']) && $files['error'] === UPLOAD_ERR_NO_FILE)) {
            if ($calledAsEndpoint) {
                return $this->error('No images provided', 400);
            }
            return []; // Return empty array if called from create/update
        }

        // Upload multiple images
        $uploadResult = ImageUpload::uploadMultiple($files, 5);

        if (!$uploadResult['success']) {
            $errorMessage = 'Failed to upload images';
            if (!empty($uploadResult['errors'])) {
                $errorMessage .= ': ' . implode(', ', $uploadResult['errors']);
            }

            if ($calledAsEndpoint) {
                return $this->error($errorMessage, 400);
            }

            // Log error but don't fail product creation
            error_log("Additional images upload failed for product {$productId}: {$errorMessage}");
            return [];
        }

        // Store uploaded images in product_images table
        $storedImages = [];
        foreach ($uploadResult['files'] as $filename) {
            $imageId = $this->productModel->addImage($productId, $filename);

            if ($imageId) {
                $storedImages[] = [
                    'id' => $imageId,
                    'filename' => $filename,
                    'url' => ImageUpload::getUrl($filename)
                ];
            } else {
                error_log("Failed to store image {$filename} in database for product {$productId}");
            }
        }

        // Log success
        if (APP_ENV === 'development') {
            error_log(sprintf(
                "Uploaded %d additional images for product %d",
                count($storedImages),
                $productId
            ));
        }

        // If called as endpoint, return JSON response
        if ($calledAsEndpoint) {
            return $this->json([
                'message' => 'Images uploaded successfully',
                'images' => $storedImages,
                'uploaded_count' => count($storedImages)
            ]);
        }

        // If called from create/update, return array of stored images
        return $storedImages;
    }


    //delete single product image
    public function deleteProductImage($imageId)
    {
        // Check if called as endpoint (has imageId parameter)
        $calledAsEndpoint = func_num_args() > 0 && is_numeric($imageId);

        if ($calledAsEndpoint) {
            $this->requireAdmin();

            // Validate image ID
            if (!$imageId || !is_numeric($imageId)) {
                return $this->error('Invalid image ID', 400);
            }
        }

        // Get image details from database
        $image = $this->productModel->getImageById($imageId);

        if (!$image) {
            if ($calledAsEndpoint) {
                return $this->error('Image not found', 404);
            }
            return false;
        }

        // Delete file from disk
        $fileDeleted = ImageUpload::delete($image['image_url']);

        if (!$fileDeleted) {
            error_log("Failed to delete image file: {$image['image_url']}");
        }

        // Delete from database
        $dbDeleted = $this->productModel->deleteImage($imageId);

        if (!$dbDeleted) {
            if ($calledAsEndpoint) {
                return $this->error('Failed to delete image from database', 500);
            }
            return false;
        }

        // Log action
        $this->log('product_image_deleted', [
            'image_id' => $imageId,
            'product_id' => $image['product_id'],
            'filename' => $image['image_url']
        ]);

        // If called as endpoint, return success response
        if ($calledAsEndpoint) {
            return $this->json([
                'message' => 'Image deleted successfully',
                'image_id' => $imageId
            ]);
        }

        return true;
    }


    //get prodcut images withe full urls
    private function getProductImagesWithUrls($productId)
    {
        $images = $this->productModel->getImages($productId);

        // Add full URLs to each image
        return array_map(function($image) {
            return [
                'id' => $image['id'],
                'filename' => $image['image_url'],
                'url' => ImageUpload::getUrl($image['image_url']),
                'created_at' => $image['created_at']
            ];
        }, $images);
    }


    //get all images for a product
    public function getProductImages($productId)
    {
        // Validate product ID
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }

        // Check if product exists
        if (!$this->productModel->exists($productId)) {
            return $this->error('Product not found', 404);
        }

        // Get images with URLs
        $images = $this->getProductImagesWithUrls($productId);

        return $this->json([
            'product_id' => (int)$productId,
            'images' => $images,
            'count' => count($images)
        ]);
    }


    //replace all additional images for a product
    public function replaceProductImages($productId)
    {
        $this->requireAdmin();

        // Validate product ID
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }

        // Check if product exists
        if (!$this->productModel->exists($productId)) {
            return $this->error('Product not found', 404);
        }

        // Check if files provided
        if (!isset($_FILES['images'])) {
            return $this->error('No images provided', 400);
        }

        // Get existing images
        $existingImages = $this->productModel->getImages($productId);

        // Delete all existing additional images
        foreach ($existingImages as $image) {
            ImageUpload::delete($image['image_url']);
            $this->productModel->deleteImage($image['id']);
        }

        // Upload new images
        $uploadResult = $this->uploadAdditionalImages($productId, $_FILES['images']);

        return $this->json([
            'message' => 'Images replaced successfully',
            'old_count' => count($existingImages),
            'new_count' => count($uploadResult),
            'images' => $uploadResult
        ]);
    }






}