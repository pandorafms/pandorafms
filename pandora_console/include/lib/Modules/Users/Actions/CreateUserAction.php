<?php

namespace PandoraFMS\Modules\Users\Actions;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Services\CreateUserService;

final class CreateUserAction
{


    public function __construct(
        private CreateUserService $createUserService
    ) {
    }


    public function __invoke(User $user): User
    {
        return $this->createUserService->__invoke($user);
    }


}
