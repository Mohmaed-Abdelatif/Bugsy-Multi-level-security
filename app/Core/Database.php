<?php
// Database Connection Class (All Levels)
//Level 1: mysql with direct queries(vulnerable to sql injection)
//Level 2: PDO with prepared statements (secure) - ready but not active yet
// Level 3: PDO with advanced features (AI, logging) - ready but not active yet

//* Design: Both MySQLi and PDO connection code exist, but only MySQLi is active for now.

//Uses Singleton pattern to ensure only ONE connection(instance) of this class exists. => use private constractor so - Only one object of Database is ever created.

/*
Usage:
Level 1 (now):
  $db = Core\Database::getInstance();
  $result = $db->query("SELECT * FROM users");  // MySQLi direct query 
Level 2 (later):
  $db = Core\Database::getInstance();
  $stmt = $db->prepare("SELECT * FROM users WHERE email = :email");  // PDO prepared
  $stmt->execute(['email' => $email]);
*/

namespace Core;

class Database
{
    //singleton instance => ensures only one database connection exists (using private staic).
    //static:(belongs to the class itself not any specific object tack from the class) acces to it useing class name or self:: if call in its class
    //private: only accessible within this class
    //privata static => to only be accessed within the class — not from outside or even child classes or object from the class.
    private static $instance = null;

    //connection properties
    private $connection; //will delete

    private $mysqli = null; //mysqli connection (for level 1)

    private $pdo = null; //PDO connection (for level 2&3)


    //track which connection are initialized (prevents multiple initialization)
    private $mysqliInitialized = false;
    private $pdoInitialized = false;



    //----------------------------------
    //Singleton Pattern Methods
    //----------------------------------

    //private constructor (singleton pattern)
    //prevent direct instantiation with 'new Database' outside the class, so cannot create an object from ouside the class
    //so Prevents: $db1 = new Database(); $db2 = new Database(); (multiple connections) ;
    // instead must use Database::getInstance(); "single connection reused"
    private function __construct()
    {
        //for now,only intitialize mysqli(level 1)
        //PDO will be initiallized later
        $this->initializeMySQLi();
    }


    //get singleton instance
    //creates connection on first call, returns existing connection on subsequent calls
    //why static?
    //in singleton ,constractor is private so cant't do new object
    //so i need mehtod that's accessible without creating an object,so this method must be static
    public static function getInstance()
    {
        if(self::$instance === null){
            self::$instance = new self(); // connects only once to this class(Database class), "use new self() not new Database()" to be work in inheritance or reusable base classe  
        }
        
        return self::$instance; //return existing instance
    }


    //---------------------------------------------------
    //mysqli connection (level1) 
    //---------------------------------------------------

    //intilaize mysqli connection
    //called automatically from constractror
    private function initializeMySQLi()
    {
        if($this->mysqliInitialized){
            return; //already connected, don't reconnect
        }

        //connect to MySQL using MySQLi
        $this->mysqli = new \mysqli(DB_HOST, DB_USER, DB_PASS,DB_NAME);

        if($this->mysqli->connect_error){
            $this->handleConnectionError(
                'MySQLi',
                $this->mysqli->connect_error,
                $this->mysqli->connect_errno,
            );
        }

        //set character set to UTF-8
        $this->mysqli->set_charset(DB_CHARSET);

        //Mark as initialized
        $this->mysqliInitialized = true;

        //log success in devlopment mode (test)
        if(APP_ENV === 'development'){
            error_log('MySQLi connection initialized successfully to: ' . DB_NAME);
        }

    }

    //get the raw MySQLi connection  object
    //used by models that need direct access to mysqli object.
    public function getMySQLi()
    {
        if(!$this->mysqliInitialized){
            $this->initializeMySQLi();
        }
        return $this->mysqli; //return \mysqli connection object
    }


    //Execute SQL query directly (vulnerable - level 1 only)
    //execute queries directly without sanitization => intentional for level 1
    public function query($sql)
    {
        if(!$this->mysqliInitialized){
            $this->initializeMySQLi();
        }

        //log query for just test (see what queries are running)
        if(APP_ENV === 'development'){
            error_log("MySQL Query (vulnerable): " . $sql);
        }

        //Execute query using mysqli object "so need intialize mysql first"
        $result = $this->mysqli->query($sql);

        //check for errors
        if(!$result){
            error_log("SQL Error: " . $this->mysqli->error);

            if(APP_ENV === 'development'){
                //don't die her,let the caller handle it
                trigger_error("SQL Error: " . $this->connection->error,E_USER_WARNING);
            }
        }

        return $result;
    }

    //get number of affected rows from last query
    //Useful for UPDATE, DELETE, INSERT queries.
    public function affectedRows()
    {
         if ($this->mysqliInitialized) {
            return $this->mysqli->affected_rows;
        }
        
        return 0;
    }



    //---------------------------------------------------
    //PDO connection (level2&3) 
    //---------------------------------------------------

    private function initializePDO()
    {

    }


    public function getPDO()
    {

    }


    public function perpare($sql)
    {

    }

    //---------------------------------------------------
    //Shared methods
    //---------------------------------------------------

    //get the last inserted auto-increment
    //Works for both MySQLi and PDO.
    public function lastInsertId()
    {
        //Try MySQLi first (Level 1)
        if ($this->mysqliInitialized) {
            return $this->mysqli->insert_id;
        }

        //Try PDO if initialized (Level 2 & 3)
        if ($this->pdoInitialized) {
            return $this->pdo->lastInsertId();
        }
        
        return 0;
    }


    //close the database connection
    //usually not needed as PHP closes it automatically
    //But available if you need it for long-running scripts.
    public function close(){
        if ($this->mysqliInitialized && $this->mysqli) {
            $this->mysqli->close();
            $this->mysqli = null;
            $this->mysqliInitialized = false;
        }
        
        if ($this->pdoInitialized) {
            $this->pdo = null;
            $this->pdoInitialized = false;
        }
        
        // Reset singleton instance
        self::$instance = null;
    }


    //---------------------------------------------------
    //Shared helper methods
    //---------------------------------------------------

    //Handle connection errors (both MySQLi and PDO)
    private function handleConnectionError($type, $error, $code)
    {
        // ALWAYS log errors (even in production)
        error_log("═══════════════════════════════════════");
        error_log("CRITICAL: {$type} Database Connection Failed!");
        error_log("═══════════════════════════════════════");
        error_log("Database: " . DB_NAME);
        error_log("Host: " . DB_HOST);
        error_log("User: " . DB_USER);
        error_log("Error: " . $error);
        error_log("Error Code: " . $code);
        error_log("Timestamp: " . date('Y-m-d H:i:s'));
        error_log("═══════════════════════════════════════");
        
        if (APP_ENV === 'development') {
            // Development: Show detailed error (helps debugging)
            die(json_encode([
                'success' => false,
                'message' => "{$type} connection failed",
                'error' => $error,
                'error_code' => $code,
                'database' => DB_NAME,
                'host' => DB_HOST
            ], JSON_PRETTY_PRINT));
        } else {
            // Production: Generic message (hide sensitive info from users)
            die(json_encode([
                'success' => false,
                'message' => 'Service temporarily unavailable. Please try again later.',
                'support' => 'If this persists, please contact support.'
            ], JSON_PRETTY_PRINT));
        }
    }


    //---------------------------------------------------
    //singleton pattern protection 
    //---------------------------------------------------

    //prevent cloning of the instance (singleton pattern)
    //private to block cloning
    private function __clone()
    {
        // prevent cloning, to ensuring there is only one istance of the class 
    }

    //prevent unserializing of the instance (singleton pattern)
    public function __wakeup()
    {
        // prevents recreating object from serializel data
        throw new \Exception("Cannot unserialize singleton");
    }
}

