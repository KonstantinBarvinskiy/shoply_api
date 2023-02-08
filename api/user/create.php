<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/user.php';

$user = new User($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->elogin)) {
            $user->item["elogin"] = $data->elogin;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No user_id"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (db()->query_value("SELECT `id` FROM `users` WHERE `elogin` = '{$data->elogin}'")) {
            http_response_code(400);
            echo json_encode(array("message" => "Already exist"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if (isset($data->group)) $user->item["group"] = $data->group;
        if (isset($data->type)) $user->item["type"] = $data->type;
        if (isset($data->name)) $user->item["name"] = $data->name;
        if (isset($data->phone)) $user->item["phone"] = '+7'.$data->phone;
        if (isset($data->address)) $user->item["address"] = $data->address;
        if (isset($data->company)) $user->item["company"] = $data->company;
        if (isset($data->inn)) $user->item["inn"] = $data->inn;
        if (isset($data->ogrn)) $user->item["ogrn"] = $data->ogrn;
        if (isset($data->jaddress)) $user->item["jaddress"] = $data->jaddress;
        if (isset($data->bik)) $user->item["bik"] = $data->bik;
        if (isset($data->rsch)) $user->item["rsch"] = $data->rsch;
        if (isset($data->subscribe)) $user->item["subscribe"] = $data->subscribe;


        if ($query = $user->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Create success"), JSON_UNESCAPED_UNICODE);
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
        }
    }

    catch (Exception $e){
        http_response_code(401);
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

else {
    http_response_code(401);
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>