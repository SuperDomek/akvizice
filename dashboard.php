<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'data.php';

define('STATUS_SKRIPTA', 4);
define('STATUS_ABSENCNE', 5);

class Dashboard{

    private $dateFormat = "Y-m-d";
    private $parameters = array(
        'start' => '',
        'end' => '',
        'status' => array(
            STATUS_SKRIPTA => false,
            STATUS_ABSENCNE => false
        ),
        'granularity' => 'month'
    );

    function Dashboard(){
        // Loading configuration
        $config = new Config('config.ini');
        $format_conf = $config->get('format');
        $this->dateFormat = $format_conf['dateformat'];

    }

    function loadParameters(){
        if ($_SERVER["REQUEST_METHOD"] == "GET") {
            $start = $this->testInput($_GET["date_start"]);
            $end = $this->testInput($_GET["date_end"]);
            $website = $this->testInput($_GET["website"]);
            $comment = $this->testInput($_GET["comment"]);
            $gender = $this->testInput($_GET["gender"]);
          }
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

    function getOverview(){

    }

}
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
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>" method="GET">
        <label for="date_start">Vyberte časový úsek k porovnání:</label>
            <input type="date" name="date_start"/>
            <input type="date" name="date_end"/>
        <br/>
        <input type="submit" value="Odeslat"/>
    </form>
</div>
<hr/>
<div id="overview">
    <h1>Jednotky</h1>
    <p>Počet aktivních titulů: <?php echo $counts['units']['total'];?></p>
    <p>Počet vypůjčených jednotek: <?php echo $counts['units']['active'];?></p>
    <p>Počet 100% vypůjčených jednotek: <?php echo $counts['units']['active'];?></p>
    </div>
<hr/>
<div id="data">
    <table>

    </table>
</div>
</body>
</html>