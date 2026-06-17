<?php
//authmiddleware runs before every v2&3 request called by App.php
//reads the jwt token from the autehorization header
//verifies it  and injects the decoded user info into $_REQUEST arr
//$_REQUEST every controller can call $this->getUser() from BaseController and get user verified data

//flow:
//Request -> App.php -> AuthMiddleware::handle() -> BaseController::getUser in v2 -> All Controllers

// if token missing or invalid -> 401 immediately, controller never runs
// if token valid → decoded user stored in $_REQUEST['authenticated_user']

// Header format the client must send: "Authorization: Bearer eyJ..."

namespace Middleware;

use Core\JWTHandler;
use Helpers\Response;

class AuthMiddleware
{
    // routes that are public, no token required even in V2
    private static array $publicRoutes = [
        'POST /register',
        'POST /login',
        'POST /adminlogin',
        'POST /password/forgot',
        'POST /password/reset',

        // Product browsing — public
        'GET /products',
        'GET /products/search',
        'GET /products/{id}',
        'GET /products/{id}/reviews',
        'GET /products/{id}/rating',
        'GET /reviews/{id}',

        // Category and brand browsing — public
        'GET /categories',
        'GET /categories/{id}/products',
        'GET /brands',
        'GET /brands/{id}/products',

        // Search — public
        'GET /search',
        'GET /search/suggestions',
        'GET /search/trending',
    ];

    // check if the current route requested is public not need auth
    private function isPublicRoute(string $method, string $route): bool
    {
        $currentPath = "{$method} {$route}";

        foreach (self::$publicRoutes as $publicRoute) {
            if ($this->routeMatches($publicRoute, $currentPath)) {
                return true;
            }
        }

        return false;
    }

    //match public route pattern against actual path
    // Handles {id} and other parameters — same logic as App.php matchPath()
    // Example: "GET /products/{id}" matches "GET /products/5"
    private function routeMatches(string $pattern, string $actual): bool
    {
        // Convert {id} to a wildcard regex
        $regex = preg_replace('/\{[a-z_]+\}/', '[^/]+', $pattern);
        $regex = '#^' . str_replace('/', '\/', $regex) . '$#';

        return (bool) preg_match($regex, $actual);
    }
    

    //extract bearer token from authorization header
    //header: "Authorization: Bearer eyJ..."
    //returns the token string or null if missing or malformed
    private function extractToken(): ?string
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        // Some PHP setups (Apache with CGI) don't populate HTTP_AUTHORIZATION
        // This is the fallback
        if (empty($authHeader)) {
            $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        }

        // Must start with "Bearer "
        if (empty($authHeader) || !str_starts_with($authHeader, 'Bearer ')) {
            return null;
        }

        // Extract the token part after "Bearer "
        $token = trim(substr($authHeader, 7));

        return empty($token) ? null : $token;
    }


    //main handle method, called by App.php before every v2 request
    //returns:true if request should continue
    //sends 401 and exits if token is missing or invalid
    public function handle(string $method, string $route): bool
    {
        //check if this route is public (no token needed)
        //let it through to continue without checking token
        if ($this->isPublicRoute($method, $route)) {
            return true; 
        }

        //extract token from Authorization header
        $token = $this->extractToken();

        if (!$token) {
            Response::error('Authentication required. Please login and provide a Bearer token.', 401);
            exit;
        }

        // Verify token (checks 3component + signature + expiry + blacklist)
        if (!JWTHandler::verify($token)) {
            Response::error('Invalid or expired token. Please login again.', 401);
            exit;
        }

        // Decode token to get user data
        $user = JWTHandler::decode($token);

        if (!$user) {
            Response::error('Token could not be decoded.', 401);
            exit;
        }

        // Inject verified user into request context
        // Controllers access this via BaseController::getUser(), not from $_sessino
        $_REQUEST['authenticated_user'] = $user;
        // needed for logout blacklisting
        $_REQUEST['jwt_token']          = $token; 

        if (APP_ENV === 'development') {
            error_log("AuthMiddleware: user authenticated — ID={$user['user_id']}, role={$user['role']}");
        }

        return true;
    }





}

