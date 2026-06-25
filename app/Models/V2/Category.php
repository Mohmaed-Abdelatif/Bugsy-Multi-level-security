<?php
// Extends V1\Category. overrides methods with raw user input in SQL
// BaseModel auto-detects v2, PDO connection used automatically

namespace Models\V2;

class Category extends \Models\V1\Category
{
    //get all categories with product count
    public function getAllWithCount(): array
    {
        $stmt = $this->connection->prepare("
            SELECT c.*, COUNT(p.id) as product_count
            FROM {$this->table} c
            LEFT JOIN products p ON p.category_id = c.id AND p.is_available = 1
            GROUP BY c.id
            ORDER BY c.name ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    // Override: getWithCount — raw string for $id
    public function getWithCount($id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT c.*, COUNT(p.id) as product_count
            FROM {$this->table} c
            LEFT JOIN products p ON p.category_id = c.id AND p.is_available = 1
            WHERE c.id = :id
            GROUP BY c.id
        ");
        $stmt->execute(['id' => $id]);
        $category = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $category ?: null;
    }


    
    // Override: nameExists — used for uniqueness check on create/update
    
    public function nameExists($name, $excludeId = null): bool
    {
        $sql    = "SELECT id FROM {$this->table} WHERE name = :name";
        $params = ['name' => $name];

        if ($excludeId !== null) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }

        $sql .= " LIMIT 1";

        $stmt = $this->connection->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetch() !== false;
    }
}