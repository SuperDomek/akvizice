<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'file.php';
require_once 'data.php';

class Count{

    private $dateFormat = "Y-m-d";

    function Count(){
        // Loading configuration
        $config = new Config('config.ini');
        $format_conf = $config->get('format');
        $this->dateFormat = $format_conf['dateformat'];
    }

    /*
    * Generates the dates to be counted between two dates. Validates against the date format from config file
    * $start string start date for the array
    * $end string end date for the array
    * return array array with the days between the dates
    */
    function generateDateArray($start, $end){

        // validate received dates
        if(!$this->validateDate($start)){
            error_log("The start date " . $start . " is invalid.");
            die("The start date " . $start . " is invalid.");
        };
        if(!$this->validateDate($end)){
            error_log("The start date " . $end . " is invalid.");
            die("The start date " . $end . " is invalid.");
        };
        // generate the date array
        $day = new DateInterval('P1D');
        $yearDates = array();
        
        for($date = new DateTime($start); $date->format($this->dateFormat) <= $end; $date->add($day)){
            $yearDates[] = $date->format($this->dateFormat);
        }
        return $yearDates;
    }

    /*
    * Function takes date string and validates it against set up format variable in this class
    * $date string The date string to be validated
    * $return bool true for valid date
    */

    function validateDate($date){
        $temp_date = DateTime::createFromFormat($this->dateFormat, $date);
        return $temp_date && $temp_date->format($this->dateFormat) === $date;
    }

    /*
    * Counts usage for all active titles for dates given; purges the corresponding dates first
    * $dates array year for which count the usage
    */
    function CountUsageAll($start_date, $end_date){
        $db = Database::getConnection();
        
        $dates_arr = $this->generateDateArray($start_date, $end_date);

        // purge corresponding dates usage from db
        $db->delete('usage', [
            'date[<>]' => [$start_date, $end_date]    
        ]);

        // validate year
        /*$year = (int) $year;
        if (!($year > 2000 && $year < 2100)){
            error_log("The year " . $year . "is not valid");
            die("The year " . $year . "is not valid");
        }*/


        //create array with active titles
        
        echo("Start: " . $start_date . PHP_EOL);
        echo("End: " . $end_date . PHP_EOL);
        


        // cycle through date array and count the usage
        foreach($dates_arr as $date){
            $titles = $this->getAllActiveTitles($date);
            $allLoans = $this->getTitlesLoans($date);
            echo "<pre>";
            echo "Date: " . $date . PHP_EOL;
            echo("Active titles count: " . count($titles) . PHP_EOL);
            echo("Loaned titles count: " . count($allLoans) . PHP_EOL);
            //print_r($titles);
            //print_r($allLoans);
            $counter = 0;
            
            foreach($titles as $adm_rec => $units){
                // if the title has any loans that day
                if(array_key_exists($adm_rec, $allLoans))
                    $loans = $allLoans[$adm_rec];
                else { // no loans for given title => skip
                    continue;
                }

                // if the status was changed from 04 or 05 after the loan to Grant then the unit is not listed,
                // but the loan is 
                // use the max number of units as loans => maximum usage
                if($loans > $units){
                    $loans = $units;
                }
                //echo("Title " . $adm_rec . ": " . $loans . " / " . $units . PHP_EOL);
                $db->insert('usage', [
                'date' => $date,
                'ADM_REC' => $adm_rec,
                'loans_count' => $loans,
                'unit_count' => $units
                ]);
            }
            //echo "Jednotky s mrtvými výpůjčkami: " . $counter . PHP_EOL;
            echo "</pre>";
        }
            
    }

    /*
    * Retrieves all active titles for a certain day with the count of their active units
    * $date string Date for the units to be active
    * return array All active titles with the count of their units
    */
    function getAllActiveTitles($date){
        $db = Database::getConnection();
        
        if(!$this->validateDate($date)){
            error_log("The start date " . $date . " is invalid.");
            die("The start date " . $date . " is invalid.");
        };

        $titles_temp = array();
        //create array with active titles
        $titles_temp = $db->select('units', [
            'ADM_REC',
            'UNITS' => Medoo::raw('COUNT(UNIT_ID)')
        ], [
            'AND' => [
                'ACQ_DATE[<=]' => $date,
                'OR' => [
                    'DELETE_DATE' => 0,
                    'DELETE_DATE[>]' => $date
                ]
            ],
            'GROUP' => 'ADM_REC'
        ]);
        // removing the first level of the array as I have only two columns
        $titles = array();
        foreach($titles_temp as $row){
            $titles[(int)$row['ADM_REC']] = (int)$row['UNITS'];
        }

        return $titles;
    }

    /*
    * Returns loaned units for a given day
    * $date string Date for which to get the loans
    * return array Array of titles and their loans
    */
    function getTitlesLoans($date){
        $db = Database::getConnection();
        
        if(!$this->validateDate($date)){
            error_log("The start date " . $date . " is invalid.");
            die("The start date " . $date . " is invalid.");
        };
        $loans_temp = $db->select('loans', [
            'ADM_REC',
            'LOANS' => Medoo::raw('COUNT(UNIT_ID)')
        ], [
            'AND' => [
                'LOAN_DATE[<=]' => $date,
                'OR' => [
                    'RETURN_DATE[>=]' => $date,
                    'RETURN_DATE' => 0
                ]
            ],
            'GROUP' => 'ADM_REC'
        ]);
        // removing the first level of the array as I have only two columns
        $loans = array();
        foreach($loans_temp as $row){
            $loans[(int)$row['ADM_REC']] = (int)$row['LOANS'];
        }
        return $loans;
    }
}

$data = new Data();
$count = new Count();
$db = Database::getConnection();
$start = date("Y-m-d", mktime(0, 0, 0, 1, 1, 2018));
$end = date("Y-m-d", mktime(0, 0, 12, 12, 31, 2018));


/*echo $start. PHP_EOL;
echo count($test) . PHP_EOL;
var_dump($test);
var_dump($db->error());*/

$count->CountUsageAll($start, $end);
?>