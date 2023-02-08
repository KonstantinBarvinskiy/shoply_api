<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/expected_item.php';

$expected_item = new Expected_item($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if ($id) {
            $expected_item->id = $id;
            if ($expected_item->delete()) {
                http_response_code(200);
                echo json_encode(array("message" => "Delete success"), JSON_UNESCAPED_UNICODE);
            }

            else {
                http_response_code(400);
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "No id"), JSON_UNESCAPED_UNICODE);
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