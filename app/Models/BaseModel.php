<?php
//BaseMode - foundation for all models
//RESPONSIBILITIES:
// - Provide CRUD operations for all models
// - Handle both MySQLi (V1) and PDO (V2/V3) connections
// - Query builder for clean, chainable queries
// - Automatic timestamps (created_at, updated_at)
// - Pagination support
// - Error handling and logging
/*
it is commen between all models:-
 Find by ID - Product::find(5)
 Find all - Product::findAll()
 Create - Product::create(['name' => 'iPhone'])
 Update - Product::update(5, ['price' => 999])
 Delete - Product::delete(5)
 Query builder - Product::where('price', '>', 500)
 Pagination - Product::paginate(20, 0)
*/

//USAGE:
// class Product extends BaseModel {
//     protected $table = 'products';
// }
// 
// $product = new Product();
// $item = $product->find(5);
// $all = $product->findAll();
 


namespace Models;

use Core\Database;

class BaseModel
{
    //database connection
    public $db;

    //connection object (mysql v1 | PDO v2&3)
    protected $connection;

    //connection type: mysql or PDO
    protected $connectionType;

    //Current api version
    protected $apiVersion;

    //table name (child classes must define this)
    protected $table;

    //primary key column name default:id (override in child if different)
    protected $primaryKey = 'id';

    //enable automatic timestamps for (created_at, updated_at)
    protected $timestamps = true;

    //query builder state (for method chaining)
    protected $queryBuilder = [
        'where' => [],
        'orderBy' => [],
        'limit' => null,
        'offset' => null,
    ];




    //constractor
    //initialize model with appropriata DB connection
    //auto derects api version and selects connection type
    public function __construct()
    {
        //validate table name if is set
        if(empty($this->table)){
            throw new \Exception(get_class($this) . 'must define $table property');
        }

        //get database instance (singleton battern)
        $this->db = Database::getInstance();

        // Detect API version from request
        $this->apiVersion = $this->detectApiVersion();

        // Initialize appropriate connection based on version
        $this->initializeConnection();


        // Log in development mode
        if (APP_ENV === 'development') {
            error_log(sprintf(
                "%s: Initialized with table '%s', connection: %s, version: %s",
                get_class($this),
                $this->table,
                $this->connectionType,
                $this->apiVersion
            ));
        }

    }



    //-------------------------------------------
    //Api version and tybe conection handeling
    //------------------------------------------

    //detect api vesion
    private function detectApiVersion()
    {
        // Method 1: From URL (/api/v1/products → v1)
        $url = $_GET['url'] ?? '';
        if (preg_match('/v([1-3])/', $url, $matches)) {
            return 'v' . $matches[1];
        }
        
        // Default: V1
        return 'v1';
    }

    //initialize database connection based on api version
    private function initializeConnection()
    {
        if ($this->apiVersion === 'v1') {
            // V1: MySQLi connection (intentionally vulnerable)
            $this->connection = $this->db->getMySQLi();
            $this->connectionType = 'mysqli';
        } else {
            // V2/V3: PDO connection (secure with prepared statements)
            $this->connection = $this->db->getPDO();
            $this->connectionType = 'pdo';
        }
    }



    //------------------
    //CRUD Methods
    //------------------

    //find single record by primary key
   public function find($id)
    {
        if ($this->connectionType === 'mysqli') {
            // V1: MySQLi direct query
            $id = $this->connection->real_escape_string($id);
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = '{$id}' LIMIT 1";
            
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("Find failed", $sql);
                return null;
            }
            
            $row = $result->fetch_assoc();
            $result->free();
            
            return $row ?: null;
            
        } else {
            // V2/V3: PDO prepared statement
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
            
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute(['id' => $id]);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                
                return $row ?: null;
            } catch (\PDOException $e) {
                $this->logError("Find failed: " . $e->getMessage(), $sql);
                return null;
            }
        }
    }

    //find all records with optional filters, sorting, and pagination offsit
    /*
    * Example:
     * $products = $productModel->findAll();
     * $products = $productModel->findAll(20, 0); // First 20 records
     * 
     * // With query builder:
     * $products = $productModel->where('price', '>', 1000)
     *                          ->orderBy('name', 'ASC')
     *                          ->findAll(10);
    */
    public function findAll($limit=null, $offset=null)
    {
       $sql = "SELECT * FROM {$this->table}";
       
       //apply query builder conditions is sets
       //add where clause from query builder
        $whereClause = $this->buildWhereClause();
        $sql .= $whereClause;
       //add order by clause from query builder
        $orderByClause = $this->buildOrderByClause();
        $sql .= $orderByClause;

        // Apply limit and offset
        if ($limit !== null) {
            $sql .= " LIMIT " . (int)$limit;
            if ($offset !== null) {
                $sql .= " OFFSET " . (int)$offset;
            }
        }

        //Execute query on connection type
        if ($this->connectionType === 'mysqli') {
            //V1: MySQLi
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("FindAll failed", $sql);
                $this->resetQueryBuilder();
                return [];
            }
            
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
            
            $this->resetQueryBuilder();
            return $rows;
            
        } else {
            // V2/V3: PDO
            try {
                $stmt = $this->connection->query($sql);
                $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
                
                $this->resetQueryBuilder();
                return $rows;
            } catch (\PDOException $e) {
                $this->logError("FindAll failed: " . $e->getMessage(), $sql);
                $this->resetQueryBuilder();
                return [];
            }
        }
    }

    //create new record
    /*
     * Example:
     * $id = $productModel->create([
     *     'name' => 'iPhone 15',
     *     'price' => 999.99,
     *     'stock' => 50
     * ]);
     * // Returns: 123 (new product ID)
    */
    public function create(array $data)
    {
        // Add timestamps if enabled
        if ($this->timestamps) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
        }

        // Build column names and values
        $columns = array_keys($data);
        $values = array_values($data);

         if ($this->connectionType === 'mysqli') {
            // V1: MySQLi
            $escapedValues = [];
            foreach ($values as $value) {
                if ($value === null) {
                    $escapedValues[] = 'NULL';
                } elseif (is_numeric($value)) {
                    $escapedValues[] = $value;
                } else {
                    $escapedValues[] = "'" . $this->connection->real_escape_string($value) . "'";
                }
            }
            
            $columnList = implode(', ', $columns);
            $valueList = implode(', ', $escapedValues);
            
            $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$valueList})";
            
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("Create failed", $sql);
                return false;
            }
            
            return $this->connection->insert_id;
            
        } else {
            // V2/V3: PDO prepared statement
            $placeholders = [];
            $params = [];
            
            foreach ($columns as $column) {
                $placeholders[] = ':' . $column; // SQL needs colon
                $params[$column] = $data[$column]; // Binding array
            }
            
            $columnList = implode(', ', $columns);
            $placeholderList = implode(', ', $placeholders);
            
            $sql = "INSERT INTO {$this->table} ({$columnList}) VALUES ({$placeholderList})";
            
            try {
                $stmt = $this->connection->prepare($sql);
                $stmt->execute($params);
                
                return $this->connection->lastInsertId();
            } catch (\PDOException $e) {
                $this->logError("Create failed: " . $e->getMessage(), $sql);
                return false;
            }
        }
    }

    //update existing record
    /*
     * Example:
     * $success = $productModel->update(5, [
     *     'price' => 899.99,
     *     'stock' => 45
     * ]);
    */
    public function update($id, array $data)
    {
       // Add updated_at timestamp if enabled
        if ($this->timestamps && !isset($data['updated_at'])) {
            $data['updated_at'] = date('Y-m-d H:i:s');
        }
        
       if ($this->connectionType === 'mysqli') {
            // V1: MySQLi 
            $setParts = [];
            foreach ($data as $column => $value) {
                if ($value === null) {
                    $setParts[] = "{$column} = NULL";
                } elseif (is_numeric($value)) {
                    $setParts[] = "{$column} = {$value}";
                } else {
                    $escapedValue = $this->connection->real_escape_string($value);
                    $setParts[] = "{$column} = '{$escapedValue}'";
                }
            }
            $setClause = implode(', ', $setParts);
            
            $id = $this->connection->real_escape_string($id);
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = '{$id}'";
            
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("Update failed", $sql);
                return false;
            }
            
            return true;
            
        } else {
            // V2/V3: PDO prepared statement 
            $setParts = [];
            $params = ['id' => $id];
            
            foreach ($data as $column => $value) {
                $setParts[] = "{$column} = :{$column}";
                $params[$column] = $value;
            }
            $setClause = implode(', ', $setParts);
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = :id";
            
            try {
                $stmt = $this->connection->prepare($sql);
                return $stmt->execute($params);
            } catch (\PDOException $e) {
                $this->logError("Update failed: " . $e->getMessage(), $sql);
                return false;
            }
        }
    }

    //delete record
    /*
     * Example:
     * $success = $productModel->delete(5);
    */
    public function delete($id)
    {
        if ($this->connectionType === 'mysqli') {
            // V1: MySQLi (VULNERABLE)
            $id = $this->connection->real_escape_string($id);
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = '{$id}'";
            
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("Delete failed", $sql);
                return false;
            }
            
            return true;
            
        } else {
            // V2/V3: PDO prepared statement (SECURE)
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
            
            try {
                $stmt = $this->connection->prepare($sql);
                return $stmt->execute(['id' => $id]);
            } catch (\PDOException $e) {
                $this->logError("Delete failed: " . $e->getMessage(), $sql);
                return false;
            }
        }
    }



    //-----------------------------------------------------------
    //query builder methods make spicialy for find all to filter
    //-----------------------------------------------------------

    //add where condition
    /*
     * $products = $productModel->where('price', '>', 500)
     *                          ->where('stock', '>', 0)
     *                          ->findAll();
    */
    public function where($field, $operator, $value)
    {
        $this->queryBuilder['where'][] = [
            'field' => $field,
            'operator' => $operator,
            'value' => $value,
            'type' => 'AND'
        ];
        
        return $this;
    }

    //add where in condition
    //$products = $productModel->whereIn('category_id', [1, 2, 3])->findAll();
    public function whereIn($field, array $values)
    {
        $this->queryBuilder['where'][] = [
            'field' => $field,
            'operator' => 'IN',
            'value' => $values,
            'type' => 'AND'
        ];
        
        return $this;
    }

    //add order by clause
    /*
     *  $products = $productModel->orderBy('price', 'DESC')
     *                          ->orderBy('name', 'ASC')
     *                          ->findAll();
    */
    public function orderBy($field, $direction = 'ASC')
    {
        $direction = strtoupper($direction);
        if (!in_array($direction, ['ASC', 'DESC'])) {
            $direction = 'ASC';
        }
        
        $this->queryBuilder['orderBy'][] = "{$field} {$direction}";
        
        return $this;
    }

    //set limit clause
    //$products = $productModel->limit(20, 40)->findAll(); // Records 41-60
    public function limit($limit, $offset = null)
    {
        $this->queryBuilder['limit'] = (int)$limit;
        
        if ($offset !== null) {
            $this->queryBuilder['offset'] = (int)$offset;
        }
        
        return $this;
    }



    //------------------
    //utility methods
    //------------------
    
    //Check if record exists
    public function exists($id)
    {
        return $this->find($id) !== null;
    }

    //count total records
    /*
     * $total = $productModel->count();
     * $filtered = $productModel->where('price', '>', 500)->count();
    */
    public function count()
    {
        // Build SQL
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $sql .= $this->buildWhereClause();
        
        if ($this->connectionType === 'mysqli') {
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->resetQueryBuilder();
                return 0;
            }
            
            $row = $result->fetch_assoc();
            $result->free();
            $count = (int)($row['total'] ?? 0);
            
        } else {
            try {
                $stmt = $this->connection->query($sql);
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                $count = (int)($row['total'] ?? 0);
            } catch (\PDOException $e) {
                $this->logError("Count failed: " . $e->getMessage(), $sql);
                $count = 0;
            }
        }
        
        $this->resetQueryBuilder();
        return $count;
    }

    //paginate results
    /*
     * $result = $productModel->paginate(20, 0);
     * // Returns:
     * [
     *     'data' => [...],         // Array of records
     *     'total' => 100,          // Total records
     *     'perPage' => 20,         // Items per page
     *     'page' => 1,             // Current page
     *     'totalPages' => 5        // Total pages
     * ]
     */
    public function paginate($perPage = 20, $offset = 0)
    {
        // Get total count (before applying limit)
        $total = $this->count();
        
        // Get page data
        $data = $this->limit($perPage, $offset)->findAll();
        
        // Calculate page number
        $page = $offset > 0 ? floor($offset / $perPage) + 1 : 1;
        
        return [
            'data' => $data,
            'total' => $total,
            'perPage' => $perPage,
            'page' => $page,
            'totalPages' => ceil($total / $perPage)
        ];
    }

    //get first record
    /*
     * $product = $productModel->where('stock', '>', 0)
     *                         ->orderBy('price', 'ASC')
     *                         ->first();
    */
    public function first()
    {
        $results = $this->limit(1)->findAll();
        return $results[0] ?? null;
    }


    //----------------------------------------------------------------------
    //query Building helpers spicialy for find all query to filter or order
    //----------------------------------------------------------------------

    //build where clause from query builder
    protected function buildWhereClause()
    {
        if (empty($this->queryBuilder['where'])) {
            return '';
        }
        
        $conditions = [];
        
         foreach ($this->queryBuilder['where'] as $where) {
            $field = $where['field'];
            $operator = $where['operator'];
            $value = $where['value'];
            
            if ($operator === 'IN') {
                // WHERE IN clause
                if ($this->connectionType === 'mysqli') {
                    $escapedValues = [];
                    foreach ($value as $val) {
                        if (is_numeric($val)) {
                            $escapedValues[] = $val;
                        } else {
                            $escapedValues[] = "'" . $this->connection->real_escape_string($val) . "'";
                        }
                    }
                    $valueList = implode(', ', $escapedValues);
                } else {
                    // PDO: Just quote values
                    $valueList = implode(', ', array_map(function($v) {
                        return $this->connection->quote($v);
                    }, $value));
                }
                $conditions[] = "{$field} IN ({$valueList})";
            } else {
                // Regular WHERE clause
                if ($this->connectionType === 'mysqli') {
                    if (is_numeric($value)) {
                        $conditions[] = "{$field} {$operator} {$value}";
                    } else {
                        $escapedValue = $this->connection->real_escape_string($value);
                        $conditions[] = "{$field} {$operator} '{$escapedValue}'";
                    }
                } else {
                    $quotedValue = $this->connection->quote($value);
                    $conditions[] = "{$field} {$operator} {$quotedValue}";
                }
            }
        }
        
        return ' WHERE ' . implode(' AND ', $conditions);
    }

    //build order by clause from query builder
    protected function buildOrderByClause()
    {
        if (empty($this->queryBuilder['orderBy'])) {
            return '';
        }
        
        return ' ORDER BY ' . implode(', ', $this->queryBuilder['orderBy']);
    }

    //reset query builder state
    protected function resetQueryBuilder()
    {
        $this->queryBuilder = [
            'where' => [],
            'orderBy' => [],
            'limit' => null,
            'offset' => null
        ];
    }

    //--------------------------------------
    // advanced features for child classes
    //--------------------------------------

    //Execute custom SQL with result fetching
    // Useful for complex queries in child classes
    protected function fetchAll($sql, $params = [])
    {
        if ($this->connectionType === 'mysqli') {
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("FetchAll failed", $sql);
                return [];
            }
            
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            $result->free();
            
            return $rows;
            
        } else {
            try {
                if (!empty($params)) {
                    $stmt = $this->connection->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $stmt = $this->connection->query($sql);
                }
                
                return $stmt->fetchAll(\PDO::FETCH_ASSOC);
            } catch (\PDOException $e) {
                $this->logError("FetchAll failed: " . $e->getMessage(), $sql);
                return [];
            }
        }
    }

    //execute custom sql with single row fetch
    protected function fetchOne($sql, $params = [])
    {
        if ($this->connectionType === 'mysqli') {
            $result = $this->connection->query($sql);
            
            if (!$result) {
                $this->logError("FetchOne failed", $sql);
                return null;
            }
            
            $row = $result->fetch_assoc();
            $result->free();
            
            return $row ?: null;
            
        } else {
            try {
                if (!empty($params)) {
                    $stmt = $this->connection->prepare($sql);
                    $stmt->execute($params);
                } else {
                    $stmt = $this->connection->query($sql);
                }
                
                $row = $stmt->fetch(\PDO::FETCH_ASSOC);
                return $row ?: null;
            } catch (\PDOException $e) {
                $this->logError("FetchOne failed: " . $e->getMessage(), $sql);
                return null;
            }
        }
    }



    //-----------------------
    // getters
    //-----------------------

    public function getTable()
    {
        return $this->table;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function getApiVersion()
    {
        return $this->apiVersion;
    }

    public function getConnectionType()
    {
        return $this->connectionType;
    }

    //--------------------------
    //error handling & logging
    //--------------------------
     protected function logError($message, $sql = '')
    {
        $logMessage = sprintf(
            "[%s] %s - Table: %s, SQL: %s",
            get_class($this),
            $message,
            $this->table,
        );
        
        error_log($logMessage);
        
        // In development, also log connection-specific errors
        if (APP_ENV === 'development') {
            if ($this->connectionType === 'mysqli' && $this->connection->error) {
                error_log("MySQLi Error: " . $this->connection->error);
            }
        }
    }

   
}



/*
basic useiag if i need to make it for level 1 only
<?php
namespace Models\V1;

use Core\Database;

class BaseModel
{
    protected $db;
    protected $connection;
    protected $table;  // Child defines this
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->connection = $this->db->getMySQLi();
    }
    
    // 1. Find by ID
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE id = $id";
        $result = $this->connection->query($sql);
        return $result ? $result->fetch_assoc() : null;
    }
    
    // 2. Find all
    public function findAll() {
        $sql = "SELECT * FROM {$this->table}";
        $result = $this->connection->query($sql);
        
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $rows[] = $row;
        }
        return $rows;
    }
    
    // 3. Create
    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $values = "'" . implode("', '", array_values($data)) . "'";
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($values)";
        $this->connection->query($sql);
        
        return $this->connection->insert_id;
    }
    
    // 4. Update
    public function update($id, $data) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = '$value'";
        }
        $setClause = implode(', ', $set);
        
        $sql = "UPDATE {$this->table} SET $setClause WHERE id = $id";
        return $this->connection->query($sql);
    }
    
    // 5. Delete
    public function delete($id) {
        $sql = "DELETE FROM {$this->table} WHERE id = $id";
        return $this->connection->query($sql);
    }
}
*/