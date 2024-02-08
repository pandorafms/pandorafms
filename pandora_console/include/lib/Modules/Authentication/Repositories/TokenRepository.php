<?php

namespace PandoraFMS\Modules\Authentication\Repositories;

use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;

interface TokenRepository
{
    /**
     * @return Token[],
     */
    public function list(TokenFilter $tokenFilter): array;

    public function count(TokenFilter $tokenFilter): int;

    public function getOne(TokenFilter $tokenFilter): Token;

    public function create(Token $token): Token;

    public function update(Token $token): Token;

    public function delete(int $id): void;

    public function getExistToken(string $label): Token;
}
