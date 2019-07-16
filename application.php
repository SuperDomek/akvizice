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
    public $exportdir;
    public $exports;

    public $parameters = array(
        'start' => '',
        'end' => '',
        'status' => array(
            'absencne' => null,
            'skripta' => null
        ),
        'granularity' => '',
        'table' => ''
    );
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
        // Loading configuration
        $config = new Config('FEosu261BP/config.ini');
        $file_conf = $config->get('files');

        // check if we got export directory in config
        if ($file_conf['exportdir']){
            $this->exportdir = $file_conf['exportdir'];
        }
        else{
            error_log("Error: The upload directory not specified");
            die();
        }
        $this->loadExports();
        
    }

    /*
    * Returns the internal variables
    */
    function getCounts(){
        return $this->counts;
        
    }

    /*
    *   Saves the names of files in export directory.
    */
    function loadExports(){
        if ($handle = opendir($this->exportdir)) {
            // load file names
            $exports = array();
            while (false !== ($entry = readdir($handle))) {
                if(strpos($entry, 'xlsx') !== false ||
                strpos($entry, 'csv') !== false)
                $exports[$entry] = explode("_", explode(".", $entry)[0]);
                print_r($exports);
            }
            
            // map file name parameters to statistics parameters
            foreach($exports as $file => $parameters){
                $index = 0;
                $temp_param = array();
                foreach($this->parameters as $parameter => $blank){
                    if(is_array($blank)){
                        foreach($blank as $parameter_arr => $blank_arr){
                            $temp_param[$parameter_arr] = $parameters[$index++];
                        }
                    }
                    else{
                        $temp_param[$parameter] = $parameters[$index++];
                    }
                }
                $exports[$file] = $temp_param;
            }
            $this->exports = $exports;
            closedir($handle);
        }
        else{
            $_SESSION['error'] = "Nelze otevřít složku s exporty.";
        }
    }

    /*
    * Function running the app
    */
    function execute(){
        //code...
        
    }
}

?>