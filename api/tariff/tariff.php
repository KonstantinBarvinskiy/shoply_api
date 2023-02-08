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

        $site = db()->query_first("SELECT * FROM `shoply`.`shoply_sites` WHERE `login` = '{$GLOBALS['config']['db']['db']}'");
        if($tariff = db()->query_first("SELECT * FROM `shoply`.`shoply_tariff` WHERE `id` = '{$site['tariff']}'")) {
            unset($tariff[0],$tariff[1],$tariff[2],$tariff[3],$tariff[4],$tariff[5],$tariff[6]);
            http_response_code(200);
            echo json_encode($tariff, JSON_UNESCAPED_UNICODE);
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