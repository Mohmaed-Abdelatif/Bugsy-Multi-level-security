<?php
// Main Application Configuration
  
// This file sets up the entire application environment:
// - Loads database config
// - Sets error reporting
// - Defines app constants
// - Configures timezone
// - Sets CORS headers

//-----------------------
 
//load database configuration
require_once __DIR__ . '/database.php';

// Define root paths             , __DIR__ only mean Current folder
define('ROOT', dirname(__DIR__)); //dirname(__DIR__) mean Go up one level "root path"
define('APP', ROOT . '/app');
define('PUBLIC_PATH', ROOT . '/public');
define('STORAGE', ROOT . '/storage');
define('CONFIG', ROOT . '/config');

// Application constants from .env
define('APP_NAME', $_ENV['APP_NAME'] ?? 'Bugsy');
define('APP_ENV', $_ENV['APP_ENV'] ?? 'development');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost/Bugsy');


//error reporting
if(APP_ENV === 'development'){
    //show all errors
    error_reporting(E_ALL); //shwo erery possible error
    ini_set('display_errors',1);
    ini_set('display_startup_errors',1);
}else{
    //hide errors from users , prevent user from seeing your code or sql errors
    error_reporting(0);
    ini_set('display_errors',0);
    ini_set('log_errors',1);
    ini_set('error_log', STORAGE . '/logs/error.log' ); //for now no error.log created
}


// This might fail on some PHP versions:
// date_default_timezone_set('Africa/Cairo');
// More compatible:
date_default_timezone_set('UTC');  // Or your timezone


//character encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');

//-------------------------------------------------
//comment when test withe html
//headers json API and CORS (cross origin resource sharing)
header('Content-type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 3600');
header('Access-Control-Allow-Credentials: true');

// CORS handling opthons request to allow user in front use the api
if($_SERVER['REQUEST_METHOD'] === 'OPTIONS'){
    http_response_code(200);
    exit();
}

//-------------------------------------------------

//autoloader
/*
when write somthing like that: $db = new Core\Database();
php will outomatically pass Core\Database to the outoload function
*/
spl_autoload_register(function ($class) {
    //***Convert namespace to file path
    // Example: Core\Database -> app/Core/Database.php
    $class = str_replace('\\', '/', $class);
    
    $paths = [
        APP . '/' . $class . '.php',
        APP . '/Controllers/' . $class . '.php',
        APP . '/Models/' . $class . '.php',
    ];
    
    foreach ($paths as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});



//----------------------------------------
if(session_status() === PHP_SESSION_NONE){
    session_start();
}


