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
// echo $_SERVER['REQUEST_URI'] .'   =>url i used <br>'; //just show the url you used ,not the path taken
// echo $_SERVER['PHP_SELF'].'       => file running<br>';
// echo $_GET['url'] . '       => url after index.php?'   ;//shows only when rewrite url accure in public/.htaccess when use url like this /api/v1/products/5<br>  ,,,,, the goal is how to hander this url to use right controller , routes will help in that'              

//-------------------

// Helpers\Response::success([
//     'user' => [
//         'id' =>1,
//         'name'=>'mohamed',
//         'email'=>'imoalsaeed@gmail.com'
//     ]
// ],'use retrieved successfuly');

// Helpers\Response::error('error',400);

// Helpers\Response::notFound();

// Helpers\Response::json(['custom'=>'data','test'=>true],200)

//-----------------------------------------

try {
    echo "<h1>Testing Database Connection</h1>";
    echo "<hr>";
    
    // Get database instance
    $db = Core\Database::getInstance();
    
    echo "✅ <strong>Database connection successful!</strong><br><br>";
    
    // Test query: Get all brands with MySQLi
    echo "<h2>Test Query 1: Get All Brands with MySQLi</h2>";
    $result = $db->query("SELECT * FROM brands LIMIT 5");
    if ($result) {
        echo "✅ Query executed successfully!<br>";
        echo "Brands found: <strong>" . $result->num_rows . "</strong><br>";
        echo "<ul>";
        while ($brand = $result->fetch_assoc()) {
            echo "<li>ID: {$brand['id']} - {$brand['name']}</li>";
        }
        echo "</ul><br>";
    } else {
        echo "❌ Query failed!<br><br>";
    }


    // Test query: Get all brands with PDO
    echo "<h2>Test Query 2: Get All Brands with PDO</h2>";
    $result = $db->prepare("SELECT * FROM brands LIMIT 5");
    $result->execute();
    if ($result) {
        echo "✅ Query executed successfully!<br>";
        echo "Brands found: <strong>" . $result->rowCount() . "</strong><br>";
        echo "<ul>";
        while ($brand = $result->fetch()) {
            echo "<li>ID: {$brand['id']} - {$brand['name']}</li>";
        }
        echo "</ul><br>";
    } else {
        echo "❌ Query failed!<br><br>";
    }
    
    // Test query: Get one brand with PDO
    echo "<h2>Test Query 3: Get one Brand with PDO</h2>";
    $result = $db->prepare("SELECT * FROM brands WHERE id = :id");
    $result->execute(['id'=>4]);
    if ($result) {
        echo "✅ Query executed successfully!<br>";
        echo "Brands found: <strong>" . $result->rowCount() . "</strong><br>";
        echo "<ul>";
        while ($brand = $result->fetch()) {
            echo "<li>ID: {$brand['id']} - {$brand['name']} => This is my old phone brand</li>";
        }
        echo "</ul><br>";
    } else {
        echo "❌ Query failed!<br><br>";
    }
         
} catch (\Exception $e) {
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
}

?>
<!-- to html work should i first command the cors headers from config,otherwise it return json -->
<!-- <!DOCTYPE html>
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
</html> -->
