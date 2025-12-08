<?php
//manages user profile and account operations
namespace Controllers\V1;

use Controllers\BaseController;
use Models\V1\User;
use Models\V1\Order;


class UserController extends BaseController
{
    private $userModel;
    private $orderModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
        $this->orderModel = new Order();
    }


    //get user profile: get /api/V1/user/{id}
    public function show($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership (users can only view their own profile, admins can view all)
        $this->checkOwnership($id, 'You cannot view this profile');
        
        // Get user profile
        $user = $this->userModel->getProfile($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        return $this->json([
            'user' => $user
        ]);
    }

    //show all users for admin
    public function showAll()
    {
        $this->requireAdmin();

        $users = $this->userModel->getAllUsers();
        
        
        return $this->json([
            'users' => $users
        ]);

    }


    //update user profile: put /api/V1/user/{id}
    /*
     * Request Body:
     * {
     *     "name": "Ahmed Mohamed Updated",
     *     "phone": "01099999999",
     *     "address": "New address"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Profile updated successfully",
     *     "data": {
     *         "user": {...}
     *     }
     * }
    */
    public function update($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot update this profile');
        
        // Get update data
        $data = $this->getAllInput();
        
        if (empty($data)) {
            return $this->error('No data provided', 400);
        }
        
        // Update profile (password, role, is_active are automatically excluded)
        $success = $this->userModel->updateProfile($id, $data);
        
        if (!$success) {
            return $this->error('Failed to update profile', 500);
        }
        
        // Get updated profile
        $user = $this->userModel->getProfile($id);
        
        // Log action
        $this->log('profile_updated', ['user_id' => $id]);
        
        return $this->json([
            'message' => 'Profile updated successfully',
            'user' => $user
        ]);
    }


    //delete user account: delete /api/V1/users/{id}
    public function delete($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot delete this account');
        
        // Delete user
        $success = $this->userModel->delete($id);
        
        if (!$success) {
            return $this->error('Failed to delete account', 500);
        }
        
        // If user deleted their own account, destroy session
        if ($this->getUserId() == $id) {
            session_destroy();
        }
        
        // Log action
        $this->log('account_deleted', ['user_id' => $id]);
        
        return $this->json([
            'message' => 'Account deleted successfully'
        ]);
    }



    //change password: put /api/V1/users/{id}/password
    /*
     * Request Body:
     * {
     *     "old_password": "pass123",
     *     "new_password": "newpass456"
     * }
     * 
     * Response:
     * {
     *     "success": true,
     *     "message": "Password changed successfully"
     * }
    */
    public function changePassword($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot change this password');
        
        // Get passwords
        $oldPassword = $this->getInput('old_password');
        $newPassword = $this->getInput('new_password');
        
        // Validate input
        if (empty($oldPassword) || empty($newPassword)) {
            return $this->error('Old password and new password are required', 400);
        }
        
        // V1: Weak validation (no complexity requirements)
        if (strlen($newPassword) < 4) {
            return $this->error('New password must be at least 4 characters', 400);
        }
        
        // Get user to verify old password
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        // V1: Verify old password
        if (!$this->userModel->verifyPassword($oldPassword, $user['password'])) {
            return $this->error('Old password is incorrect', 400);
        }
        
        // Change password
        $success = $this->userModel->changePassword($id, $oldPassword, $newPassword);
        
        if (!$success) {
            return $this->error('Failed to change password', 500);
        }
        
        // Log action
        $this->log('password_changed', ['user_id' => $id]);
        
        return $this->json([
            'message' => 'Password changed successfully'
        ]);
    }


    //get user order history: get /api/V1/users/{id}/orders
    public function orders($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot view these orders');
        
        // Get pagination
        $pagination = $this->getPagination(10);
        
        // Get orders
        $orders = $this->userModel->getOrders($id, $pagination['perPage'], $pagination['offset']);
        
        // Get total count
        $total = $this->userModel->countOrders($id);
        
        // Calculate pagination
        $totalPages = ceil($total / $pagination['perPage']);
        
        return $this->json([
            'orders' => $orders,
            'total_orders' => $total,
            'pagination' => [
                'total' => $total,
                'perPage' => $pagination['perPage'],
                'page' => $pagination['page'],
                'totalPages' => $totalPages
            ]
        ]);
    }


    
    //-----------------------------------------------------------------
    // address management (for V2: will have separate addresses table)
    //-----------------------------------------------------------------

    //get user addresses: get /api/V1/users/{id}/addresses
    public function addresses($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot view these addresses');
        
        // Get user
        $user = $this->userModel->find($id);
        
        if (!$user) {
            return $this->error('User not found', 404);
        }
        
        // V1: Return address from profile
        // V2 TODO: Separate addresses table with multiple addresses
        return $this->json([
            'addresses' => [
                [
                    'id' => 1,
                    'type' => 'default',
                    'address' => $user['address'],
                    'is_default' => true
                ]
            ]
        ]);
    }



    //add new address : post /api/V1/users/{id}/addresses
    //in V1 will updates user address
    //in v2 will add to separate addresses table
    public function addAddress($id)
    {
        // Require authentication
        $this->requireAuth();
        
        // Validate ID
        if (!$id || !is_numeric($id)) {
            return $this->error('Invalid user ID', 400);
        }
        
        // Check ownership
        $this->checkOwnership($id, 'You cannot add address to this account');
        
        // Get address
        $address = $this->getInput('address');
        
        if (empty($address)) {
            return $this->error('Address is required', 400);
        }
        
        // V1: Update user's address field
        $success = $this->userModel->update($id, ['address' => $address]);
        
        if (!$success) {
            return $this->error('Failed to add address', 500);
        }
        
        // Log action
        $this->log('address_added', ['user_id' => $id]);
        
        return $this->json([
            'message' => 'Address added successfully',
            'address' => [
                'id' => 1,
                'type' => 'default',
                'address' => $address,
                'is_default' => true
            ]
        ]);
    }

}

