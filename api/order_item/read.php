<?php
include_once '../config/core.php';
new headers_api();
use \Firebase\JWT\JWT;

// файлы, необходимые для подключения к базе данных
include_once '../objects/order_item.php';

// создание объекта
$order_item = new Order_item($db);

// получаем данные
$data = json_decode(json_encode($_GET), false);

// получаем jwt
$headers = getallheaders();
$jwt=isset($headers['Authorization']) ? $headers['Authorization'] : "";

// если JWT не пуст
if($jwt) {

    // если декодирование выполнено успешно, показать данные
    try {

        // декодирование jwt
        $decoded = JWT::decode($jwt, $key, array('HS256'));
        if (isset($data->order)) $order_item->order = $data->order;
        else {
            http_response_code(400);
            echo json_encode(array("message" => "No order"), JSON_UNESCAPED_UNICODE);
            die();
        }

        if($order_item->read()) {
            // запрашиваем данные
            $stmt = $order_item->read();
            $num = $stmt->rowCount();

            // проверка, найдено ли больше 0 записей
            if ($num>0) {

                // массив данных
                $items_arr=array();
                $items_arr["records"]=array();

                // получаем содержимое таблицы
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)){

                    // извлекаем строку
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

                // устанавливаем код ответа - 200 OK
                http_response_code(200);

                // выводим данные в формате JSON
                echo json_encode($items_arr, JSON_UNESCAPED_UNICODE);

            } else {
                // устанавливаем код ответа - 200 OK
                http_response_code(204);

                // выводим данные в формате JSON
                echo json_encode(array("message" => "Товары не найдены"), JSON_UNESCAPED_UNICODE);
            }

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