<?php
/**
*    The class storing the uploaded files and storing the values into db
*/

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'database.php';

class File{

    private $fileName;
    private static $uploaddir;
    private $fileType;
    private $dataType;
    private static $phpFileUploadErrors = array(
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    );

    /*
    * Constructor
    */
    function File(){
        // Loading configuration
        $config = new Config('FEosu261BP/config.ini');
        $file_conf = $config->get('files');

        // check if we got upload directory in config
        if ($file_conf['uploaddir']){
            if (!self::$uploaddir)
                self::$uploaddir = $file_conf['uploaddir'];
        }
        else{
            error_log("Error: The upload directory not specified");
            die();
        }

        // create the upload folder if it doesn't exist
        if (!file_exists($file_conf['uploaddir'])) {
            mkdir($file_conf['uploaddir'], 0755, true);
        }
    }

    /*
    * Load the file according to the filename specified
    */
    function loadFile(string $filename){
        //code...
    }

    /*
    * Validating the file after upload and moving it to upload directory
    * This will work only in the upload script because of the $_FILES variable
    */
    function validateFile($upload_type){
        
        $uploaded_file = $_FILES[$upload_type];
        if($uploaded_file['size'] !== 0 && $uploaded_file['tmp_name']){
            //extract filetype extension
            $ext = end((explode(".", $uploaded_file['name'])));
            $filename = $upload_type . "-" . date("d-m-Y") . "." . $ext;
            if (!move_uploaded_file($uploaded_file['tmp_name'], self::$uploaddir . "/" . $filename)) {
                error_log("File not validated: " . self::$phpFileUploadErrors[$uploaded_file['error']]);
                die();
            }
        $this->fileType = $ext;
        $this->fileName = $filename;
        $this->dataType = $upload_type;
        }
        else{
            error_log("No file uploaded.");
            die();
        }
    }

    /*
    * Returns the file name
    */
    function getFileName(){
        return $this->fileName;
    }

    /*
    * Function running the app
    */
    function getFilePath(){
        return self::$uploaddir . "/" . $this->fileName;
    }

    /*
    * Returns file type extension from the file
    * WARNING: the extension is retrieved from the uploaded file name
    */
    function getFileType(){
        return $this->fileType;
    }

    /*
    * Returns file type extension from the file
    */
    function getDataType(){
        return $this->dataType;
    }
    
    /*
    * Function running the app
    */
    function renameFile(){
        //code...
    }

    /*
    * Function running the app
    */
    function deleteFile(){
        //code...
    }
}

?>