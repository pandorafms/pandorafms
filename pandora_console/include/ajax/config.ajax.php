<?php

$token_name = get_parameter('token_name', 0);
$no_boolean = (bool) get_parameter('no_boolean', 0);

$value_token = db_get_value(
    'value',
    'tconfig',
    'token',
    $token_name
);

if ($no_boolean === true) {
    echo json_encode(io_safe_output($value_token));
} else {
    echo (bool) $value_token;
}
