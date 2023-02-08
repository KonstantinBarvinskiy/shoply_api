<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

include_once '../objects/order_item.php';
include_once '../objects/order.php';

$order_item = new Order_item($db);
$order = new Order($db);

$data = json_decode(file_get_contents("php://input"));

$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";
$id=isset($data->id) ? $data->id : "";

if($jwt) {
    try {
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->order)) $order_item->order = $data->order;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No order"),JSON_UNESCAPED_UNICODE);
            die();
        }

        if (isset($data->items)) {
            $items = json_decode(json_encode($data->items), true);
            foreach ($items as $key=>$item) {
                if (isset($item['product']) and
                    isset($item['brand']) and
                    isset($item['name']) and
                    isset($item['top']) and
                    isset($item['price']) and
                    isset($item['count'])) {
                    if (!isset($item['attr'])) $item['attr'] = '';
                } else {
                    http_response_code(400);
                    echo json_encode(array("message" => "No required data"),JSON_UNESCAPED_UNICODE);
                    die();
                }
            }
            $order_item->items = $items;
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No items"),JSON_UNESCAPED_UNICODE);
            die();
        }

        if ($query = $order_item->create()) {
            $order->int["id"] = $data->order;
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

            $order_item->order = $data->order;
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

            http_response_code(201);
            echo json_encode(array("message" => "Create success", "order" => $order_data, "items" => $items),JSON_UNESCAPED_UNICODE);
        }
        else {
            http_response_code(400);
            echo json_encode(array("message" => "Невозможно выполнить данную операцию."),JSON_UNESCAPED_UNICODE);
        }
    }

    catch (Exception $e){
        http_response_code(401);
        echo json_encode(array(
            "message" => "Доступ закрыт",
            "error" => $e->getMessage()
        ));
    }
}

else {
    http_response_code(401);
    echo json_encode(array("message" => "Доступ закрыт."));
}
?>