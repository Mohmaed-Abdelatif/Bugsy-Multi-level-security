<?php
//manage categores, public usere read access only , admin only write

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Category;
use Models\V1\Product;
use Helpers\ImageUpload;


class CategoryController extends BaseController
{
    private $categoryModel;
    private $productModel;


    public function __construct()
    {
        parent::__construct();
        $this->categoryModel = new Category();
        $this->productModel = new Product();
    }


    //get all categories: get /api/v1/categories
    public function index()
    {
        // Get all categories with product count
        $categories = $this->categoryModel->getAllWithCount();

        foreach($categories as &$category){
            if ($category && $category['cat_image']) {
            $category['cat_image_url'] = ImageUpload::getUrl($category['cat_image']);
            }
        }
        unset($category);
        
        return $this->json([
            'categories' => $categories
        ]);
    }


    //get products by category: get /api/v1/categories/{id}/products
    public function categoryProducts($id)
    {
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid category ID', 400);
        }
        
        $category = $this->categoryModel->getWithCount($id);
        
        if (!$category) {
            return $this->error('category not found', 404);
        }
        
        // Get pagination
        $pagination = $this->getPagination(20);
        
        // Get products by category
        $products = $this->productModel->getByCategory($id, $pagination['perPage'], $pagination['offset']);
        
        // Count total products
        $total = $this->productModel->where('category_id', '=', $id)->where('is_available', '=', 1)->count();
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        return $this->json([
            'category' => $category,
            'products' => $products,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }

    //create category (for admin): post /api/v1/categories
    /*
     * 
     * Request Body:
     * {
     *     "name": "Laptops",
     *     "description": "Notebook computers"
     * }
    */
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
            if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['cat_image']);
                
                if ($uploadResult['success']) {
                    $data['cat_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }
        } else {
            // Handle JSON data
            $data = $this->getAllInput();
            
            // Handle base64 image if present
            if (isset($data['cat_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['cat_image_base64']);
                
                if ($uploadResult['success']) {
                    $data['cat_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
                
                unset($data['cat_image_base64']);
            }
        }

        
        // Validate
        if (!isset($data['name']) || empty($data['name'])) {
                return $this->error("Field name is required", 400);
        }
        
        // Check if name exists
        if ($this->categoryModel->nameExists($data['name'])) {
            return $this->error('Category name already exists', 409);
        }
        
        // Create category (method create in base model)
        $categoryId = $this->categoryModel->create($data);
        
        if (!$categoryId) {
            if (isset($data['cat_image'])) {
                ImageUpload::delete($data['cat_image']);
            }
            return $this->error('Failed to create category', 500);// If category creation failed, delete uploaded image

        }
        
        // Get created category
        $category = $this->categoryModel->find($categoryId);

        // Add full image URL
        if ($category && $category['cat_image']) {
            $category['cat_image_url'] = APP_URL . '/public/uploads/products/' . $category['cat_image'];
        } else {
            $category['cat_image_url'] = APP_URL . '/public/uploads/products/no-image.png';
        }
    
        // Add full image URL
        if ($category && $category['cat_image']) {
            $category['cat_image_url'] = ImageUpload::getUrl($category['cat_image']);
        }
        
        // Log action
        $this->log('category_created', ['category_id' => $categoryId]);
        
        return $this->json([
            'message' => 'Category created successfully',
            'category' => $category
        ], null, 201);
    }


    //update category(for admin): put /api/v1/categories/{id}
        public function update($id)
    {
        
        $this->requireAdmin();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid category ID', 400);
        }
        
        // Get existing category
        $existingcategory = $this->categoryModel->find($id);
        
        if (!$existingcategory) {
            return $this->error('category not found', 404);
        }


        // Determine content type
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

        if ($isMultipart) {
            $data = $_POST;
            
            // Handle new main image
            if (isset($_FILES['cat_image']) && $_FILES['cat_image']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['cat_image']);
                
                if ($uploadResult['success']) {
                    // Delete old image
                    if ($existingcategory['cat_image']) {
                        ImageUpload::delete($existingcategory['cat_image']);
                    }
                    
                    $data['cat_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }
        } else {
            $data = $this->getAllInput();
            
            // Handle base64 image
            if (isset($data['cat_image_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['cat_image_base64']);
                
                if ($uploadResult['success']) {
                    // Delete old image
                    if ($existingcategory['cat_image']) {
                        ImageUpload::delete($existingcategory['cat_image']);
                    }
                    
                    $data['cat_image'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
                
                unset($data['cat_image_base64']);
            }
        }

        if (empty($data)) {
            return $this->error('No data provided', 400);
        }
        
        // Check if exists
        if (!$this->categoryModel->exists($id)) {
            return $this->error('Category not found', 404);
        }
        
        
        // If name is being updated, check if it exists
        if (isset($data['name']) && $this->categoryModel->nameExists($data['name'], $id)) {
            return $this->error('Category name already exists', 409);
        }
        
        // Update
        $success = $this->categoryModel->update($id, $data);
        
        if (!$success) {
            return $this->error('Failed to update category', 500);
        }
        
        // Get updated category
        $category = $this->categoryModel->find($id);

        // Add full image URL
        if ($category && $category['cat_image']) {
            $category['cat_image_url'] = ImageUpload::getUrl($category['cat_image']);
        }

        // Log action
        $this->log('category_updated', ['category_id' => $id]);
        
        return $this->json([
            'message' => 'Category updated successfully',
            'category' => $category
        ]);
    }


    //delete category: delete /api/v1/categories/{id}
    public function delete($id)
    {
        
        $this->requireAdmin();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid category ID', 400);
        }
        
        // Check if exists
        $category = $this->categoryModel->find($id);
        
        if (!$category) {
            return $this->error('Category not found', 404);
        }
    
         // Delete main image
        if ($category['cat_image']) {
            ImageUpload::delete($category['cat_image']);
        }

        // Delete
        $success = $this->categoryModel->delete($id);
        
        if (!$success) {
            return $this->error('Failed to delete category', 500);
        }
        
        // Log action
        $this->log('category_deleted', ['category_id' => $id, 'name' => $category['name']]);
        
        return $this->json([
            'message' => 'Category deleted successfully'
        ]);
    }





}