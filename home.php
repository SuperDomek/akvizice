<?php
require_once 'header.php';
//$user->checkSession();
?>

<?php
require_once 'application.php';

$application = new Application();
$counts = $application->getCounts();
?>


<?php
if($user->validate(LEVEL_ADMIN)){ ?>
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
        <p>
            <a href="count.php"><button>Přejít</button></a>
        </p>
    </div>
<hr/>
<?php } ?>
    <div id="dashboard">
        <h1>Zobrazení dat</h1>
        <p>
            <a href="statistics.php"><button>Přejít</button></a>
        </p>
    </div>
<hr/>
    <div id="exports">
        <h1>Uložené exporty</h1>
        <ul>
        <?php
        // NEED TO SECURE DOWNLOAD THROUGH download.php
        // SEE HERE https://stackoverflow.com/a/14025030/7364458
        foreach($application->exports as $exportFile => $parameters){
            echo "<li>";
            echo "<a href=\"$application->exportdir/$exportFile\">$exportFile</a>";
            echo "<ul>";
            foreach($parameters as $parameter => $value){
                echo "<li>";
                echo "$parameter: " . $value;
                echo "</li>";
            }
            echo "</ul>";
            echo "</li>";
        }
        ?>
        </ul>
    </div>
</div>
</body>
</html>

