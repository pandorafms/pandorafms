<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;

final class GetProfileService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    ) {
    }

    public function __invoke(int $idProfile): Profile
    {
        $profileFilter = new ProfileFilter();
        /** @var Profile $entityFilter */
        $entityFilter = $profileFilter->getEntityFilter();
        $entityFilter->setIdProfile($idProfile);

        return $this->profileRepository->getOne($profileFilter);
    }
}
