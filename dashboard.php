<?php require_once 'header.php'?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;
require_once 'data.php';

class Dashboard{

    public $dateFormat = "Y-m-d";
    public $parameters = array(
        'start' => '2018-01-01',
        'end' => '2018-12-31',
        'status' => array(
            STATUS_ABSENCNE => true,
            STATUS_SKRIPTA => true
        ),
        'granularity' => 'month',
        'table' => 'all'
    );
    public $overview = array(
        'active_titles' => 0, // status not specific
        'active_units' => 0, // status specific
        'avg_fully_loaned' => 0, // status specific
        'avg_loaned_units' => 0 // status specific
    );
    public $tableHeader = array();

    function Dashboard(){
        // Loading configuration
        $config = new Config('config.ini');
        $format_conf = $config->get('format');
        $this->dateFormat = $format_conf['dateformat'];
        if(isset($_GET))
            $this->loadParameters();
        $this->loadOverview();
        $this->getTimeDataHeader();
    }

    function loadParameters(){
        if ($_SERVER["REQUEST_METHOD"] == "GET" && !empty($_GET)) {
            $this->parameters['start'] = $this->testInput($_GET["date_start"]);
            $this->parameters['end'] = $this->testInput($_GET["date_end"]);
            if(isset($_GET["status"])){
                $temp = $this->parameters['status'];
                foreach($_GET["status"] as $status){ // if set up then true automatically
                    unset($temp[(int)$status]);
                }
                foreach($temp as $status => $set){ // if not set then false
                    $this->parameters['status'][$status] = false;
                }
            }
            else{ // checkboxes are empty (no reason)
                $this->parameters['status'][STATUS_SKRIPTA] = true;
                $this->parameters['status'][STATUS_ABSENCNE] = true;
            }
            $this->parameters['granularity'] = $this->testInput($_GET["granularity"]);
            $this->parameters['table'] = $this->testInput($_GET["table"]);
          }
        /*echo "<pre>";
        print_r($this->parameters);
        echo "</pre>";*/
    }

    /*
    * Loading data for the overview section. Queries are status sensitive.
    *
    *
    */
    function loadOverview(){
        $db = Database::getConnection();
        $count_tmp = $db->select('units', [
            "COUNT" => Medoo::raw('COUNT(DISTINCT(ADM_REC))')
        ], [
            'AND' => [
                'OR' => [
                    "DELETE_DATE" => 0,
                    'DELETE_DATE[>]' => $this->parameters['end']
                ],
                "ACQ_DATE[<=]" => $this->parameters['end'],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ]
        ]);

        $this->overview['active_titles'] = array_pop($count_tmp)['COUNT'];

        $active_units = $db->count('units', [
            'AND' => [
                'OR' => [
                    'DELETE_DATE' => 0,
                    'DELETE_DATE[>]' => $this->parameters['end']
                ],
                'ACQ_DATE[<=]' => $this->parameters['end'],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ]
        ]);
        $this->overview['active_units'] = $active_units;

        $avg_loaned_units = $db->avg('usage', 'loans_count', [
            'AND' => [
                'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ]
        ]);
        $this->overview['avg_loaned_units'] = $avg_loaned_units;
        
        
        $fully_loaned = $db->select('usage', [
            'date',
            'LOANED' => Medoo::raw('COUNT(date)')
        ], [
            'AND' => [
                'loans_count[=]unit_count',
                'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ],
            'GROUP' => 'date'
        ]);
        
         // removing the first level of the array as I have only two columns
        $avg_fully_loaned_temp = array();
        foreach($fully_loaned as $row){
            $avg_fully_loaned_temp[$row['date']] = (int)$row['LOANED'];
        }
        if(!empty($avg_fully_loaned_temp))
            $avg_fully_loaned = array_sum($avg_fully_loaned_temp) / count($avg_fully_loaned_temp);
        else{
            error_log("The average fully loaned array is empty.");
            die("The average fully loaned array is empty.");
        }
        $this->overview['avg_fully_loaned'] = $avg_fully_loaned;
        
        /*echo "<pre>";
        print_r($this->overview);
        echo "</pre>";*/
    }

    /*
    * Simple data validation from w3schools.com
    *
    */
    function testInput($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

     /*
    * Renders HTML code for data table header
    * $return string The html string for header
    */
    function renderTableHeader() {
        // First row
        $header = "<tr>";
        $headerArr = $this->tableHeader;
        //$size = serialize($data);
        //echo strlen($size) . " bytes" . PHP_EOL;
        foreach($headerArr['information'] as $column){
            $header .= '<th rowspan="2">' . $column . "</th>";
        }
        foreach($headerArr['stats'] as $date => $columns){
            $header .= '<th colspan="2">' . $date . "</th>";
        }
        $header .= "</tr>";
        // Second row
        $header .= "<tr>";
        foreach($headerArr['stats'] as $date => $columns){
            foreach($columns as $column){
                $header .= "<th>" . $column . "</th>";
            }
        }
        $header .= "</tr>";
        return $header;
    }

    function renderTableBody(){

        $datas = $this->getData();
        $informationHeader = $this->tableHeader['information'];
        unset($informationHeader[array_search('ADM_REC', $informationHeader)]);
        $statsHeader = $this->tableHeader['stats'];
        $body = "";
        // first summary row
        $body .= "<tr>";
        $body .= '<th colspan="2">Celkem titulů</th>';
        $body .= "<td>" . count($datas) . "</td>";
        $body .= "</tr>";
        // view for loans
        foreach($datas as $adm_rec => $data){
            $row = "<tr>";
            $row .= "<th>" . $adm_rec . "</th>";
            
            foreach($informationHeader as $column){
                $row .= '<td class="information">' . $data[$column] . "</td>";
            }
            foreach($statsHeader as $date => $columns){
                if(isset($data['STATS'][$date])){
                    foreach($columns as $column){
                        $row .= '<td class="stats">' . $data['STATS'][$date][$column] . " %</td>";
                    }
                }
                else{
                    $row .= '<td class="stats">-</td>';
                    $row .= '<td class="stats">-</td>';
                }
            }
            $row .= "</tr>";
            $body .= $row . PHP_EOL;
        }
        return $body;
    }

    /*
    * Based on the dates from parameters creates an array of arrays for each time point
    * return array An array where the key is Data point string and value an empty array
    */
    function getTimeDataHeader(){
        $granularity = $this->parameters['granularity'];
        if($granularity === 'month'){
            $month = new DateTime($this->parameters['start']); // initialization
            $end = new DateTime($this->parameters['end']);
            $tableHeader = array();
            $tableHeader['information'][] = 'ADM_REC';
            $tableHeader['information'][] = 'TITLE';
            $tableHeader['information'][] = 'CALLNO';
            $tableHeader['information'][] = 'UNITS';
            for($month; ($month->diff($end)->m + ($month->diff($end)->y*12)) > 0; $month->modify('first day of next month')){
                $tableHeader['stats'][$month->format('Y-m')][] = 'AVRG';
                $tableHeader['stats'][$month->format('Y-m')][] = 'STD';
            }
            $tableHeader['stats'][$end->format('Y-m')][] = 'AVRG';
            $tableHeader['stats'][$end->format('Y-m')][] = 'STD';
        }
        /*echo "<pre>";
        print_r($tableHeader);
        echo "</pre>";*/
        $this->tableHeader = $tableHeader;
        

        return;
    }
    /*
    * Fills loan data into the data array with prefilled header
    * dataHeader array The result of getDataHeader function
    * return array An array where the key is a Data point string and value an array with data to show
    */
    function getData(){
        $db = Database::getConnection();
        $filter = array(); // array for the select HAVING in case filter is on
        $where = array(
            'AND' => [
                'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ],
            'GROUP' => Medoo::raw('substr(date, 1,7),ADM_REC,status')
        );
        switch($this->parameters['table']){
            case 'all':
                unset($filter);
                break;
            case '90':
                $filter['AVRG_LOANED[>=]'] = 90;
                $where['HAVING'] = $filter;
                break;
            case '10':
                $filter['AVRG_LOANED[<=]'] = 10;
                $where['HAVING'] = $filter;
                break;
            default:
                error_log("Unrecognized table filter parameter? " . $this->parameters['table']);
                die("Unrecognized table filter parameter? " . $this->parameters['table']);
        }
        $select = $db->select('usage',[
            '[>]titles' => 'ADM_REC'
        ],[
           'DATE' => Medoo::raw('substr(date, 1, 7)'),
           'usage.ADM_REC (ADM_REC)',
           'titles.TITLE (TITLE)',
           'titles.CALLNO (CALLNO)',
           'status',
           'AVRG_UNITS' => Medoo::raw('AVG(unit_count)'),
           'AVRG_LOANED' => Medoo::raw('AVG(loans_count/unit_count) * 100'),
           'STD_DEV' => Medoo::raw('STDDEV_SAMP(loans_count/unit_count) * 2 * 100')
        ], $where);

        // transform the select rows into a datatable
        $data = array();
        foreach($select as $row){
            $adm_rec = (int)$row['ADM_REC'];
            if(!isset($data[$adm_rec])){
                $data[$adm_rec] = array();
                $data[$adm_rec]['TITLE'] = $row['TITLE'];
                $data[$adm_rec]['CALLNO'] = $row['CALLNO'];
                $data[$adm_rec]['UNITS'] = (int)$row['AVRG_UNITS'];
                $data[$adm_rec]['STATS'] = array();
            }
            $data[$adm_rec]['STATS'][$row['DATE']]['AVRG'] = round($row['AVRG_LOANED']);
            $data[$adm_rec]['STATS'][$row['DATE']]['STD'] = round($row['STD_DEV'],1);
        }
        /*echo "<pre>";
        //print_r($select);
        //print_r($data);
        echo "</pre>";*/
        return $data;
    }

}

$dashboard = new Dashboard();
?>

<html lang="cs">
<head>
  <meta charset="utf-8">

  <title>Dashboard | Simple collection use counter</title>
  <meta name="description" content="Collection usage counter">
  <meta name="author" content="Dominik Bláha, blahad@sic.czu.cz">

  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

  <!--<link rel="stylesheet" href="css/styles.css?v=1.0">-->

</head>

<body>
<div id="forms-section">
    <h1>Nastavení</h1>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="GET">
        <label for="date_start">Vyberte časový úsek k porovnání:</label>
            <input type="date" name="date_start" value="<?php echo $dashboard->parameters['start'];?>"/>
            <input type="date" name="date_end" value="<?php echo $dashboard->parameters['end'];?>"/>
        <br/>
        <label for="status">Status:</label>
            <input type="checkbox" name="status[]" value="04" <?php echo ($dashboard->parameters['status'][STATUS_ABSENCNE]) ? "checked" : "";?>>Absenčně</input>
            <input type="checkbox" name="status[]" value="05" <?php echo ($dashboard->parameters['status'][STATUS_SKRIPTA]) ? "checked" : "";?>>Skripta</input>
        <br/>
        <label for="granularity">Granularita:</label>
            <select name="granularity">
                <!--<option value="day" <?php echo ($dashboard->parameters['granularity'] == 'day') ? "selected" : "";?>>Den</option>-->
                <option value="month" <?php echo ($dashboard->parameters['granularity'] == 'month') ? "selected" : "";?>>Měsíc</option>
                <!--<option value="year" <?php echo ($dashboard->parameters['granularity'] == 'year') ? "selected" : "";?>>Rok</option>-->
            </select>
        <br/>
        <label for="table">Filtrovat data na:</label>
            <select name="table">
                <option value="all" <?php echo ($dashboard->parameters['table'] == 'all') ? "selected" : "";?>>Všechno</option>
                <option value="90" <?php echo ($dashboard->parameters['table'] == '90') ? "selected" : "";?>>Plně vypůjčené (90+ %)</option>
                <option value="10" <?php echo ($dashboard->parameters['table'] == '10') ? "selected" : "";?>>Málo půjčované (10- %)</option>
            </select>
        <br/>
        <input type="submit" value="Odeslat"/>
    </form>
</div>
<hr/>
<div id="overview">
    <h1>Přehled</h1>
    <p>Počet aktivních titulů: <?php echo $dashboard->overview['active_titles'];?></p>
    <p>Počet aktivních jednotek: <?php echo $dashboard->overview['active_units'];?></p>
    <p>Průměrně vypůjčených jednotek na titul: <?php echo $dashboard->overview['avg_loaned_units'];?></p>
    <p>Průměrný počet 100% vypůjčených titulů za den: <?php echo $dashboard->overview['avg_fully_loaned'];?></p>
    </div>
<hr/>
<div id="data">
    <h1>Data</h1>
    <div id="container">
        <table>
            <thead>
                <?php print_r($dashboard->renderTableHeader());?>
            </thead>
            <tbody>
                <?php print_r($dashboard->renderTableBody());?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>