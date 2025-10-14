index  
<?php
// Load configuration
require_once '../config/config.php';

echo   '<br>'. APP_NAME . '<br>';

echo APP_ENV . '<br>';
echo APP_URL . '<br>';

echo DB_NAME . '<br>';
echo empty(DB_PASS) ? '(empty)' : '******* (hidden)'  . '<br>'; 
echo ROOT  . '<br>';
echo $_SERVER['REQUEST_URI'] .'   =>url i used <br>'; //just show the url you used ,not the path taken
echo $_SERVER['PHP_SELF'].'       => file running<br>';
echo $_GET['url'] . '       => url after index.php? <br>' ; //shows only when rewrite url accure in public/.htaccess when use url like this /api/v1/products/5<br>  ,,,,, the goal is how to hander this url to use right controller , routes will help in that' 

echo $_GET['sort']. '            => quere parameter after the url';
?>