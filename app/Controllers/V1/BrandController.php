<?php

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Brand;
use Models\V1\Product;
use Helpers\ImageUpload;

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

        foreach($brands as &$brand){
            if ($brand && $brand['logo']) {
            $brand['logo_url'] = ImageUpload::getUrl($brand['logo']);
            }
        }
        unset($brand);
        
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

        //determine if request is multipart (file upload) or from json (base64)
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = strpos($contentType, 'multipart/form-data') !== false;

        if ($isMultipart) {
            // Handle multipart form data
            $data = $_POST; // Form fields
            
            // Handle main image upload
            if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                $uploadResult = ImageUpload::upload($_FILES['logo']);
                
                if ($uploadResult['success']) {
                    $data['logo'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
            }
        } else {
            // Handle JSON data
            $data = $this->getAllInput();
            
            // Handle base64 image if present
            if (isset($data['logo_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['logo_base64']);
                
                if ($uploadResult['success']) {
                    $data['logo'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }
                
                unset($data['logo_base64']);
            }
        }

        if (!isset($data['name']) || empty($data['name'])) {
                return $this->error("Field name is required", 400);
        }

        $brandId = $this->brandModel->create($data);

        if (!$brandId) {
            if (isset($data['logo'])) {
                ImageUpload::delete($data['logo']);
            }
            return $this->error('Failed to create brand', 500);// If brand creation failed, delete uploaded image
        }

        
        // Get created brand
        $brand = $this->brandModel->find($brandId);

        // Add full image URL
        if ($brand && $brand['logo']) {
            $brand['logo_url'] = APP_URL . '/public/uploads/products/' . $brand['logo'];
        } else {
            $brand['logo_url'] = APP_URL . '/public/uploads/products/no-image.png';
        }
    
        // Add full image URL
        if ($brand && $brand['logo']) {
            $brand['logo_url'] = ImageUpload::getUrl($brand['logo']);
        }
        
        $this->log('brand_created',['brand_id'=>$brandId]);
        
        return $this->json([
            'message' => 'Brand created successfully',
            'brand' => $brand
        ], null, 201);
    }


    //update brand (for admin): put /api/v1/brands/{id}
        
    /*
     * For file uploads, use: POST with _method=PUT
     * URL: /api/v1/brands/5
     * Body: form-data
     *   - _method: PUT
     *   - name: Updated Brand
     *   - logo: [file]
    */
    public function update($id)
    {
        // Require admin
        $this->requireAdmin();

        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid brand ID', 400);
        }

        // Get existing brand
        $existingBrand = $this->brandModel->find($id);

        if (!$existingBrand) {
            return $this->error('Brand not found', 404);
        }

        // Determine content type and check method override
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

                // Handle new logo upload
                if (isset($_FILES['logo']) && $_FILES['logo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $uploadResult = ImageUpload::upload($_FILES['logo']);

                    if ($uploadResult['success']) {
                        // Delete old logo
                        if ($existingBrand['logo']) {
                            ImageUpload::delete($existingBrand['logo']);
                        }

                        $data['logo'] = $uploadResult['filename'];
                    } else {
                        return $this->error($uploadResult['error'], 400);
                    }
                }
            } else {
                // Multipart PUT without POST override - not supported
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
            if (isset($data['logo_base64'])) {
                $uploadResult = ImageUpload::uploadBase64($data['logo_base64']);

                if ($uploadResult['success']) {
                    // Delete old logo
                    if ($existingBrand['logo']) {
                        ImageUpload::delete($existingBrand['logo']);
                    }

                    $data['logo'] = $uploadResult['filename'];
                } else {
                    return $this->error($uploadResult['error'], 400);
                }

                unset($data['logo_base64']);
            }
        }

        // Check if we have data to update
        if (empty($data)) {
            return $this->error('No data provided', 400);
        }

        // If name is being updated, check if it already exists
        if (isset($data['name']) && $this->brandModel->nameExists($data['name'], $id)) {
            return $this->error('Brand name already exists', 409);
        }

        // Update brand
        $success = $this->brandModel->update($id, $data);

        if (!$success) {
            return $this->error('Failed to update brand', 500);
        }

        // Get updated brand
        $brand = $this->brandModel->find($id);

        // Add full image URL
        if ($brand && $brand['logo']) {
            $brand['logo_url'] = ImageUpload::getUrl($brand['logo']);
        }

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

        // Delete main image
        if ($brand['logo']) {
            ImageUpload::delete($brand['logo']);
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