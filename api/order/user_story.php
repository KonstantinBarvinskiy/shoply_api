<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order.php';

// создание объекта
$order = new Order($db);

// получаем данные
$data = json_decode(json_encode($_GET), false);

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->user_id)) $order->user_id = $data->user_id;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'user_id'"), JSON_UNESCAPED_UNICODE);
            die();
        }

        // создание
        if($stat = $order->userStory()) {

            $word = morph($stat['orders'], "заказ", "заказа", "заказов");
            $stat['word'] = "{$word}";

            http_response_code(200);
            echo json_encode($stat);

        }
        else {
            http_response_code(204);
            echo json_encode(array("message" => "Нет заказов"), JSON_UNESCAPED_UNICODE);
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