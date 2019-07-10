<?php require_once 'header.php';?>

<?php
/**
*    This is a singleton class connecting to db
*/

use PHLAK\Config\Config;
use Medoo\Medoo;

class Database {
    private static $db;
    private $connection;

    /*
    * Constructor
    */
    function Database(){
        // Loading configuration
        $config = new Config('FEosu261BP/config.ini');
        $db_conf = $config->get('mysql');
        $this->connection = new Medoo($db_conf);
        $this->connection->query("SET NAMES utf8");
        $this->connection->query("SET sql_mode = ANSI_QUOTES");
    }


    /*
    * Function returning the db connection
    */
    public static function getConnection(){
        if(self::$db == null){
            self::$db = new Database();
        }
        return self::$db->connection;
    }
}

?>