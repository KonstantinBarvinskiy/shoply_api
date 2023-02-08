<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/product.php';

$product = new Product($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if($product->getAttrNames()) {
            $stmt = $product->getAttrNames();
            $num = $stmt->rowCount();
            if ($num>0) {
                $products_arr=array();
                $products_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $product_item=array(
                        "id" => $id,
                        "name" => $name
                    );
                    array_push($products_arr["records"], $product_item);
                }
                http_response_code(200);
                echo json_encode($products_arr, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Атрибуты не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить атрибуты"), JSON_UNESCAPED_UNICODE);
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