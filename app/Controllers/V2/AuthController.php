<?php
//secure authentication, JWT tokens, bcrypt password hashing, tokien blocklist
//differnt from V1:
//register()       → bcrypt hash, strong validation, no auto-login session
//login()          → returns JWT token (not session_id)
//logout()         → blacklists JWT in sessions table
//forgotPassword() → no longer reveals if email exists (V1 did)
//resetPassword()  → requires a reset token (not just user_id)
//addAdmin()       → requires JWT admin token (not just session)


namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\User;
use Core\JWTHandler;

class AuthController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }



    //register
    //POST /api/v2/register
    public function register(): void
    {
        $name     = trim($this->getInput('name',     ''));
        $email    = trim($this->getInput('email',    ''));
        $password = trim($this->getInput('password', ''));
        $phone    = trim($this->getInput('phone',    ''));
        $address  = trim($this->getInput('address',  ''));

        // V2: strong validation
        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one letter and one number';
        }

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        // Check email uniqueness
        if ($this->userModel->emailExists($email)) {
            $this->error('Email already registered', 409);
            return;
        }

        // Register
        $userId = $this->userModel->register([
            'name'    => $name,
            'email'   => $email,
            'password'=> $password,
            'phone'   => $phone,
            'address' => $address,
            'role'    => 'customer'
        ]);

        if (!$userId) {
            $this->error('Registration failed. Please try again.', 500);
            return;
        }

        $user = $this->userModel->getProfile($userId);

        // V2: no auto-login session — user must login to get a token
        $this->log('user_registered_v2', ['user_id' => $userId, 'email' => $email]);

        $this->json([
            'message' => 'Registration successful. Please login.',
            'user'    => $user
        ], null, 201);
    }


    //admin add new admin
    //POST /api/v2/admin/add
    public function addAdmin(): void
    {
        // requireAdmin() checks JWT from (requireAdmin() + role from isAdmin() = admin)
        $this->requireAdmin();

        $name     = trim($this->getInput('name',     ''));
        $email    = trim($this->getInput('email',    ''));
        $password = trim($this->getInput('password', ''));
        $phone    = trim($this->getInput('phone',    ''));
        $address  = trim($this->getInput('address',  ''));

        $errors = [];

        if (empty($name)) {
            $errors['name'] = 'Name is required';
        }

        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address';
        }

        if (empty($password)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
            $errors['password'] = 'Password must contain at least one letter and one number';
        }

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        if ($this->userModel->emailExists($email)) {
            $this->error('Email already registered', 409);
            return;
        }

        $userId = $this->userModel->register([
            'name'    => $name,
            'email'   => $email,
            'password'=> $password,
            'phone'   => $phone,
            'address' => $address,
            'role'    => 'admin'
        ]);

        if (!$userId) {
            $this->error('Failed to create admin', 500);
            return;
        }

        $user = $this->userModel->getProfile($userId);

        $this->log('admin_added_v2', ['new_admin_id' => $userId, 'by' => $this->getUserId()]);

        $this->json([
            'message' => 'Admin added successfully',
            'user'    => $user
        ], null, 201);
    }





    //Login
    //POST /api/v2/login
    public function login(): void
    {
        $email    = trim($this->getInput('email',    ''));
        $password = trim($this->getInput('password', ''));

        // Validate input
        $errors = [];
        if (empty($email))    $errors['email']    = 'Email is required';
        if (empty($password)) $errors['password'] = 'Password is required';

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        //findByCredentials fetches by email then verifies bcrypt in PHP
        $user = $this->userModel->findByCredentials($email, $password);

        if (!$user) {
            $this->error('Invalid credentials', 401);
            return;
        }

        if (!$user['is_active']) {
            $this->error('Account is deactivated. Contact support.', 403);
            return;
        }

        // Remove password before generating token and returning response
        unset($user['password']);

        // Generate JWT token
        $token = JWTHandler::generate([
            'user_id' => (int)$user['id'],
            'email'   => $user['email'],
            'name'    => $user['name'],
            'role'    => $user['role']
        ]);

        $this->log('login_success_v2', ['user_id' => $user['id']]);

        $this->json([
            'message'    => 'Login successful',
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) JWT_EXPIRY,
            'user'       => $user
        ]);
    }


    //login for admin
    //POST /api/v2/adminlogin
    public function adminLogin(): void
    {
        $email    = trim($this->getInput('email',    ''));
        $password = trim($this->getInput('password', ''));

        if (empty($email) || empty($password)) {
            $this->error('Email and password are required', 400);
            return;
        }

        $admin = $this->userModel->findByAdminEmail($email);

        if (!$admin || !$this->userModel->verifyPassword($password, $admin['password'])) {
            // V2 same message for both cases — no information disclosure
            $this->error('Invalid credentials', 401);
            return;
        }

        if (!$admin['is_active']) {
            $this->error('Account is deactivated', 403);
            return;
        }

        unset($admin['password']);

        $token = JWTHandler::generate([
            'user_id' => (int)$admin['id'],
            'email'   => $admin['email'],
            'name'    => $admin['name'],
            'role'    => $admin['role']
        ]);

        $this->log('admin_login_success_v2', ['user_id' => $admin['id']]);

        $this->json([
            'message'    => 'Admin login successful',
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => (int) JWT_EXPIRY,
            'user'       => $admin
        ]);
    }




    //logout
    //POST /api/v2/logout
    public function logout(): void
    {
        // AuthMiddleware already verified the token so:
        // $_REQUEST['jwt_token'] was stored by AuthMiddleware
        $token = $_REQUEST['jwt_token'] ?? null;

        if (!$token) {
            $this->error('No token to invalidate', 400);
            return;
        }

        // Blacklist the token — stored as SHA256 hash in sessions table
        $blacklisted = JWTHandler::blacklist($token);

        if (!$blacklisted) {
            $this->error('Logout failed. Please try again.', 500);
            return;
        }

        $userId = $this->getUserId();
        $this->log('logout_v2', ['user_id' => $userId]);

        $this->json([
            'message' => 'Logged out successfully. Token has been invalidated.'
        ]);
    }



    //forget password
    //POST /api/v2/password/forgot
    public function forgotPassword(): void
    {
        $email = trim($this->getInput('email', ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Valid email is required', 400);
            return;
        }

        // V2: always return same message , no information
        // V1 returned "Email not found" which told attackers which emails exist
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            // In a real V2 system: generate a reset token, store it, send email,but till now no gmail massage lenked
            // For Bugsy V2: we log the request and return a safe message
            // V3 will add actual email delivery
            $this->log('password_reset_requested_v2', [
                'email'   => $email,
                'user_id' => $user['id']
            ]);
        }

        // SAME response whether email exists or not — prevents email enumeration
        $this->json([
            'message' => 'If that email exists in our system, a reset link has been sent.',
            'note'    => 'V2: use POST /api/v2/password/reset with email + new_password'
        ]);
    }


    //reset password
    //POST /api/v2/password/reset
    public function resetPassword(): void
    {
        $email       = trim($this->getInput('email',        ''));
        $newPassword = trim($this->getInput('new_password', ''));

        $errors = [];
       
        if (empty($email)) {
            $errors['email'] = 'Email is required';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Email must be a valid email address';
        }

        if (empty($newPassword)) {
            $errors['password'] = 'Password is required';
        } elseif (strlen($newPassword) < 8) {
            $errors['password'] = 'Password must be at least 8 characters';
        } elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors['password'] = 'Password must contain at least one letter and one number';
        }

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        $user = $this->userModel->findByEmail($email);

        // V2: same message whether user found or not — no information disclosure
        if (!$user) {
            $this->json(['message' => 'If that email exists, the password has been reset.']);
            return;
        }

        $success = $this->userModel->resetPasswordDirect($user['id'], $newPassword);

        if (!$success) {
            $this->error('Reset failed. Please try again.', 500);
            return;
        }

        $this->log('password_reset_v2', ['user_id' => $user['id']]);

        $this->json(['message' => 'Password reset successfully. Please login with your new password.']);
    }

    
    public function me(): void
    {
        $this->requireAuth();

        $userId = $this->getUserId();
        $user   = $this->userModel->getProfile($userId);

        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        $this->json(['user' => $user]);
    }

}



