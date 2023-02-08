<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/expected_item.php';

$expected_item = new Expected_item($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) $expected_item->item["id"] = $data->id;
        if (isset($data->product_id)) $expected_item->item["product_id"] = $data->product_id;
        if (isset($data->email)) $expected_item->item["email"] = $data->email;

        if($expected_item->read()) {
            $stmt = $expected_item->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "product_id" => $product_id,
                        "email" => $email,
                        "attr" => $attr,
                        "date" => $date
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Отслеживания не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить отслеживаемые товары"), JSON_UNESCAPED_UNICODE);
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