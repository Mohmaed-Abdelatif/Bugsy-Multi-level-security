<?php 
//this file loads DB variables from .env file and defines them as php constants for use through the app

$envFile = __DIR__ . '/../.env';

// echo $envFile;
// echo "<br>";

if(file_exists($envFile)){
    $lines = file($envFile,FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach($lines as $line){
        //skip  if line start with # or empty
        if(strpos(trim($line),'#') === 0 || empty(trim($line))){
            continue;
        }

        //parse key=value format if line have =
        if(strpos($line,'=') !== false){
            list($key, $value) = explode('=',$line,2);
            $key = trim($key);
            $value = trim($value);

            // removes both single quatos and double from the start and the end of value
            $value = trim($value, '"\'');

            //store in $_ENV superglobal
            $_ENV[$key] = $value;
        }

    }
}

//define DB cnstants
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'bugsydb');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', $_ENV['DB_CHARSET'] ?? 'utf8mb4');



// echo $_ENV['APP_ENV']. " Environment <br>";
// // For debugging will remove in production
// if ($_ENV['APP_ENV'] === 'development') {
//     echo "DB contants:- <br>";
//     echo "DB_HOST: " . DB_HOST . "<br>";
//     echo "DB_NAME: " . DB_NAME . "<br>";
//     echo "DB_USER: " . DB_USER . "<br>";
//     echo "DB_PASS: " . DB_PASS . "<br>";
//     echo "DB_CHARSET: " . DB_CHARSET . "<br>";
//     echo '<br>';
//     echo "App configuration:- <br>";
//     echo "APP_NAME: " . $_ENV['APP_NAME'] . "<br>";
//     echo "APP_ENV: " . $_ENV['APP_ENV'] . "<br>";
//     echo "APP_URL: " . $_ENV['APP_URL'] . "<br>";
// }