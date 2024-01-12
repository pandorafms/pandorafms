<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Entities\UserFilter;
use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileDataMapper;

final class GetUserService
{


    public function __construct(
        private UserRepository $userRepository,
    ) {
    }


    public function __invoke(string $idUser): User
    {
        $userFilter = new UserFilter();

        /*
            @var User $entityFilter
        */
        $entityFilter = $userFilter->getEntityFilter();
        $entityFilter->setIdUser($idUser);

        return $this->userRepository->getOne($userFilter);
    }


}
