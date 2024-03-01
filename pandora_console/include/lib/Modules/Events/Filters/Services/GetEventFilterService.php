<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;

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
