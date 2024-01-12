<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Users\Entities\UserFilter;
use PandoraFMS\Modules\Users\Repositories\UserRepository;

final class ListUserService
{


    public function __construct(
        private UserRepository $userRepository,
    ) {
    }


    public function __invoke(UserFilter $userFilter): array
    {
        return $this->userRepository->list($userFilter);
    }


}
