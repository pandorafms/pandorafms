<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Services\Audit;
use PandoraFMS\Modules\Users\Entities\User;

use PandoraFMS\Modules\Users\Repositories\UserRepository;

final class DeleteUserService
{
    public function __construct(
        private Audit $audit,
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(User $user): void
    {
        $idUser = $user->getIdUser();

        $this->userRepository->delete($idUser);

        $this->audit->write(
            'User Management',
            ' Deleted user #'.$idUser
        );
    }
}
