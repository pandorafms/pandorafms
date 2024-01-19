<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Users\Validations\UserValidation;

final class CreateUserService
{
    public function __construct(
        private Audit $audit,
        private UserRepository $userRepository,
        private UserValidation $userValidation
    ) {
    }

    public function __invoke(User $user): User
    {
        $this->userValidation->__invoke($user);

        $user = $this->userRepository->create($user);

        // TODO: Save pass.
        \save_pass_history($user->getIdUser(), $user->getPasswordValidate());

        $this->audit->write(
            AUDIT_LOG_USER_MANAGEMENT,
            'Create user '.$user->getIdUser(),
            json_encode($user->toArray())
        );

        return $user;
    }
}
