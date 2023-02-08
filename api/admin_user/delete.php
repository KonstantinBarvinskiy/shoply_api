<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/admin_user.php';

// создание объекта
$admin_user = new admin_user($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if ($id) {

            $admin_user->id = $id;

            $admins = $admin_user->read();
            $num_admins = $admins->rowCount();
            if ($num_admins == 1) {
                // код ответа
                http_response_code(400);

                // показать сообщение об ошибке
                echo json_encode(array("message" => "Нельзя удалять единственного администратора"), JSON_UNESCAPED_UNICODE);
            }

            if ($admin_user->delete()) {
                // устанавливаем код ответа - 200 OK
                http_response_code(200);

                // выводим данные в формате JSON
                echo json_encode(array("message" => "Delete success"), JSON_UNESCAPED_UNICODE);
            }

            else {
                // код ответа
                http_response_code(400);

                // показать сообщение об ошибке
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "No id"), JSON_UNESCAPED_UNICODE);
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