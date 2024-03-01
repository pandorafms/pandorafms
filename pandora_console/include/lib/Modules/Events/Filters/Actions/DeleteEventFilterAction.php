<?php

namespace PandoraFMS\Modules\Events\Filters\Actions;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Services\DeleteEventFilterService;

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
