<!doctype html>

<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first
require_once 'application.php';

$application = new Application();
$counts = $application->getCounts();
?>

<html lang="cs">
<head>
  <meta charset="utf-8">

  <title>Simple collection use counter</title>
  <meta name="description" content="Collection usage counter">
  <meta name="author" content="Dominik Bláha, blahad@sic.czu.cz">

  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

  <!--<link rel="stylesheet" href="css/styles.css?v=1.0">-->

</head>

<body>
<div id="forms-section">
    <div id="titles">
        <h1>Tituly</h1>
        <p>Počet záznamů: <?php echo $counts['titles']['total'];?></p>
        <p>Počet titulů s jednotkami: <?php print_r($counts['titles']['active']);?></p>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="file" value="titles"/>
            <label for="titles">Vyberte soubor k aktualizaci seznamu titulů <i class="fas fa-info" title="File columns format:ADM_REC|CALLNO|TITLE|ISBN|AUTHOR &#013;case-insensitive and order-insensitive; encoding UTF-8"></i></label>
            <br/>
            <input type="file" name="titles" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát tituly"/>
        </form>
    </div>
<hr/>
    <div id="units">
        <h1>Jednotky</h1>
        <p>Počet záznamů: <?php echo $counts['units']['total'];?></p>
        <p>Počet aktivních jednotek: <?php echo $counts['units']['active'];?></p>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="file" value="units"/>
            <label for="units">Vyberte soubor k aktualizaci seznamu jednotek <i class="fas fa-info" title="File columns format:ADM_REC|unit_id|status|acq_date|delete_date"></i></label>
            <br/>
            <input type="file" name="units" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát jednotky"/>
        </form>
    </div>
<hr/>
    <div id="loans">
        <h1>Historie výpůjček</h1>
        <p>Počet záznamů: <?php echo $counts['loans'];?></p>
        <form action="upload.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="file" value="loans"/>
            <label for="units">Vyberte soubor k aktualizaci seznamu výpůjček <i class="fas fa-info" title="File columns format:timestamp|ADM_REC|unit_id|loan_date|return_date"></i></label>
            <br/>
            <input type="file" name="loans" accept=".csv, .xlsx"/>
            <br/>
            <input type="submit" value="Nahrát historii výpůjček"/>
        </form>
    </div>
<hr/>
    <div id="usage">
        <h1>Výpočet využívanosti</h1>
        <p>Počet záznamů: <?php echo $counts['usage'];?></p>
        <form action="count.php" method="POST">
            <input type="submit" value="Přepočítat využívanost"/>
        </form>
    </div>
</div>
</body>
</html>

