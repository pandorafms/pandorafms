<?php

namespace PandoraFMS\Modules\Events\Filters\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Services\CreateEventFilterService;

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
