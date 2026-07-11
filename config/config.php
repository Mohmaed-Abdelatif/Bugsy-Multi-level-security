<?php
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

//jwt congig for v2&v3
define('JWT_SECRET', $_ENV['JWT_SECRET'] ?? 'imoalsaeed_01019902711_mohamedabdeltifalsaeed');
define('JWT_EXPIRY', (int)($_ENV['JWT_EXPIRY'] ?? 3600)); // 3600/60 = 1 hour


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


date_default_timezone_set('UTC'); 


//character encoding
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');


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
ini_set('session.cookie_samesite', 'None');
ini_set('session.cookie_secure', '1');
ini_set('session.cookie_httponly', '0');
ini_set('session.cookie_domain', '.bugsy.store');

if(session_status() === PHP_SESSION_NONE){
    session_start();
}

$requestUri = $_SERVER['REQUEST_URI'] ?? '';

if (preg_match('/\.(css|js|jpg|jpeg|png|gif)$/i', $requestUri)) {
    header_remove('Cache-Control');
    header_remove('Pragma');
    header_remove('Expires');
    header('Cache-Control: public, max-age=600');
}
