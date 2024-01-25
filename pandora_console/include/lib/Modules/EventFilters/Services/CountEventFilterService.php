<?php

namespace PandoraFMS\Modules\EventFilters\Services;

use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\EventFilters\Repositories\EventFilterRepository;

final class CountEventFilterService
{
    public function __construct(
        private EventFilterRepository $eventFilterRepository,
    ) {
    }

    public function __invoke(EventFilterFilter $eventFilterFilter): int
    {
        return $this->eventFilterRepository->count($eventFilterFilter);
    }
}
