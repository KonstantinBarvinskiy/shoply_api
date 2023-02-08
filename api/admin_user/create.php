<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/admin_user.php';
$admin_user = new Admin_user($db);

include_once '../libs/ZFmail.php';

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
        if (isset($data->password) AND isset($data->email)) {
            $admin_user->item["password"] = $data->password;
            $admin_user->item["email"] = $admin_user->email = $data->email;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if ($admin_user->emailExists()) {
            http_response_code(400);
            echo json_encode(array("message" => "This email already exist"), JSON_UNESCAPED_UNICODE);
            die();
        }
        checkPassword($data->password, $err);
        if (!empty($err)) {
            http_response_code(400);
            echo json_encode($err, JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->login)) $admin_user->item["login"] = $data->login;
        if (isset($data->type)) $admin_user->item["type"] = $data->type;
        if (isset($data->access)) $admin_user->item["access"] = $data->access;
        if (isset($data->name)) $admin_user->item["name"] = $data->name;
        if (isset($data->post)) $admin_user->item["post"] = $data->post;
        if (isset($data->color)) $admin_user->item["color"] = $data->color;

        if ($query = $admin_user->create()) {
            $mail = new ZFmail($data->email, 'noreply@' . $_SERVER['HTTP_HOST'], 'Вы зарегистрированы в системе управления сайтом ' . $_SERVER['HTTP_HOST'], "Система управления сайтом: http://{$_SERVER['HTTP_HOST']}/admin\nЛогин: {$data->email}\nПароль: {$data->password}\n");
            $mail->send();

            http_response_code(201);
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
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