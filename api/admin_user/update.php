<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/admin_user.php';

// создание объекта 'User'
$admin_user = new admin_user($db);

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
            $admin_user->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->id)) $admin_user->item["id"] = $data->id;
        if (isset($data->login)) $admin_user->item["login"] = $data->login;
        if (isset($data->password) and !empty($data->password)) {
            checkPassword($data->password, $err);
            if (!empty($err)) {
                http_response_code(400);
                echo json_encode($err, JSON_UNESCAPED_UNICODE);
                die();
            }
            $admin_user->item["password"] = $data->password;
        }
        if (isset($data->type)) $admin_user->item["type"] = $data->type;
        if (isset($data->access)) $admin_user->item["access"] = $data->access;
        if (isset($data->name)) $admin_user->item["name"] = $data->name;
        if (isset($data->post)) $admin_user->item["post"] = $data->post;
        if (isset($data->email)) $admin_user->item["email"] = $data->email;
        if (isset($data->color)) $admin_user->item["color"] = $data->color;

        if (!isset($admin_user->item)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"));
            die();
        }

        if ($query = $admin_user->update()) {
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