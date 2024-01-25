<?php

namespace PandoraFMS\Modules\EventFilters\Actions;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Services\CreateEventFilterService;

final class CreateEventFilterAction
{
    public function __construct(
        private CreateEventFilterService $createEventFilterService
    ) {
    }

    public function __invoke(EventFilter $eventFilter): EventFilter
    {
        return $this->createEventFilterService->__invoke($eventFilter);
    }
}
