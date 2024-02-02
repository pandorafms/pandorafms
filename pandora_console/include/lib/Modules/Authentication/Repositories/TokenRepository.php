<?php

namespace PandoraFMS\Modules\Authentication\Repositories;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenDataMapper;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class TokenRepository
{
    public function __construct(
        private Repository $repository,
        private TokenDataMapper $tokenDataMapper
    ) {
    }

    /**
     * @return Token[],
    */
    public function list(TokenFilter $tokenFilter): array
    {
        return $this->repository->__list(
            $tokenFilter,
            $this->tokenDataMapper
        );
    }

    public function count(TokenFilter $tokenFilter): int
    {
        return $this->repository->__count(
            $tokenFilter,
            $this->tokenDataMapper
        );
    }

    public function getOne(TokenFilter $tokenFilter): Token
    {
        return $this->repository->__getOne(
            $tokenFilter,
            $this->tokenDataMapper
        );
    }

    public function create(Token $token): Token
    {
        $id = $this->repository->__create($token, $this->tokenDataMapper);
        return $token->setIdToken($id);
    }

    public function update(Token $token): Token
    {
        return $this->repository->__update(
            $token,
            $this->tokenDataMapper,
            $token->getIdToken()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->tokenDataMapper);
    }

}
