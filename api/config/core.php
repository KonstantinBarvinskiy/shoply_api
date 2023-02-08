<?php
// показывать сообщения об ошибках
error_reporting(E_ALL);

// установить часовой пояс по умолчанию
date_default_timezone_set('Europe/Moscow');

$_SERVER['SERVER_NAME'] = str_replace('www.','',$_SERVER['SERVER_NAME']);
define('DIR', '/var/www/web/sites/'.$_SERVER['SERVER_NAME'].'/');

include DIR.'config.php';

// переменные, используемые для JWT
$key = $GLOBALS['config']['jwt']['key'];
$iss = "http://{$_SERVER['SERVER_NAME']}";
$aud = "http://{$_SERVER['SERVER_NAME']}";
$iat = time();
$nbf = time();
$exp = time() + (24 * 60 * 60);

include_once '../libs/additionalFunctions.php';
include_once '../libs/db.php';
include_once '../libs/SimpleData.php';
include_once '../config/headers_api.php';
include_once '../config/database.php';

$db_server = $GLOBALS['config']['db']['server'];
$db_name = $GLOBALS['config']['db']['db'];
$db_user = $GLOBALS['config']['db']['user'];
$db_pass = $GLOBALS['config']['db']['password'];

$database = new Database($db_server, $db_name, $db_user, $db_pass);
$db = $database->getConnection();

//$lib = scandir(DIR.'/admin/lib',1);
//foreach($lib as $file) {
//    if(substr($file,-3,3) == 'php') include DIR.'/admin/lib/'.$file;
//}
//include_once DIR.'/system/lib/additionalFunctions.php';
//include_once DIR.'/system/lib/systemFunctions.php';
//include_once DIR.'/system/lib/media.php';

$MySQL_obj = new MySQL();
function db() {
    global $MySQL_obj;
    return clone $MySQL_obj;
}

include '../libs/SCurl.php';
$selectelStorage = new SelectelStorage($GLOBALS['config']['cs']['login'], $GLOBALS['config']['cs']['password']);
if($GLOBALS['config']['cs']['container']) $container = $selectelStorage->getContainer($GLOBALS['config']['cs']['container']);
include '../libs/Images.php';
include '../libs/media.php';
$img = new Images();
function imget() {
    global $img;
    return clone $img;
}
//include '../libs/MediaFiles.php';
//$mfile = new MediaFiles();
//function mfiles() {
//    global $mfile;
//    return clone $mfile;
//}

include_once '../libs/php-jwt-master/src/BeforeValidException.php';
include_once '../libs/php-jwt-master/src/ExpiredException.php';
include_once '../libs/php-jwt-master/src/SignatureInvalidException.php';
include_once '../libs/php-jwt-master/src/JWT.php';

?>