<?php require_once 'header.php';?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;

require_once 'database.php';


class User{

    public $user = "";

    function User(){
        $db = Database::getConnection();

        if($_SERVER['REQUEST_METHOD'] == 'POST'){ //processing form
            $source = $_SERVER['HTTP_REFERER'];
            $login = $_POST['name'];
            $pass = $_POST['pass'];
            $level = LEVEL_USER;
            $type = $_POST['type'];
            
            // verify the type with coming request url
            if(!isset($_SERVER['HTTP_REFERER'])
            || !preg_match("/^http[s]?:\/\/" . $_SERVER['SERVER_NAME'] . "\/.*" . $type . ".php/i", $source)){
                error_log("Error: The form type does not match the requested action.");
                $_SESSION['error'] = "Error: The form type does not match the requested action.";
                header("Location: " . $source);
                exit();
            }
            if(isset($_POST['pass_check'])){
                $pass_check = $_POST['pass_check'];
                if($pass != $pass_check){
                    error_log("Error: Second password doesn't match.");
                    $_SESSION['error'] = "Error: Second password doesn't match.";
                    header("Location: " . $source);
                }
            }

            switch($type){
                case 'register':
                    $this->register($login, $pass, $level);
                    break;
                case 'login':
                    $this->processLogin($login, $pass);
                    break;
                default:
                    error_log("Error: Unknown form.");
                    $_SESSION['error'] = "Error: Unknown form.";
                    header("Location: " . $source);
            }
            
            header("Location: index.php");
            
        }
    }

    function processLogin($login, $password){
        
        $db = Database::getConnection();

        $user = $db->get('users', [
            'login', 'hash', 
        ], [
            'login' => $login,
        ]);
        
        if(!empty($user)){
            if(password_verify($password, $user['hash'])){
                $_SESSION['user_login'] = $login;
            }
            else{
                error_log("Error: Wrong password.");
                $_SESSION['error'] = "Error: Wrong password.";
            }
        }
        else{
            error_log("Error: User " . $user . "doesn't exist.");
            $_SESSION['error'] = "Error: User " . $user . "doesn't exist.";
        }

        /*echo "<pre>";
        print_r($user);
        echo "Logging in user: " . $login . PHP_EOL;
        echo "Password: " . $password . PHP_EOL;
        echo "Logged in user: " . $_SESSION['user_login'] . PHP_EOL;
        echo "</pre>";*/
    }

    function register($login, $password, $level){
        $db = Database::getConnection();

        // check if user exists
        if($db->has('users', [
            'login' => $login
        ])){
            error_log("Error: User already exists.");
            $_SESSION['error'] = "Error: User already exists.";
            header("Location: " . $source);
        }

        // generate hash
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $db->insert('users', [
            'login' => $login,
            'hash' => $hash,
            'level' => $level
        ]);

        /*echo "<pre>";
        echo "Registering user: " . $login . PHP_EOL;
        echo "Password: " . $password . PHP_EOL;
        echo "Access level: " . $level . PHP_EOL;
        echo "Hash: " . $hash . PHP_EOL;
        echo "</pre>";*/
    }

    function checkSession(){
        $db = Database::getConnection();
        if(isset($_SESSION['user_login'])){
            $user_session = $_SESSION['user_login'];
            $user_db = $db->get('users', [
                'login'
            ], [
                'login' => $user_session
            ]);

            if(empty($user_db)){
                $_SESSION['error'] = "Error: Unknown user.";
                header("Location: login.php");
                exit();
            }
            else{
                $this->user = $user_db['login'];
            }
        }
        // do not redirect from login or register page
        elseif(strpos($_SERVER['PHP_SELF'], "login") === FALSE && strpos($_SERVER['PHP_SELF'], "register") === FALSE){
            $_SESSION['error'] = "Error: User not logged in.";
            header("Location: login.php");
        }
    }

    function validate($level = LEVEL_USER){
        $user = $this->user;
        $db = Database::getConnection();
        $user_db = $db->get('users', [
            'login',
            'level'
        ], [
            'login' => $user
        ]);
        if($user_db['level'] <= $level){
            return true;
        }
        else
            return false;
    }
}

$user = new User();

?>