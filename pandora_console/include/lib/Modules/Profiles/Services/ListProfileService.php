<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;

final class ListProfileService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    ) {
    }

    public function __invoke(ProfileFilter $profileFilter): array
    {
        return $this->profileRepository->list($profileFilter);
    }
}
