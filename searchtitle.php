<?php //require_once 'header.php' -- cannot return the header with json
require_once 'database.php';?>

<?php

use PHLAK\Config\Config;
use Medoo\Medoo;

//get the q parameter from URL
$q=$_GET["q"];
$hint = "";
$db = Database::getConnection();

//if query is at least 3 chars, start search
if (isset($_GET["q"]) && strlen($q)>=3) {
    $select = $db->select('units',[
        '[>]titles' => 'ADM_REC'
    ],[
        'units.ADM_REC',
        'BARCODE' => Medoo::raw('MAX(units.BARCODE)'),
        'CALL_NO' => Medoo::raw('MIN(units.CALL_NO)'),
        'TITLE' => Medoo::raw('MAX(titles.TITLE)'),
        'ISBN' => Medoo::raw('MAX(titles.ISBN)')
    ],[
        'OR' => [
            'units.ADM_REC[REGEXP]' => "[0]+$q.*",
            'units.BARCODE[~]' => $q,
            'units.CALL_NO[~]' => $q,
            'titles.TITLE[~]' => $q,
            'titles.ISBN[~]' => $q
        ],
        'GROUP' => ['ADM_REC']
    ]);
    //$hint = $select;
    $hint = array();
    foreach($select as $row){
        $adm_rec = (int)$row['ADM_REC'];
        if(!isset($hint[$adm_rec])){
            $hint[$adm_rec] = array();
            $hint[$adm_rec]['TITLE'] = explode("/", $row['TITLE'])[0];
            $hint[$adm_rec]['BARCODE'] = trim($row['BARCODE']);
            $hint[$adm_rec]['CALL_NO'] = $row['CALL_NO'];
            $hint[$adm_rec]['ISBN'] = explode(" ", $row['ISBN'])[0];
        }
    }
}

// Set output to "no suggestion" if no hint was found
// or to the correct values
if ($hint=="") {
    $response="nenalezeno";
} else {
    $response=$hint;
}

//output the response
header('Content-Type: application/json');
echo json_encode($response);
?>