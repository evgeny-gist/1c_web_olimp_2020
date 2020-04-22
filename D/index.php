<?php
require_once '../functions.php';
require_once 'Offer.php';
require_once 'ShopWarehouse.php';
require_once 'OfferAccepted.php';

$input_content = file_get_contents('input.txt');
$lines = explode("\r", $input_content);

$output_content = '';

/** @var ShopWarehouse[] $shops */
$shops = []; // Магазины и склад
/** @var Offer[] $offers */
$offers = [];
/** @var OfferAccepted $accepted_offers */
$accepted_offers = [];

$is_offer = false;
// Парсим изначальные данные в удобный вид
foreach ($lines as $line) {
    if (strlen($line) <= 2) { // Признак перехода с скадов или магазинов на заявки
        $is_offer = true;
        continue;
    }
    if ($is_offer) {
        $offer = new Offer();
        $parsed_line = explode(' ', $line);
        foreach ($parsed_line as $key => $value) {
            $value = trim($value);
            switch ($key) { // Парсим строку
                case 0:
                    $offer->id = $value;
                    break;
                case 1:
                    $offer->target = $value;
                    break;
                case 2:
                    $offer->price = $value;
                    break;
                case 3:
                    $offer->count = $value;
            }
        }
        if ($offer->target == 'W')
            $offer->price *= 1.1;
        $offers[$offer->id] = $offer;
    } else {
        $Shop = new ShopWarehouse();
        list($capacity_target, $capacity_value) = explode(' ', $line);
        $Shop->name = trim($capacity_target);
        $Shop->capacity = trim($capacity_value);
        $shops[$Shop->name] = $Shop;
    }
}


/**
 * Анализирует все предожения и выбирает лучшее
 * @param $offers
 * @param $shops
 */
function select_best_offers(&$offers, &$shops)
{
    // Выбираем лучшие предложения для каждого магазина
    foreach ($offers as $key => $offer) {
        if ($offer->count < 1) { // Отклоняем "нулевые" предложения
            unset($offers[$key]);
            continue;
        }
        if (!$shops[$offer->target]->best_offer) {
            $shops[$offer->target]->setBestRequest($offer);
            continue;
        }
        if ($offer->price < $shops[$offer->target]->best_offer->price)
            $shops[$offer->target]->setBestRequest($offer);
    }
}

/**
 * Принимает лучшее предложение если оно подходит по условию
 * @param $offers
 * @param $shops
 * @param $accepted_offers
 */
function accept_best_offer(&$offers, &$shops, &$accepted_offers)
{
    $needed_in_shops = 0;
    $accepted_count = 0;
    foreach ($shops as $shop)
        if ($shop->name != 'W')
            $needed_in_shops += $shop->capacity;
    foreach ($accepted_offers as $accepted_offer)
        $accepted_count += $accepted_offer->count;
    if ($accepted_count >= $needed_in_shops)
        return;

    // Принимаем лучшее предложение
    /** @var Offer $best_offer */
    $best_offer = null;
    foreach ($shops as $shop) {
        if (!$best_offer)
            $best_offer = $shop->best_offer;
        else
            if ($shop->best_offer)
                if ($best_offer->price > $shop->best_offer->price)
                    $best_offer = $shop->best_offer;
    }
    $count_to_accept = $best_offer->count;
    if (!$best_offer->target)
        return;
    $shop_cat_accept = $shops[$best_offer->target]->get_empty_count();
    if ($shop_cat_accept < 1) { // Если магазин полон
        unset($offers[$best_offer->id]);
        return;
    }
    if ($best_offer->count > $shop_cat_accept)
        $count_to_accept = $shop_cat_accept;
    $shops[$best_offer->target]->filled += $count_to_accept;

    $OfferAccepted = new OfferAccepted();
    $OfferAccepted->id = $best_offer->id;
    $OfferAccepted->count = $count_to_accept;
    $accepted_offers[] = $OfferAccepted;
    unset($offers[$best_offer->id]);
    $shops[$best_offer->target]->best_offer = null;
    return;
}

// Проходимся по всем предложениям в поиске лучших
foreach ($offers as $offer) {
    select_best_offers($offers, $shops);
    accept_best_offer($offers, $shops, $accepted_offers);
}

dd($shops, 'Магазины');
dd($accepted_offers, 'Принятые предложения');

foreach ($accepted_offers as $accepted_offer) {
    $output_content .= "{$accepted_offer->id} {$accepted_offer->count}\r\n";
}

file_put_contents('output.txt', $output_content);