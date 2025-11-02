<?php
namespace Models\V1;

use Models\BaseModel;

class User extends BaseModel
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $timestamps = true;


    //find use by email
    public function findByEmail($email)
    {
        $email = $this->connection->real_escape_string($email);
        $sql = "SELECT * FROM {$this->table} WHERE email = '{$email}' LIMIT 1";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("FindByEmail failed", $sql);
            return null;
        }
        
        $user = $result->fetch_assoc();
        $result->free();
        
        return $user ?: null;
    }


    //verify user passwork v1 MD5 comparison (week)
    public function verifyPassword($plainPassword, $hashedPassword)
    {
        return md5($plainPassword) === $hashedPassword;
    }


    //hash passwork v1 mD5 (weak)
    public function hashPassword($password){
        return md5($password);
    }

    //check if email already exists : during register
    public function emailExists($email)
    {
        $user = $this->findByEmail($email);
        return $user !== null;
    }



    //--------------------------
    // registraton
    //--------------------------

    //register new user
    public function register(array $data)
    {
        // Hash password
        $data['password'] = $this->hashPassword($data['password']);
        
        // Set default role if not provided
        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }
        
        // Set default active status
        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }
        
        // Create user using BaseModel's create method
        $userId = $this->create($data);
        
        if ($userId) {
            // Log successful registration
            if (APP_ENV === 'development') {
                error_log("User registered: ID={$userId}, Email={$data['email']}");
            }
        }
        
        return $userId;
    }



    //---------------------------------------
    // User Profile Methods 
    //---------------------------------------

    //get user profile
    public function getProfile($userId)
    {
        $user = $this->find($userId);
        
        if ($user) {
            // Remove sensitive data
            unset($user['password']);
        }
        
        return $user;
    }

    //update user profile
    public function updateProfile($userId, array $data)
    {
        // Remove password if accidentally included
        unset($data['password']);
        
        // Prevent role change through profile update
        unset($data['role']);
        
        // Prevent changing active status through profile
        unset($data['is_active']);
        
        return $this->update($userId, $data);
    }



    //change user password 
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        // Get user
        $user = $this->find($userId);

        // V1: Verify old password
        if (!$this->verifyPassword($oldPassword, $user['password'])) {
            return false;
        }
        
        if (!$user) {
            return false;
        }

        $hashedPassword = $this->hashPassword($newPassword);
        return $this->update($userId, ['password' => $hashedPassword]);
    }


    public function resetPasswordDirect($userId, $newPassword)
    {
        // Hash new password
        $hashedPassword = $this->hashPassword($newPassword);
        
        // Update password
        $success = $this->update($userId, ['password' => $hashedPassword]);
        
        if ($success) {
            if (APP_ENV === 'development') {
                error_log("Password reset for user ID: {$userId}");
            }
        }
        
        return $success;
    }




    //---------------------------------------
    // User Orders & Addresses (for later controllers)
    //---------------------------------------
    public function getOrders($userId, $limit = 20, $offset = 0)
    {
        $userId = $this->connection->real_escape_string($userId);
        $limit = (int)$limit;
        $offset = (int)$offset;
        
        $sql = "SELECT * FROM orders WHERE user_id = '{$userId}' 
                ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";
        
        $result = $this->connection->query($sql);
        
        if (!$result) {
            $this->logError("GetOrders failed", $sql);
            return [];
        }
        
        $orders = [];
        while ($row = $result->fetch_assoc()) {
            $orders[] = $row;
        }
        $result->free();
        
        return $orders;
    }


    //count user total orders
    public function countOrders($userId)
    {
       $userId = $this->connection->real_escape_string($userId);
        $sql = "SELECT COUNT(*) as total FROM orders WHERE user_id = '{$userId}'";
        
        $result = $this->connection->query($sql);
        if (!$result) {
            return 0;
        }
        
        $row = $result->fetch_assoc();
        $result->free();
        
        return (int)($row['total'] ?? 0); 
    }


    //---------------------------
    // admin methods for admins
    //---------------------------

    public function getAllUsers($limit = 50, $offset = 0)
    {
        // Use BaseModel's findAll with limit/offset
        $users = $this->findAll($limit, $offset);
        
        // Remove passwords from results
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        return $users;
    }

    //get users by role
    public function getUsersByRole($role, $limit = 50, $offset = 0)
    {
        $users = $this->where('role', '=', $role)
                      ->limit($limit, $offset)
                      ->findAll();
        
        // Remove passwords
        foreach ($users as &$user) {
            unset($user['password']);
        }
        
        return $users;
    }

    //Activate/Deactivate user (admin only)
    public function setActiveStatus($userId, $isActive)
    {
        return $this->update($userId, ['is_active' => $isActive ? 1 : 0]);
    }

    //change user role (admin only)
    //V2/V3: Will require additional permission checks
    public function changeRole($userId, $role)
    {
        // Validate role
        if (!in_array($role, ['customer', 'admin'])) {
            return false;
        }
        
        return $this->update($userId, ['role' => $role]);
    }


    //check if user is admin 
    public function isAdmin($userId)
    {
        $user = $this->find($userId);
        return $user && $user['role'] === 'admin';
    }

    //check if use is active3
    public function isActive($userId)
    {
        $user = $this->find($userId);
        return $user && $user['is_active'] == 1;
    }
}