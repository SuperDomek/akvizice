<?php require_once 'header.php';?>

<?php
/**
*    This is the core class running the app
*/

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'database.php';

class Application {
    private $counts = [
        'titles' => array(),
        'units' => array(),
        'loans' => 0,
        'usage' => 0
    ];
    /*
    * Constructor
    */
    function Application(){
        $db = Database::getConnection();
        $this->counts['titles']['total'] = $db->count('titles');
        $count_tmp = $db->select('titles', [
            '[>]units' => ["ADM_REC" => "ADM_REC"]
        ], [
            "COUNT" => Medoo::raw('COUNT(DISTINCT(titles.ADM_REC))')
        ], [
            "units.DELETE_DATE" => 0
        ]);
        $this->counts['titles']['active'] = array_pop($count_tmp)['COUNT'];
        $this->counts['units']['total'] = $db->count('units');
        $this->counts['units']['active'] = $db->count('units', [
            'DELETE_DATE' => 0
        ]);
        $this->counts['loans'] = $db->count('loans');
        $this->counts['usage'] = $db->count('usage');
        
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