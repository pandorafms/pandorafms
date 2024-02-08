<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Shared\Services\Config;

final class ValidateServerIdentifierTokenService
{
    public function __construct(
        private readonly Config $config,
    ) {
    }

    public function __invoke(string $token): bool {
        $serverUniqueIdentifier = $this->config->get('server_unique_identifier');
        $apiPassword = $this->config->get('api_password');

        $tokenUniqueServerIdentifier = md5($serverUniqueIdentifier).md5($apiPassword);
        return ($tokenUniqueServerIdentifier === $token);
    }
}
