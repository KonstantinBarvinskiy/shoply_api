<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/product.php';

$product = new Product($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $product->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No id"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->price)) $product->product["price"] = $data->price;
        if (isset($data->price_old)) $product->product["price_old"] = $data->price_old;
        if (isset($data->discount)) $product->product["discount"] = $data->discount;
        if (isset($data->remain)) $product->product["remain"] = $data->remain;
        if (isset($data->weight)) $product->product["weight"] = $data->weight;
        if (empty($product->product)) {
            http_response_code(400);
            echo json_encode(array("message" => "No required data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $product->updateVariants()) {
            http_response_code(200);
            echo json_encode(array("message" => "Update success"), JSON_UNESCAPED_UNICODE);
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