<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

require_once 'file.php';
require_once 'data.php';

$data = new Data();
$data->countUsage(2018);

//$counts = new Counts();
?>