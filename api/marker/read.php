<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/marker.php';

$marker = new Marker($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) $marker->item["id"] = $data->id;
        if (isset($data->show)) $marker->item["show"] = $data->show;

        if($marker->read()) {
            $stmt = $marker->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "name" => $name,
                        "text" => $text,
                        "color" => $color,
                        "show" => $show
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Маркеры не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить маркеры"), JSON_UNESCAPED_UNICODE);
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