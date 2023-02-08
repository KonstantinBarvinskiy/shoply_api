<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/image.php';
include_once '../objects/product.php';

$image = new Image($db);
$product = new Product($db);

include_once '../libs/MediaFiles.php';
$mfile = new MediaFiles();

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if ($id) {
            $product->id = $id;
            if ($product->delete()) {
                db()->query("DELETE FROM `products_variants` WHERE `product_id` IN ({$id})");
                db()->query("DELETE FROM `products_multi` WHERE `one_id` IN ({$id})");
                db()->query("DELETE FROM `products_multi` WHERE `multi_id` IN ({$id})");
                db()->query("DELETE i FROM `shop_orders_items` i LEFT JOIN `shop_orders` o ON i.`order` = o.`id` WHERE i.`product` IN ({$id}) AND o.`complete` = 'N'");

                if ($ids = explode(',', $id)) {
                    $j = 0;
//                    if ($files = $mfile->GetFiles('Catalog', $id)) {
//                        foreach ($files as $type=>$types) {
//                            foreach ($types as $unit=>$units) {
//                                if ($mfile->DelFile($units['id'])) $j++;
//                            }
//                        }
//                    }
                    if ($files = db()->rows("SELECT `id` FROM `mediafiles` WHERE `module` = 'Catalog' AND `module_id` = '{$id}'")) {
                        foreach ($files as $file) {
                            if ($mfile->DelFile($file['id'])) $j++;
                        }
                    }

                    $i = 0;
                    foreach ($ids as &$item) {
                        $image->module = 'Catalog';
                        $image->module_id = $id;
                        if ($image->deleteMany()) $i++;
                    }
                }

                http_response_code(200);
                echo json_encode(array("message" => 'Delete success'), JSON_UNESCAPED_UNICODE);
            }
            else {
                http_response_code(400);
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
        }

        else {
            http_response_code(400);
            echo json_encode(array("message" => "No id"), JSON_UNESCAPED_UNICODE);
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