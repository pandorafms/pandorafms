<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;

final class CountProfileService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    ) {
    }

    public function __invoke(ProfileFilter $profileFilter): int
    {
        return $this->profileRepository->count($profileFilter);
    }
}
