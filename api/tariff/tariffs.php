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

        if($tariff = db()->rows("SELECT * FROM `shoply`.`shoply_tariff`")) {
            foreach ($tariff as $k=>$t) {
                unset($tariff[$k][0], $tariff[$k][1], $tariff[$k][2], $tariff[$k][3], $tariff[$k][4], $tariff[$k][5], $tariff[$k][6]);
            }
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