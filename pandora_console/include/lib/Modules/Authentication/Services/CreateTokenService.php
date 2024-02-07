<?php

namespace PandoraFMS\Modules\Authentication\Services;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;
use PandoraFMS\Modules\Authentication\Validations\TokenValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

final class CreateTokenService
{
    public function __construct(
        private Audit $audit,
        private TokenRepository $tokenRepository,
        private TokenValidation $tokenValidation,
        private GenerateUserTokenService $generateUserTokenService,
        private GenerateUserUUIDService $generateUserUUIDService,
        private PrepareUserTokenService $prepareUserTokenService
    ) {
    }

    public function __invoke(Token $token): Token
    {
        $this->tokenValidation->__invoke($token);

        $stringToken = $this->generateUserTokenService->__invoke();
        $userUUID = $this->generateUserUUIDService->__invoke();
        $hashedToken = $this->prepareUserTokenService->__invoke($stringToken);

        $token->setUuid($userUUID);
        $token->setChallenge($hashedToken);
        $token->setToken($userUUID.'-'.$stringToken);
        $token = $this->tokenRepository->create($token);

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Create token '.$token->getLabel(),
            json_encode($token->toArray())
        );

        return $token;
    }
}
