<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/order_item.php';
include_once '../objects/order.php';

$order_item = new Order_item($db);
$order = new Order($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));

        if ($id) {

            $order_item->id = $id;

            if ($query = $order_item->delete()) {
                $order->int["id"] = $query;
                $stmt = $order->read();
                $num = $stmt->rowCount();
                if ($num>0) {
                    $items_arr = array();
                    $items_arr["records"] = array();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        extract($row);
                        $item = array(
                            "id" => $id,
                            "user_id" => $user_id,
                            "admin_id" => $admin_id,
                            "name" => $name,
                            "mail" => $mail,
                            "phone" => $phone,
                            "address" => $address,
                            "complete" => $complete,
                            "comment" => $comment,
                            "delivery_type" => $delivery_type,
                            "delivery_cost" => $delivery_cost,
                            "delivery_term" => $delivery_term,
                            "point_id" => $point_id,
                            "delivery_city" => $delivery_city,
                            "delivery" => $delivery,
                            "delivery_tariff_id" => $delivery_tariff_id,
                            "delivery_mode_id" => $delivery_mode_id,
                            "pickpoint_id" => $pickpoint_id,
                            "dispatch" => $dispatch,
                            "price" => $price,
                            "promo_code" => $promo_code,
                            "discount" => $discount,
                            "paymethod" => $paymethod,
                            "status" => $status,
                            "paid" => $paid,
                            "date" => stupDate($date),
                            "modified" => stupDate($modified),
                            "deleted" => $deleted
                        );

                        $order_data = $item;
                        break;
                    }
                }

                $order_item->order = $query;
                $stmt = $order_item->read();
                $num = $stmt->rowCount();
                if ($num>0) {
                    $items_arr=array();
                    $items_arr["records"]=array();

                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){
                        extract($row);
                        $item=array(
                            "id" => $id,
                            "product" => $product,
                            "brand" => $brand,
                            "order" => $order,
                            "name" => $name,
                            "attr" => $attr,
                            "attrs" => "",
                            "variant" => "",
                            "link" => $link,
                            "top" => $top,
                            "price" => $price,
                            "count" => $count,
                            "total_fake" => $total_fake,
                            "created" => stupDate($created),
                            "modified" => stupDate($modified),
                            "external_id" => $external_id,
                            "weight" => $weight,
                            "src" => $src,
                        );
                        $image = $img->GetMainImage('Catalog', $item['product']);
                        $item['src'] = $image['src'];
                        $item['resize'] = imget()->ResizeImage($item['src'], 112, 112);

                        if (!empty($item['attr'])) {
                            $attrs = explode(', ', $item['attr']);
                            if (count($attrs) == 2) {
                                $attr1 = explode(': ', $attrs[0]);
                                $attr2 = explode(': ', $attrs[1]);
                                $attrs = array(
                                    array(
                                        "name" => $attr1[0],
                                        "value" => $attr1[1]
                                    ),
                                    array(
                                        "name" => $attr2[0],
                                        "value" => $attr2[1]
                                    )
                                );
                                $variant = db()->query_value("SELECT vars.id FROM `products_variants` vars
                                LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                                LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                                LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                                LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                                WHERE vals.value = '{$attr1[1]}' AND valss.value = '{$attr2[1]}' AND vars.product_id = {$item['product']}");
                            }
                            else {
                                $attr = explode(': ', $attrs[0]);
                                $attrs = array(
                                    array(
                                        "name" => $attr[0],
                                        "value" => $attr[1]
                                    )
                                );
                                $variant = db()->query_value("SELECT vars.id FROM `products_variants` vars
                                LEFT JOIN `products_attr_values` vals ON vals.id = vars.var_primary_val
                                LEFT JOIN `products_attr_values` valss ON valss.id = vars.var_second_val
                                LEFT JOIN `products_attr_names` an ON vars.var_primary_id = an.id
                                LEFT JOIN `products_attr_names` ans ON vars.var_second_id = ans.id
                                WHERE (vals.value = '{$attr[1]}' OR valss.value = '{$attr[1]}') AND vars.product_id = {$item['product']}");
                            }
                            $item['attrs'] = $attrs;
                            $item['variant'] = $variant;
                        }

                        array_push($items_arr["records"], $item);
                    }
                    $items = $items_arr['records'];
                }

                // устанавливаем код ответа - 200 OK
                http_response_code(200);

                // выводим данные в формате JSON
                echo json_encode(array("message" => "Delete success", "order" => $order_data, "items" => $items), JSON_UNESCAPED_UNICODE);
            }

            else {
                // код ответа
                http_response_code(400);

                // показать сообщение об ошибке
                echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
            }
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "No id"), JSON_UNESCAPED_UNICODE);
        }
    }

        // если декодирование не удалось, это означает, что JWT является недействительным
    catch (Exception $e){
        // код ответа
        http_response_code(401);

        // сообщение об ошибке
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ), JSON_UNESCAPED_UNICODE);
    }
}

// показать сообщение об ошибке, если jwt пуст
else {
    // код ответа
    http_response_code(401);

    // сообщить пользователю что доступ запрещен
    echo json_encode(array("message" => "Доступ закрыт."), JSON_UNESCAPED_UNICODE);
}
?>