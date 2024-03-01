<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistIdUserService
{
    public function __construct(
        private UserRepository $UserRepository,
    ) {
    }

    public function __invoke(string $idUser): bool
    {
        try {
            $this->UserRepository->getExistUser($idUser);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
