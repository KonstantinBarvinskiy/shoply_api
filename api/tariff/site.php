<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if($site = db()->query_first("SELECT * FROM `shoply`.`shoply_sites` WHERE `login` = '{$GLOBALS['config']['db']['db']}'")) {
            unset($site[0],$site[1],$site[2],$site[3],$site[4],$site[5],$site[6],$site[7],$site[8],$site[9]);
            $site['paid_till'] = stupDate($site['paid_till']);
            $site['created'] = stupDate($site['created']);
            http_response_code(200);
            echo json_encode($site, JSON_UNESCAPED_UNICODE);
        }
    }

    catch (Exception $e){
        http_response_code(401);
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

else {
    http_response_code(401);
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>