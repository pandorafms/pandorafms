<?php

namespace PandoraFMS\Modules\Shared\Middlewares;

use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class AclListMiddleware
{
    public function __construct(
        private readonly Config $config
    ) {
    }

    public function check(string $ipOrigin): bool
    {
        $result = true;
        try {
            require_once $this->config->get('homedir').'/include/functions_api.php';
            if ((bool) \isInACL($ipOrigin) === false) {
                $result = false;
            }
        } catch (NotFoundException) {
            $result = false;
        }

        return $result;
    }
}
