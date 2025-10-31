<?php
namespace Models\v1;

use Models\BaseModel;

class Brand extends BaseModel
{
    protected $table = 'brands';
    protected $primaryKey = 'id';
    protected $timestamps = true;



    //get all brands with product count
    public function getAllWithCount()
    {
        $sql = "SELECT b.*, COUNT(p.id) as product_count
                FROM {$this->table} b
                LEFT JOIN products p ON b.id = p.brand_id AND p.is_available = 1
                GROUP BY b.id
                ORDER BY b.name ASC
        ";
        
        return $this->fetchAll($sql);
    }

    //get brands with product count
    public function getWithCount($id)
    {
        $id = $this->connection->real_escape_string($id);
        
        $sql = "
            SELECT b.*, COUNT(p.id) as product_count
            FROM {$this->table} b
            LEFT JOIN products p ON c.id = p.category_id AND p.is_available = 1
            WHERE b.id = '{$id}'
            GROUP BY b.id
            LIMIT 1
        ";
        
        return $this->fetchOne($sql);
    }


    //get brand with its products
    public function getWithProducts($brandId, $limit = 20, $offset = 0)
    {
        $brand = $this->find($brandId);
        
        if (!$brand) {
            return null;
        }
        
        $sql = "SELECT * FROM products
                WHERE brand_id = " . (int)$brandId . "
                AND is_available = 1
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $brand['products'] = $this->fetchAll($sql);
        
        // Get total count
        $sql = "SELECT COUNT(*) as total FROM products 
                WHERE brand_id = " . (int)$brandId . " AND is_available = 1";
        $result = $this->fetchOne($sql);
        $brand['total_products'] = (int)($result['total'] ?? 0);
        
        return $brand;
    }

    //get popular brands (most products)
    public function getPopular($limit = 10)
    {
        $sql = "SELECT b.*, COUNT(p.id) as product_count
                FROM {$this->table} b
                LEFT JOIN products p ON b.id = p.brand_id AND p.is_available = 1
                GROUP BY b.id
                HAVING product_count > 0
                ORDER BY product_count DESC
                LIMIT {$limit}
        ";
        
        return $this->fetchAll($sql);
    }


    //check if brand name exists
    public function nameExists($name, $excludeId = null)
    {
        $name = $this->connection->real_escape_string($name);
        
        $sql = "SELECT id FROM {$this->table} WHERE name = '{$name}'";
        
        if ($excludeId) {
            $excludeId = $this->connection->real_escape_string($excludeId);
            $sql .= " AND id != '{$excludeId}'";
        }
        
        $sql .= " LIMIT 1";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            return false;
        }
        
        $exists = $result->num_rows > 0;
        $result->free();
        
        return $exists;
    }



    //search brands by name
    public function searchByName($keyword)
    {
        // VULNERABLE in V1
        $keyword = $this->connection->real_escape_string($keyword);
        
        $sql = "SELECT * FROM {$this->table}
                WHERE name LIKE '%{$keyword}%'
                ORDER BY name ASC
        ";
        
        return $this->fetchAll($sql);
    }

}