<?php

namespace PandoraFMS\Modules\Events\Actions;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Services\GetEventService;

final class GetEventAction
{
    public function __construct(
        private GetEventService $getEventService
    ) {
    }

    public function __invoke(int $idEvent): Event
    {
        return $this->getEventService->__invoke($idEvent);
    }
}
