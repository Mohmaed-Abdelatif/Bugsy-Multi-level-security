<?php
//inheret from v1 but override some methods
//password_hash() bcrypt instead of MD5
//every query will use pdo prepared statements
//baseModel auto detect v2 from url so will uses pdo outomatic

namespace Models\V2;

class User extends \Models\V1\User
{
    
    //password hashing with bcrypt
    //v1 used md5 "week"
    //v2 uses password_hash() with bcrypt
    public function hashPassword($password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    //veryify password  against bcrypt hasy
    //will make it support md5 too ,coz DB have user from v1 there passwords is md5 hashed
    public function verifyPassword($plainPassword,$hashedPassword): bool
    {
        // bcrypt hash (always 60 char or more)
        if (strlen($hashedPassword) >= 60) {
            return password_verify($plainPassword, $hashedPassword);
        }

        // legacy md5 for users created in v1
        return md5($plainPassword) === $hashedPassword;
    }


    //Authentication queries, pdo statments
    public function findByEmail($email): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }

    //find admin by his email
    public function findByAdminEmail($email): ?array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM {$this->table} 
             WHERE email = :email AND role = 'admin' LIMIT 1"
        );
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        return $user ?: null;
    }


    //find user by credentials(email + passord)
    //v1 put password check inside sql - injectable + md5
    //v2 fetches user by email then verifies password in php
    //this is correct method: sql only fetches user and php verifies
    public function findByCredentials($email,$password): ?array
    {
        // Step 1: fetch user by email only
        $user = $this->findByEmail($email);

        if (!$user) {
            return null;
        }

        // Step 2: verify password in PHP — never in SQL
        if (!$this->verifyPassword($password, $user['password'])) {
            return null;
        }

        return $user;
    }


    // check if email already exist — reuse findByEmail (already PDO)
    public function emailExists($email): bool
    {
        return $this->findByEmail($email) !== null;
    }



    //Registration - bcrypt hash
    public function register(array $data): int|false
    {
        // Hash password with bcrypt (overrides V1 which used MD5)
        $data['password'] = $this->hashPassword($data['password']);

        if (!isset($data['role'])) {
            $data['role'] = 'customer';
        }

        if (!isset($data['is_active'])) {
            $data['is_active'] = 1;
        }

        //baseModel::create() uses pdo prepared statement automatically
        //because BaseModel detected v2 and set connectionType = 'pdo'
        return $this->create($data);
    }




    //password management - pdo
    public function changePassword($userId, $oldPassword, $newPassword): bool
    {
        $user = $this->find($userId);

        if (!$user) {
            return false;
        }

        if (!$this->verifyPassword($oldPassword, $user['password'])) {
            return false;
        }

        // BaseModel::update() uses PDO automatically
        return $this->update($userId, [
            'password' => $this->hashPassword($newPassword)
        ]);
    }

    public function resetPasswordDirect($userId, $newPassword): bool
    {
        return $this->update($userId, [
            'password' => $this->hashPassword($newPassword)
        ]);
    }



    //--------------------------------------------------
    // profile — pdo (inherited CRUD is already pdo)
    //--------------------------------------------------
    // getProfile(),updateProfile(),updateProfilePhoto(),
    // deleteProfilePhoto(),...etc in v1,,, all inherited from V1\User
    // They call BaseModel::find() and BaseModel::update()
    // which are already PDO in V2. No override needed.



    //user orders
    public function getOrders($userId, $limit = 20, $offset = 0): array
    {
        $stmt = $this->connection->prepare(
            "SELECT * FROM orders 
             WHERE user_id = :user_id 
             ORDER BY created_at DESC 
             LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':user_id', $userId, \PDO::PARAM_INT);
        $stmt->bindValue(':limit',   $limit,  \PDO::PARAM_INT);
        $stmt->bindValue(':offset',  $offset, \PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }


    //count user total orders
    public function countOrders($userId): int
    {
        $stmt = $this->connection->prepare(
            "SELECT COUNT(*) as total FROM orders WHERE user_id = :user_id"
        );
        $stmt->execute(['user_id' => $userId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        return (int)($row['total'] ?? 0);
    }




}




