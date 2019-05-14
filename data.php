<?php
/**
*    This is the data handling class
*/

use PHLAK\Config\Config;
use Medoo\Medoo;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

require_once 'database.php';
require_once 'file.php';

class Data {
    
    /*
    * Constructor
    */
    function Data(){
        $db = Database::getConnection();
    }

    /*
    * Returns the internal variables
    * @file File - object with the file information
    */
    function processFile($file){
        //...
        
    }

    /*
    * Function running the app
    */
    function returnRow(){
        //code...
        
    }
}

?>