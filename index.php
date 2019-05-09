<?php
require dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

use PHLAK\Config\Config;
use Medoo\Medoo;

$config = new Config('config.ini');

$db_conf = $config->get('mysql');

$db = new Medoo($db_conf);


print_r($db->info());
?>

