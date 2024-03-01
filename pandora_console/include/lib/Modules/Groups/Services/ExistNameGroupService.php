<?php

namespace PandoraFMS\Modules\Groups\Services;

use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistNameGroupService
{
    public function __construct(
        private GroupRepository $GroupRepository,
    ) {
    }

    public function __invoke(string $name): bool
    {
        $GroupFilter = new GroupFilter();
        /** @var Group $entityFilter */
        $entityFilter = $GroupFilter->getEntityFilter();
        $entityFilter->setName($name);

        try {
            $this->GroupRepository->getOne($GroupFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
