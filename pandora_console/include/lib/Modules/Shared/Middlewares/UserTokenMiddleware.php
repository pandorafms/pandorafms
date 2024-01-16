<?php

namespace PandoraFMS\Modules\Shared\Middlewares;

use PandoraFMS\Modules\Authentication\Services\GetUserTokenService;
use PandoraFMS\Modules\Authentication\Services\UpdateTokenService;
use PandoraFMS\Modules\Authentication\Services\ValidateUserTokenService;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Services\Timestamp;
use Psr\Http\Message\ServerRequestInterface as Request;

class UserTokenMiddleware
{
    public function __construct(
        private readonly ValidateUserTokenService $validateUserTokenService,
        private readonly GetUserTokenService $getUserTokenService,
        private readonly UpdateTokenService $updateTokenService,
        private readonly Timestamp $timestamp
    ) {
    }

    public function check(Request $request): bool
    {
        global $config;
        $authorization = ($request->getHeader('Authorization')[0] ?? '');

        /*
            @var ?Token $token
        */
        try {
            $authorization = str_replace('Bearer ', '', $authorization);
            preg_match(
                '/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/',
                $authorization,
                $matches
            );

            $uuid = ($matches[0] ?? '');
            $token = str_replace($uuid.'-', '', $authorization);
            $validToken = $this->validateUserTokenService->__invoke($uuid, $token);
            $token = $this->getUserTokenService->__invoke($uuid);
            if ($token !== null && $validToken) {
                $oldToken = clone $token;
                $token->setLastUsage($this->timestamp->getMysqlCurrentTimestamp(0));
                $this->updateTokenService->__invoke($token, $oldToken);
            }
        } catch (NotFoundException) {
            $token = null;
        }

        if ($token !== null) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            $_SESSION['id_usuario'] = $token->getIdUser();
            $config['id_user'] = $token->getIdUser();

            if (session_status() === PHP_SESSION_ACTIVE) {
                session_write_close();
            }
        }

        return $token !== null && $validToken;
    }
}
