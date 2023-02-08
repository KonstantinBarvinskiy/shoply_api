<?php

include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/user.php';

// создание объекта
$user = new User($db);

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
            $user->id = $data->id;
        } else {
            http_response_code(401);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->group)) $user->item["group"] = $data->group;
        if (isset($data->type)) $user->item["type"] = $data->type;
        if (isset($data->elogin)) $user->item["elogin"] = $data->elogin;
        if (isset($data->name)) $user->item["name"] = $data->name;
        if (isset($data->email)) $user->item["email"] = $data->email;
        if (isset($data->phone)) $user->item["phone"] = '+7'.$data->phone;
        if (isset($data->address)) $user->item["address"] = $data->address;
        if (isset($data->company)) $user->item["company"] = $data->company;
        if (isset($data->inn)) $user->item["inn"] = $data->inn;
        if (isset($data->ogrn)) $user->item["ogrn"] = $data->ogrn;
        if (isset($data->jaddress)) $user->item["jaddress"] = $data->jaddress;
        if (isset($data->bik)) $user->item["bik"] = $data->bik;
        if (isset($data->rsch)) $user->item["rsch"] = $data->rsch;
        if (isset($data->subscribe)) $user->item["subscribe"] = $data->subscribe;
        if (isset($data->lastenter)) $user->item["lastenter"] = $data->lastenter;
        if (isset($data->created)) $user->item["created"] = $data->created;
        if (isset($data->modified)) $user->item["modified"] = $data->modified;

        if (!isset($user->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $user->update()) {
            // устанавливаем код ответа - 200 OK
            http_response_code(200);

            // выводим данные в формате JSON
            echo json_encode(array("message" => "Update success"), JSON_UNESCAPED_UNICODE);
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