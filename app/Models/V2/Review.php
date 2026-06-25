<?php
// Models/V2/Review.php
// Extends V1\Review, overrides ALL methods that had raw string interpolation
// BaseModel auto-detects v2 → PDO connection used automatically


namespace Models\V2;

class Review extends \Models\V1\Review
{
    // Override: getByProduct — V1 used raw string for $productId    
    public function getByProduct( $productId, $limit = 10, $offset = 0): array
    {
        $stmt = $this->connection->prepare("
            SELECT r.*, u.name as user_name, u.email as user_email
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = :product_id
            ORDER BY r.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        $stmt->bindValue(':product_id', $productId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit',      $limit,     \PDO::PARAM_INT);
        $stmt->bindValue(':offset',     $offset,    \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    // Override: getWithUser — V1 used raw string for $reviewId
    public function getWithUser( $reviewId): ?array
    {
        $stmt = $this->connection->prepare("
            SELECT r.*, u.email as user_email, u.name as user_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = :id
        ");
        $stmt->execute(['id' => $reviewId]);
        $review = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $review ?: null;
    }


    // Override: getByUser — V1 used raw string for $userId
    public function getByUser($userId): array
    {
        $stmt = $this->connection->prepare("
            SELECT r.*, p.name as product_name, p.main_image as product_image
            FROM {$this->table} r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE r.user_id = :user_id
            ORDER BY r.created_at DESC
        ");
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    // Check if user purchased this product
    // Override: isVerifiedPurchase — V1 used raw string
    public function isVerifiedPurchase($userId, $productId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT COUNT(*) as purchase_count
            FROM order_items oi
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = :user_id
            AND oi.product_id = :product_id
            AND o.status != 'cancelled'
        ");
        $stmt->execute(['user_id' => $userId, 'product_id' => $productId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $result && $result['purchase_count'] > 0;
    }


    
    public function createReview($data): int|false
    {
        // One review per user per product — inherited hasUserReviewed() is safe
        if ($this->hasUserReviewed($data['user_id'], $data['product_id'])) {
            error_log("User {$data['user_id']} already reviewed product {$data['product_id']}");
            return false;
        }

        // Verified purchase check — now PDO-safe
        $data['is_verified_purchase'] = $this->isVerifiedPurchase(
            $data['user_id'],
            $data['product_id']
        ) ? 1 : 0;

        if ($data['rating'] < 1.0 || $data['rating'] > 5.0) {
            error_log("Invalid rating: {$data['rating']}");
            return false;
        }

        // BaseModel::create() — already PDO in v2
        $reviewId = $this->create($data);

        if ($reviewId) {
            $this->updateProductRating($data['product_id']);

            if (APP_ENV === 'development') {
                error_log("Review created: ID={$reviewId}, Product={$data['product_id']}, Rating={$data['rating']}");
            }
        }

        return $reviewId;
    }


    public function updateReview($reviewId, $data): bool
    {
        $existingReview = $this->find($reviewId); // BaseModel::find() — already PDO

        if (!$existingReview) {
            return false;
        }

        if (isset($data['rating'])) {
            if ($data['rating'] < 1.0 || $data['rating'] > 5.0) {
                error_log("Invalid rating: {$data['rating']}");
                return false;
            }
        }

        $success = $this->update($reviewId, $data); // BaseModel::update() — already PDO

        if ($success) {
            $this->updateProductRating($existingReview['product_id']);

            if (APP_ENV === 'development') {
                error_log("Review updated: ID={$reviewId}");
            }
        }

        return $success;
    }


    public function deleteReview($reviewId): bool
    {
        $review = $this->find($reviewId);

        if (!$review) {
            return false;
        }

        $success = $this->delete($reviewId); // BaseModel::delete() — already PDO

        if ($success) {
            $this->updateProductRating($review['product_id']);

            if (APP_ENV === 'development') {
                error_log("Review deleted: ID={$reviewId}");
            }
        }

        return $success;
    }

    //Calculate and update product rating 
    //Override: updateProductRating — V1 used raw string
    public function updateProductRating($productId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT rating FROM {$this->table}
            WHERE product_id = :product_id
            ORDER BY rating ASC
        ");
        $stmt->execute(['product_id' => $productId]);
        $reviews = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($reviews)) {
            $this->updateProductRatingValue($productId, 0.00);
            return true;
        }

        $ratings = array_column($reviews, 'rating');
        $count   = count($ratings);
        $sum     = array_sum($ratings);
        $average = round($sum / $count, 2);

        $this->updateProductRatingValue($productId, $average);

        if (APP_ENV === 'development') {
            error_log("Product {$productId} rating updated to {$average} (from {$count} reviews)");
        }

        return true;
    }

    //Update product rating value in database(helper method)
    private function updateProductRatingValue(int $productId, float $rating): bool
    {
        $stmt = $this->connection->prepare(
            "UPDATE products SET rating = :rating WHERE id = :id"
        );

        return $stmt->execute(['rating' => $rating, 'id' => $productId]);
    }


    //Get rating statistics for a product
    //Override: getRatingStats — V1 used raw string
    public function getRatingStats( $productId): array
    {
        $stmt = $this->connection->prepare("
            SELECT 
                COUNT(*) as total_reviews,
                AVG(rating) as average_rating,
                MIN(rating) as min_rating,
                MAX(rating) as max_rating,
                SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as five_star,
                SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as four_star,
                SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as three_star,
                SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as two_star,
                SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as one_star
            FROM {$this->table}
            WHERE product_id = :product_id
        ");
        $stmt->execute(['product_id' => $productId]);
        $stats = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$stats || $stats['total_reviews'] == 0) {
            return [
                'total_reviews'  => 0,
                'average_rating' => 0.00,
                'distribution'   => [
                    '5_star' => 0, '4_star' => 0, '3_star' => 0, '2_star' => 0, '1_star' => 0
                ]
            ];
        }

        $productStmt = $this->connection->prepare("SELECT rating FROM products WHERE id = :id");
        $productStmt->execute(['id' => $productId]);
        $product = $productStmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'total_reviews'  => (int)$stats['total_reviews'],
            'average_rating' => (float)($product['rating'] ?? 0),
            'min_rating'     => (float)$stats['min_rating'],
            'max_rating'     => (float)$stats['max_rating'],
            'distribution'   => [
                '5_star' => (int)$stats['five_star'],
                '4_star' => (int)$stats['four_star'],
                '3_star' => (int)$stats['three_star'],
                '2_star' => (int)$stats['two_star'],
                '1_star' => (int)$stats['one_star'],
            ]
        ];
    }


    //check if user already voted on this review
    public function hasUserVoted($reviewId, $userId): bool
    {
        $stmt = $this->connection->prepare("
            SELECT id FROM review_helpfulness 
            WHERE review_id = :review_id AND user_id = :user_id 
            LIMIT 1
        ");
        $stmt->execute(['review_id' => $reviewId, 'user_id' => $userId]);

        return $stmt->fetch() !== false;
    }


    
    // Override: addHelpfulVote — V1 used raw string
    private function addHelpfulVote(int $reviewId, int $userId): bool
    {
        $stmt = $this->connection->prepare("
            INSERT INTO review_helpfulness (review_id, user_id, is_helpful) 
            VALUES (:review_id, :user_id, 1)
        ");

        return $stmt->execute(['review_id' => $reviewId, 'user_id' => $userId]);
    }


    // Override: incrementHelpful — V1 used raw string
    private function incrementHelpful(int $reviewId): bool
    {
        $stmt = $this->connection->prepare("
            UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = :id
        ");

        return $stmt->execute(['id' => $reviewId]);
    }


    // Override: removeHelpfulVote — V1 used raw string
    private function removeHelpfulVote(int $reviewId, int $userId): bool
    {
        $stmt = $this->connection->prepare("
            DELETE FROM review_helpfulness 
            WHERE review_id = :review_id AND user_id = :user_id
        ");

        return $stmt->execute(['review_id' => $reviewId, 'user_id' => $userId]);
    }


    // Override: decrementHelpful — V1 used raw string
    private function decrementHelpful(int $reviewId): bool
    {
        $stmt = $this->connection->prepare("
            UPDATE {$this->table} 
            SET helpful_count = GREATEST(helpful_count - 1, 0) 
            WHERE id = :id
        ");

        return $stmt->execute(['id' => $reviewId]);
    }

    //toggle helpful vote
    //first call: add vote and increse count
    //second call: remove vote and decrement count
    //Override: toggleHelpful — orchestrates the PDO-safe private methods above
    public function toggleHelpful($reviewId, $userId): array
    {
        $alreadyVoted = $this->hasUserVoted($reviewId, $userId);

        if ($alreadyVoted) {
            $this->removeHelpfulVote($reviewId, $userId);
            $this->decrementHelpful($reviewId);
            $action = 'removed';
        } else {
            $this->addHelpfulVote($reviewId, $userId);
            $this->incrementHelpful($reviewId);
            $action = 'added';
        }

        $review = $this->find($reviewId); // BaseModel::find() — already PDO
        $count  = (int)($review['helpful_count'] ?? 0);

        return [
            'action'        => $action,
            'voted'         => !$alreadyVoted,
            'helpful_count' => $count
        ];
    }


    // Override: getTopHelpfulReviews — V1 used raw string
    public function getTopHelpfulReviews( $productId, $limit = 5): array
    {
        $stmt = $this->connection->prepare("
            SELECT r.*, u.name as user_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = :product_id
            AND r.helpful_count > 0
            ORDER BY r.helpful_count DESC, r.created_at DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':product_id', $productId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit',      $limit,     \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    
}