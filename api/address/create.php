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
        if (isset($data->user_id)) {
            $address->item["user_id"] = $address->item["user_id"] = $data->user_id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No user_id"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->city) &&
            isset($data->street) &&
            isset($data->house)) {
            $address->item["city"] = $data->city;
            $address->item["street"] = $data->street;
            $address->item["house"] = $data->house;
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->flat)) $address->item["flat"] = $data->flat;

        if ($query = $address->create()) {
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