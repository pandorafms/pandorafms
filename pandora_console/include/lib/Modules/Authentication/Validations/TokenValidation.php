<?php

namespace PandoraFMS\Modules\Authentication\Validations;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\ExistLabelTokenService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\Timestamp;
use PandoraFMS\Modules\Users\Services\GetUserService;

final class TokenValidation
{
    public function __construct(
        private Config $config,
        private Timestamp $timestamp,
        private GetUserService $getUserService,
        private ExistLabelTokenService $existLabelTokenService
    ) {
    }

    public function __invoke(Token $token, ?Token $oldToken = null): void
    {
        if (!$token->getLabel()) {
            throw new BadRequestException(__('Label is missing'));
        }

        if ($oldToken === null || $oldToken->getLabel() !== $token->getLabel()) {
            if ($this->existLabelTokenService->__invoke($token->getLabel()) === true) {
                throw new BadRequestException(
                    __('Label %s is already exists', $token->getLabel())
                );
            }
        }

        if (is_user_admin($this->config->get('id_user')) === false
           || empty($token->getIdUser()) === true
        ) {
            $token->setIdUser($this->config->get('id_user'));
        } else {
            $this->validateUser($token->getIdUser());
        }
    }

    protected function getCurrentTimestamp(): string
    {
        return $this->timestamp->getMysqlCurrentTimestamp(0);
    }

    private function validateUser(string $idUser): void
    {
        $this->getUserService->__invoke($idUser);
    }
}
