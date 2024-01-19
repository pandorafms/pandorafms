<?php

namespace PandoraFMS\Modules\Events\Actions;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Services\DeleteEventService;

final class DeleteEventAction
{
    public function __construct(
        private DeleteEventService $deleteEventService
    ) {
    }

    public function __invoke(Event $event): void
    {
        $this->deleteEventService->__invoke($event);
    }
}
