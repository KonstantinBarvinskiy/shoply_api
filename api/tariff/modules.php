<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/module.php';

$module = new Module($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) $module->item["id"] = $data->id;
        if (isset($data->module)) $module->item["module"] = $data->module;
        if (isset($data->callname)) $module->item["callname"] = $data->callname;
        if (isset($data->value)) $module->item["value"] = $data->value;

        if($module->read()) {
            $stmt = $module->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "block" => $block,
                        "name" => $name,
                        "callname" => $callname,
                        "value" => $value
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Модули не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить модули"), JSON_UNESCAPED_UNICODE);
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