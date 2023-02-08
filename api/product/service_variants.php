<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

$data = json_decode(json_encode($_GET), false);

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        $vars = db()->rows("SELECT * FROM `products_attr` ORDER BY `product_id` ASC");
        foreach ($vars as $var) {
            if (!db()->query_first("SELECT * FROM `products_attr_values` WHERE `attr_id` = '{$var['attr_id']}' AND `value` = '{$var['value']}'")) {
                db()->query("INSERT INTO `products_attr_values` SET `attr_id` = '{$var['attr_id']}', `value` = '{$var['value']}'");
                $val = db()->query_first("SELECT * FROM `products_attr_values` WHERE `attr_id` = '{$var['attr_id']}' AND `value` = '{$var['value']}'");
            } else {
                $val = db()->query_first("SELECT * FROM `products_attr_values` WHERE `attr_id` = '{$var['attr_id']}' AND `value` = '{$var['value']}'");
            }
            db()->query("INSERT INTO `products_variants` SET 
                `product_id` = '{$var['product_id']}',
                `var_primary_id` = '{$var['attr_id']}',
                `var_primary_val` = '{$val['id']}',
                `price` = '{$var['price']}',
                `remain` = '{$var['remain']}'
                ");
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