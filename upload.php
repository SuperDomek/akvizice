<?php require_once 'header.php';?>

<?php


require_once 'file.php';
require_once 'data.php';

$uploaded_file = new File();

$uploaded_file->validateFile($_POST['file']);

/*echo "<pre>";
print_r($uploaded_file->getFileName());
print_r($uploaded_file->getFilePath());*/

$data = new Data();
$data->processFile($uploaded_file);

?>