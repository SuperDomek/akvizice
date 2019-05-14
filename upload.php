<?php
require_once dirname(__DIR__) . '/akvizice/vendor/autoload.php'; // It must be called first

require_once 'file.php';
require_once 'data.php';

$uploaded_file = new File();

$uploaded_file->validateFile($_POST['file']);

print_r($uploaded_file->getFileName());
print_r($uploaded_file->getFilePath());


?>