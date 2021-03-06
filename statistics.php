<?php require_once 'header.php'?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use Box\Spout\Common\Entity\Row;
require_once 'data.php';

class Dashboard{

    public $dateFormat = "Y-m-d";
    public $exportdir = "";
    public $parameters = array(
        'start' => '2018-01-01',
        'end' => '2018-12-31',
        'status' => array(
            STATUS_ABSENCNE => true,
            STATUS_SKRIPTA => true
        ),
        'granularity' => 'month',
        'table' => 'all',
        'title' => null
    );
    public $overview = array(
        'active_titles' => 0, // status not specific
        'active_units' => 0, // status specific
        'avg_fully_loaned' => 0, // status specific
        'avg_loaned_units' => 0 // status specific
    );
    public $tableHeader = array();
    public $activeTitles = array();

    function Dashboard(){
        // Loading configuration
        $config = new Config('FEosu261BP/config.ini');
        $format_conf = $config->get('format');
        $file_conf = $config->get('files');

        // check if we got export directory in config
        if ($file_conf['exportdir']){
            $this->exportdir = $file_conf['exportdir'];
        }
        else{
            error_log("Error: The upload directory not specified");
            die();
        }

        // create the upload folder if it doesn't exist
        if (!file_exists($file_conf['exportdir'])) {
            mkdir($file_conf['exportdir'], 0755, true);
        }

        $this->dateFormat = $format_conf['date_format'];

        

        if(!empty($_GET)){
            $this->loadParameters();
            $this->getTimeDataHeader();
            if(isset($_GET['export'])){
                $source = $_SERVER['HTTP_REFERER'];
                // filter the export from request from url so we don't loop
                $redirect = explode("&export", $source)[0];
                $this->export($_GET['export']);
                header("Location: " . $redirect);
                exit();
            }
        }

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
            $this->parameters['title'] = $this->testInput($_GET['title_search']);
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

        $active_titles_rows = $db->select('units', [
            'ADM_REC',
            'STATUS',
            'COUNT' => Medoo::raw('COUNT(UNIT_ID)')
        ], [
            'AND' => [
                'OR' => [
                    "DELETE_DATE" => 0,
                    'DELETE_DATE[>]' => $this->parameters['end']
                ],
                "ACQ_DATE[<=]" => $this->parameters['end'],
                'STATUS' => array_keys($this->parameters['status'], true, true)
            ],
            'GROUP' => ['ADM_REC', 'STATUS']
        ]);

        $active_titles = array();
        foreach($active_titles_rows as $row){
            $adm_rec = (int)$row['ADM_REC'];
                if(!isset($active_titles[$adm_rec])){
                    $active_titles[$adm_rec] = array();
                }
                $active_titles[$adm_rec][(int)$row['STATUS']] = (int) $row['COUNT'];
        }

        $this->overview['active_titles'] = count($active_titles);
        $this->activeTitles = $active_titles;

        $active_units_count = 0;
        foreach($active_titles as $adm_rec => $status_arr){
            foreach($status_arr as $status => $units){
                $active_units_count += $units;
            }
        }
        $this->overview['active_units'] = $active_units_count;

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
        //print_r($this->overview);
        //print_r($active_titles);
        //print_r($active_units_count);
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
    * Renders data table header according to type
    * $type string Type of rendering used
    * $writer reference_object Writer object used for exports
    * $return string The html string for header
    */
    function renderTableHeader($type, &$writer = null) {
        if($type == "HTML"){
            // First row
            $header = "<tr>";
            $headerArr = $this->tableHeader;
            //$size = serialize($data);
            //echo strlen($size) . " bytes" . PHP_EOL;
            foreach($headerArr['information'] as $column => $default){
                $header .= '<th rowspan="2" class="sortable">' . $column . "</th>";
            }
            foreach($headerArr['stats'] as $date => $columns){
                $header .= '<th>' . $date . "</th>";
            }
            $header .= "</tr>";
            // Second row
            $header .= "<tr>";
            foreach($headerArr['stats'] as $date => $columns){
                foreach($columns as $column => $default){
                    if($column == 'STD')
                        continue;
                    $header .= "<th>" . $column . "</th>";
                }
            }
            $header .= "</tr>";
            return $header;
        }
        else if($type == "Excel(XLSX)"){
            $firstRow = array();
            $secondRow = array();
            $rows = array();
            // First row
            $headerArr = $this->tableHeader;
            foreach($headerArr['information'] as $column => $default){
                // empty cell because Spout doesn't know merging
                $firstRow[] = "";
            }
            foreach($headerArr['stats'] as $date => $columns){
                $firstRow[] = $date;
                $firstRow[] = "";
            }
            $rows[] = $firstRow;
            // Second row
            foreach($headerArr['information'] as $column => $default){
                $secondRow[] = $column;
            }
            foreach($headerArr['stats'] as $date => $columns){
                foreach($columns as $column => $default){
                    $secondRow[] = $column;
                }
            }
            $rows[] = $secondRow;
            $writer->addRows($rows);
            /*echo "<pre>";
            print_r($writer);
            echo "</pre>";*/
        }
    }

    function renderTableBody($type, &$writer = null){
        $datas = $this->getData();
        $informationHeader = $this->tableHeader['information'];
        unset($informationHeader[array_search('ADM_REC', $informationHeader)]);
        $statsHeader = $this->tableHeader['stats'];
        if($type == "HTML"){
            $body = "";
            // first summary row
            /*$body .= "<tr>";
            $body .= '<th colspan="2">Celkem titulů</th>';
            $body .= "<td>" . count($datas) . "</td>";
            $body .= "</tr>";*/
            // view for loans
            foreach($datas as $adm_rec => $data){
                $row = "<tr>";
                $row .= "<th>" . $adm_rec . "</th>";
                
                foreach($informationHeader as $column => $default){
                    if($column == 'MIN' || $column == 'MAX'){
                        $row .= '<td class="information">' . $data[$column] . " %</td>";
                    }
                    else{
                        $row .= '<td class="information">' . $data[$column] . "</td>";
                    }
                }
                foreach($statsHeader as $date => $columns){
                    if(isset($data['STATS'][$date])){
                        foreach($columns as $column => $default){
                            if(($data['STATS'][$date]['STD'] * 2 > $data['STATS'][$date]['AVRG'] * 0.4) && $column == 'AVRG')
                                $row .= '<td class="stats std_high" title="STD: ' . $data['STATS'][$date]['STD'] . '">' . $data['STATS'][$date][$column] . " %</td>";
                            else if($column == 'AVRG'){
                                $row .= '<td class="stats">' . $data['STATS'][$date][$column] . " %</td>";
                            }
                        }
                    }
                    else{
                        $row .= '<td class="stats">-</td>';
                        //$row .= '<td class="stats">-</td>';
                    }
                }
                $row .= "</tr>";
                $body .= $row . PHP_EOL;
            }
            return $body;
        }
        else if($type == "Excel(XLSX)"){
            foreach($datas as $adm_rec => $data){
                $row = array();
                $row[] = $adm_rec;
                
                foreach($informationHeader as $column => $default){
                    $row[] = $data[$column];
                }
                foreach($statsHeader as $date => $columns){
                    if(isset($data['STATS'][$date])){
                        foreach($columns as $column => $default){
                            $row[] = $data['STATS'][$date][$column];
                        }
                    }
                    else{
                        $row[] = "0";
                        $row[] = "0";
                    }
                }
                $writer->addRow($row);
            }
        }
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
            $tableHeader['information']['ADM_REC'] = 0;
            $tableHeader['information']['TITLE'] = '';
            $tableHeader['information']['CALLNO'] = '';
            $tableHeader['information']['UNITS'] = 0;
            $tableHeader['information']['MIN'] = 0;
            $tableHeader['information']['MAX'] = 0;
            for($month; ($month->diff($end)->m + ($month->diff($end)->y*12)) > 0; $month->modify('first day of next month')){
                $tableHeader['stats'][$month->format('Y-m')]['AVRG'] = 0;
                $tableHeader['stats'][$month->format('Y-m')]['STD'] = 0;
            }
            $tableHeader['stats'][$end->format('Y-m')]['AVRG'] = 0;
            $tableHeader['stats'][$end->format('Y-m')]['STD'] = 0;
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
        $show_zero_usage = false;
        $filter = null; // array for the select HAVING in case filter is on

        switch($this->parameters['table']){
            case 'all':
                $show_zero_usage = true;
                break;
            case '90':
                $filter = 90;
                break;
            case '10':
                $filter = 10;
                $show_zero_usage = true;
                break;
            default:
                error_log("Unrecognized table filter parameter? " . $this->parameters['table']);
                die("Unrecognized table filter parameter? " . $this->parameters['table']);
        }

        // if user inputs title ADM_REC then search for it no matter the status
        if(empty($this->parameters['title'])){
            $where = array(
                'AND' => [
                    'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
                    'STATUS' => array_keys($this->parameters['status'], true, true)
                ],
                'GROUP' => Medoo::raw('substr(date, 1,7),ADM_REC,status')
            );
        }
        else{
            $where = array(
                'AND' => [
                    'date[<>]' => [$this->parameters['start'], $this->parameters['end']],
                    'usage.ADM_REC' => $this->parameters['title']
                ],
                'GROUP' => Medoo::raw('substr(date, 1,7),ADM_REC,status')
            );
            $show_zero_usage = false;
            $filter = null;
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
           'STD_DEV' => Medoo::raw('STDDEV_POP(loans_count/unit_count) * 100')
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
                $data[$adm_rec]['MIN'] = 0;
                $data[$adm_rec]['MAX'] = 0;
                $data[$adm_rec]['STATS'] = $this->tableHeader['stats'];
            }
            $data[$adm_rec]['STATS'][$row['DATE']]['AVRG'] = round($row['AVRG_LOANED']);
            $data[$adm_rec]['STATS'][$row['DATE']]['STD'] = round($row['STD_DEV'],1);
        }

        unset($select);
        // fill zero values for titles with active units but zero usage
        // these are not pulled with select from usage table (they don't have a record)
        if($show_zero_usage){
            foreach($this->activeTitles as $adm_rec => $status){
                if(!isset($data[$adm_rec])){
                    $data[$adm_rec] = array();
                    $data[$adm_rec]['TITLE'] = $row['TITLE'];
                    $data[$adm_rec]['CALLNO'] = $row['CALLNO'];
                    $data[$adm_rec]['UNITS'] = (int)$row['AVRG_UNITS'];
                    $data[$adm_rec]['STATS'] = $this->tableHeader['stats'];
                }
            }
        }

        // Set up min and max value for the watched period
        foreach($data as $adm_rec => $title){
            $min = 100;
            $max = 0;
            foreach($title['STATS'] as $timepoint => $stats){
                if($stats['AVRG'] < $min){
                    $min = $stats['AVRG'];
                }
                if($stats['AVRG'] > $max){
                    $max = $stats['AVRG'];
                }
            }
            $data[$adm_rec]['MIN'] = $min;
            $data[$adm_rec]['MAX'] = $max;
        }
        if (isset($filter)){
            $data = $this->filterData($data, $filter);
        }
        //echo "<pre>";
        //print_r($select);
        //print_r($this->tableHeader);
        //print_r($data);
        //echo "</pre>";
        return $data;
    }

    /*
    *   Filters the returned data structure according to the filter chosen by the user
    *   $datas array multidimensional data array
    *   return array filtered data array
    */
    function filterData($data, $filter){
        foreach($data as $adm_rec => $title){
            if ($filter == 10){
                if ($title['MAX'] >= $filter)
                    unset($data[$adm_rec]);
            }
            else if ($filter == 90){
                if ($title['MAX'] <= $filter)
                    unset($data[$adm_rec]);
            }
        }
        return $data;
    }

    function export($type){
        if($type == "Excel(XLSX)"){
            
            // generate export filename
            $filename = $this->createFileName($this->parameters, "_") . ".xlsx";
            $filepath = $this->exportdir . "/" . $filename;
            if(!file_exists($filepath)){
                $writer = WriterFactory::create(Type::XLSX);
                $writer->openToFile($filepath);
                // generate header
                $this->renderTableHeader($type, $writer);
                // generate body
                $this->renderTableBody($type, $writer);
                $writer->close();
                $_SESSION['error'] = "Export " . $filename . " uložen do složky s exporty.";
            }
            else{
                $_SESSION['error'] = "Export již existuje ve složce s Exporty.";
            }
        }
    }

    /*
    *   Recursive function for creating the export file name from given parameters
    *   $array array Parameters from the search form
    *   $separator string Separator for each parameter in the filename
    *   Solution reused from https://stackoverflow.com/a/3900091/7364458
    */
    function createFileName($array, $separator){
        $ret = "";

        foreach($array as $item){
            if (is_array($item)){
                $ret .= $this->createFileName($item, $separator) . $separator;
            }
            else{
                // solution for value false
                $ret .= ($item ? $item : "0") . $separator;
            }
        }
        $ret = substr($ret, 0, 0 - strlen($separator));
        return $ret;
    }
}
    

$dashboard = new Dashboard();
?>

<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="GET">
<div id="forms-section">
    <h1>Nastavení</h1>    
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
        <label for="title_search">Vyberte konkrétní jednotku: </label>
        <input type="text" size="50" id="title_search" list="titles-datalist" name="title_search" placeholder="ADM_REC, čár. kód, signatura, název, ISBN" autocomplete="off" value="<?php echo $dashboard->parameters['title'];?>" />
        <datalist id="titles-datalist">
        </datalist>
        <br/>
        <input type="submit" value="Odeslat"/>
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
    <?php
    if(!empty($_GET)){
    ?>
    <input type="submit" name="export" class="menu" value="Excel(XLSX)"/>
    <h1>Data</h1>
    <div id="container">
        <table>
            <thead>
                <?php print_r($dashboard->renderTableHeader("HTML"));?>
            </thead>
            <tbody>
                <?php print_r($dashboard->renderTableBody("HTML"));?>
            </tbody>
        </table>
    </div>
    <?php
    }
    ?>
</div>
</form>
</body>
</html>