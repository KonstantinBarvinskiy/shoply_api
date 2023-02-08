<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/address.php';

$address = new Address($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $address->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->user_id)) $address->item["user_id"] = $data->user_id;
        if (isset($data->main)) $address->item["main"] = $data->main;
        if (isset($data->city)) $address->item["city"] = $data->city;
        if (isset($data->street)) $address->item["street"] = $data->street;
        if (isset($data->house)) $address->item["house"] = $data->house;
        if (isset($data->flat)) $address->item["flat"] = $data->flat;
        if ($address->item["main"] == 'Y') $address->main = $address->item["main"];

        if (!isset($address->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $address->update()) {
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