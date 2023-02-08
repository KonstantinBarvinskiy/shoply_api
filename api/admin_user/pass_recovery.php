<?php
include_once '../config/core.php';
new headers_api();

include_once '../objects/admin_user.php';
$admin_user = new admin_user($db);

include_once '../libs/ZFmail.php';

$data = json_decode(file_get_contents("php://input"));

$admin_user->email = $data->email;
$email_exists = $admin_user->emailExists();

if ($email_exists) {

    $admin_user->item['password'] = $new_pass = pass_gen(8);

    if ($admin_user->update()) {
        $mail = new ZFmail($admin_user->email,
            'noreply@'.$_SERVER['SERVER_NAME'],
            'Восстановление пароля', "Ваш новый пароль для входа в систему управления сайтом <a href='http://{$_SERVER['SERVER_NAME']}/admin'>{$_SERVER['SERVER_NAME']}</a> : {$new_pass}",
            true);
        $mail->send();

        http_response_code(200);
        echo json_encode(array("message" => "New password send to mail"), JSON_UNESCAPED_UNICODE);
    }
} else {
    http_response_code(400);
    echo json_encode(array("message" => "This user doesn't exist"), JSON_UNESCAPED_UNICODE);
}