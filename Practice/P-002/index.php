<?php

/* 
P-002 "Расчёт оплаченных заказов в копейках"
Теория: PHP-003, PHP-009, PHP-010, PHP-018.
*/

function paidTotalKopecks(array $orders): int
{
    $sum = 0;
    foreach ($orders as $order) {

        if (
            array_key_exists('status', $order) === true &&
            $order['status'] === 'paid' &&
            array_key_exists('amount_kop', $order) === true &&
            is_int($order['amount_kop']) === true &&
            $order['amount_kop'] >= 0
        ) {
            $sum += $order['amount_kop'];
        }
    }
    return $sum;
}

$orders = [
    ['status' => 'paid', 'amount_kop' => 12500],
    ['status' => 'pending', 'amount_kop' => 99000],
    ['status' => 'paid', 'amount_kop' => 0],
    ['status' => 'paid', 'amount_kop' => null],
    ['status' => 'paid', 'amount_kop' => 2500],
    ['status' => 'paid', 'amount_kop' => '500'],
    ['status' => 'paid', 'amount_kop' => -40],
    ['status' => 'paid'],
    ['amount_kop' => 10000],
];

var_dump(paidTotalKopecks($orders));
