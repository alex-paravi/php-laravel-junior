<?php

/* 
P-003 "Фильтрация и форматирование каталога"
Теория: PHP-009, PHP-011, PHP-018.
*/

$products = [
    [
        'id' => 1,
        'title' => 'Mouse',
        'category' => 'tech',
        'active' => true,
    ],
    [
        'id' => 2,
        'title' => 'Lamp',
        'category' => 'home',
        'active' => true,
    ],
    [
        'id' => 3,
        'title' => 'Cable',
        'category' => 'tech',
        'active' => false,
    ],
    [
        'id' => 4,
        'title' => 'Keyboard',
        'category' => 'tech',
        'active' => true,
    ],
    [
        'id' => 4,
        'title' => 'Keyboard',
        'category' => 'tech',
    ],
];

function activeCategoryProducts(array $products, string $category): array
{
    $array = [];
    foreach ($products as $product) {
        if (
            array_key_exists('id', $product) === true
            && array_key_exists('title', $product) === true
            && array_key_exists('category', $product) === true
            && array_key_exists('active', $product) === true
            && $product['active'] === true
            && $product['category'] === $category
        ) {
            $array[] = $product;
        }
    }
    return $array;
}


var_dump(activeCategoryProducts($products, 'tech'));
