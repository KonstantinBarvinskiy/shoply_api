<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/menu.php';

$menu = new Menu($db);

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) $menu->item["id"] = $data->id;
        if (isset($data->name)) $menu->item["name"] = $data->name;
        if (isset($data->show)) $menu->item["show"] = $data->show;
        if (isset($data->deleted)) $menu->item["deleted"] = $data->deleted;
        else $menu->item["deleted"] = 'N';

        if($menu->read()) {
            $stmt = $menu->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $items_arr=array();
                $items_arr["records"]=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "name" => $name,
                        "show" => $show,
                        "deleted" => $deleted
                    );
                    array_push($items_arr["records"], $item);
                }

                http_response_code(200);
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Меню не найдены'), JSON_UNESCAPED_UNICODE);
            }

        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно отобразить меню"), JSON_UNESCAPED_UNICODE);
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