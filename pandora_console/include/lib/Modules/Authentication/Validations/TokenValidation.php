<?php

namespace PandoraFMS\Modules\Authentication\Validations;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Services\ExistLabelTokenService;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Shared\Services\Timestamp;

final class TokenValidation
{
    public function __construct(
        private Config $config,
        private Timestamp $timestamp,
        private ExistLabelTokenService $existLabelTokenService
    ) {
    }

    public function __invoke(Token $token, ?Token $oldToken = null): void
    {
        if (!$token->getLabel()) {
            throw new BadRequestException(__('Label is missing'));
        }

        if($oldToken === null || $oldToken->getLabel() !== $token->getLabel()) {
            if($this->existLabelTokenService->__invoke($token->getLabel()) === true) {
                throw new BadRequestException(
                    __('Label %s is already exists', $token->getLabel())
                );
            }
        }

        if($oldToken === null) {
            $token->setIdUser($this->config->get('id_user'));
        }
    }

    protected function getCurrentTimestamp(): string
    {
        return $this->timestamp->getMysqlCurrentTimestamp(0);
    }
}
