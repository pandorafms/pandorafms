<?php

namespace PandoraFMS\Modules\Events\Repositories;

use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Entities\EventDataMapper;
use PandoraFMS\Modules\Events\Entities\EventFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class EventRepository
{
    public function __construct(
        private Repository $repository,
        private EventDataMapper $eventDataMapper
    ) {
    }

    /**
     * @return Event[],
    */
    public function list(EventFilter $eventFilter): array
    {
        return $this->repository->__list(
            $eventFilter,
            $this->eventDataMapper
        );
    }

    public function count(EventFilter $eventFilter): int
    {
        return $this->repository->__count(
            $eventFilter,
            $this->eventDataMapper
        );
    }

    public function getOne(EventFilter $eventFilter): Event
    {
        return $this->repository->__getOne(
            $eventFilter,
            $this->eventDataMapper
        );
    }

    public function create(Event $event): Event
    {
        $id = $this->repository->__create($event, $this->eventDataMapper);
        return $event->setIdEvent($id);
    }

    public function update(Event $event): Event
    {
        return $this->repository->__update(
            $event,
            $this->eventDataMapper,
            $event->getIdEvent()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->eventDataMapper);
    }

}
