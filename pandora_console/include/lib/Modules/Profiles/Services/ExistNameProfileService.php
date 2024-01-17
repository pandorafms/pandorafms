<?php

namespace PandoraFMS\Modules\Profiles\Services;

use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Profiles\Repositories\ProfileRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistNameProfileService
{
    public function __construct(
        private ProfileRepository $profileRepository,
    ) {
    }

    public function __invoke(string $name): bool
    {
        $profileFilter = new ProfileFilter();
        /** @var Profile $entityFilter */
        $entityFilter = $profileFilter->getEntityFilter();
        $entityFilter->setName($name);

        try {
            $this->profileRepository->getOne($profileFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
