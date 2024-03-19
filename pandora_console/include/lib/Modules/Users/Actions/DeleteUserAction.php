<?php

namespace PandoraFMS\Modules\Users\Actions;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Services\DeleteUserService;

final class DeleteUserAction
{
    public function __construct(
        private DeleteUserService $deleteUserService
    ) {
    }

    public function __invoke(User $user): void
    {
        $this->deleteUserService->__invoke($user);
    }
}
