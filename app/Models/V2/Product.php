<?php
// Extends V1\Product, overrides only methods with raw user input in SQL
// NOT overridden (inherited, already safe):
// BaseModel auto-detects v2 → PDO connection used automatically


namespace Models\V2;

class Product extends \Models\V1\Product
{
    // Override: getWithNames , V1 used raw string for $id
    public function getWithNames($id): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.{$this->primaryKey} = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $id]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$product) {
            return null;
        }

        $product['main_image_url'] = $product['main_image']
            ? APP_URL . '/uploads/products/' . $product['main_image']
            : APP_URL . '/uploads/products/no-image.png';

        return $product;
    }


    // Override: searchByName
    //V1: "WHERE p.name LIKE '%{$keyword}%'" — classic SQL injection point
    //V2: bound parameter with LIKE wildcards built safely in PHP
    public function searchByName( $keyword, $limit = null): array
    {
        $sql = "
            SELECT p.*, c.name as category_name, b.name as brand_name
            FROM {$this->table} p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN brands b ON p.brand_id = b.id
            WHERE p.name LIKE :keyword
            AND p.is_available = 1
            ORDER BY p.rating DESC
        ";

        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
        }

        $stmt = $this->connection->prepare($sql);


        $stmt->execute(['keyword' => '%' . $keyword . '%']);

        $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        // Add image URLs to each result
        foreach ($results as &$product) {
            $product['main_image_url'] = $product['main_image']
                ? APP_URL . '/uploads/products/' . $product['main_image']
                : APP_URL . '/uploads/products/no-image.png';
        }

        return $results;
    }


    // Override: addImage — V1 used raw string
    public function addImage( $productId,  $imageUrl): int|false
    {
        $stmt = $this->connection->prepare(
            "INSERT INTO product_images (product_id, image_url) VALUES (:product_id, :image_url)"
        );

        $success = $stmt->execute([
            'product_id' => $productId,
            'image_url'  => $imageUrl,
        ]);

        return $success ? (int)$this->connection->lastInsertId() : false;
    }


    // Override: getImages — V1 used raw string for $productId
    public function getImages( $productId): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM product_images WHERE product_id = :product_id ORDER BY created_at ASC"
        );
        $stmt->execute(['product_id' => $productId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    // Override: getImageById — V1 used raw string for $imageId
    public function getImageById($imageId): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM product_images WHERE id = :id LIMIT 1"
        );
        $stmt->execute(['id' => $imageId]);
        $image = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $image ?: null;
    }


}