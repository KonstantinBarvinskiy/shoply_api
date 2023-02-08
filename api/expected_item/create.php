<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/expected_item.php';

$expected_item = new Expected_item($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->product_id) &&
            isset($data->email)) {
            $expected_item->item["product_id"] = $data->product_id;
            $expected_item->item["email"] = $data->email;
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->attr)) $expected_item->item["attr"] = $data->attr;

        if ($query = $expected_item->create()) {
            if ($query == 'Already exist') {
                http_response_code(400);
                echo json_encode(array("message" => "Already exist"), JSON_UNESCAPED_UNICODE);
                die();
            }

            http_response_code(201);
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
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