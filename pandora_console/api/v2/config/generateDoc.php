<?php

require_once __DIR__.'/../../../vendor/autoload.php';

$exclude = ['tests'];
$pattern = '*.php';

$openapi = \OpenApi\Generator::scan(
    \OpenApi\Util::finder(
        [
            __DIR__.'/..',
            __DIR__.'/../../../include/lib/Modules',
        ],
        $exclude,
        $pattern
    )
);

header('Content-Type: application/json');
file_put_contents(__DIR__.'/../public/swagger.json', $openapi->toJson());
