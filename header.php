<!doctype html>

<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first
define('STATUS_ABSENCNE', 4);
define('STATUS_SKRIPTA', 5);
define('STATUS_NOVINKA', 6);
?>

<html lang="cs">
<head>
  <meta charset="utf-8">

  <title>Simple collection use counter</title>
  <meta name="description" content="Collection usage counter">
  <meta name="author" content="Dominik Bláha, blahad@sic.czu.cz">

  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

  <link rel="stylesheet" href="css/styles.css?v=1.0">

</head>
<body>