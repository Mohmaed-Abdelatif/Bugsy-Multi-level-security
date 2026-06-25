<?php
// Extends V1\Brand — overrides methods with raw user input in SQL
// BaseModel auto-detects v2 → PDO connection used automatically

namespace Models\V2;

class Brand extends \Models\V1\Brand
{
    //get all brands with product count
    public function getAllWithCount(): array
    {
        $stmt = $this->connection->prepare("
            SELECT b.*, COUNT(p.id) as product_count
            FROM {$this->table} b
            LEFT JOIN products p ON p.brand_id = b.id AND p.is_available = 1
            GROUP BY b.id
            ORDER BY b.name ASC
        ");
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    // Override: getWithCount
    public function getWithCount($id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT b.*, COUNT(p.id) as product_count
            FROM {$this->table} b
            LEFT JOIN products p ON p.brand_id = b.id AND p.is_available = 1
            WHERE b.id = :id
            GROUP BY b.id
        ");
        $stmt->execute(['id' => $id]);
        $brand = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $brand ?: null;
    }


    // Override: nameExists 
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