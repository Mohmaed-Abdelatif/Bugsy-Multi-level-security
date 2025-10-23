<?php
//handle product endpoints
namespace Controllers\v1;

use Controllers\BaseController;
use Models\v1\Product;

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
        $result = $query->paginate($pagination['perPage'],$pagination['offset']);

        //get products with full details (category and brand names)
        $products = [];
        foreach($result['data'] as $product){
            $products[] = $this->productModel->getWithNames($product['id']);
        }

        //return response
        return $this->json([
            'products' => $products,
            'pagination' => [
                'total' => $result['total'],
                'perPage' => $result['perPage'],
                'page' => $result['page'],
                'totalPages' => $result['totalPages']
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
    public function create()
    {
        // Get input data
        $data = $this->getAllInput();
        
        // Basic validation (weak in V1)
        $required = ['name', 'price', 'stock', 'category_id', 'brand_id'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                return $this->error("Field '{$field}' is required", 400);
            }
        }
        
        // Validate data types (V1: basic, V2: robust)
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
            return $this->error('Failed to create product', 500);
        }
        
        // Get created product
        $product = $this->productModel->getWithNames($productId);
        
        // Log action (V2/V3 will use audit_logs table)
        $this->log('product_created', ['product_id' => $productId]);
        
        // Return success
        return $this->json([
            'message' => 'Product created successfully',
            'product' => $product
        ], 201);
    }


    //update product (admin only) : put /api/v1/products/{id}
    public function update($id)
    {
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid product ID', 400);
        }
        
        // Check if product exists
        if (!$this->productModel->exists($id)) {
            return $this->error('Product not found', 404);
        }
        
        // Get update data
        $data = $this->getAllInput();
        
        if (empty($data)) {
            return $this->error('No data provided', 400);
        }
        
        // Validate numeric fields if present
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
        
        // Log action
        $this->log('product_updated', ['product_id' => $id]);
        
        // Return success
        return $this->json([
            'message' => 'Product updated successfully',
            'product' => $product
        ]);
    }


    //delete product (admin only) : delete /api/v1/products/{id}
    public function delete($id)
    {
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid product ID', 400);
        }
        
        // Check if product exists
        $product = $this->productModel->find($id);
        
        if (!$product) {
            return $this->error('Product not found', 404);
        }
        
        // Delete product
        $success = $this->productModel->delete($id);
        
        if (!$success) {
            return $this->error('Failed to delete product', 500);
        }
        
        // Log action
        $this->log('product_deleted', [
            'product_id' => $id,
            'product_name' => $product['name']
        ]);
        
        // Return success
        return $this->json([
            'message' => 'Product deleted successfully'
        ]);
    }

    
    



}