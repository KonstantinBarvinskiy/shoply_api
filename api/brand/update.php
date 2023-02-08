<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/brand.php';

// создание объекта
$brand = new Brand($db);

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
        if (isset($data->id)) {
            $brand->id = $data->id;
        } else {
                        http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->order)) $brand->item["order"] = $data->order;
        if (isset($data->name)) $brand->item["name"] = $data->name;
        if (isset($data->nav)) $brand->item["nav"] = $data->nav;
        if (isset($data->text)) $brand->item["text"] = $data->text;
        if (isset($data->show)) $brand->item["show"] = $data->show;
        if (isset($data->deleted)) $brand->item["deleted"] = $data->deleted;
        if (isset($data->show_main)) $brand->item["show_main"] = $data->show_main;
        if (isset($data->href_official)) $brand->item["href_official"] = $data->href_official;

        if (!isset($brand->item)) {
                        http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $brand->update()) {
            // устанавливаем код ответа - 200 OK
            http_response_code(200);

            // выводим данные в формате JSON
            echo json_encode(array("message" => "Update success"), JSON_UNESCAPED_UNICODE);
        }

        // сообщение, если не удается обновить данные
        else {
                        http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
        }
    }

        // если декодирование не удалось, это означает, что JWT является недействительным
    catch (Exception $e){
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
        http_response_code(401);

    // сообщить пользователю что доступ запрещен
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>