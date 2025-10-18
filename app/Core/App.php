<?php
// responsibilities:-
// 1. Parse incoming HTTP request
// 2. Extract API version (v1/v2/v3)
// 3. Match URL to route pattern
// 4. Extract parameters from URL
// 5. Instantiate controller
// 6. Call controller method
// 7. Handle errors (404, 405, 500)
// 8. **Prepare for middleware** (V2/V3)

// Design Philosophy:
// - Clean separation: parsing → matching → dispatching
// - Scalable: middleware hooks ready for V2/V3
// - Testable: each method does ONE thing
// - Maintainable: clear comments, descriptive names


// API endpoints exm:
//http://localhost/Bugsy/api/v1/products/5
//http://localhost/Bugsy/api/v1/products/5?sort=price


namespace Core;

use Exception;
use Helpers\Response;


class App
{
    private $Url;  //for cleaned url
    private $method; //HTTP method
    private $version; //api version (v1, v2, v3)
    private $route; //route pathe after version
    private $params = []; //array extracted url parameters
    private $requestBody = []; //array request body (parsed json)

    private $routes = []; //array loaded routes from config/routes.php

    private $middleware = []; //array middleware stack (empty in v1,just for v2&v3)



    //Initialize the application
    public function __construct()
    {
        //load all routes from config
        $this->loadRoutes();

        //parse incoming request
        $this->parseRequest();
    }


    //-----------------------
    // Main Execution
    //-----------------------
    //run the application, called form index
    public function run()
    {
        // echo $this->route ."<br>";

        //set cors headers for all response
        $this->setCorsHeaders();

        //Handle OPTIONS preflight requests (for CORS)
        if ($this->method === 'OPTIONS') {
            http_response_code(200);
            exit;
        }

        // Apply middleware (empty in V1, real in V2/V3)
        $this->applyMiddleware();

        //match the route
        $handler = $this->matchRoute();

        // echo $handler;
        if(!$handler){
            //no route matched the url route
            $this->handleNotFound();            return;
        }

               
        //send to controller
        $this->dispatch($handler);
    }

    //-------------------------------------------------------------------
    // Parse Request
    //-------------------------------------------------------------------

    //parse the incomming http request
    //extracts: method, url, version, route, body
    private function parseRequest()
    {
        $this->method = $_SERVER['REQUEST_METHOD'];

        //get url from .htaccess rewrite or (default to empty)
        $this->Url = $_GET['url'] ?? '';

        //clean the url
        $this->Url = $this->cleanUrl($this->Url);

        //extract version (v1, v2, v3)
        $this->extractVersion();

        //get route path (everything after version)
        $this->extractRoute();

        //parse request body (for create & edite ,,, POST & PUT requests)
        $this->parseRequestBody();

        //Log request in development mode (for test)
        if (APP_ENV === 'development') {
            error_log("+++++++++ REQUEST +++++++++");
            error_log("Method: {$this->method}");
            error_log("URL: {$this->Url}");
            error_log("Version: {$this->version}");
            error_log("Route: {$this->route}");
            error_log("Params: " . json_encode($this->params));
            error_log("Body: " . json_encode($this->requestBody));
        }

    }


    //clean and validate URL
    private function cleanUrl($url)
    {
        //remove query string (we will use $_Get for that)
        $url = strtok($url,'?');

        //remove slashes at the start and the end
        $url = trim($url,'/');

        //sanitize remove dangerous characters
        $url = filter_var($url,FILTER_SANITIZE_URL);

        //convert to lowercase for consistency
        $url = strtolower($url); 

        return $url;
    }

    //extract API version from URL
    //Examples:
    //-"api/v1/products" → version = "v1"
    //-"api/v2/users/5" → version = "v2"
    //-"products" → version = "v1" (default)
    private function extractVersion()
    {
        //Split URL into parts
        $parts = explode('/', $this->Url);

        //check if URL starts with "api"
        if(isset($parts[0]) && $parts[0] === 'api'){
            // check for version (v1, v2, v3)
            if(isset($parts[1]) && preg_match('/^v[1-3]$/',$parts[1])){
                $this->version = $parts[1];
                return;
            }
        }

        //default to v1 if no version entered
        $this->version = 'v1';
    }


    //extract route path (eveything after version)
    //Examples:
    // - "api/v1/products/5" → route = "products/5"
    // - "api/v2/cart/add" → route = "cart/add"
    private function extractRoute()
    {
        $parts = explode('/', $this->Url);

        //remove 'api' and version from parts
        if(isset($parts[0]) && $parts[0] === 'api'){
            array_shift($parts); //remove first item "api"
            
            if(isset($parts[0]) && preg_match('/^v[1-3]$/',$parts[0])){
                array_shift($parts); //remove first item "version"
            }
        }

        //join remainig parts
        $this->route = implode('/',$parts);

        //ensure route starts with '/' for matching
        if($this->route && $this->route[0] !== '/'){
            $this->route = '/' . $this->route;
        }

        //handle empty route (root)
        if(empty($this->route)){
            $this->route = '/';
        }

    }

    //parse request body (json)
    //stores in $this->requestBody parameter for controllers to access
    //It supports both JSON and form data.
    private function parseRequestBody()
    {
        //Only runs for methods that might include a body
        if(in_array($this->method, ['POST', 'PUT', 'DELETE'])){
            //read raw input
            $input = file_get_contents('php://input');

            if(!empty($input)){
                //try to decode as JSON to becomes an associative array.
                $decodedInput = json_decode($input,true);

                if(json_last_error() === JSON_ERROR_NONE){
                    $this->requestBody = $decodedInput; // if decode success so it saved as associative array
                }else{
                    //if JSON decoding fails, it tries to parse the body as URL-encoded form data
                    parse_str($input, $this->requestBody);
                }
            }
        }

    }



    //---------------------------------------------------------------
    // Route Loading and Matching
    //---------------------------------------------------------------

    //load routes from config/routes.php
    private function loadRoutes()
    {
        $routesFile = CONFIG . '/routes.php';

        if(!file_exists($routesFile)){
            throw new \Exception('Routes config file not found');
        }

        //load routes (returns array)
        $this->routes = require $routesFile;

        if(!is_array($this->routes)){
            throw new \Exception('Routes configuration must return an array');
        }
    }


    //**********match current request to a route patteern
    //Examples:
    // - Route pattern: "GET /products/{id}"
    // - Request: "GET /products/5"
    // - Match! Extract: ['id' => 5]
    private function matchRoute()
    {
        //get routes for current version
        $versionRoutes = $this->routes[$this->version] ?? [];

        if(empty($versionRoutes)){
            error_log("No routes defined for version: {$this->version}");
            return false;
        }

        // loop through routes for this version
        foreach($versionRoutes as $pattern => $handler){
            //parse pattern from string to array list : "GET /products/{id}" → ['GET', '/products/{id}']
            list($routeMethod, $routePattern) = explode(" ", $pattern, 2);

            //check if HTTP method mathces
            if($routeMethod !== $this->method){
                continue;
            }

            if($this->matchPath($routePattern, $this->route)){
                return $handler;
            }
        }

        //Didn't find match
        return false;
    }



    //**********match URL path against route pattern
    //Extracts parameters like {id}, etc.
    //(Route pattern "/products/{id}" , actual request path "/products/6")
    private function matchPath($pattern, $path)
    {
        // Convert pattern to regex
        // Replace {id} with named capture group (?P<id>[^/]+)
        $regex = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
        
        // Escape forward slashes
        $regex = str_replace('/', '\/', $regex);
        
        // Add anchors (must match entire path)
        $regex = '/^' . $regex . '$/';
        
        // Try to match
        if (preg_match($regex, $path, $matches)) {
            // Extract named parameters
            foreach ($matches as $key => $value) {
                // Skip numeric keys (we only want named parameters)
                if (!is_numeric($key)) {
                    $this->params[$key] = $value;
                    // If path is /products/5, then:
                    // $this->params = ['id' => '5'];
                }
            }
            return true;
        }
        
        return false;
    }

    //----------------------------------
    //Controller Dispatching
    //----------------------------------

    //************dispatch request to controller
    //(controler handelar  exm:"V1\ProductController@show") for the URL route
    private function dispatch($handler)
    {
        //parse handler: "V1\ProductController@show" → ['V1\ProductController', 'show']
        list($controllerName, $method)=explode('@',$handler);


        //build full controller class name
        //"V1\ProductController" becomes "Controllers\V1\ProductController"
        $controllerClass = "Controllers\\{$controllerName}";

        //check if controller class exists (should make namespace in controller file for make autoloder work)
        if(!class_exists($controllerClass)){
            error_log("Controller not found: {$controllerClass}");
            $this->handleNotFound();
            return;
        }

        // Instantiate controller
        $controller = new $controllerClass();
        // Check if method exists
        if (!method_exists($controller, $method)) {
            error_log("Method not found: {$controllerClass}::{$method}");
            $this->handleNotFound();
            return;
        }


        // Pass request data to controller
        // without this : the controller will be blind
        // controller can't access to request data
        $controller->setRequestData([
            'params' => $this->params,
            'query' => $_GET, //Data passed in the URL after the ? symbol (classic $_GET). so it contine key => value
            'body' => $this->requestBody,
            'method' => $this->method,
            'version' => $this->version
        ]);

        // Call controller method with URL parameters
        // If route has {id}, pass it as method argument
        $paramValues = array_values($this->params);
        call_user_func_array([$controller, $method], $paramValues);
        // Instead of:
        // $controller->show($id, $slug);

    }


    //--------------------------------------------
    //Middleware
    //--------------------------------------------
    /**
     * Apply middleware stack
     * Empty in V1, will be populated in V2/V3
     * 
     * V2 will add:
     * - JWT authentication
     * - Rate limiting
     * - Input validation
     * 
     * V3 will add:
     * - AI threat detection
     * - Advanced logging
     * - 2FA checks
    */
    private function applyMiddleware()
    {

        // V1: No middleware (intentionally vulnerable)
        // V2: Will add authentication, rate limiting, etc.
        // V3: Will add AI detection, advanced security
        
        // Placeholder for future middleware
        // Example structure for V2:
        /*
        foreach ($this->middleware as $middlewareClass) {
            $middleware = new $middlewareClass();
            $result = $middleware->handle($this->method, $this->route, $this->requestBody);
            
            if (!$result['success']) {
                $this->sendError($result['message'], $result['code']);
                exit;
            }
        }
        */

        // if ($this->version === 'v2' || $this->version === 'v3') {
        // // Check JWT token
        // $auth = new Middleware\AuthMiddleware();
        // if (!$auth->check()) {
        //     $this->sendError('Unauthorized', 401);
        // }
        
        // // Check rate limit
        // $rateLimit = new Middleware\RateLimitMiddleware();
        // if (!$rateLimit->check()) {
        //     $this->sendError('Too Many Requests', 429);
        // }
        // }
    }

    //set CORS headers for cross origin requests
    //Allows frontend to call this API
    private function setCorsHeaders()
    {
        // Allow requests from any origin (change in production!)
        header('Access-Control-Allow-Origin: *');
        
        // Allow these HTTP methods
        header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
        
        // Allow these headers
        header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
        
        // Cache preflight requests for 1 hour
        header('Access-Control-Max-Age: 3600');
        
        // Always return JSON
        header('Content-Type: application/json; charset=utf-8');
    }



    //--------------------------------
    // Error handeling
    //--------------------------------
    //handle 404 not found
    private function handleNotFound()
    {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Not Found',
            'message' => 'The requested endpoint does not exist',
            'method' => $this->method,
            'url' => $this->Url,
            'version' => $this->version,
            'available_versions' => ['v1', 'v2', 'v3']
        ], JSON_PRETTY_PRINT);
        exit;
    }

    //send error response
    private function sendError($message, $code = 400)
    {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'error' => 'Request Error',
            'message' => $message
        ], JSON_PRETTY_PRINT);
        exit;
    }


    //Debugging in development
    public function getRequestInfo()
    {
        if(APP_ENV !== 'development'){
            return [];
        }

        return[
            'method' => $this->method,
            'rul' => $this->Url,
            'version' => $this->version,
            'route' => $this->route,
            'params' => $this->params,
            'query' => $this->$_GET, //Data passed in the URL after the ? symbol (classic $_GET).so it contine key =>value
            'body' => $this->requestBody,
        ];
    }

}

// Design Decisions: Why separate parseRequest() from matchRoute()?
//  - Single Responsibility Principle
//  - Easier to test each part independently
//  - Can reuse parsing logic in V2/V3

