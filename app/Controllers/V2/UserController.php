<?php
//differences from v1:
//-getUserId() reads from JWT token — not from session or request body
//-calls Models\V2\User (bcrypt + PDO)

namespace Controllers\V2;

use Controllers\BaseController;
use Models\V2\User;
use Helpers\V2\ImageUpload;

class UserController extends BaseController
{
    private User $userModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new User();
    }


    // get user profile
    // GET /api/v2/user/{id}
    public function show(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        // V2: enforced ownership — can only view own profile (admin can view all)
        $this->checkOwnership($id, 'You cannot view this profile');

        $user = $this->userModel->getProfile($id);

        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        if ($user['profile_photo']) {
            $user['profile_photo_url'] = ImageUpload::getUserPhotoUrl($user['profile_photo']);
        } else {
            $user['profile_photo_url'] = null;
        }

        $this->json(['user' => $user]);
    }


    //get all shop users (admin only)
    // GET /api/v2/users  
    public function showAll(): void
    {
        $this->requireAdmin();

        $users = $this->userModel->getAllUsers();

        $this->json([
            'users' => $users,
            'total' => count($users)
        ]);
    }


    //update user profile
    // PUT /api/v2/user/{id}
    public function update(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        $this->checkOwnership($id, 'You cannot update this profile');

        $data = $this->getAllInput();

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        // V2: validate fields if present
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Validation failed', 422, ['email' => 'Must be a valid email address']);
            return;
        }

        $success = $this->userModel->updateProfile($id, $data);

        if (!$success) {
            $this->error('Failed to update profile', 500);
            return;
        }

        $user = $this->userModel->getProfile($id);

        $this->log('profile_updated_v2', ['user_id' => $id]);

        $this->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }


    //change password
    // PUT /api/v2/users/{id}/password
    public function changePassword(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        $this->checkOwnership($id, 'You cannot change this password');

        $oldPassword = $this->getInput('old_password', '');
        $newPassword = $this->getInput('new_password', '');

        $errors = [];
        if (empty($oldPassword)) $errors['old_password'] = 'Old password is required';
        if (empty($newPassword)) $errors['new_password'] = 'New password is required';
        elseif (strlen($newPassword) < 8) $errors['new_password'] = 'Must be at least 8 characters';
        elseif (!preg_match('/[A-Za-z]/', $newPassword) || !preg_match('/[0-9]/', $newPassword)) {
            $errors['new_password'] = 'Must contain at least one letter and one number';
        }

        if (!empty($errors)) {
            $this->error('Validation failed', 422, $errors);
            return;
        }

        $success = $this->userModel->changePassword($id, $oldPassword, $newPassword);

        if (!$success) {
            // V2: same message for wrong old password vs DB error — no disclosure
            $this->error('Password change failed. Check your old password.', 400);
            return;
        }

        $this->log('password_changed_v2', ['user_id' => $id]);

        $this->json(['message' => 'Password changed successfully. Please login again with your new password.']);
    }


    //delete user account
    //DELETE /api/v2/users/{id}
    public function delete(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        $this->checkOwnership($id, 'You cannot delete this account');

        $success = $this->userModel->delete($id);

        if (!$success) {
            $this->error('Failed to delete account', 500);
            return;
        }

        $this->log('account_deleted_v2', ['user_id' => $id]);

        $this->json(['message' => 'Account deleted successfully']);
    }


    //get use orders history
    // GET /api/v2/users/{id}/orders
    public function orders(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        // V2: enforced — user can only see own orders
        $this->checkOwnership($id, 'You cannot view these orders');

        $pagination = $this->getPagination(10);
        $orders     = $this->userModel->getOrders($id, $pagination['perPage'], $pagination['offset']);
        $total      = $this->userModel->countOrders($id);

        $this->json([
            'orders'       => $orders,
            'total_orders' => $total,
            'pagination'   => [
                'total'      => $total,
                'perPage'    => $pagination['perPage'],
                'page'       => $pagination['page'],
                'totalPages' => ceil($total / $pagination['perPage'])
            ]
        ]);
    }


    // show user's addresses
    // GET /api/v2/users/{id}/addresses
    public function addresses(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        $this->checkOwnership($id, 'You cannot view these addresses');

        $user = $this->userModel->find($id);

        if (!$user) {
            $this->error('User not found', 404);
            return;
        }

        $this->json([
            'addresses' => [[
                'id'         => 1,
                'type'       => 'default',
                'address'    => $user['address'],
                'is_default' => true
            ]]
        ]);
    }


    //add user address
    //POST /api/v2/users/{id}/addresses
    public function addAddress(int $id): void
    {
        $this->requireAuth();

        if (!$id || !is_numeric($id)) {
            $this->error('Invalid user ID', 400);
            return;
        }

        $this->checkOwnership($id, 'You cannot add address to this account');

        $address = trim($this->getInput('address', ''));

        if (empty($address)) {
            $this->error('Validation failed', 422, ['address' => 'Address is required']);
            return;
        }

        $success = $this->userModel->update($id, ['address' => $address]);

        if (!$success) {
            $this->error('Failed to add address', 500);
            return;
        }

        $this->json([
            'message' => 'Address added successfully',
            'address' => ['id' => 1, 'type' => 'default', 'address' => $address, 'is_default' => true]
        ]);
    }


    //show current user data
    // GET /api/v2/me
    public function currentSessioninfo(): void
    {
        $this->requireAuth();

        $user = $this->userModel->getProfile($this->getUserId());

        $this->json([
            'message'      => 'Current authenticated user',
            'user'         => $user,
            'token_expiry' => JWT_EXPIRY . ' seconds'
        ]);
    }


    //updata current user data
    //PUT /api/v2/me
    public function updateCurrentinfo(): void
    {
        $this->requireAuth();

        $id   = $this->getUserId(); // from JWT — not from request body
        $data = $this->getAllInput();

        if (empty($data)) {
            $this->error('No data provided', 400);
            return;
        }

        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $this->error('Validation failed', 422, ['email' => 'Must be a valid email address']);
            return;
        }

        $success = $this->userModel->updateProfile($id, $data);

        if (!$success) {
            $this->error('Failed to update profile', 500);
            return;
        }

        $user = $this->userModel->getProfile($id);

        $this->json([
            'message' => 'Profile updated successfully',
            'user'    => $user
        ]);
    }


    
    //photo management — same as V1, just with JWT auth
    
    //upload or update use profile photo
    //post /api/v1/user/{id}/photo
    public function uploadPhoto(int $id): void
    {
        $this->requireAuth();
        $this->checkOwnership($id, 'You cannot update this profile photo');

        $user = $this->userModel->find($id);
        if (!$user) { $this->error('User not found', 404); return; }

        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        $isMultipart = str_contains($contentType, 'multipart/form-data');

        if ($isMultipart) {
            if (!isset($_FILES['photo']) || $_FILES['photo']['error'] === UPLOAD_ERR_NO_FILE) {
                $this->error('No photo file provided', 400); return;
            }
            $result = ImageUpload::uploadUserPhoto($_FILES['photo'], $id);
        } else {
            $base64 = $this->getInput('photo_base64');
            if (empty($base64)) { $this->error('No photo data provided', 400); return; }
            $result = ImageUpload::uploadBase64UserPhoto($base64, $id);
        }

        if (!$result['success']) { $this->error($result['error'], 400); return; }

        //delete profile photo if exist do add new one
        if ($user['profile_photo']) ImageUpload::deleteUserPhoto($user['profile_photo']);

        $this->userModel->updateProfilePhoto($id, $result['filename']);

        $this->json([
            'message'   => 'Profile photo updated successfully',
            'photo_url' => ImageUpload::getUserPhotoUrl($result['filename'])
        ]);
    }

    //get user profile photo
    //get /api/v1/user/{id}/photo
    public function getPhoto(int $id): void
    {
        $user = $this->userModel->find($id);
        if (!$user) { $this->error('User not found', 404); return; }

        $this->json([
            'photo_url' => $user['profile_photo']
                ? ImageUpload::getUserPhotoUrl($user['profile_photo'])
                : null,
            'has_photo' => !empty($user['profile_photo'])
        ]);
    }

    //delete user profile photo
    //delete /api/v1/user/{id}/photo
    public function deletePhoto(int $id): void
    {
        $this->requireAuth();
        $this->checkOwnership($id, 'You cannot delete this profile photo');

        $user = $this->userModel->find($id);
        if (!$user)                  { $this->error('User not found', 404);         return; }
        if (!$user['profile_photo']) { $this->error('No profile photo exists', 404); return; }

        ImageUpload::deleteUserPhoto($user['profile_photo']);
        $this->userModel->deleteProfilePhoto($id);

        $this->json(['message' => 'Profile photo deleted successfully']);
    }
}