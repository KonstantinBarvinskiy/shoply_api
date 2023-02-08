<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/order_item.php';

$order_item = new order_item($db);

$data = json_decode(file_get_contents("php://input"));

echo date_diff(1604862000, 1596308400);
die();



$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->order)) $order_item->order = $data->order;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No order"),JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $order_item->recountCall()) {
            http_response_code(201);
            echo json_encode($query,JSON_UNESCAPED_UNICODE);
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."),JSON_UNESCAPED_UNICODE);
        }
    }

    catch (Exception $e){
        http_response_code(401);
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ));
    }
}

else {
    http_response_code(401);
    echo json_encode(array("message" => "Доступ закрыт."));
}
?>