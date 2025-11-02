<?php
//handle product reviws

namespace Models\V1;

use Models\BaseModel;

class Review extends BaseModel
{
    protected $table = 'reviews';
    
    // Get all reviews for a product
    public function getByProduct($productId, $limit = 10, $offset = 0)
    {
        $sql = "
            SELECT r.*,u.name as user_name,u.email as user_email
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = {$productId}
            ORDER BY r.created_at DESC
            LIMIT {$limit} OFFSET {$offset}
        ";
        
        return $this->fetchAll($sql);
    }
    
    //Get single review by ID with user details
    public function getWithUser($reviewId)
    {
        $sql = "
            SELECT  r.*, u.email as user_email, u.name as user_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.id = {$reviewId}
        ";
        
        return $this->fetchOne($sql);
    }
    
    
    //Get reviews by user 
    public function getByUser($userId)
    {
        $sql = "
            SELECT  r.*, p.name as product_name, p.main_image as product_image
            FROM {$this->table} r
            LEFT JOIN products p ON r.product_id = p.id
            WHERE r.user_id = {$userId}
            ORDER BY r.created_at DESC
        ";
        
        return $this->fetchAll($sql);
    }
    
    
    //Check if user already reviewed this product
    public function hasUserReviewed($userId, $productId)
    {
        return $this->where('user_id', '=', $userId)->where('product_id', '=', $productId)->first() !== null;
    }
    
    //Get user's review for a product
    public function getUserReview($userId, $productId)
    {
        return $this->where('user_id', '=', $userId)
                    ->where('product_id', '=', $productId)
                    ->first();
    }
    
    
    //Check if user purchased this product (for verified purchase badge)
    //V1: Simple check - did user have an order with this product?
    //V2: Will check if order was delivered/completed
    public function isVerifiedPurchase($userId, $productId)
    {
        $sql = "
            SELECT COUNT(*) as purchase_count
            FROM order_items oi
            LEFT JOIN orders o ON oi.order_id = o.id
            WHERE o.user_id = {$userId}
            AND oi.product_id = {$productId}
            AND o.status != 'cancelled'
        ";
        
        $result = $this->fetchOne($sql);
        return $result && $result['purchase_count'] > 0;
    }
    
    
    //Create new review and update product rating
    public function createReview($data)
    {
        // Check if user already reviewed
        if ($this->hasUserReviewed($data['user_id'], $data['product_id'])) {
            error_log("User {$data['user_id']} already reviewed product {$data['product_id']}");
            return false;
        }
        
        // Check if verified purchase
        $data['is_verified_purchase'] = $this->isVerifiedPurchase(
            $data['user_id'], 
            $data['product_id']
        ) ? 1 : 0;
        
        // Validate rating (1.0 to 5.0)
        if ($data['rating'] < 1.0 || $data['rating'] > 5.0) {
            error_log("Invalid rating: {$data['rating']}");
            return false;
        }
        
        // Create review
        $reviewId = $this->create($data);
        
        if ($reviewId) {
            // Update product rating (median calculation)
            $this->updateProductRating($data['product_id']);
            
            if (APP_ENV === 'development') {
                error_log("Review created: ID={$reviewId}, Product={$data['product_id']}, Rating={$data['rating']}");
            }
        }
        
        return $reviewId;
    }
    
    
    //Update existing review and recalculate product rating
    public function updateReview($reviewId, $data)
    {
        // Get existing review to know which product to update
        $existingReview = $this->find($reviewId);
        
        if (!$existingReview) {
            return false;
        }
        
        // Validate rating if provided
        if (isset($data['rating'])) {
            if ($data['rating'] < 1.0 || $data['rating'] > 5.0) {
                error_log("Invalid rating: {$data['rating']}");
                return false;
            }
        }
        
        // Update review
        $success = $this->update($reviewId, $data);
        
        if ($success) {
            // Recalculate product rating
            $this->updateProductRating($existingReview['product_id']);
            
            if (APP_ENV === 'development') {
                error_log("Review updated: ID={$reviewId}");
            }
        }
        
        return $success;
    }
    
    
    //Delete review and recalculate product rating
    public function deleteReview($reviewId)
    {
        // Get review to know which product to update
        $review = $this->find($reviewId);
        
        if (!$review) {
            return false;
        }
        
        // Delete review
        $success = $this->delete($reviewId);
        
        if ($success) {
            // Recalculate product rating
            $this->updateProductRating($review['product_id']);
            
            if (APP_ENV === 'development') {
                error_log("Review deleted: ID={$reviewId}");
            }
        }
        
        return $success;
    }
    

    //Calculate and update product rating using MEDIAN 
    public function updateProductRating($productId)
    {
        // Get all ratings for this product (sorted)
        $sql = "
            SELECT rating 
            FROM {$this->table}
            WHERE product_id = {$productId}
            ORDER BY rating ASC
        ";
        
        $reviews = $this->fetchAll($sql);
        
        if (empty($reviews)) {
            // No reviews - set rating to 0
            $this->updateProductRatingValue($productId, 0.00);
            return true;
        }
        
        // Extract ratings into array
        $ratings = array_column($reviews, 'rating');
        $count = count($ratings);
        $sum = array_sum($ratings);

        
        // Calculate average
        $average = round($sum / $count, 2);
        
        // Update product rating
        $this->updateProductRatingValue($productId, $average);
        
        if (APP_ENV === 'development') {
            error_log("Product {$productId} rating updated to {$average} (from {$count} reviews)");
        }
        
        return true;
    }
    
    
    //Update product rating value in database
    private function updateProductRatingValue($productId, $rating)
    {
        $sql = "UPDATE products SET rating = {$rating} WHERE id = {$productId}";
        return $this->connection->query($sql);
    }
    
    
    //Get rating statistics for a product
    public function getRatingStats($productId)
    {
        $sql = "
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
            WHERE product_id = {$productId}
        ";
        
        $stats = $this->fetchOne($sql);
        
        if (!$stats || $stats['total_reviews'] == 0) {
            return [
                'total_reviews' => 0,
                'average_rating' => 0.00,
                'distribution' => [
                    '5_star' => 0,
                    '4_star' => 0,
                    '3_star' => 0,
                    '2_star' => 0,
                    '1_star' => 0
                ]
            ];
        }
        
        // Get median (current product rating)
        $productSql = "SELECT rating FROM products WHERE id = {$productId}";
        $product = $this->fetchOne($productSql);
        
        return [
            'total_reviews' => (int)$stats['total_reviews'],
            'average_rating' => (float)($product['rating'] ?? 0),
            'min_rating' => (float)$stats['min_rating'],
            'max_rating' => (float)$stats['max_rating'],
            'distribution' => [
                '5_star' => (int)$stats['five_star'],
                '4_star' => (int)$stats['four_star'],
                '3_star' => (int)$stats['three_star'],
                '2_star' => (int)$stats['two_star'],
                '1_star' => (int)$stats['one_star']
            ]
        ];
    }
    
    
    //Mark review as helpful
    public function incrementHelpful($reviewId)
    {
        if ($this->connectionType === 'mysqli') {
            $sql = "UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = {$reviewId}";
            return $this->connection->query($sql);
        } else {
            $sql = "UPDATE {$this->table} SET helpful_count = helpful_count + 1 WHERE id = :id";
            try {
                $stmt = $this->connection->prepare($sql);
                return $stmt->execute(['id' => $reviewId]);
            } catch (\PDOException $e) {
                error_log("Failed to increment helpful count: " . $e->getMessage());
                return false;
            }
        }
    }
    
    
    //Get top helpful reviews for a product
    public function getTopHelpfulReviews($productId, $limit = 5)
    {
        $sql = "
            SELECT 
                r.*,
                u.name as user_name
            FROM {$this->table} r
            LEFT JOIN users u ON r.user_id = u.id
            WHERE r.product_id = {$productId}
            AND r.helpful_count > 0
            ORDER BY r.helpful_count DESC, r.created_at DESC
            LIMIT {$limit}
        ";
        
        return $this->fetchAll($sql);
    }
    
    
    //Count reviews by product
    public function countByProduct($productId)
    {
        return $this->where('product_id', '=', $productId)->count();
    }
}