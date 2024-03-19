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
        string $strToken,
    ): bool {
        $token = $this->getUserTokenService->__invoke($uuid);
        $validity = $token?->getValidity();
        $challenge = $token?->getChallenge();

        if (empty($validity) === false) {
            if (strtotime($validity) < time()) {
                return false;
            }
        }

        return password_verify(
            $strToken,
            $challenge
        );
    }
}
