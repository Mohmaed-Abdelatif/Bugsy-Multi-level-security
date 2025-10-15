<?php
// Database Connection Class (All Levels)
//Level 1: mysql with direct queries(vulnerable to sql injection)
//Level 2: PDO with prepared statements (secure) - ready but not active yet
//so This class handles the database connection using MySQLi.
//Uses Singleton pattern to ensure only ONE connection(instance) exists. => use private constractor so - Only one object of Database is ever created.

/*
  Usage:
  $db = Core\Database::getInstance()->getConnection();
  or
  $db = Core\Database::getInstance();
  $result = $db->query("SELECT * FROM users");
*/

namespace Core;

use mysqli;

class Database
{
    //singleton instance => ensures only one database connection exists (using private staic).
    //static:(belongs to the class itself not any specific object tack from the class) acces to it useing class name or self:: if call in its class
    //privata static => to only be accessed within the class — not from outside or even child classes.
    private static $instace = null;

    //mysql connection object
    private $connection;


    //private constructor (singleton pattern)
    //prevent direct instantiation with 'new Database' outside the class, so cannot create an object from ouside the class $obj=new DataBase();
    // instead must use Database::getInstance()
    private function __construct()
    {
        //connect to mysql using new mysqli
        $this->connection = new \mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        //check for connection eerrors
        if($this->connection->connect_error)
        {
            error_log("-------------------------------------------");
            error_log("CRITICAL: Database connection failed!");
            error_log("Error: " . $this->connection->connect_error);
            error_log("Error Code: " . $this->connection->connect_errno);
            error_log("Time: " . date('Y-m-d H:i:s'));
            error_log("-------------------------------------------");

            if(APP_ENV === 'development'){
                //show detailed error for debugging
                die(json_encode([
                    'success' => false,
                    'message' =>'DataBase connection faild',
                    'error' => $this->connection->connect_error,
                    'error_code' =>$this->connection->connect_errno
           ],JSON_PRETTY_PRINT));
            }else{
                //hide details in production
                die(json_encode([
                    'success' =>false,
                    'message' =>'Service temporarily unavailable, Please try again later'
                ],JSON_PRETTY_PRINT));
            }
        }

        // Set character set to UTF-8
        $this->connection->set_charset(DB_CHARSET);

        //develpement mode :log successful connection (for testing)
        if(APP_ENV === 'development'){
            error_log('DataBase connected successfully to: ' . DB_NAME);
        }
    }


    //get singleton instance
    //creates connection on first call, returns existing connection on subsequent calls
    //in singleton ,constractor is private so cant't do new datbse
    //so i need mehtod that's accessible without creating an object,so this method must be static
    public static function getInstance()
    {
        if(self::$instace === null){
            self::$instace = new self(); // connects only once to this class(Database class), "use new self() not new Database()" to be work in inheritance or reusable base classe  
        }
        
        return self::$instace; //return Database
        // return Database::$instace; // not flixable and not useful in inheritance coz it will always return an DataBase class object even called from a child class
    }


    //get the raw myqli connection 
    //used by models to execute queries
    public function getConnection()
    {
        return $this->connection; //return \mysqli connection "global mysql that fall above"
    }


    //Execute a SQL query (vulnerable in level 1)
    //execute queries directly without sanitization => intentional for level 1
    public function query($sql)
    {
        //log query for just test in development mode
        if(APP_ENV === 'development'){
            error_log("SQL Query: " . $sql);
        }

        //Execute query
        $result = $this->connection->query($sql);

        //check for errors
        if(!$result){
            error_log("SQL Error: " . $this->connection->error);

            //in development
            if(APP_ENV === 'development'){
                //don't die her,let the caller handle it
                trigger_error("SQL Error: " . $this->connection->error,E_USER_WARNING);
            }
        }

        return $result;
    }


    //get the last inserted auto-increment ID
    public function lastInsertId()
    {
        return $this->connection->insert_id;
    }


    //Escape a string for use in SQL Query
    //not enough to prvent sql injection ,level 2 will use prepared statments instaed
    public function escape($value){
        return $this->connection->real_escape_string($value);
    }


    //get number of affected rows from last query
    public function affectedRow(){
        return $this->connection->affected_rows;
    }

    //close the database connection
    //usually not needed as PHP closes it automatically
    public function close(){
        if($this->connection){
            $this->connection->close();
            self::$instace = null;
        }
    }

    //prevent cloning of the instance (singleton pattern)
    private function __clone()
    {
        // prevent cloning by making __clone() private it block cloning; to ensuring there is only one istance of the class 
    }

    //prevent unserializing of the instance (singleton pattern)
    public function __wakeup()
    {
        // prevents unserializing the singleton object using unserialize()
        throw new \Exception("Cannot unserialize singleton");
    }
}

