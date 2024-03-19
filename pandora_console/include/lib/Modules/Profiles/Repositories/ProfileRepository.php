<?php

namespace PandoraFMS\Modules\Profiles\Repositories;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Entities\ProfileDataMapper;
use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class ProfileRepository
{
    public function __construct(
        private Repository $repository,
        private ProfileDataMapper $profileDataMapper
    ) {
    }

    /**
     * @return Profile[],
    */
    public function list(ProfileFilter $profileFilter): array
    {
        return $this->repository->__list(
            $profileFilter,
            $this->profileDataMapper
        );
    }

    public function count(ProfileFilter $profileFilter): int
    {
        return $this->repository->__count(
            $profileFilter,
            $this->profileDataMapper
        );
    }

    public function getOne(ProfileFilter $profileFilter): Profile
    {
        return $this->repository->__getOne(
            $profileFilter,
            $this->profileDataMapper
        );
    }

    public function create(Profile $profile): Profile
    {
        $id = $this->repository->__create($profile, $this->profileDataMapper);
        return $profile->setIdProfile($id);
    }

    public function update(Profile $profile): Profile
    {
        return $this->repository->__update(
            $profile,
            $this->profileDataMapper,
            $profile->getIdProfile()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->profileDataMapper);
    }

}
