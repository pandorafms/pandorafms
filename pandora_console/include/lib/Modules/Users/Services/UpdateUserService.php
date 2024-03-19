<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Users\Validations\UserValidation;

final class UpdateUserService
{
    public function __construct(
        private Audit $audit,
        private UserRepository $userRepository,
        private UserValidation $userValidation
    ) {
    }

    public function __invoke(User $user, User $oldUser): User
    {
        $this->userValidation->__invoke($user, $oldUser);

        $user = $this->userRepository->update($user);

        // TODO: Save pass.
        if (empty($user->getPasswordValidate()) === false) {
            \save_pass_history($user->getIdUser(), $user->getPasswordValidate());
        }

        $this->audit->write(
            'User Management',
            ' Update user '.$user->getIdUser(),
            json_encode($user->toArray())
        );

        return $user;
    }
}
