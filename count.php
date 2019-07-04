<?php require_once 'header.php';
//$user->checkSession();
if($user->validate(LEVEL_ADMIN) === false){
    $_SESSION['error'] = "Access denied.";
    header("Location: home.php");
}
?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;
require_once 'file.php';
require_once 'data.php';

class Count{

    private $dateFormat = "Y-m-d";

    function Count(){
        // Loading configuration
        $config = new Config('FEosu261BP/config.ini');
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
    * The only place that stores statuses is units table; so the counts for statuses
    * is based on the status that the unit currently has and not on the status of the unit at the time of the loan
    * $dates array year for which count the usage
    */
    function CountUsageAll($start_date, $end_date){
        $db = Database::getConnection();
        
        $dates_arr = $this->generateDateArray($start_date, $end_date);

        // purge corresponding dates usage from db
        $db->delete('usage', [
            'date[<>]' => [$start_date, $end_date]    
        ]);

        //create array with active titles
        
        //echo("Start: " . $start_date . PHP_EOL);
        //echo("End: " . $end_date . PHP_EOL);

        // cycle through date array and count the usage
        foreach($dates_arr as $date){
            //echo "<pre>";
            $titles = $this->getAllActiveTitles($date);
            $allLoans = $this->getTitlesLoans($date);
            
            //echo "Date: " . $date . PHP_EOL;
            //echo("Active titles count: " . count($titles) . PHP_EOL);
            //echo("Loaned titles count: " . count($allLoans) . PHP_EOL);
            //print_r($titles);
            //print_r($allLoans);
            
            foreach($titles as $adm_rec => $units){
                //the title can have units with multiple statuses

                // if the title has any loans that day
                if(array_key_exists($adm_rec, $allLoans))
                    $loans = $allLoans[$adm_rec]; // here I save an array with statuses
                else { // no loans for given title => skip
                    continue;
                }

                // if the status was changed from 04 or 05 after the loan to Grant then the unit is not listed,
                // but the loan is 
                // use the max number of units as loans => maximum usage
                
                //echo("Title " . $adm_rec . ": " . $loans . " / " . $units . PHP_EOL);
                foreach($loans as $status => $loans_status){
                    $units_status = $units[$status];
                    if($loans_status > $units_status){
                        $loans_status = $units_status;
                    }
                    //echo("Title " . $adm_rec . " status " . $status . ": " . $loans_status . " / " . $units_status . PHP_EOL);
                    $db->insert('usage', [
                    'date' => $date,
                    'ADM_REC' => $adm_rec,
                    'status' => $status,
                    'loans_count' => $loans_status,
                    'unit_count' => $units_status
                    ]);
                }
                
            }
            //echo "Jednotky s mrtvými výpůjčkami: " . $counter . PHP_EOL;
            //echo "</pre>";
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
            'STATUS',
            'UNITS' => Medoo::raw('COUNT(UNIT_ID)')
        ], [
            'AND' => [
                'ACQ_DATE[<=]' => $date,
                'OR' => [
                    'DELETE_DATE' => 0,
                    'DELETE_DATE[>]' => $date
                ]
            ],
            'GROUP' => ['ADM_REC', 'STATUS']
        ]);
        // removing the first level of the array and creating second level with pairs "STATUS" => "UNITS"
        $titles = array();
        foreach($titles_temp as $row){
            $adm_rec = (int)$row['ADM_REC'];
            
            if(!isset($titles[$adm_rec])){ // check if this ADM_REC is already in the new array
                $titles[$adm_rec] = array();
            }
            $titles[$adm_rec][(int)$row['STATUS']] = (int)$row['UNITS'];
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
            '[>]units' => ['ADM_REC', 'UNIT_ID']
        ], [
            'loans.ADM_REC',
            'units.status',
            'LOANS' => Medoo::raw('COUNT(loans.UNIT_ID)')
        ], [
            'AND' => [
                'loans.LOAN_DATE[<=]' => $date,
                'OR' => [
                    'loans.RETURN_DATE[>=]' => $date,
                    'loans.RETURN_DATE' => 0
                ],
                'units.STATUS[!]' => null // don't return loans data for units with actual status out of scope
            ],
            'GROUP' => ['loans.ADM_REC', 'units.STATUS']
        ]);
        //print_r($loans_temp);
        // removing the first level of the array as I have only two columns
        $loans = array();
        foreach($loans_temp as $row){
            $adm_rec = (int)$row['ADM_REC'];
            
            if(!isset($loans[$adm_rec])){ // check if this ADM_REC is already in the new array
                $loans[$adm_rec] = array();
            }
            $loans[$adm_rec][(int)$row['status']] = (int)$row['LOANS'];
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