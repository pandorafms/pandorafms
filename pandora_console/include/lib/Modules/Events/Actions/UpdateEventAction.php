<?php

namespace PandoraFMS\Modules\Events\Actions;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Services\UpdateEventService;

final class UpdateEventAction
{
    public function __construct(
        private UpdateEventService $updateEventService
    ) {
    }

    public function __invoke(Event $event, Event $oldEvent): Event
    {
        return $this->updateEventService->__invoke($event, $oldEvent);
    }
}
