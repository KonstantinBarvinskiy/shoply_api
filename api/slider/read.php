<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/slider.php';

$slider = new Slider($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) $slider->item["id"] = $data->id;

        if($slider->read()) {
            $stmt = $slider->read();
            $num = $stmt->rowCount();
            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    extract($row);

                    $item=array(
                        "id" => $id,
                        "order" => $order,
                        "name" => $name,
                        "link" => $link,
                        "link_text" => $link_text,
                        "is_light" => $is_light,
                        "show" => $show,
                        "deleted" => $deleted
                    );

                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);
            }
            else {
                http_response_code(204);
                echo json_encode(array("message" => 'Элементы слайдера не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить элементы слайдера"), JSON_UNESCAPED_UNICODE);
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