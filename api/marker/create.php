<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/marker.php';

$marker = new Marker($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->name)) {
            $marker->item["name"] = $marker->item["name"] = $data->name;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No name"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->text)) $marker->item["text"] = $data->text;
        if (isset($data->color)) $marker->item["color"] = $data->color;
        if (isset($data->show)) $marker->item["show"] = $data->show;
        else $marker->item["show"] = "Y";

        if ($query = $marker->create()) {
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