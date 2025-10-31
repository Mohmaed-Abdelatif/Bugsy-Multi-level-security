<?php

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Brand;
use Models\V1\Product;

class BrandController extends BaseController
{
    private $brandModel;
    private $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->brandModel = new Brand();
        $this->productModel = new Product();
    }


    //get all brands: get GET /api/v1/brands
    public function index()
    {
        // Get all brands with product count
        $brands = $this->brandModel->getAllWithCount();
        
        return $this->json([
            'brands' => $brands
        ]);
    }


    //get products by brand
    public function products($id)
    {
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid brand ID', 400);
        }
        
        // Get brand
        $brand = $this->brandModel->getWithCount($id);
        
        if (!$brand) {
            return $this->error('Brand not found', 404);
        }
        
        // Get pagination
        $pagination = $this->getPagination(20);
        
        // Get products by brand
        $products = $this->productModel->getByBrand($id, $pagination['perPage'], $pagination['offset']);
        
        // Count total products
        $total = $this->productModel->where('brand_id', '=', $id)
                                    ->where('is_available', '=', 1)
                                    ->count();
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        return $this->json([
            'brand' => $brand,
            'products' => $products,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }



    //create brand (for admin)
    public function create()
    {
        // Require admin
        $this->requireAdmin();
        
        // Get input
        $name = $this->getInput('name');
        $logo = $this->getInput('logo');
        
        // Validate
        if (empty($name)) {
            return $this->error('Brand name is required', 400);
        }
        
        // Check if name exists
        if ($this->brandModel->nameExists($name)) {
            return $this->error('Brand name already exists', 409);
        }
        
        // Create brand
        $brandId = $this->brandModel->create([
            'name' => $name,
            'logo' => $logo
        ]);
        
        if (!$brandId) {
            return $this->error('Failed to create brand', 500);
        }
        
        // Get created brand
        $brand = $this->brandModel->find($brandId);
        
        $this->log('brand_created', ['brand_id' => $brandId, 'name' => $name]);
        
        return $this->json([
            'message' => 'Brand created successfully',
            'brand' => $brand
        ], null, 201);
    }


    //update brand (for admin): put /api/v1/brands/{id}
    public function update($id)
    {
        // Require admin
        $this->requireAdmin();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid brand ID', 400);
        }
        
        // Check if exists
        if (!$this->brandModel->exists($id)) {
            return $this->error('Brand not found', 404);
        }
        
        // Get input
        $data = $this->getAllInput();
        
        if (empty($data)) {
            return $this->error('No data provided', 400);
        }
        
        // If name is being updated, check if it exists
        if (isset($data['name']) && $this->brandModel->nameExists($data['name'], $id)) {
            return $this->error('Brand name already exists', 409);
        }
        
        // Update
        $success = $this->brandModel->update($id, $data);
        
        if (!$success) {
            return $this->error('Failed to update brand', 500);
        }
        
        // Get updated brand
        $brand = $this->brandModel->find($id);
        
        // Log action
        $this->log('brand_updated', ['brand_id' => $id]);
        
        return $this->json([
            'message' => 'Brand updated successfully',
            'brand' => $brand
        ]);
    }


    //delete brand (for admin): delete /api/v1/brands/{id}
    public function delete($id)
    {
        // Require admin
        $this->requireAdmin();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid brand ID', 400);
        }
        
        // Check if exists
        $brand = $this->brandModel->find($id);
        
        if (!$brand) {
            return $this->error('Brand not found', 404);
        }
        
        // Delete
        $success = $this->brandModel->delete($id);
        
        if (!$success) {
            return $this->error('Failed to delete brand', 500);
        }
        
        // Log action
        $this->log('brand_deleted', ['brand_id' => $id, 'name' => $brand['name']]);
        
        return $this->json([
            'message' => 'Brand deleted successfully'
        ]);
    }


}