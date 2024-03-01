<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Repositories;

use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Repositories\Repository;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileDataMapper;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;

class UserProfileRepository
{
    public function __construct(
        private Repository $repository,
        private UserProfileDataMapper $userProfileDataMapper
    ) {
    }

    /** @return UserProfile[] */
    public function list(UserProfileFilter $userProfileFilter): array
    {
        return $this->repository->__list(
            $userProfileFilter,
            $this->userProfileDataMapper
        );
    }

    public function count(UserProfileFilter $userProfileFilter): int
    {
        return $this->repository->__count(
            $userProfileFilter,
            $this->userProfileDataMapper
        );
    }

    public function getOne(UserProfileFilter $userProfileFilter): UserProfile
    {
        return $this->repository->__getOne(
            $userProfileFilter,
            $this->userProfileDataMapper
        );
    }

    public function create(UserProfile $userProfile): UserProfile
    {
        try {
            $id = $this->repository->__create($userProfile, $this->userProfileDataMapper);
            return $userProfile->setIdUserProfile($id);
        } catch (\Throwable $th) {
            throw new BadRequestException(__('User profile already exists in the bbdd:'.$th->getMessage()));
        }
    }

    public function update(UserProfile $userProfile): UserProfile
    {
        return $this->repository->__update(
            $userProfile,
            $this->userProfileDataMapper,
            $userProfile->getIdUserProfile()
        );
    }

    public function delete(int $id, ?string $key = null): void
    {
        $this->repository->__delete($id, $this->userProfileDataMapper, $key);
    }
}
