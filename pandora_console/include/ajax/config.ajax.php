<?php

$token_name = get_parameter('token_name', 0);

$value_token = db_get_value(
    'value',
    'tconfig',
    'token',
    $token_name
);

echo (bool) $value_token;
