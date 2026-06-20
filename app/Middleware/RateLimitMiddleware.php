<?php
// RateLimitMiddleware: runs BEFORE AuthMiddleware in the v2/v3 stack
// protects against brute-force attacks on login/register
// sliding window: N attempts per IP+endpoint within a time window
/*
 before auth, Because a brute-force attacker on /login has
 no valid token yet, if we checked auth first, every attempt
 would just get a generic 401 and the attacker could try forever.
 Rate limiting must catch them before any password check happens.
*/

namespace Middleware;

use Core\Database;
use Helpers\Response;

class RateLimitMiddleware
{
    // Only rate-limit these sensitive endpoints
    // Other routes (browsing products, etc.) are not rate-limited
    private static array $limitedRoutes = [
        'POST /login'           => ['max' => 7,  'window' => 120],  // 10 attempts / 2 m
        'POST /adminlogin'      => ['max' => 5,  'window' => 450],
        'POST /register'        => ['max' => 15, 'window' => 300], // 15 / 5 m
        'POST /password/forgot' => ['max' => 5,  'window' => 900],
        'POST /password/reset'  => ['max' => 5,  'window' => 900],
    ];


    // Main handle method — called by App.php
    // Returns true if request should continue
    // Sends 429 and exits if rate limit exceeded
    public function handle(string $method, string $route): bool
    {
        $routeKey = "{$method} {$route}";

        // Only check rate limit on sensitive routes
        if (!isset(self::$limitedRoutes[$routeKey])) {
            return true;
        }

        $config = self::$limitedRoutes[$routeKey];
        $ip     = $this->getClientIp();
        $identifier = "{$ip}:{$routeKey}";

        $status = $this->checkAndIncrement($identifier, $config['max'], $config['window']);

        if (!$status['allowed']) {
            Response::error(
                'Too many attempts. Please try again later.',
                429,
                ['retry_after_seconds' => $status['retry_after']]
            );
            exit;
        }

        return true;
    }


    // Check current attempt count and increment atomically
    // TIMESTAMPDIFF/NOW() instead of PHP's strtotime() + time().
    // This removes the PHP-timezone-vs-MySQL-timezone mismatch
    private function checkAndIncrement(string $identifier, int $max, int $window): array
    {
        try {
            $db  = Database::getInstance();
            $pdo = $db->getPDO();

            // Fetch current record + elapsed seconds, all computed by MySQL
            $stmt = $pdo->prepare("
                SELECT 
                    attempts, 
                    TIMESTAMPDIFF(SECOND, window_start, NOW()) AS elapsed_seconds
                FROM rate_limits 
                WHERE identifier = :id 
                LIMIT 1
            ");
            $stmt->execute(['id' => $identifier]);
            $record = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$record) {
                // First attempt ever for this identifier — create record
                $insert = $pdo->prepare(
                    "INSERT INTO rate_limits (identifier, attempts, window_start) 
                     VALUES (:id, 1, NOW())"
                );
                $insert->execute(['id' => $identifier]);

                return ['allowed' => true, 'retry_after' => 0];
            }

            $elapsed = (int) $record['elapsed_seconds'];

            // Defensive: elapsed should never be negative, but guard anyway
            if ($elapsed < 0) {
                $elapsed = 0;
            }

            if ($elapsed > $window) {
                // Window expired — reset counter to 1
                $reset = $pdo->prepare(
                    "UPDATE rate_limits SET attempts = 1, window_start = NOW() WHERE identifier = :id"
                );
                $reset->execute(['id' => $identifier]);

                return ['allowed' => true, 'retry_after' => 0];
            }

            // Still within window — check if limit exceeded
            if ((int)$record['attempts'] >= $max) {
                $retryAfter = $window - $elapsed;
                return ['allowed' => false, 'retry_after' => max(1, $retryAfter)];
            }

            // Increment attempts atomically
            $increment = $pdo->prepare(
                "UPDATE rate_limits SET attempts = attempts + 1 WHERE identifier = :id"
            );
            $increment->execute(['id' => $identifier]);

            return ['allowed' => true, 'retry_after' => 0];

        } catch (\PDOException $e) {
            error_log("RateLimitMiddleware failed: " . $e->getMessage());
            // Fail open — if DB has an issue, don't block legitimate users
            return ['allowed' => true, 'retry_after' => 0];
        }
    }


    // Get the real client IP, accounting for proxies
    private function getClientIp(): string
    {
        // Check common proxy headers first (if behind a load balancer/CDN)
        $headers = ['HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ip = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }
}