<?php

namespace PandoraFMS\Modules\Events\Actions;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Services\CreateEventService;

final class CreateEventAction
{
    public function __construct(
        private CreateEventService $createEventService
    ) {
    }

    public function __invoke(Event $event): Event
    {
        return $this->createEventService->__invoke($event);
    }
}
