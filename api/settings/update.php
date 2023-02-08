<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/settings.php';

$settings = new Settings($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"), JSON_UNESCAPED_UNICODE);
            die();
        } else {
            $settings->filter = json_decode(json_encode($data), true);
        }

        if ($query = $settings->update()) {
            http_response_code(200);
            echo json_encode(array("message" => "Update success"), JSON_UNESCAPED_UNICODE);
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
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