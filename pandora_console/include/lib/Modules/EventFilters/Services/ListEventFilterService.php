<?php

namespace PandoraFMS\Modules\EventFilters\Services;

use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\EventFilters\Repositories\EventFilterRepository;

final class ListEventFilterService
{
    public function __construct(
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(EventFilterFilter $eventFilterFilter): array
    {
        return $this->eventFilterRepository->list($eventFilterFilter);
    }
}
