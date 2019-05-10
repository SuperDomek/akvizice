<?php
/**
*    This is the core class running the app
*/

use PHLAK\Config\Config;
use Medoo\Medoo;

require 'database.php';

class Application {
    private $counts = [
        'titles' => 0,
        'units' => 0,
        'loans' => 0
    ];
    /*
    * Constructor
    */
    function Application(){
        $db = Database::getConnection();
        $this->counts['titles'] = $db->count('titles');
        $this->counts['units'] = $db->count('units');
        $this->counts['loans'] = $db->count('loans');
    }

    /*
    * Returns the internal variables
    */
    function getCounts(){
        return $this->counts;
        
    }

    /*
    * Function running the app
    */
    function execute(){
        //code...
        
    }
}

?>