<?php

namespace PandoraFMS\Modules\Users\Repositories;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Entities\UserFilter;

interface UserRepository
{
    /**
     * @return User[],
     */
    public function list(UserFilter $userFilter): array;

    public function count(UserFilter $userFilter): int;

    public function getOne(UserFilter $userFilter): User;

    public function create(User $user): User;

    public function update(User $user): User;

    public function delete(string $id): void;

    public function getExistUser(string $idUser): User;
}
