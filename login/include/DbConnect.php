<?php
/* 
 * Encoding : UTF-8
 * Created on  : 2019-2-16 17:14:11 by Wenzhuo ZHAO , wenzhuo.zhao@u-psud.fr
 */
class DbConnect {
    private $conn;
    function __construct() {        
    }
    /**
     * Establishing database connection
     * @return database connection handler
     */
    function connect() {
        include_once dirname(__FILE__) . '/Config.php';
        // Connecting to mysql database
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
 
        // Check for database connection error
        if (mysqli_connect_errno()) {
            echo "Failed to connect to MySQL: " . mysqli_connect_error();
            
        }
 
        // returing connection resource
        return $this->conn;
        
    }
 
}
/*test connection
$con = new DbConnect();
$con->connect();*/

