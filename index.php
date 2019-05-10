<!doctype html>

<?php
require dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first
require 'application.php';

$application = new Application();
$counts = $application->getCounts();
?>

<html lang="cs">
<head>
  <meta charset="utf-8">

  <title>Simple collection use counter</title>
  <meta name="description" content="Collection usage counter">
  <meta name="author" content="Dominik Bláha, blahad@sic.czu.cz">

  <!--<link rel="stylesheet" href="css/styles.css?v=1.0">-->

</head>

<body>
    <div id="titles">
        <h1>Tituly</h1>
        <p>Počet záznamů: <?php echo $counts['titles'];?></p>
        <form action="upload.php">
            <input type="hidden" value="titles"/>
            <label for="titles">Vyberte soubor k aktualizaci seznamu titulů:</label>
            <input type="file" name="titles" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát tituly"/>
        </form>
    </div>
<hr/>
    <div id="units">
        <h1>Jednotky</h1>
        <p>Počet záznamů: <?php echo $counts['units'];?></p>
        <form action="upload.php">
            <input type="hidden" value="units"/>
            <label for="units">Vyberte soubor k aktualizaci seznamu jednotek:</label>
            <input type="file" name="units" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát jednotky"/>
        </form>
    </div>
<hr/>
    <div id="loans">
        <h1>Historie výpůjček</h1>
        <p>Počet záznamů: <?php echo $counts['loans'];?></p>
        <form action="upload.php">
            <input type="hidden" value="loans"/>
            <label for="units">Vyberte soubor k aktualizaci seznamu výpůjček:</label>
            <input type="file" name="loans" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát historii výpůjček"/>
        </form>
    </div>

</body>
</html>

