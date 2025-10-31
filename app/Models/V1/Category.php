<?php
namespace Models\v1;

use Models\BaseModel;

class Category extends BaseModel
{
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    //get all categories with product count
    public function getAllWithCount()
    {
        $sql = "SELECT c.*, COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_available = 1
                GROUP BY c.id, c.name, c.description, c.created_at
                ORDER BY c.name ASC
        ";
        
        return $this->fetchAll($sql);
    }


    //get category with product count
    public function getWithCount($id)
    {
        $id = $this->connection->real_escape_string($id);
        
        $sql = "
            SELECT c.*, COUNT(p.id) as product_count
            FROM {$this->table} c
            LEFT JOIN products p ON c.id = p.category_id AND p.is_available = 1
            WHERE c.id = '{$id}'
            GROUP BY c.id, c.name, c.description, c.created_at
            LIMIT 1
        ";
        
        return $this->fetchOne($sql);
    }


    //get category with its products
    public function getWithProducts($categoryId, $limit = 20, $offset = 0)
    {
        $category = $this->find($categoryId);
        
        if (!$category) {
            return null;
        }
        
        $sql = "SELECT * FROM products
                WHERE category_id = " . (int)$categoryId . "
                AND is_available = 1
                ORDER BY created_at DESC
                LIMIT {$limit} OFFSET {$offset}";
        
        $category['products'] = $this->fetchAll($sql);
        
        // Get total count
        $sql = "SELECT COUNT(*) as total FROM products 
                WHERE category_id = " . (int)$categoryId . " AND is_available = 1";
        $result = $this->fetchOne($sql);
        $category['total_products'] = (int)($result['total'] ?? 0);
        
        return $category;
    }


    //get popular categories (most products)
    public function getPopular($limit = 10)
    {
        $sql = "SELECT c.*, COUNT(p.id) as product_count
                FROM {$this->table} c
                LEFT JOIN products p ON c.id = p.category_id AND p.is_available = 1
                GROUP BY c.id, c.name, c.description, c.created_at
                HAVING product_count > 0
                ORDER BY product_count DESC
                LIMIT {$limit}
        ";
        
        return $this->fetchAll($sql);
    }
    

    //check if category name exists
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



    //search categories by name
    public function searchByName($keyword)
    {
        // VULNERABLE in V1
        $keyword = $this->connection->real_escape_string($keyword);
        
        $sql = "SELECT * FROM {$this->table}
                WHERE name LIKE '%{$keyword}%'
                OR description LIKE '%{$keyword}%'
                ORDER BY name ASC";
        
        return $this->fetchAll($sql);
    }


}