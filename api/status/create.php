<?php

include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/status.php';

// создание объекта
$status = new Status($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->order) and
            isset($data->name) and
            isset($data->color)) {
            $status->item["order"] = $data->order;
            $status->item["name"] = $data->name;
            $status->item["color"] = $data->color;
        } else {
                        http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $status->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
        }

        else {
                        http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
        }
    }

        // если декодирование не удалось, это означает, что JWT является недействительным
    catch (Exception $e){

        // код ответа
                http_response_code(401);

        // сообщение об ошибке
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

// показать сообщение об ошибке, если jwt пуст
else {

    // код ответа
        http_response_code(401);

    // сообщить пользователю что доступ запрещен
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>