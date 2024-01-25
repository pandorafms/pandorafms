<?php

namespace PandoraFMS\Modules\EventFilters\Actions;

use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\EventFilters\Services\DeleteEventFilterService;

final class DeleteEventFilterAction
{
    public function __construct(
        private DeleteEventFilterService $deleteEventFilterService
    ) {
    }

    public function __invoke(EventFilter $eventFilter): void
    {
        $this->deleteEventFilterService->__invoke($eventFilter);
    }
}
