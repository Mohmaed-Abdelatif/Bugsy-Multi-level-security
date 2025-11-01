<?php
//Handles all database operations for products table
//Inherits CRUD and query builder from BaseModel
 
namespace Models\v1;

use Models\BaseModel;

class Product extends BaseModel
{
    //table name
    protected $table = 'products';
    //primary key
    protected $primaryKey = 'id';
    //enable timestamps
    protected $timestamps = true;




    //----------------------
    // custom query methods
    //----------------------
    
    //get product with category and brand names
    public function getWithNames($id)
    {
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.{$this->primaryKey} = '{$id}'
            LIMIT 1
        ";

        $product = $this->fetchOne($sql);
    
        // Add full image URL
        if ($product && $product['main_image']) {
            $product['main_image_url'] = APP_URL . '/public/uploads/products/' . $product['main_image'];
        } else {
            $product['main_image_url'] = APP_URL . '/public/uploads/products/no-image.png';
        }
        
        return $product;
    }


    //get all with category and brand names
    public function getAllWithNames($limit=null, $offset=null)
    {
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.is_available = 1
            ORDER BY p.created_at DESC
        ";

        if($limit){
            $sql = $sql . "LIMIT {$limit}";
            if($offset){
                $sql = $sql . "OFFSET {$offset}";
            }
        }

        return $this->fetchAll($sql);
    }


    //search products by name (v1 vlunerable, v2 secure use prepard statements)
    public function searchByName($keyword, $limit = null)
    {
        $sql ="
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.name LIKE '%{$keyword}%'  
            AND p.is_available = 1
            ORDER BY p.rating DESC     
        ";

        if($limit){
            $sql = $sql . "LIMIT {$limit}";
        }

        return $this->fetchAll($sql);
    }


    //get product by category
    public function getByCategory($categoryId, $limit=null, $offset=null)
    {
        //useing query builder methods that made in basemodel
        return $this->where('category_id','=',$categoryId)->where('is_available','=',1)->orderBy('rating','DESC')->findAll($limit,$offset);
    }

    //get product by brand
    public function getByBrand($brandId, $limit=null,$offset=null)
    {
        return $this->where('brand_id','=',$brandId)->where('is_available','=',1)->orderBy('rating','DESC')->findAll($limit,$offset);
    }


    //get featured products
    public function getFeatured($limit=10)
    {
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.is_available = 1
            And p.stock > 0
            ORDER BY p.rating DESC, p.created_at DESC
            LIMIT {$limit}
        ";

        return $this->fetchAll($sql);
    }

    //get latest products
    public function getLatest($limit=10)
    {
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.is_available = 1
            And p.stock > 0
            ORDER BY p.created_at DESC
            LIMIT {$limit}
        ";

        return $this->fetchAll($sql);
    }

    //filter products by price range
    public function getByPriceRange($minPrice, $maxPrice, $limit=null)
    {
        return $this->where('price', '>=', $minPrice)->where('price', '<=', $maxPrice)->where('is_available', '=', 1)->orderBy('price', 'ASC')->findAll($limit);
    }

    
    //update product stock
    public function updateStock($id, $quantity)
    {
        return $this->update($id,['stock' => $quantity]);
    }

    //decrease product stock after purchase
    public function decreaseStock($id, $quantity)
    {
        //get current product data
        $product = $this->find($id);

        if(!$product){
            return false;
        }

        $newStock = max(0, $product['stock'] - $quantity);

        return $this->updateStock($id, $newStock);
    }

    //check if product stock is enough
    public function checkStock($id, $requestedQuantity = 1)
    {
        $product = $this->find($id);

        if(!$product){
            return false;
        }

        return $product['stock'] >= $requestedQuantity;
    }

    //get products with low stock (for admin alerts)
    public function getLowStock($lowStock = 3)
    {
        return $this->where('stock', '<=', $lowStock)->where('stock', '>', 0)->where('is_available', '=', 1)->orderBy('stock', 'ASC')->findAll();
    }

    //get out of stock products (for admin)
    public function getOutOfStock()
    {
        return $this->where('stock', '=', 0)
                    ->where('is_available', '=', 1)
                    ->findAll();
    }

}
