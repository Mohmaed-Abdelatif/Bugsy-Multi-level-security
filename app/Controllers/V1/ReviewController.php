<?php
//Handles product reviews and ratings
namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\Review;
use Models\V1\Product;

class ReviewController extends BaseController
{
    private $reviewModel;
    private $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->reviewModel = new Review();
        $this->productModel = new Product();
    }
    
    //  Get all reviews for a product: get /api/V1/products/{productId}/reviews
    /**
     * Query params:
     * - page (default: 1)
     * - per_page (default: 10)
     * - sort (options: recent, helpful, rating_high, rating_low)
     * 
     * Response:
     * {
     *     "success": true,
     *     "data": {
     *         "reviews": [...],
     *         "pagination": {...},
     *         "stats": {...}
     *     }
     * }
    */
    public function index($productId)
    {
        // Validate product ID
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }
        
        // Check if product exists
        if (!$this->productModel->exists($productId)) {
            return $this->error('Product not found', 404);
        }
        
        // Get pagination
        $pagination = $this->getPagination(10);
        
        // Get sorting
        $sort = $this->getQuery('sort', 'recent');
        
        // Get reviews
        $reviews = $this->reviewModel->getByProduct(
            $productId,
            $pagination['perPage'],
            $pagination['offset']
        );
        
        // Get total count
        $total = $this->reviewModel->countByProduct($productId);
        
        // Get rating statistics
        $stats = $this->reviewModel->getRatingStats($productId);
        
        // Return response
        return $this->json([
            'reviews' => $reviews,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ],
            'stats' => $stats
        ]);
    }
    
    
    // get single review: get /api/V1/reviews/{reviewId}
    
    public function show($reviewId)
    {
        // Validate review ID
        if (!$reviewId || !is_numeric($reviewId)) {
            return $this->error('Invalid review ID', 400);
        }
        
        // Get review with user details
        $review = $this->reviewModel->getWithUser($reviewId);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        
        return $this->json(['review' => $review]);
    }
    

    //Get product rating summary: get /api/V1/products/{productId}/rating
    public function rating($productId)
    {
        // Validate product ID
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }
        
        // Check if product exists
        if (!$this->productModel->exists($productId)) {
            return $this->error('Product not found', 404);
        }
        
        // Get rating statistics
        $stats = $this->reviewModel->getRatingStats($productId);
        $stats['product_id'] = (int)$productId;
        
        return $this->json($stats);
    }
    
    //Create new review: post /api/V1/products/{productId}/reviews
     
    /**
     * Body:
     * {
     *     "rating": 4.5,          // 1.0 to 5.0
     *     "title": "Great phone!",
     *     "comment": "Very satisfied with this purchase..."
     * }
    */
    public function create($productId)
    {
        $this->requireAuth();
        
        // Validate product ID
        if (!$productId || !is_numeric($productId)) {
            return $this->error('Invalid product ID', 400);
        }
        
        // Check if product exists
        if (!$this->productModel->exists($productId)) {
            return $this->error('Product not found', 404);
        }
        
        // Get input data
        $data = $this->getAllInput();
        
        $userId = $this->getUserId();
        
        if (!$userId) {
            return $this->error('user_id is required', 400);
        }
        
        // Validate required fields
        if (!isset($data['rating'])) {
            return $this->error('Rating is required', 400);
        }
        
        // Validate rating range
        $rating = (float)$data['rating'];
        if ($rating < 1.0 || $rating > 5.0) {
            return $this->error('Rating must be between 1.0 and 5.0', 400);
        }
        
        // Check if user already reviewed this product
        if ($this->reviewModel->hasUserReviewed($userId, $productId)) {
            return $this->error('You have already reviewed this product', 409);
        }
        
        // Prepare review data
        $reviewData = [
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating,
            'title' => $this->getInput('title', ''),
            'comment' => $this->getInput('comment', '')
        ];
        
        // Create review (automatically updates product rating)
        $reviewId = $this->reviewModel->createReview($reviewData);
        
        if (!$reviewId) {
            return $this->error('Failed to create review', 500);
        }
        
        // Get created review
        $review = $this->reviewModel->getWithUser($reviewId);
        
        // Log action
        $this->log('review_created', [
            'review_id' => $reviewId,
            'product_id' => $productId,
            'user_id' => $userId,
            'rating' => $rating
        ]);
        
        return $this->json([
            'message' => 'Review created successfully',
            'review' => $review
        ], 'Review created successfully', 201);
    }
    
    // Update existing review: put /api/V1/reviews/{reviewId}
    /**
     * Body:
     * {
     *     "rating": 5.0,
     *     "title": "Updated title",
     *     "comment": "Updated comment"
     * }
     */
    public function update($reviewId)
    {
        $this->requireAuth();

        // Validate review ID
        if (!$reviewId || !is_numeric($reviewId)) {
            return $this->error('Invalid review ID', 400);
        }
        
        // Check if review exists
        $existingReview = $this->reviewModel->find($reviewId);
        
        if (!$existingReview) {
            return $this->error('Review not found', 404);
        }

        // Check ownership
        $this->checkOwnership($existingReview['user_id'], 'You cannot modify this cart');
        
        // Get update data
        $data = $this->getAllInput();
        
        if (empty($data)) {
            return $this->error('No data provided', 400);
        }
        
        // Validate rating if provided
        if (isset($data['rating'])) {
            $rating = (float)$data['rating'];
            if ($rating < 1.0 || $rating > 5.0) {
                return $this->error('Rating must be between 1.0 and 5.0', 400);
            }
        }
        
        // Update review (automatically recalculates product rating)
        $success = $this->reviewModel->updateReview($reviewId, $data);
        
        if (!$success) {
            return $this->error('Failed to update review', 500);
        }
        
        // Get updated review
        $review = $this->reviewModel->getWithUser($reviewId);
        
        // Log action
        $this->log('review_updated', [
            'review_id' => $reviewId,
            'changes' => array_keys($data)
        ]);
        
        return $this->json([
            'message' => 'Review updated successfully',
            'review' => $review
        ]);
    }
    
    
    // Delete review: delete /api/V1/reviews/{reviewId}
    public function delete($reviewId)
    {
        $this->requireAuth();

        // Validate review ID
        if (!$reviewId || !is_numeric($reviewId)) {
            return $this->error('Invalid review ID', 400);
        }
        
        // Check if review exists
        $review = $this->reviewModel->find($reviewId);
        
        if (!$review) {
            return $this->error('Review not found', 404);
        }
        
        // Check ownership
        $this->checkOwnership($review['user_id'], 'You cannot modify this cart');
        
        // Delete review (automatically recalculates product rating)
        $success = $this->reviewModel->deleteReview($reviewId);
        
        if (!$success) {
            return $this->error('Failed to delete review', 500);
        }
        
        // Log action
        $this->log('review_deleted', [
            'review_id' => $reviewId,
            'product_id' => $review['product_id']
        ]);
        
        return $this->json([
            'message' => 'Review deleted successfully'
        ]);
    }
    
    // Mark review as helpful: post /api/V1/reviews/{reviewId}/helpful
    // V1: Simple counter increment (no tracking who voted)
    // V2: Will use review_helpfulness table to track votes
    public function markHelpful($reviewId)
    {
        // Validate review ID
        if (!$reviewId || !is_numeric($reviewId)) {
            return $this->error('Invalid review ID', 400);
        }
        
        // Check if review exists
        if (!$this->reviewModel->exists($reviewId)) {
            return $this->error('Review not found', 404);
        }
        
        // Increment helpful count
        $success = $this->reviewModel->incrementHelpful($reviewId);
        
        if (!$success) {
            return $this->error('Failed to mark review as helpful', 500);
        }
        
        // Get updated review
        $review = $this->reviewModel->find($reviewId);
        
        return $this->json([
            'message' => 'Review marked as helpful',
            'helpful_count' => (int)$review['helpful_count']
        ]);
    }
    
    /**
     * Get user's reviews
     * GET /api/V1/users/{userId}/reviews
     * 
     * V1: VULNERABLE - Anyone can see any user's reviews
     */
    public function userReviews($userId)
    {
        $this->requireAuth();

        // Check ownership
        $this->checkOwnership($userId, 'You cannot modify this cart');
        
        // Validate user ID
        if (!$userId || !is_numeric($userId)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Get user's reviews
        $reviews = $this->reviewModel->getByUser($userId);

        
        return $this->json([
            'reviews' => $reviews,
            'total' => count($reviews)
        ]);
    }
}