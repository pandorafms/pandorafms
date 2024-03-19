<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;

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
