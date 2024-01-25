<?php

namespace PandoraFMS\Modules\EventFilters\Actions;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Services\UpdateEventFilterService;

final class UpdateEventFilterAction
{
    public function __construct(
        private UpdateEventFilterService $updateEventFilterService
    ) {
    }

    public function __invoke(EventFilter $eventFilter, EventFilter $oldEventFilter): EventFilter
    {
        return $this->updateEventFilterService->__invoke($eventFilter, $oldEventFilter);
    }
}
