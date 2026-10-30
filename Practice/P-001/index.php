<?php

/* 
P-001 "Необязательные поля и нулевые значения"
Теория: PHP-003, PHP-005, PHP-006.
*/

function inspectField(array $payload, string $key): array
{
    if (array_key_exists($key, $payload) === false) {
        return ['present' => false, 'state' => 'absent'];
    } elseif ($payload[$key] === null) {
        return ['present' => true, 'state' => 'null'];
    } elseif ($payload[$key] === false) {
        return ['present' => true, 'state' => 'false'];
    } elseif ($payload[$key] === 0) {
        return ['present' => true, 'state' => 'zero'];
    } elseif ($payload[$key] === '') {
        return ['present' => true, 'state' => 'empty-string'];
    } else {
        return ['present' => true, 'state' => 'value'];
    }
}

$array = [];

var_dump(inspectField($array, 'age'));
