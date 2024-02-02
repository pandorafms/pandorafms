<?php

namespace PandoraFMS\Modules\Authentication\Services;

final class GenerateUserTokenService
{
    public function __construct(
    ) {
    }

    public function __invoke(): string
    {
        $base = preg_replace(
            '/[^a-zA-Z0-9]/', '', base64_encode(random_bytes(100)),
        );

        $token = substr($base, 0, 8);
        $token .= '-'.substr($base, 8, 24);

        return $token;
    }
}
