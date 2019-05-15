<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

require_once 'file.php';
require_once 'data.php';

$uploaded_file = new File();

$uploaded_file->validateFile($_POST['file']);

echo "<pre>";
print_r($uploaded_file->getFileName());
print_r($uploaded_file->getFilePath());

$data = new Data();
$data->processFile($uploaded_file);
echo "<pre>";

?>