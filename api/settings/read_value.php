<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/settings.php';

$settings = new settings($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->callname)) $settings->filter['callname']= $data->callname;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No callname"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if($settings->read()) {
            $stmt = $settings->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $value = $row['value'];

                }
                http_response_code(200);
                echo json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Настройка не найдена или значение пустое'), JSON_UNESCAPED_UNICODE);
            }
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