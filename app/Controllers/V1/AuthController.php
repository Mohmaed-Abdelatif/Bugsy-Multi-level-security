<?php
// AuthController - Handles user authentication
// V1: Session-based auth with weak security (intentionally vulnerable)
// V2: JWT tokens with strong validation
// V3: JWT + 2FA + biometric support

namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\User;

class AuthController extends BaseController
{
    private $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    //---------------------------------------
    // Registration
    //---------------------------------------
    /**
     * Register new user
     * POST /api/v1/register
     * 
     * Request Body:
     * {
     *     "name": "John Doe",
     *     "email": "john@example.com",
     *     "password": "password123",
     *     "phone": "01012345678",
     *     "address": "123 Main St, Cairo"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Registration successful",
     *     "data": {
     *         "user": {...},
     *         "session_id": "..."
     *     }
     * }
    */
    public function register()
    {
        // Get input data
        $name = $this->getInput('name');
        $email = $this->getInput('email');
        $password = $this->getInput('password');
        $phone = $this->getInput('phone');
        $address = $this->getInput('address');

        // V1: Basic validation (weak)
        if (empty($name) || empty($email) || empty($password)) {
            return $this->error('Name, email, and password are required', 400);
        }

        // V1: Weak email validation (just checks @ symbol)
        if (!strpos($email, '@')) {
            return $this->error('Invalid email format', 400);
        }

        // V1: Weak password validation (no minimum length, no complexity)
        if (strlen($password) < 4) {
            return $this->error('Password must be at least 4 characters', 400);
        }

        // Check if email already exists
        if ($this->userModel->emailExists($email)) {
            return $this->error('Email already registered', 409);
        }

        // Register user
        $userId = $this->userModel->register([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'address' => $address,
            'role' => 'customer' // Default role
        ]);

        if (!$userId) {
            return $this->error('Registration failed', 500);
        }

        // Get user data (without password)
        $user = $this->userModel->getProfile($userId);

        // V1: Auto-login after registration (create session)
        $this->createSession($user);

        // Log action
        $this->log('user_registered', ['user_id' => $userId, 'email' => $email]);

        // Return success
        return $this->json([
            'message' => 'Registration successful',
            'user' => $user,
            'session_id' => session_id()
        ], null, 201);
    }


    //make admin add new admin: POST /api/v1/admin/add
    public function addAdmin()
    {
        $this->requireAdmin();

        // Get input data
        $name = $this->getInput('name');
        $email = $this->getInput('email');
        $password = $this->getInput('password');
        $phone = $this->getInput('phone');
        $address = $this->getInput('address');

        // V1: Basic validation (weak)
        if (empty($name) || empty($email) || empty($password)) {
            return $this->error('Name, email, and password are required', 400);
        }

        // V1: Weak email validation (just checks @ symbol)
        if (!strpos($email, '@')) {
            return $this->error('Invalid email format', 400);
        }

        // V1: Weak password validation (no minimum length, no complexity)
        if (strlen($password) < 4) {
            return $this->error('Password must be at least 4 characters', 400);
        }

        // Check if email already exists
        if ($this->userModel->emailExists($email)) {
            return $this->error('Email already registered', 409);
        }

        // Register user
        $userId = $this->userModel->register([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'phone' => $phone,
            'address' => $address,
            'role' => 'admin' // Default role
        ]);

        if (!$userId) {
            return $this->error('Registration failed', 500);
        }

        // Get user data (without password)
        $user = $this->userModel->getProfile($userId);

        // Return success
        return $this->json([
            'message' => 'new admin added successfuly',
            'user' => $user,
        ], null, 201);
    }



    //---------------------------------------
    // Login
    //---------------------------------------
    /**
     * User login
     * POST /api/v1/login
     * 
     * Request Body:
     * {
     *     "email": "john@example.com",
     *     "password": "password123"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Login successful",
     *     "data": {
     *         "user": {...},
     *         "session_id": "..."
     *     }
     * }
    */
     public function login()
    {
        // Get credentials
        $email = $this->getInput('email');
        $password = $this->getInput('password');

        // Validate input
        if (empty($email) || empty($password)) {
            return $this->error('Email and password are required', 400);
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // V1: Generic error message (information disclosure vulnerability)
            return $this->error('Invalid email', 401);
        }

        // V2/V3 TODO: Check if account is locked after failed attempts

        // Verify password
        if (!$this->userModel->verifyPassword($password, $user['password'])) {
            // V1: No rate limiting, no lockout after failed attempts
            $this->log('login_failed', ['email' => $email]);
            return $this->error('rong password', 401);
        }

        // Check if user is active
        if (!$user['is_active']) {
            return $this->error('Account is deactivated', 403);
        }

        // Remove password from user data
        unset($user['password']);

        // V1: Create session (vulnerable - no IP check, no expiration)
        $this->createSession($user);

        // Log successful login
        $this->log('login_success', ['user_id' => $user['id'], 'email' => $email]);

        // Return success
        return $this->json([
            'message' => 'Login successful',
            'user' => $user,
            'session_id' => session_id()
        ]);
    }




    
    //---------------------------------------
    // Logout
    //---------------------------------------
    /**
     * User logout
     * POST /api/v1/logout
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Logout successful"
     * }
    */
    public function logout()
    {
        // Log action before destroying session
        if (isset($_SESSION['user_id'])) {
            $this->log('user_logout', ['user_id' => $_SESSION['user_id']]);
        }

        // Destroy session
        $this->destroySession();

        // Return success
        return $this->json([
            'message' => 'Logout successful'
        ]);
    }




    //--------------------------------------------------------------
    //forget password (v1 just update password with exist email)
    //-------------------------------------------------------------
    
    //post /api/v1/password/forgot
    /*
     * Request Body:
     * {
     *     "email": "user@example.com"
     * }
     * 
    */
    public function forgotPassword()
    {
        $email = $this->getInput('email');

        if (empty($email)) {
            return $this->error('Email is required', 400);
        }

        if (!strpos($email, '@')) {
            return $this->error('Invalid email format', 400);
        }

        // Find user by email
        $user = $this->userModel->findByEmail($email);

        // v1 vulnarable
        // tells attacker if email exists in system
        if (!$user) {
            return $this->error('Email not found in our system', 404);
        }

        // This allows anyone to reset any password with just an email
        $this->log('password_reset_requested', [
            'email' => $email,
            'user_id' => $user['id']
        ]);

        return $this->json([
            "success"=> true,
            'message' => 'Password reset information retrieved',
            'email' => $email,
            'user_id' => $user['id'],  
            'note' => 'v1: use this user_id with new password in /password/reset endpoint'
        ]);
    }


    //reset password: post /api/v1/password/reset
    /*
     * Request Body:
     * {
     *     "email": "user@example.com",
     *     "new_password": "newpass123"
     * }
     * 
     * OR (even worse - using user_id directly):
     * {
     *     "user_id": 5,
     *     "new_password": "newpass123"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Password reset successfully"
     * }
    */
    public function resetPassword()
    {
        $email = $this->getInput('email');
        $userId = $this->getInput('user_id');
        $newPassword = $this->getInput('new_password');

        if (empty($newPassword)) {
            return $this->error('New password is required', 400);
        }

        if (strlen($newPassword) < 4) {
            return $this->error('Password must be at least 4 characters', 400);
        }

        $user = null;

        if ($userId) {
            $user = $this->userModel->find($userId);
        } elseif ($email) {
            $user = $this->userModel->findByEmail($email);
        } else {
            return $this->error('Email or user_id is required', 400);
        }

        if (!$user) {
            // V1 VULNERABILITY: Information disclosure
            return $this->error('User not found', 404);
        }

        // Reset password directly (NO SECURITY CHECKS!)
        $success = $this->userModel->resetPasswordDirect($user['id'], $newPassword);

        if (!$success) {
            return $this->error('Failed to reset password', 500);
        }

        // V1: Destroy all sessions (basic security measure)
        // In V2/V3, we would invalidate all user sessions from database
        if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user['id']) {
            $this->destroySession();
        }

        // Log action
        $this->log('password_reset_completed', [
            'user_id' => $user['id'],
            'email' => $user['email'],
            'method' => $userId ? 'user_id' : 'email'
        ]);

        return $this->json([
            'message' => 'Password reset successfully',
            'note' => 'Please login with your new password'
        ]);
    }







    //---------------------------------------
    // Session Management
    //---------------------------------------
    /*
     V1: No session expiration (session lives forever)
     V2 TODO: Add session expiration
     $_SESSION['expires_at'] = time() + (60 * 60 * 2); // 2 hour
     V1: No IP binding (session can be hijacked)
     V2 TODO: Bind session to IP address
     $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR']
     V2/V3 TODO: Store session in database for tracking
     This will help with:
      Multi-device logout
      Session revocation
      Security audit logs
    */

    //Create user session
    // V1: Basic session (no expiration, no IP binding)
    // V2/V3: Will add session expiration, IP binding, refresh tokens
    private function createSession($user)
    {
        // V1: Store user data in session (vulnerable - no encryption)
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();


        if (APP_ENV === 'development') {
            error_log("Session created for user: {$user['id']} ({$user['email']})");
        }
    }


    //destroy user session
    private function destroySession()
    {
        // Clear session variables
        $_SESSION = [];

        // Destroy session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }

        // Destroy session
        session_destroy();

        if (APP_ENV === 'development') {
            error_log("Session destroyed");
        }
    }


    //---------------------------------------
    // Session Validation (helper - not used can delete -but keep it maby use it)
    //---------------------------------------

    // Check if user is logged in
    // Used by BaseController::requireAuth()
    public static function isLoggedIn()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // V1: simple check (no expiration, no IP validation)
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    // Get current user from session
    // Used by BaseController::getUser()
    public static function getCurrentUser()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!self::isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }
    //check if current user is admin (noo need exist in basecontroller)
    // public static function isAdmin()
    // {
    //     $user = self::getCurrentUser();
    //     return $user && $user['role'] === 'admin';
    // }
    //get current user ID
    public static function getCurrentUserId()
    {
        $user = self::getCurrentUser();
        return $user ? $user['id'] : null;
    }

  




}
