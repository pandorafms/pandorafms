<?php

namespace PandoraFMS\Modules\Events\Services;

use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Entities\EventFilter;
use PandoraFMS\Modules\Events\Enums\EventStatusEnum;
use PandoraFMS\Modules\Events\Repositories\EventRepository;

final class KeepInProcessStatusExtraIdEventService
{
    public function __construct(
        private Config $config,
        private EventRepository $eventRepository
    ) {
    }

    public function __invoke(Event $event): Event
    {
        if((bool) $this->config->get('keep_in_process_status_extra_id') === true) {
            if($event->getStatus() === EventStatusEnum::NEW) {
                $eventFilter = new EventFilter();
                /** @var Event $entityFilter */
                $entityFilter = $eventFilter->getEntityFilter();
                $entityFilter->setIdExtra($event->getIdExtra());
                $entityFilter->setStatus(EventStatusEnum::INPROCESS);
                $inprocessCount = $this->eventRepository->count($eventFilter);

                if(empty($inprocessCount) === false) {
                    /** @var Event $inprocessLastEvent */
                    $inprocessLastEvent = end($this->eventRepository->list($eventFilter));
                    $event->setAckUtimestamp($inprocessLastEvent->getAckUtimestamp());
                    $event->setEventCustomId($inprocessLastEvent->getEventCustomId());
                }
            }
        }

        return $event;
    }
}
