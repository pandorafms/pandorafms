<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Users\Validations\UserValidation;
use PandoraFMS\Modules\Shared\Services\Audit;

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

        $this->audit->write(
            'User Management',
            ' Create user #'.$user->getIdUser()
        );

        // TODO: Campos personalizados.
        return $user;
    }


}
