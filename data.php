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
    
    private static $title_columns = array("ADM_REC", "CALLNO", "TITLE", "ISBN", "AUTHOR");
    private static $units_columns = array("ADM_REC", "UNIT_ID", "STATUS", "ACQ_DATE", "UPDATE_DATE");
    private static $loans_columns = array("TIMESTAMP", "ADM_REC", "UNIT_ID", "LOAN_DATE", "RETURN_DATE");

    /*
    * Constructor
    */
    function Data(){
    }

    /*
    * Takes the spreadsheet file and saves the rows into the DB.
    * @file File - object with the file information
    */
    function processFile($file){
        $file_columns = array();

        switch ($file->getFileType()){
            case 'xlsx':
                $reader = ReaderFactory::create(Type::XLSX);
                break;
            case 'csv':
                $reader = ReaderFactory::create(Type::CSV);
                break;
            default:
                error_log("Error: Unsupported file type.");
                die();
        }
        $reader->open($file->getFilePath());

        $counters = array(
            "inserted" => 0,
            "updated" => 0,
            "skipped" => 0
        );

        foreach ($reader->getSheetIterator() as $sheet) {
            $file_columns = array();
            foreach ($sheet->getRowIterator() as $index => $row) {
                if($index == 1){ // first row = headers
                    $file_columns = $this->processHeader($row, $file->getDataType());
                    echo "<br/>";
                    print_r("Original header");
                    echo "<br/>";
                    print_r($row);
                    echo "<br/>";
                    print_r("Processed header". ": " . $file->getDataType());
                    echo "<br/>";
                    print_r($file_columns);
                }
                else{
                    $counter = $this->processRow($row, $file->getDataType(), $file_columns);
                    $counters[$counter]++;
                    //echo "<br/>";
                    //print_r($row);
                    //print_r((int) $index . ": " . print_r($row));
                    //echo "<br/>";
                }
                
            }
        }
        echo "<pre>";
        print_r($counters);
        echo "</pre>";

        $reader->close();
    }

    /*
    * Looks for the mandatory columnd in spreadsheet
    * $row Array Array of cells in the first row of spreadsheet
    * $dataType string Specifies the type of data to process - title, units, loans, etc.
    * return array of mandatory headers and their column indexes in the spreadsheet
    */
    private function processHeader($row, $dataType){
        $header_indexes = array();
        $mandatory_headers = array();
        //transform all the column headers to uppercase letters
        foreach ($row as $key => $column_text){
            $row[$key] = strtoupper($column_text);
        }
        // loads mandatory columns according to datatype
        switch ($dataType){
            case "titles":
                $mandatory_headers = self::$title_columns;
                break;
            case "units":
                $mandatory_headers = self::$units_columns;
                break;
            case "loans":
                $mandatory_headers = self::$loans_columns;
                break;
            default:
                error_log("Error: Unknown datatype");
                die("Error: Unknown datatype");
        }
        // check the header row for mandatory columns and write down their position
        foreach ($mandatory_headers as $column){
            $position = array_search($column, $row, true);
            if ($position !== FALSE){
                $header_indexes[$column] = $position;
            }
            else{
                error_log("Error: The spreadsheet does not contain the mandatory column: " . $column);
                die("Error: The spreadsheet does not contain the mandatory column: " . $column);
            }
        }
        return $header_indexes;
    }

    /*
    * Inserts the row data in database according to headers indexes
    * $row Array Row values from the file
    * $dataType string Type of data in the row
    * $headers Array Column names and their order in the data
    */
    function processRow($row, $dataType, $headers){
        $db = Database::getConnection();
        $import_array = array();
        // fill the array with columns and their values
        foreach($headers as $column => $index){
            $import_array[$column] = $row[$index];
        }
        // test output
        /*echo "<pre>";
        echo "Import array";
        print_r($import_array);
        echo "</pre>";*/
        
        if($dataType === "titles"){
            // check whether data already exists
            $exists = $db->has($dataType, [
                "ADM_REC" => $import_array['ADM_REC']
            ]);
            $unchanged = $db->has($dataType, $import_array);
            if (!$exists){
                $data = $db->insert($dataType, $import_array);
                /*echo "<pre>";
                print_r("Inserted row: " . $import_array['ADM_REC']);
                echo "</pre>";*/
                $return = "inserted";
            }
            elseif ($exists && $unchanged){
                // skip
                /*echo "<pre>";
                print_r("Skipping row: " . $import_array['ADM_REC']);
                echo "</pre>";*/
                $return = "skipped";
            }
            else{
                $adm_rec['ADM_REC'] = $import_array['ADM_REC']; // saving row id
                unset($import_array['ADM_REC']); // deleting id from the array
                $data = $db->update($dataType, $import_array, $adm_rec);
                /*echo "<pre>";
                print_r("Updated row: " . $adm_rec['ADM_REC']);
                echo "</pre>";*/
                $return = "updated";
            }
        }
        elseif ($dataType === "units"){

        }
        elseif ($dataType === "loans"){

        }
        else {
            error_log("Error: Unknown datatype: " . $dataType);
            die("Error: Unknown datatype: " . $dataType);
        }
        return $return;
    }
}

?>