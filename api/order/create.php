<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order.php';

// создание объекта
$order = new Order($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->admin_id)) {
            $order->admin_id = $data->admin_id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'admin_id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->admin_id)) $order->order["admin_id"] = $data->admin_id;
//        if (isset($data->name)) $order->order["name"] = $data->name;
//        if (isset($data->mail)) $order->order["mail"] = $data->mail;
//        if (isset($data->phone)) $order->order["phone"] = $data->phone;
//        if (isset($data->address)) $order->order["address"] = $data->address;
//        if (isset($data->complete)) $order->order["complete"] = $data->complete;
//        if (isset($data->comment)) $order->order["comment"] = $data->comment;
//        if (isset($data->delivery_type)) $order->order["delivery_type"] = $data->delivery_type;
//        if (isset($data->delivery_cost)) $order->order["delivery_cost"] = $data->delivery_cost;
//        if (isset($data->point_id)) $order->order["point_id"] = $data->point_id;
//        if (isset($data->delivery)) $order->order["delivery"] = $data->delivery;
//        if (isset($data->pickpoint_id)) $order->order["pickpoint_id"] = $data->pickpoint_id;
//        if (isset($data->dispatch)) $order->order["dispatch"] = $data->dispatch;
//        if (isset($data->price)) $order->order["price"] = $data->price;
//        if (isset($data->promo_code)) $order->order["promo_code"] = $data->promo_code;
//        if (isset($data->paymethod)) $order->order["paymethod"] = $data->paymethod;
//        if (isset($data->status)) $order->order["status"] = $data->status;
//        if (isset($data->paid)) $order->order["paid"] = $data->paid;

        if ($query = $order->create()) {
            http_response_code(201);
            echo json_encode(array("message" => "Create success", "id" => $query), JSON_UNESCAPED_UNICODE);
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