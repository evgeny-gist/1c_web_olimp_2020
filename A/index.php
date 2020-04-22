<?php
require_once '../functions.php';

$input_content = file_get_contents('input.txt');
$lines = explode("\r", $input_content);

$is_request = false;
$goods = [];
$requests = [];

foreach ($lines as $line) {
    if (strlen($line) <= 2) { // Признак перехода с товаров на заявки
        $is_request = true;
        continue;
    }
    if ($is_request) { // Если это заявка
        $request = [];
        $is_good_id = true;
        $old_good_id = null;
        $request_parts = explode(' ', $line);
        $request_id = intval(array_shift($request_parts));
        foreach ($request_parts as $key => $part) { // Заполняем массив с параметрами заявки заявкой
            if ($is_good_id) {
                $request[$part] = 0;
                $old_good_id = $part;
                $is_good_id = false;
            } else {
                $request[$old_good_id] = $part;
                $is_good_id = true;
            }
        }
        $requests[$request_id] = $request;
    } else { // Если это остатки товара
        list($good_id, $good_count) = explode(' ', $line);
        $goods[intval($good_id)] = intval($good_count);
    }
}

dd($goods, 'Изначальные остатки');
dd($requests, 'Заявки на покупку');

foreach ($requests as $request_id => $request) {
    $is_can_buy = true;
    foreach ($request as $good_id => $good_count) // Проверяем все запросы на товары
        if ($goods[$good_id] < $good_count) // Если товаров в наличии не хватает
            $is_can_buy = false; // Ставим признак отмены
    if ($is_can_buy) // Если может купить
        foreach ($request as $good_id => $good_count) // "Отгружаем" товары
            $goods[$good_id] -= $good_count;
}
dd($goods, 'Товары на выходе');

$output_content = '';
foreach ($goods as $good_id => $good_count)
    $output_content .= "$good_id $good_count \r\n";

file_put_contents('output.txt', $output_content);