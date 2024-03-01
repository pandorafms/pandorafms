<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Users\Entities\UserFilter;
use PandoraFMS\Modules\Users\Repositories\UserRepository;

final class CountUserService
{
    public function __construct(
        private UserRepository $userRepository,
    ) {
    }

    public function __invoke(UserFilter $userFilter): int
    {
        return $this->userRepository->count($userFilter);
    }
}
