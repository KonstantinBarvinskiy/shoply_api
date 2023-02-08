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
        if (isset($data->id)) $product->id = $data->id;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if($product->getVariants()) {
            $stmt = $product->getVariants();
            $num = $stmt->rowCount();
            if ($num>0) {
                $products_arr=array();
                $products_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $product_item=array(
                        "id" => $id,
                        "product_id" => $product_id,
                        "var_primary_id" => $var_primary_id,
                        "var_primary_name" => $var_primary_name,
                        "var_primary_val" => $var_primary_val,
                        "var_primary_value" => $var_primary_value,
                        "var_second_id" => $var_second_id,
                        "var_second_name" => $var_second_name,
                        "var_second_val" => $var_second_val,
                        "var_second_value" => $var_second_value,
                        "external_id" => $external_id,
                        "price" => $price,
                        "price_old" => $price_old,
                        "discount" => $discount,
                        "remain" => $remain,
                        "weight" => $weight,
                        "img" => $img
                    );
                    array_push($products_arr["records"], $product_item);
                }
                http_response_code(200);
                echo json_encode($products_arr, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Варианты не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить варианты товара"), JSON_UNESCAPED_UNICODE);
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