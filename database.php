<?php //require_once 'header.php';
if($_SERVER['SERVER_NAME'] == 'localhost')
require_once $_SERVER['DOCUMENT_ROOT'] . '/akvizice/vendor/autoload.php'; // It must be called first
else
require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // It must be called first
?>

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