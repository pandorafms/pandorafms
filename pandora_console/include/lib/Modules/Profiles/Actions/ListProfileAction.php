<?php

namespace PandoraFMS\Modules\Profiles\Actions;

use PandoraFMS\Modules\Profiles\Entities\ProfileFilter;
use PandoraFMS\Modules\Profiles\Services\CountProfileService;
use PandoraFMS\Modules\Profiles\Services\ListProfileService;

use PandoraFMS\Modules\Shared\Entities\PaginationData;

final class ListProfileAction
{
    public function __construct(
        private ListProfileService $listProfileService,
        private CountProfileService $countProfileService
    ) {
    }

    public function __invoke(ProfileFilter $profileFilter): array
    {
        return (new PaginationData(
            $profileFilter->getPage(),
            $profileFilter->getSizePage(),
            $this->countProfileService->__invoke($profileFilter),
            $this->listProfileService->__invoke($profileFilter)
        ))->toArray();
    }
}
