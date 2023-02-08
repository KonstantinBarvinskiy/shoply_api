<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/feedback.php';

$feedback = new Feedback($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $feedback->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->pid)) $feedback->item["pid"] = $data->pid;
        if (isset($data->rating)) $feedback->item["rating"] = $data->rating;
        if (isset($data->author)) $feedback->item["author"] = $data->author;
        if (isset($data->text)) $feedback->item["text"] = $data->text;

        if (!isset($feedback->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $feedback->update()) {
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