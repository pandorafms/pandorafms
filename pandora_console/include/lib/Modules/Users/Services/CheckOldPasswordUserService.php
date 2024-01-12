<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Repositories\Repository;
use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Entities\UserDataMapper;
use PandoraFMS\Modules\Users\Entities\UserFilter;

final class CheckOldPasswordUserService
{
    public function __construct(
        private Repository $repository,
        private UserDataMapper $userDataMapper
    ) {
    }

    public function __invoke(User $user): void
    {
        $userFilter = new UserFilter();
        $userFilter->setIdUser($user->getIdUser());
        $userFilter->setPassword($user->getOldPassword());
        try {
            $this->repository->__getOne(
                $userFilter,
                $this->userDataMapper
            );
        } catch (NotFoundException) {
            throw new BadRequestException(__('User or the old password is not correct'));
        }
    }
}
