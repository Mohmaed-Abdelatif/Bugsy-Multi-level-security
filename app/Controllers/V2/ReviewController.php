<?php
//getUserId() reads from JWT — cannot be faked
//markHelpful() user_id comes from verified JWT, not fakeable session
//Validator used for input checking
//Audit log written on create/update/delete

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\Review;
use Models\V2\Product;
use Helpers\Validator;

class ReviewController extends BaseController
{
    private Review  $reviewModel;
    private Product $productModel;

    public function __construct()
    {
        parent::__construct();
        $this->reviewModel  = new Review();
        $this->productModel = new Product();
    }


    // GET /api/v2/products/{id}/reviews — public
    public function index(int $productId): void
    {
        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        $pagination = $this->getPagination(10);

        $reviews = $this->reviewModel->getByProduct(
            $productId,
            $pagination['perPage'],
            $pagination['offset']
        );

        $total = $this->reviewModel->countByProduct($productId);
        $stats = $this->reviewModel->getRatingStats($productId);

        $this->json([
            'reviews'    => $reviews,
            'pagination' => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ],
            'stats' => $stats
        ]);
    }


    // GET /api/v2/reviews/{id} — public
    public function show(int $reviewId): void
    {
        $review = $this->reviewModel->getWithUser($reviewId);

        if (!$review) {
            $this->error('Review not found', 404);
            return;
        }

        $this->json(['review' => $review]);
    }


    // GET /api/v2/products/{id}/rating — public
    public function rating(int $productId): void
    {
        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        $stats = $this->reviewModel->getRatingStats($productId);
        $stats['product_id'] = $productId;

        $this->json($stats);
    }


    // POST /api/v2/products/{id}/reviews — requires auth
    public function create(int $productId): void
    {
        $this->requireAuth();

        if (!$this->productModel->exists($productId)) {
            $this->error('Product not found', 404);
            return;
        }

        $userId = $this->getUserId(); //from verified JWT, cannot be faked

        $errors = Validator::make($this->getAllInput())
            ->required(['rating'])
            ->numeric('rating')
            ->max('title', 255)
            ->validate();

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        $rating = (float)$this->getInput('rating');

        if ($rating < 1.0 || $rating > 5.0) {
            $this->error('Validation failed', 422, ['rating' => 'Rating must be between 1.0 and 5.0']);
            return;
        }

        if ($this->reviewModel->hasUserReviewed($userId, $productId)) {
            $this->error('You have already reviewed this product', 409);
            return;
        }

        $reviewData = [
            'product_id' => $productId,
            'user_id'    => $userId,
            'rating'     => $rating,
            'title'      => trim($this->getInput('title', '')),
            'comment'    => trim($this->getInput('comment', '')),
        ];

        // createReview() handles verified-purchase check + rating recalculation
        $reviewId = $this->reviewModel->createReview($reviewData);

        if (!$reviewId) {
            $this->error('Failed to create review', 500);
            return;
        }

        $review = $this->reviewModel->getWithUser($reviewId);

        $this->log('review_created_v2', [
            'review_id'  => $reviewId,
            'product_id' => $productId,
            'user_id'    => $userId,
            'rating'     => $rating
        ]);

        $this->json([
            'message' => 'Review created successfully',
            'review'  => $review
        ], null, 201);
    }


    // PUT /api/v2/reviews/{id} — owner or admin only
    public function update(int $reviewId): void
    {
        $this->requireAuth();

        $existingReview = $this->reviewModel->find($reviewId);

        if (!$existingReview) {
            $this->error('Review not found', 404);
            return;
        }

        //V2:checkOwnership now actually enforced via JWT-verified user id
        $this->checkOwnership($existingReview['user_id'], 'You can only edit your own review');

        $data = $this->getAllInput();

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['rating'])) {
            $rating = (float)$data['rating'];
            if ($rating < 1.0 || $rating > 5.0) {
                $this->error('Validation failed', 422, ['rating' => 'Rating must be between 1.0 and 5.0']);
                return;
            }
        }

        $success = $this->reviewModel->updateReview($reviewId, $data);

        if (!$success) {
            $this->error('Failed to update review', 500);
            return;
        }

        $review = $this->reviewModel->getWithUser($reviewId);

        $this->log('review_updated_v2', [
            'review_id' => $reviewId,
            'user_id'   => $this->getUserId(),
            'changes'   => array_keys($data)
        ]);

        $this->json([
            'message' => 'Review updated successfully',
            'review'  => $review
        ]);
    }


    // DELETE /api/v2/reviews/{id} — owner or admin only
    public function delete(int $reviewId): void
    {
        $this->requireAuth();

        $review = $this->reviewModel->find($reviewId);

        if (!$review) {
            $this->error('Review not found', 404);
            return;
        }

        $this->checkOwnership($review['user_id'], 'You can only delete your own review');

        $success = $this->reviewModel->deleteReview($reviewId);

        if (!$success) {
            $this->error('Failed to delete review', 500);
            return;
        }

        $this->log('review_deleted_v2', [
            'review_id'  => $reviewId,
            'product_id' => $review['product_id'],
            'user_id'    => $this->getUserId()
        ]);

        $this->json(['message' => 'Review deleted successfully']);
    }


    // POST /api/v2/reviews/{id}/helpful — requires auth
    // V1 vulnerability: user_id could theoretically be spoofed if session was hijacked.
    // V2: user_id comes from cryptographically verified JWT — cannot be faked even with network access.
    public function markHelpful(int $reviewId): void
    {
        $this->requireAuth();

        if (!$this->reviewModel->exists($reviewId)) {
            $this->error('Review not found', 404);
            return;
        }

        $userId = $this->getUserId();

        $result = $this->reviewModel->toggleHelpful($reviewId, $userId);

        $message = $result['action'] === 'added'
            ? 'Review marked as helpful'
            : 'Review helpful vote removed';

        $this->json([
            'message'       => $message,
            'action'        => $result['action'],
            'voted'         => $result['voted'],
            'helpful_count' => $result['helpful_count']
        ]);
    }


    // GET /api/v2/users/{id}/reviews — owner or admin only
    public function userReviews(int $userId): void
    {
        $this->requireAuth();

        $this->checkOwnership($userId, 'You cannot view these reviews');

        $reviews = $this->reviewModel->getByUser($userId);

        $this->json([
            'reviews' => $reviews,
            'total'   => count($reviews)
        ]);
    }
}