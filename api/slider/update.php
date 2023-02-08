<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/slider.php';

$slider = new Slider($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $slider->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->order)) $slider->item["order"] = $data->order;
        if (isset($data->name)) $slider->item["name"] = $data->name;
        if (isset($data->link)) $slider->item["link"] = $data->link;
        if (isset($data->link_text)) $slider->item["link_text"] = $data->link_text;
        if (isset($data->is_light)) $slider->item["is_light"] = $data->is_light;
        if (isset($data->show)) $slider->item["show"] = $data->show;

        if (!isset($slider->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $slider->update()) {
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