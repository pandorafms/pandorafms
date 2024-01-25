<?php

namespace PandoraFMS\Modules\EventFilters\Services;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\EventFilters\Repositories\EventFilterRepository;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;

final class ExistNameEventFilterService
{
    public function __construct(
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(string $name): bool
    {
        $eventFilterFilter = new EventFilterFilter();
        /** @var EventFilter $entityFilter */
        $entityFilter = $eventFilterFilter->getEntityFilter();
        $entityFilter->setName($name);

        try {
            $this->eventFilterRepository->getOne($eventFilterFilter);
            return true;
        } catch (NotFoundException) {
            return false;
        }
    }
}
