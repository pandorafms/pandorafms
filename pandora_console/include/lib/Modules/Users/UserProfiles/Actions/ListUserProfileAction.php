<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Actions;

use PandoraFMS\Modules\Shared\Entities\PaginationData;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;
use PandoraFMS\Modules\Users\UserProfiles\Services\CountUserProfileService;
use PandoraFMS\Modules\Users\UserProfiles\Services\ListUserProfileService;

final class ListUserProfileAction
{
    public function __construct(
        private ListUserProfileService $listUserProfileService,
        private CountUserProfileService $countUserProfileService
    ) {
    }

    public function __invoke(UserProfileFilter $userProfileFilter): array
    {
        return (new PaginationData(
            $userProfileFilter->getPage(),
            $userProfileFilter->getSizePage(),
            $this->countUserProfileService->__invoke($userProfileFilter),
            $this->listUserProfileService->__invoke($userProfileFilter)
        ))->toArray();
    }
}
