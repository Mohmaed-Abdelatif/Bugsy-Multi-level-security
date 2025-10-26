<?php
//BaseController: foundation for all controllers
// RESPONSIBILITY:
// - Bridge between Router (App.php) and specific controllers
// - Provide common tools that ALL controllers need
// - Handle request data access (query, body, params)
// - Handle responses (JSON, errors)
// - Handle validation (basic in V1, robust in V2)
// - Handle authentication (placeholder in V1, real in V2/V3)
// - Scalability: Ready for V2/V3 features

// UPDATED: Added session-based authentication for V1

namespace Controllers;

use Core\Database;
use Helpers\Response;

class BaseController
{
    //database connetion: available to all child contollers
    protected $db;

    //array ass all request data from router
    protected $requestData = [
        'params' => [],  // /{}
        'query' => [],   // after ?
        'body' => [],    // request body
        'method' => '',
        'version' => '',
    ];

    //null in v1 (no authentication)
    protected $user = null;


    //constructor to initialize DB connection
    public function __construct()
    {
        //get database instance (singleton pattern ensure only one connection)
        $this->db = Database::getInstance();

        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }


        if (APP_ENV === 'development') {
            error_log("BaseController: DB connected");
        }
    }   


    //receive request data from router(App)
    public function setRequestData(array $data)
    {
        //full requestData key with keys of data from router(must keys exist)
        $this->requestData = array_merge($this->requestData, $data);

        if (APP_ENV === 'development') {
            error_log("BaseController: Request data set");
            error_log("  Method: " . $this->requestData['method']);
            error_log("  Version: " . $this->requestData['version']);
            error_log("  Params: " . json_encode($this->requestData['params']));
            error_log("  Query: " . json_encode($this->requestData['query']));
            error_log("  Body: " . json_encode($this->requestData['body']));
        }
    }

    //-------------------------
    // Data getters (read data)
    //-------------------------

    //getters for request body (json data)
    protected function getInput($key, $default=null)
    {
        return $this->requestData['body'][$key] ?? $default;
    }

    //get all request body (json data)
    protected function getAllInput()
    {
        return $this->requestData['body'];
    }

    //check if request body has a specific key
    protected function hasInput($key)
    {
        return isset($this->requestData['body'][$key]);
    }


    //get value from query string (?key=value) after url route
    protected function getQuery($key=null, $default=null)
    {
        if($key === null){
            return $this->requestData['query'];
        }

        return $this->requestData['query'][$key] ?? $default;
    }

    //check if requset has a specific query parameter
    protected function hasQuery($key)
    {
        return isset($this->requestData['query'][$key]);
    }


    //get URL parameters {id}
    //route: ger /product/{id}
    //and rul like this : /prodcut/20
    //so param is 20
    protected function getParam($key, $default=null)
    {
        return $this->requestData['params'][$key] ?? $default;
    }


    //get http method
    protected function getMethod()
    {
        return $this->requestData['method'];
    }


    //get api version
    protected function getVersion()
    {
        return $this->requestData['version'];       
    }



    //-------------------------------------------
    // response methods (send data back to clint)
    //-------------------------------------------

    // send json success respons
    protected function json($data, $message=null, $statusCode=200)
    {
        //use response helper for fixed format
        Response::success($data,$message,$statusCode);
    }

    //send json error response
    protected function error($message, $statusCode=400,$errors=[])
    {
        //use response helper for fixed format
        Response::error($message,$statusCode,$errors);
    }



    //-------------------------------------------
    // validation methods (check incoming data)
    //-------------------------------------------

    //validate request input (basic in v1, sollid in v2&V3)

    // Example usage in controller:
    //  public function create() {
    //      // Check required fields
    //      $this->validate([
    //          'name' => 'required',
    //          'email' => 'required',
    //          'password' => 'required'
    //      ]);
    // If validation fails, error response sent automatically
    // If passes, code continues

    protected function validate(array $rules)
    {
        $errors = [];
        $input = $this->getAllInput();

        foreach($rules as $field => $rule){
            if(!isset($input[$field]) || empty($input[$field])){
                $errors[$field] = ucfirst($field) . 'is required';
            }
        }

        //V2 TODO: Add more rules
        //-'email' => Check valid email format
        //-'integer' => Check is numeric
        //-'min:X' => Check minimum length/value
        //-'max:X' => Check maximum length/value
        //-'regex:X' => Check regex pattern

        //if validation faild, send error response and stop
        if(!empty($errors)){
            $this->error("Validation failed",422,$errors);
            return false;
        }

        return true;
    }


    //** Sanitize input data (basic in v1, sollid in v2&V3)
    //v1 only trims whitespace, No xss protection (intentionally)

    protected function  sanitize($data)
    {
        //v1: minimum sanitizaion
        if(is_string($data)){
            return trim($data);
        }

        if(is_array($data)){
            //Applies the method $this->sanitize() to each element of the $data array
            return array_map([$this,'sanitize'],$data); 
        }

        return $data;
        //V2 TODO: Add robust sanitization
        //-htmlspecialchars() for XSS protection
        //-strip_tags() to remove HTML
        //-filter_var() for specific types
    }



    //---------------------------------------------
    //Authentication methods weak auth for v1 , jwt for v2&3
    //---------------------------------------------

    //Require authentication
    // V1: Session-based check
    // V2: JWT validation
    // V3: JWT + 2FA
    // return bool True if authenticated, false otherwise (sends error and exits)
    protected function requireAuth()
    {
         if ($this->getVersion() === 'v1') {
            // V1: Check session
            if (!$this->isLoggedIn()) {
                $this->error('Authentication required. Please login.', 401);
                exit; // Stop execution
            }
            
            // Load user data from session into $this->user
            $this->user = $this->getUserFromSession();
            
            return true;
            
        } else {
            // V2/V3: JWT validation
            /*// V2 TODO: Implement JWT validation
             
             
             // Get JWT token from Authorization header
             $token = $this->getBearerToken();
             
             if (!$token) {
                 $this->error('Unauthorized - No token provided', 401);
                 return false;
             }
             
             // Decode and validate token
             $decoded = JWT::decode($token, JWT_SECRET);
             
             if (!$decoded) {
                 $this->error('Unauthorized - Invalid token', 401);
                 return false;
             }
             
             // Store authenticated user data
             $this->user = [
                 'id' => $decoded['user_id'],
                 'email' => $decoded['email'],
                 'name' => $decoded['name'],
                 'role' => $decoded['role']  // 'customer', 'admin', etc.
             ];
             
             return true;
             */
             
             // V3 TODO: Add 2FA check
             /*
             // After JWT validation, check if 2FA required
             if ($this->user['requires_2fa'] && !$this->verify2FA()) {
                 $this->error('2FA verification required', 403);
                 return false;
             }
            */
            $this->error('V2/V3 authentication not implemented yet', 501);
            exit;
        }

        
    }


    //require admin role
    // check if current user is authenticated and has admin role
    protected function requireAdmin()
    {
        // First check if user is authenticated
        if (!$this->requireAuth()) {
            return false;
        }
        
        // Check if user has admin role
        if (!$this->isAdmin()) {
            $this->error('Admin access required. You do not have permission.', 403);
            exit;
        }
        
        return true;
    }


    //check if user is logged in
    // v1 check php session
    private function isLoggedIn()
    {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }


    //check if current user is admin
    // must be called after requreauth() to access admin role
     protected function isAdmin()
    {
        if (!$this->user) {
            return false;
        }
        
        return $this->user['role'] === 'admin';
    }



    //get user data from session
    // v1 read from $_session
     private function getUserFromSession()
    {
        if (!$this->isLoggedIn()) {
            return null;
        }

        return [
            'id' => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['user_email'] ?? null,
            'name' => $_SESSION['user_name'] ?? null,
            'role' => $_SESSION['user_role'] ?? 'customer'
        ];
    }


    //Get current authenticated user 
    //V1 Usage (VULNERABLE):Controller must get user_id from request or session:
    // $userId = $this->getInput('user_id');  // Anyone can fake this!
    //V2 Usage (SECURE):Controller gets user from authenticated token:
    // $user = $this->getUser();
    // $userId = $user['id'];  // Verified from JWT!
    protected function getUser()
    {
        return $this->user;
        
        // V2/V3: Returns data like:
        /*
        [
            'id' => 5,
            'email' => 'user@example.com',
            'name' => 'John Doe',
            'role' => 'customer'
        ]
        */
    }



    //get current user_id for V1 operations (HELPER METHOD)
    // V1: Gets user_id from session after requireAuth()
    // V2/V3: Gets user_id from authenticated JWT token (SECURE!)
    //This method bridges V1 and V2/V3 behavior
    protected function getUserId()
    {
        if (!$this->user) {
            return null;
        }
        
        return $this->user['id'];

        // will make if and else to check version num to show were to return id from session or jwt tokn
    }



    // Check if current user owns the resource 
    protected function checkOwnership($resourceUserId, $errorMessage = 'You do not have access to this resource')
    {
        // Make sure user is authenticated first
        if (!$this->user) {
            $this->error('Authentication required', 401);
            exit;
        }
        
        // Admin can access everything
        if ($this->isAdmin()) {
            return true;
        }
        
        // Check if user owns the resource
        if ($this->user['id'] != $resourceUserId) {
            $this->error($errorMessage, 403);
            exit;
        }
        
        return true;
    }



    //Check if user has permission (PLACEHOLDER for V2/V3)
    protected function checkPermission($permission)
    {
        // V1: simple role chek
        // admin has all permissions,cutomers have none
        return $this->isAdmin();
        
        // V2/V3 TODO: Implement proper RBAC with permissions table
    }



   //-------------------------
   // helper methods 
   //------------------------- 

   // Get pagination parameters from query string
   //Example:
   // URL: /products?page=2&per_page=20
   //$pagination = $this->getPagination();
   // Returns: ['page' => 2, 'perPage' => 20, 'offset' => 20]
   // Use in query:
   //$products = Product::paginate($pagination['perPage'], $pagination['offset']);    
    protected function getPagination($defaultPerPage = 20)
    {
        $page = max(1, (int) $this->getQuery('page', 1));
        $perPage = max(1, min(100, (int) $this->getQuery('per_page', $defaultPerPage)));
        $offset = ($page - 1) * $perPage;
        
        return [
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset
        ];
    }



    //Log activity (for debugging and audit)
    // Example:
    //  $this->log('product_created', ['product_id' => 123]);
    protected function log($action, array $data = [])
    {
        if (APP_ENV === 'development') {
            error_log("Controller Action: {$action}");
            error_log("Data: " . json_encode($data));
        }
        
        // V2/V3 TODO: Store in audit log table
        // This will include:
        // - user_id (who did the action)
        // - action (what they did)
        // - resource_type (what they modified)
        // - resource_id (which specific record)
        // - ip_address (where from)
        // - timestamp (when)
    }

}