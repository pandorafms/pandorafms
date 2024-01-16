<?php

namespace PandoraFMS\Modules\Users\Actions;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Services\UpdateUserService;

final class UpdateUserAction
{
    public function __construct(
        private UpdateUserService $updateUserService
    ) {
    }

    public function __invoke(User $user, User $oldUser): User
    {
        return $this->updateUserService->__invoke($user, $oldUser);
    }
}
