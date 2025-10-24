<?php
// TestController - Simple endpoints for testing authentication
// This controller is ONLY for testing V1 authentication


namespace Controllers\V1;

use Controllers\BaseController;

class TestController extends BaseController
{
    //---------------------------------------
    // Public Endpoint (No Auth Required)
    //---------------------------------------
    
    /**
     * Public test endpoint
     * GET /api/v1/test/public
     * 
     * No authentication required
     */
    public function publicTest()
    {
        return $this->json([
            'message' => 'This is a public endpoint',
            'auth_required' => false,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    //---------------------------------------
    // Protected Endpoint (Auth Required)
    //---------------------------------------
    
    /**
     * Protected test endpoint
     * GET /api/v1/test/protected
     * 
     * Requires authentication
     */
    public function protectedTest()
    {
        // This will fail if user is not logged in
        $this->requireAuth();
        
        // If we reach here, user is authenticated
        $user = $this->getUser();
        
        return $this->json([
            'message' => 'You are authenticated!',
            'auth_required' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    //---------------------------------------
    // Admin Only Endpoint
    //---------------------------------------
    
    /**
     * Admin test endpoint
     * GET /api/v1/test/admin
     * 
     * Requires admin role
     */
    public function adminTest()
    {
        // This will fail if user is not admin
        $this->requireAdmin();
        
        // If we reach here, user is admin
        $user = $this->getUser();
        
        return $this->json([
            'message' => 'You are an admin!',
            'admin_required' => true,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    //---------------------------------------
    // Session Info (Debug)
    //---------------------------------------
    
    /**
     * Get current session info
     * GET /api/v1/test/session
     * 
     * Shows current session data (for debugging only)
     */
    public function sessionInfo()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return $this->json([
            'message' => 'Current session info',
            'session_id' => session_id(),
            'logged_in' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : false,
            'session_data' => [
                'user_id' => $_SESSION['user_id'] ?? null,
                'user_email' => $_SESSION['user_email'] ?? null,
                'user_name' => $_SESSION['user_name'] ?? null,
                'user_role' => $_SESSION['user_role'] ?? null,
                'login_time' => $_SESSION['login_time'] ?? null,
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    //---------------------------------------
    // Ownership Test
    //---------------------------------------
    
    /**
     * Test ownership check
     * GET /api/v1/test/ownership/{user_id}
     * 
     * Tests if current user can access another user's resource
     */
    public function ownershipTest($userId)
    {
        // Require authentication first
        $this->requireAuth();
        
        // This will fail if user tries to access another user's resource
        // Admin will always pass
        $this->checkOwnership($userId, "You cannot access user {$userId}'s data");
        
        // If we reach here, user owns the resource or is admin
        $currentUser = $this->getUser();
        
        return $this->json([
            'message' => 'Ownership check passed',
            'current_user_id' => $currentUser['id'],
            'requested_user_id' => $userId,
            'is_admin' => $this->isAdmin(),
            'access_granted' => true
        ]);
    }
}