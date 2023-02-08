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
        if (isset($data->id)) {
            $expected_item->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->user_id)) $expected_item->item["user_id"] = $data->user_id;
        if (isset($data->main)) $expected_item->item["main"] = $data->main;
        if (isset($data->city)) $expected_item->item["city"] = $data->city;
        if (isset($data->street)) $expected_item->item["street"] = $data->street;
        if (isset($data->house)) $expected_item->item["house"] = $data->house;
        if (isset($data->flat)) $expected_item->item["flat"] = $data->flat;
        if ($expected_item->item["main"] == 'Y') $expected_item->main = $expected_item->item["main"];

        if (!isset($expected_item->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $expected_item->update()) {
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