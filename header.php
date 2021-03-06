<?php
session_start();
if($_SERVER['SERVER_NAME'] == 'localhost')
  require_once $_SERVER['DOCUMENT_ROOT'] . '/akvizice/vendor/autoload.php'; // It must be called first
else
  require_once $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php'; // It must be called first
define('STATUS_ABSENCNE', 4);
define('STATUS_SKRIPTA', 5);
define('STATUS_NOVINKA', 6);

define('LEVEL_ADMIN', 0x1);
define('LEVEL_USER', 0x10);

require_once 'user.php';
if(isset($user)){
  $user->checkSession();
}
/*echo "<pre>";
print_r($_SERVER);
echo "</pre>";*/
?>
<!doctype html>

<html lang="cs">
<head>
<meta http-equiv="content-type"
  content="text/html; charset=utf-8">

  <title>Simple collection use counter</title>
  <meta name="description" content="Collection usage counter">
  <meta name="author" content="Dominik Bláha, blahad@sic.czu.cz">

  <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.7.0/css/all.css" integrity="sha384-lZN37f5QGtY3VHgisS14W3ExzMWZxybE1SJSEsQp9S+oqd12jhcu+A56Ebc1zFSJ" crossorigin="anonymous">

  <link rel="stylesheet" href="css/styles.css?v=1.1">

  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>

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
  function hideLoader(){
    document.getElementById("loader").style.display = "none";
  }

$(document).ready(function() {
  $("#title_search").keyup(function() {
  var dataList = $('#titles-datalist');
  var input = $('#title_search');
  if (input.val().length >= 3){ // start searching from 3 chars and more
  // Create a new XMLHttpRequest.
    $.ajax({
      type: "GET",
      dataType: 'json',
      url: "searchtitle.php",
      data: {
        q: input.val()
      },
      success: function(json) {
        dataList.empty(); //clearing the datalist after each search
        $.each(json, function (idx, title){
          var option = $("<option/>").text(idx + ' | ' + title.TITLE + ' | ' + title.CALL_NO + ' | ' + title.BARCODE + ' | ' + title.ISBN).val(idx);
          dataList.append(option);
        });
      }
    });
  }
  });
});
</script>
</head>
<body onLoad="hideLoader();">
<!-- Loader -->
<div id="loader"></div>
<!-- Error MSG -->
<div id="error_msg" class="error" <?php echo isset($_SESSION['error'])  ? "style=\"display:block;\"" :"" ; ?> >
  <p>
  <?php
  if (isset($_SESSION['error'])) {
    echo $_SESSION['error'] . PHP_EOL;
    unset($_SESSION['error']);
  }
  ?>
  </p>
</div>
<!-- Menu -->
<?php
  if(isset($_SESSION['user_login'])){
    echo '<a href="logout.php" class="menu"><button>Odhlásit</button></a>';
  }
?>
<a href="index.php" class="menu"><button>Domů</button></a>