<?php

namespace PandoraFMS\Modules\Authentication\Services;

final class ValidateUserTokenService
{
    public function __construct(
        private readonly GetUserTokenService $getUserTokenService,
    ) {
    }

    public function __invoke(
        string $uuid,
        string $token,
    ): bool {

        $challenge = $this->getUserTokenService->__invoke($uuid)?->getChallenge();
        return password_verify(
            $token,
            $challenge
        );
    }
}
