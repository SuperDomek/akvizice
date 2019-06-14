<?php require_once 'header.php'?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;
require_once 'data.php';

class Dashboard{

    private $dateFormat = "Y-m-d";
    public $parameters = array(
        'start' => '2018-01-01',
        'end' => '2018-12-31',
        'status' => array(
            STATUS_SKRIPTA => true,
            STATUS_ABSENCNE => true
        ),
        'granularity' => 'month',
        'table' => 'fully_loaned'
    );
    public $overview = array(
        'active_titles' => 0, // status not specific
        'active_units' => 0, // status specific
        'avg_fully_loaned' => 0, // status specific
        'avg_loaned_units' => 0 // status specific
    );

    function Dashboard(){
        // Loading configuration
        $config = new Config('config.ini');
        $format_conf = $config->get('format');
        $this->dateFormat = $format_conf['dateformat'];
        if(isset($_GET))
            $this->loadParameters();
        $this->loadOverview();
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
                "ACQ_DATE[<=]" => $this->parameters['end']
            ]
        ]);

        $this->overview['active_titles'] = array_pop($count_tmp)['COUNT'];
        
        $status_arr = array();
        foreach($this->parameters['status'] as $status => $set){
            if($set){
                $status_arr[] = $status;
            }
        }

        $active_units = $db->count('units', [
            'AND' => [
                'OR' => [
                    'DELETE_DATE' => 0,
                    'DELETE_DATE[>]' => $this->parameters['end']
                ],
                'ACQ_DATE[<=]' => $this->parameters['end'],
                'STATUS' => $status_arr
            ]
        ]);
        $this->overview['active_units'] = $active_units;

        $avg_loaned_units = $db->avg('usage', 'loans_count', [
            'date[<>]' => [$this->parameters['start'], $this->parameters['end']]
        ]);
        $this->overview['avg_loaned_units'] = $avg_loaned_units;
        
        
        $fully_loaned = $db->select('usage', [
            'date',
            'LOANED' => Medoo::raw('COUNT(date)')
        ], [
            'AND' => [
                'loans_count[=]unit_count',
                'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
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
            <input type="checkbox" name="status[]" value="04" <?php echo ($dashboard->parameters['status'][STATUS_SKRIPTA]) ? "checked" : "";?>>Skripta</input>
            <input type="checkbox" name="status[]" value="05" <?php echo ($dashboard->parameters['status'][STATUS_ABSENCNE]) ? "checked" : "";?>>Absenčně</input>
        <br/>
        <label for="granularity">Granularita:</label>
            <select name="granularity">
                <option value="day" <?php echo ($dashboard->parameters['granularity'] == 'day') ? "selected" : "";?>>Den</option>
                <option value="month" <?php echo ($dashboard->parameters['granularity'] == 'month') ? "selected" : "";?>>Měsíc</option>
                <option value="year" <?php echo ($dashboard->parameters['granularity'] == 'year') ? "selected" : "";?>>Rok</option>
            </select>
        <br/>
        <label for="table">Zobrazit data pro:</label>
            <select name="table">
                <option value="all" <?php echo ($dashboard->parameters['table'] == 'all') ? "selected" : "";?>>Všechno</option>
                <option value="fully_loaned" <?php echo ($dashboard->parameters['table'] == 'fully_loaned') ? "selected" : "";?>>Plně vypůjčené</option>
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
    <div id="table">
        <table>

        </table>
    <div>
</div>
</body>
</html>