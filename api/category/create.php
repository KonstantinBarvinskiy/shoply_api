<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/category.php';

// создание объекта
$category = new category($db);

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
        if (isset($data->name)) {
            $category->name = $data->name;
        } else {
                        http_response_code(400);
            echo json_encode(array("message" => "No 'name'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->nav)) $category->item["nav"] = $data->nav;
        if (isset($data->name)) $category->item["name"] = $data->name;
        if (isset($data->top)) $category->item["top"] = $data->top;
        else $category->item["top"] = 0;


        if ($query = $category->create()) {
            // устанавливаем код ответа - 200 OK
            http_response_code(201);

            // выводим данные в формате JSON
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
                        http_response_code(400);

            // показать сообщение об ошибке
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