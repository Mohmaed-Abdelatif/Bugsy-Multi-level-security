<?php
//manage categores, public usere read access only , admin only write

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Category;
use Models\V1\Product;


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
        $total = $this->productModel->where('category_id', '=', $id)
                                    ->where('is_available', '=', 1)
                                    ->count();
        
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
        
        // Get input
        $name = $this->getInput('name');
        $description = $this->getInput('description');
        
        // Validate
        if (empty($name)) {
            return $this->error('Category name is required', 400);
        }
        
        // Check if name exists
        if ($this->categoryModel->nameExists($name)) {
            return $this->error('Category name already exists', 409);
        }
        
        // Create category (method create in base model)
        $categoryId = $this->categoryModel->create([
            'name' => $name,
            'description' => $description
        ]);
        
        if (!$categoryId) {
            return $this->error('Failed to create category', 500);
        }
        
        // Get created category
        $category = $this->categoryModel->find($categoryId);
        
        // Log action
        $this->log('category_created', ['category_id' => $categoryId, 'name' => $name]);
        
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
        
        // Check if exists
        if (!$this->categoryModel->exists($id)) {
            return $this->error('Category not found', 404);
        }
        
        // Get input
        $data = $this->getAllInput();
        
        if (empty($data)) {
            return $this->error('No data provided', 400);
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