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

// try {
//     echo "<h1>Testing Database Connection</h1>";
//     echo "<hr>";
    
//     // Get database instance
//     $db = Core\Database::getInstance();
    
//     echo "✅ <strong>Database connection successful!</strong><br><br>";
    
//     // Test query: Get all brands with MySQLi
//     echo "<h2>Test Query 1: Get All Brands with MySQLi</h2>";
//     $result = $db->query("SELECT * FROM brands LIMIT 5");
//     if ($result) {
//         echo "✅ Query executed successfully!<br>";
//         echo "Brands found: <strong>" . $result->num_rows . "</strong><br>";
//         echo "<ul>";
//         while ($brand = $result->fetch_assoc()) {
//             echo "<li>ID: {$brand['id']} - {$brand['name']}</li>";
//         }
//         echo "</ul><br>";
//     } else {
//         echo "❌ Query failed!<br><br>";
//     }


//     // Test query: Get all brands with PDO
//     echo "<h2>Test Query 2: Get All Brands with PDO</h2>";
//     $result = $db->prepare("SELECT * FROM brands LIMIT 5");
//     $result->execute();
//     if ($result) {
//         echo "✅ Query executed successfully!<br>";
//         echo "Brands found: <strong>" . $result->rowCount() . "</strong><br>";
//         echo "<ul>";
//         while ($brand = $result->fetch()) {
//             echo "<li>ID: {$brand['id']} - {$brand['name']}</li>";
//         }
//         echo "</ul><br>";
//     } else {
//         echo "❌ Query failed!<br><br>";
//     }
    
//     // Test query: Get one brand with PDO
//     echo "<h2>Test Query 3: Get one Brand with PDO</h2>";
//     $result = $db->prepare("SELECT * FROM brands WHERE id = :id");
//     $result->execute(['id'=>4]);
//     if ($result) {
//         echo "✅ Query executed successfully!<br>";
//         echo "Brands found: <strong>" . $result->rowCount() . "</strong><br>";
//         echo "<ul>";
//         while ($brand = $result->fetch()) {
//             echo "<li>ID: {$brand['id']} - {$brand['name']} => This is my old phone brand</li>";
//         }
//         echo "</ul><br>";
//     } else {
//         echo "❌ Query failed!<br><br>";
//     }
         
// } catch (\Exception $e) {
//     echo "❌ <strong>Error:</strong> " . $e->getMessage();
// }
//--------------------------------------
//test basemodel
use Models\v1\Product;

// In a test controller:
$product = new Product();

// for mysql
// // Test 1: Find by ID
// $item = $product->find(1);
// var_dump($item); // Should show iPhone 15 Pro Max

// // Test 2: Get all products
// $all = $product->findAll();
// var_dump(count($all)); // Should show 22

// // Test 3: Query builder
// $expensive = $product->where('price', '>', 40000)
//                      ->orderBy('price', 'DESC')
//                      ->findAll();
// var_dump($expensive); // Should show expensive products
// $expensive = $product->db->query("SELECT * FROM products where price > 40000        ");
//  $rows = [];
// while ($row = $expensive->fetch_assoc()) {
//                 $rows[] = $row;
// }
// var_dump($rows);

// Test 4: Pagination
$paginated = $product->paginate(10, 0);
var_dump($paginated); // Should show first 10 products with pagination info




?>