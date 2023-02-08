<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;
//print_r($_SERVER);
include_once '../objects/admin_user.php';

// создание объекта 'User'
$admin_user = new admin_user($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// устанавливаем значения
$admin_user->email = $data->email;
$email_exists = $admin_user->emailExists();

// существует ли электронная почта и соответствует ли пароль тому, что находится в базе данных
if ( $email_exists && password_verify($data->password, $admin_user->password) ) {

    $token = array(
        "iss" => $iss,
        "aud" => $aud,
        "iat" => $iat,
        "nbf" => $nbf,
        "exp" => $exp,
        "data" => array(
            "id" => $admin_user->id,
            "email" => $admin_user->email
        )
    );

    $admin_user->login();

    // код ответа
    http_response_code(200);

    // создание jwt
    $jwt = JWT::encode($token, $key);
    echo json_encode(
        array(
            "message" => ("Успешный вход в систему."),
            "jwt" => $jwt
        ), JSON_UNESCAPED_UNICODE
    );
}

// Если электронная почта не существует или пароль не совпадает,
// сообщим пользователю, что он не может войти в систему
else {

//    new headers_api("POST");
    http_response_code(401);

    // сказать пользователю что войти не удалось
    echo json_encode(array("message" => "Ошибка входа."), JSON_UNESCAPED_UNICODE);
}

$filelog = 'api.txt';
$write = '';
$write .= file_get_contents($filelog);
//    $write .= 'Запрос от '.date("Y-m-d H:i:s :\r\n");
$write .= json_encode(
    json_decode(file_get_contents("php://input"), true));
$write .= "\r\n------------------------------------------------\r\n";
file_put_contents($filelog, $write);

?>