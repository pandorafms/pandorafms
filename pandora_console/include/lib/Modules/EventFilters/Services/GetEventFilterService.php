<?php

namespace PandoraFMS\Modules\EventFilters\Services;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\EventFilters\Repositories\EventFilterRepository;

final class GetEventFilterService
{
    public function __construct(
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(int $idEventFilter): EventFilter
    {
        $eventFilterFilter = new EventFilterFilter();
        /** @var EventFilter $entityFilter */
        $entityFilter = $eventFilterFilter->getEntityFilter();
        $entityFilter->setIdEventFilter($idEventFilter);

        return $this->eventFilterRepository->getOne($eventFilterFilter);
    }
}
