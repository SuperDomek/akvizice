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

  <link rel="stylesheet" href="css/styles.css?v=1.1">

  
  <script type="text/javascript">
    // solution for table sort
    // reused from https://stackoverflow.com/a/49041392/7364458
    // and https://stackoverflow.com/a/53880407/7364458
    document.addEventListener('DOMContentLoaded', function () {
    const getCellValue = (tr, idx) => tr.children[idx].innerText || tr.children[idx].textContent;
    const comparer = (idx, asc) => (a, b) => ((v1, v2) => v1 !== '' && v2 !== '' && !isNaN(v1) && !isNaN(v2) ? v1 - v2 : v1.toString().localeCompare(v2))(getCellValue(asc ? a : b, idx), getCellValue(asc ? b : a, idx));
    // do the work...
    document.querySelectorAll('.sortable').forEach(th => th.addEventListener('click', (() => {
        const table = th.closest('table');
        const tbody = table.querySelector('tbody');
        Array.from(tbody.querySelectorAll('tr'))
            .sort(comparer(Array.from(th.parentNode.children).indexOf(th), this.asc = !this.asc))
            .forEach(tr => tbody.appendChild(tr));
    })));
}, false);
  </script>
</head>
<body>