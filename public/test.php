test
<?php
// Load configuration
require_once '../config/config.php';

// phpinfo();
// echo APP_NAME;

// echo APP_NAME;
// echo APP_ENV;
// echo APP_URL;

// echo DB_NAME;
// echo empty(DB_PASS) ? '(empty)' : '******* (hidden)'; 
// echo ROOT;
echo $_SERVER['REQUEST_URI'] .'   =>url i used <br>'; //just show the url you used ,not the path taken
echo $_SERVER['PHP_SELF'].'       => file running<br>';
echo $_GET['url'] . '       => url after index.php?   , shows only when rewrite url accure in public/.htaccess when use url like this /api/v1/products/5<br>  ,,,,, the goal is how to hander this url to use right controller , routes will help in that'              

?>
<!-- to html work should i first command the cors headers from config,otherwise it return json -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    <h1>🎉 Configuration Test</h1>
    
    <div class="success">
        ✅ Configuration files loaded successfully!
    </div>
    
    <h2>📊 Configuration Values:</h2>
    <table>
        <tr>
            <th>Constant</th>
            <th>Value</th>
        </tr>
        <tr>
            <td>APP_NAME</td>
            <td><?php echo APP_NAME; ?></td>
        </tr>
        <tr>
            <td>APP_ENV</td>
            <td><?php echo APP_ENV; ?></td>
        </tr>
        <tr>
            <td>APP_URL</td>
            <td><?php echo APP_URL; ?></td>
        </tr>
        <tr>
            <td>DB_HOST</td>
            <td><?php echo DB_HOST; ?></td>
        </tr>
        <tr>
            <td>DB_NAME</td>
            <td><?php echo DB_NAME; ?></td>
        </tr>
        <tr>
            <td>DB_USER</td>
            <td><?php echo DB_USER; ?></td>
        </tr>
        <tr>
            <td>DB_PASS</td>
            <td><?php echo empty(DB_PASS) ? '(empty)' : '******* (hidden)'; ?></td>
        </tr>
        <tr>
            <td>ROOT</td>
            <td><?php echo ROOT; ?></td>
        </tr>
    </table>
    
    <div class="info">
        <strong>Next step:</strong> Test database connection!
    </div>
</body>
</html>
