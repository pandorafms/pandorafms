<?php

namespace PandoraFMS\Modules\Events\Filters\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Services\UpdateEventFilterService;

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
