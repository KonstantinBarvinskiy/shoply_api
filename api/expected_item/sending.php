<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/expected_item.php';
$expected_item = new Expected_item($db);

include_once '../libs/ZFmail.php';

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
//print_r($_SERVER);

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if($expected_item->read()) {
            $stmt = $expected_item->read();
            $num = $stmt->rowCount();

            if ($num>0) {
                $m = 0;
                $subs=array();

                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                    extract($row);
                    $item=array(
                        "id" => $id,
                        "product_id" => $product_id,
                        "email" => $email,
                        "attr" => $attr,
                        "date" => $date
                    );
                    array_push($subs, $item);
                }

                $shop_name = db()->query_value("SELECT `value` FROM `settings` WHERE `callname` = 'sitename'");
                foreach ($subs as $sub) {
                    if (empty($sub['attr'])) {
                        $product = db()->query_first("SELECT * FROM `products` WHERE `id` = '{$sub['product_id']}'");
                        if ($product['remain'] > 0) $send = true;
                    }
                    else {
                        $product = db()->query_first("SELECT * FROM `products` WHERE `id` = '{$sub['product_id']}'");
                        $attrs = explode(', ', $sub['attr']);
                        if (count($attrs) == 2) {
                            $attr1 = explode(': ', $attrs[0]);
                            $attr2 = explode(': ', $attrs[1]);
                            $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM `products_variants` vars
                        LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                        LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                        LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                        LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                        WHERE vals.value = '{$attr1[1]}' AND valss.value = '{$attr2[1]}' AND vars.product_id = {$sub['product_id']}");
                        }
                        else {
                            $attr = explode(': ', $attrs[0]);
                            $variant = db()->query_first("SELECT vars.*, vals.value AS var_primary_value, valss.value AS var_second_value, vals.order as prim_order, valss.order as sec_order, an.name as var_primary_name, ans.name as var_second_name FROM `products_variants` vars
                        LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                        LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                        LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                        LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                        WHERE (vals.value = '{$attr[1]}' OR valss.value = '{$attr[1]}') AND vars.product_id = {$sub['product_id']}");
                        }
                        if ($variant['remain'] > 0) $send = true;
                    }
                    if (($send == true) && ($product['deleted'] == 'N') && ($product['show'] == 'Y')) {
                        if ($product['nav']) $end_link = $product['nav'];
                        else $end_link = $product['id'];
                        $item_link = ItemLink($product['top'], $end_link);
                        $prod_mail = $product['name'];
                        if ($sub['attr']) $prod_mail .= ', '.$sub['attr'];

                        $mail = new ZFmail($sub['email'], 'noreply@'.$_SERVER['HTTP_HOST'], "Уведомление о поступлении товара {$prod_mail}", "Здравствуйте!\n\nМагазин \"{$shop_name}\" уведомляет вас о том, что товар {$prod_mail} поступил в продажу.\n\nЗаказать его вы можете по ссылке: https://{$item_link}\n\nСпасибо!");
                        if ($mail->send()) $m++;
//                        $fwrite .= "Уведомление на {$sub['email']} о поступлении {$sub['name']}, {$sub['attr']}\r\n";
                        db()->query("DELETE FROM `products_order` WHERE `id` = '{$sub['id']}'");
                        unset($send, $variant, $attrs, $item_link, $product);
                    }
                }
                if ($m > 0) {
                    http_response_code(200);
                    echo json_encode(array("message" => "Sending success"), JSON_UNESCAPED_UNICODE);
                }
                else {
                    http_response_code(204);
                    echo json_encode(array("message" => "Nothing to send"), JSON_UNESCAPED_UNICODE);
                }
            } else {
                http_response_code(204);
                echo json_encode(array("message" => 'Отслеживания не найдены'), JSON_UNESCAPED_UNICODE);
            }
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить операцию"), JSON_UNESCAPED_UNICODE);
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

function ItemLink($top=0, $id=0) {
    $top=(int)$top;
    $i = 1;

    $nav = db()->query_first("SELECT `id`, `top`, `nav`, `show` FROM `products_topics` WHERE `id` = '{$top}'", MYSQL_ASSOC);
    $category = $nav['nav'];
    if($top != 0 || $id != 0) {
        while($i != 10) {
            if($nav['top'] != 0){
                $nav = db()->query_first("SELECT `id`, `top`, `nav`, `show` FROM `products_topics` WHERE `id` = '{$nav['top']}'", MYSQL_ASSOC);
                $c = $category;
                $category = $nav['nav'].'/'.$c;
            }else $i = 10;
        }
    }

    $link = $_SERVER['HTTP_HOST'].'/catalog/'.$category.'/'.$id;

    return $link;
}
?>