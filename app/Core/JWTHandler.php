<?php     
//no external library need for jwt,i need essientials only hash_hmac() and base64 encode
//php has hash_hmac() built-in func

//jwtHandler: genetates, verifies and decodes jwt tokens
//used by: authMiddleware to verify, v2/authcontroller on login

// Token structure: header.payload.signature  "all base64 url encoded"
// header:  {"alg":"HS256","typ":"JWT"}
// payload: {"user_id":5,"email":"...","role":"customer","iat":...,"exp":...}
// signature: HMAC-SHA256_algorithem(header.payload, JWT_SECRET)

namespace Core;

class JWTHandler
{
    //---------------------------------------------------------------------------
    //normal Base64 can include +, /, and = which are problematic in URLs and HTTP headers.
    //jwt must be transmitted in URLs (like Authorization: Bearer <token>), so it uses Base64URL to stay safe and compatible.

    // makes data URLsafe for jwt
    // Base64URL encode (jwt uses URL-safe base64 — no +, /, or = characters)
    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    //restores it back to normal Base64
    // Base64URL decode
    private static function base64UrlDecode(string $data): string
    {
        // Restore standard base64 characters and padding
        return strtr($data, '-_', '+/') . str_repeat('=', (4 - strlen($data) % 4) % 4);
    }
    //---------------------------------------------------------------------------



    //generate signed jwt token
    //called when login() by v2/authcontroller::login()
    /*
     // $payload = [
     //     'user_id' => 5,
     //     'email'   => 'user@example.com',
     //     'name'    => 'x',
     //     'role'    => 'customer'
     // ]
     // Returns: "eyJ..." token string
    */
    public static function generate(array $payload): string
    {
        //Header: metadata of token
        $header = self::base64UrlEncode(json_encode([
            'alg' => 'HS256',
            'typ' => 'JWT'
        ]));

        //add to payload array issued-at and expiry timestamps
        $payload['iat'] = time();                       // issued at (now)
        $payload['exp'] = time() + (int) JWT_EXPIRY;   // expires at (now + seconds)

        //payload: contain thd data about the user of session
        $payload = self::base64UrlEncode(json_encode($payload));

        //Signature — sign header.payload with secret to ecsures the token hasn't edit in 
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true)
        );

        //jwt token
        return "{$header}.{$payload}.{$signature}";
    }


    //verify a token, returns true or false
    //called by :AutheMiddleware before every v2 request
    /*
     checks:
     .token has 3 parts
     .signature matches (header+payload)
     .token is not expired
     .token is not blacklist in sessions table after logout
    */
    public static function verify(string $token): bool
    {
        $parts = explode('.', $token);

        //1-must have exactly 3 parts
        if (count($parts) !== 3) {
            return false;
        }

        [$header, $payload, $signature] = $parts;

        // Recompute expected signature
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "{$header}.{$payload}", JWT_SECRET, true)
        );

        //2-signature must match
        //Unlike ===, which may leak timing information
        //hash_equals always takes the same amount of time to compare, to prevent timing attacks
        if (!hash_equals($expectedSignature, $signature)) {
            return false;
        }

        // Decode payload to check expiry
        $decodedPayload = self::decodePayload($payload);

        if (!$decodedPayload) {
            return false;
        }

        //3-token must not be expired
        if (!isset($decodedPayload['exp']) || time() > $decodedPayload['exp']) {
            return false;
        }

        //4-Token must not be blacklisted (logged out)
        if (self::isBlacklisted($token)) {
            return false;
        }

        return true;
    }


    //---------------------------------------------------------------------------
    // Decode a base64url-encoded payload string → associative array
    private static function decodePayload(string $payloadBase64): ?array
    {
        $json = base64_decode(self::base64UrlDecode($payloadBase64));

        if (!$json) {
            return null;
        }

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $data;
    }

    // Check if token hash exists in sessions table (blacklisted) after logout
    private static function isBlacklisted(string $token): bool
    {
        try {
            $db   = Database::getInstance();
            $pdo  = $db->getPDO();
            $hash = hash('sha256', $token);

            $stmt = $pdo->prepare("
                SELECT id FROM sessions WHERE token_hash = :hash LIMIT 1
            ");
            $stmt->execute(['hash' => $hash]);

            return $stmt->fetch() !== false;

        } catch (\PDOException $e) {
            error_log("JWT blacklist check failed: " . $e->getMessage());
            // fail open here — if DB is down we don't want to lock everyone out
            // in v3 we will fail closed (deny on DB error)
            return false;
        }
    }
    //---------------------------------------------------------------------------



    //decode a token=> retues payload array or null
    //called by AuthMiddleware  after verify() passed
    //to extract user data and inject into request context
    public static function decode(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 3) {
            return null;
        }

        return self::decodePayload($parts[1]);
    }


    //blacklist a token on logout
    //called by v2/Authcontroller::logout()
    //stores sha256 hash of the token in sessions table
    //verify() checks this table on every request
    public static function blacklist(string $token): bool
    {
        try {
            $db     = Database::getInstance();
            $pdo    = $db->getPDO();
            $hash   = hash('sha256', $token);
            $userId = self::decode($token)['user_id'] ?? null;

            // Decode expiry so we can store it — useful for cleanup later
            $payload   = self::decode($token);
            $expiresAt = isset($payload['exp'])
                ? date('Y-m-d H:i:s', $payload['exp'])
                : date('Y-m-d H:i:s', time() + (int) JWT_EXPIRY);

            $stmt = $pdo->prepare("
                INSERT INTO sessions (user_id, token_hash, ip_address, user_agent, expires_at)
                VALUES (:user_id, :token_hash, :ip, :agent, :expires_at)
                ON DUPLICATE KEY UPDATE expires_at = :expires_at2
            ");

            $stmt->execute([
                'user_id'    => $userId,
                'token_hash' => $hash,
                'ip'         => $_SERVER['REMOTE_ADDR'] ?? null,
                'agent'      => $_SERVER['HTTP_USER_AGENT'] ?? null,
                'expires_at' => $expiresAt,
                'expires_at2'=> $expiresAt
            ]);

            return true;

        } catch (\PDOException $e) {
            error_log("JWT blacklist failed: " . $e->getMessage());
            return false;
        }
    }




}
