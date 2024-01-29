<?php

namespace PandoraFMS\Modules\Events\Filters\Services;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Events\Filters\Repositories\EventFilterRepository;

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
