<?php require_once 'header.php'?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'database.php';


class User{

    function User(){
        $login = htmlspecialchars($_POST['name']);
        $pass = $_POST['pass'];
        $level = LEVEL_USER;
        $type = $_POST['type'];

        // verify the type with coming request url
        $source = $_SERVER['HTTP_REFERER'];
        if(!preg_match("/\/" . $type . ".php/i", $source)){
            error_log("The form type does not match the requested action.");
            die("The form type does not match the requested action.");
        }
        if($type == 'register')
            $this->register($login, $pass, $level);
        else
            $this->processLogin();
    }

    function processLogin(){

    }

    function register($login, $password, $level){
        echo "<pre>";
        echo "Registering user: " . $login . PHP_EOL;
        echo "Password: " . $password . PHP_EOL;
        echo "Access level: " . $level . PHP_EOL;
        echo "</pre>";
        $db = Database::getConnection();
        //$db->insert()

    }

    function validate($level = LEVEL_USER){

    }
}

$user = new User();
?>