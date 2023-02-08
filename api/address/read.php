<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/address.php';

$address = new Address($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->user_id)) $address->item["user_id"] = $data->user_id;
        if (isset($data->city)) $address->item["city"] = $data->city;
        if (isset($data->main)) $address->item["main"] = $data->main;

        if($address->read()) {
            $stmt = $address->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "user_id" => $user_id,
                        "main" => $main,
                        "city" => $city,
                        "street" => $street,
                        "house" => $house,
                        "flat" => $flat
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Адреса не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить адреса"), JSON_UNESCAPED_UNICODE);
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