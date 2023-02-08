<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/order.php';
include_once '../objects/order_item.php';

$order = new Order($db);
$order_item = new Order_item($db);

// получаем данные
$data = json_decode(file_get_contents("php://input"));

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->id)) {
            $order->id = $data->id;
        } else {
            http_response_code(400);
            echo json_encode(array("message" => "No 'id'"), JSON_UNESCAPED_UNICODE);
            die();
        }
        if (isset($data->user_id)) $order->order["user_id"] = $data->user_id;
        if (isset($data->admin_id)) $order->order["admin_id"] = $data->admin_id;
        if (isset($data->name)) $order->order["name"] = $data->name;
        if (isset($data->mail)) $order->order["mail"] = $data->mail;
        if (isset($data->phone)) $order->order["phone"] = '+7'.$data->phone;
        if (isset($data->address)) $order->order["address"] = $data->address;
        if (isset($data->complete)) $order->order["complete"] = $data->complete;
        if (isset($data->comment)) $order->order["comment"] = $data->comment;
        if (isset($data->delivery_type)) $order->order["delivery_type"] = $data->delivery_type;
        if (isset($data->delivery_cost)) $order->order["delivery_cost"] = $data->delivery_cost;
        if (isset($data->point_id)) $order->order["point_id"] = $data->point_id;
        if (isset($data->delivery_city)) $order->order["delivery_city"] = $data->delivery_city;
        if (isset($data->delivery)) $order->order["delivery"] = $data->delivery;
        if (isset($data->delivery_tariff_id)) $order->order["delivery_tariff_id"] = $data->delivery_tariff_id;
        if (isset($data->delivery_mode_id)) $order->order["delivery_mode_id"] = $data->delivery_mode_id;
        if (isset($data->pickpoint_id)) $order->order["pickpoint_id"] = $data->pickpoint_id;
        if (isset($data->dispatch)) $order->order["dispatch"] = $data->dispatch;
        if (isset($data->price)) $order->order["price"] = $data->price;
        if (isset($data->promo_code)) $order->order["promo_code"] = $data->promo_code;
        if (isset($data->discount)) $order->order["discount"] = $data->discount;
        if (isset($data->paymethod)) $order->order["paymethod"] = $data->paymethod;
        if (isset($data->status)) $order->order["status"] = $data->status;
        if (isset($data->paid)) $order->order["paid"] = $data->paid;
        if (isset($data->date)) $order->order["date"] = $data->date;
        if (isset($data->modified)) $order->order["modified"] = $data->modified;
        if (isset($data->deleted)) $order->order["deleted"] = $data->deleted;

        if (!isset($order->order)) {
            http_response_code(400);
            echo json_encode(array("message" => "No data"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $order->update()) {
            $order->int["id"] = $data->id;
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

            $order_item->order = $data->id;
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
            echo json_encode(array("message" => "Update success", "order" => $order_data, "items" => $items), JSON_UNESCAPED_UNICODE);
        }

        // сообщение, если не удается обновить данные
        else {
            // код ответа
            http_response_code(400);

            // показать сообщение об ошибке
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."), JSON_UNESCAPED_UNICODE);
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