<?php

namespace PandoraFMS\Modules\Events\Filters\Repositories;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilterDataMapper;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Shared\Repositories\Repository;

class EventFilterRepository
{
    public function __construct(
        private Repository $repository,
        private EventFilterDataMapper $eventFilterDataMapper
    ) {
    }

    /**
     * @return EventFilter[],
    */
    public function list(EventFilterFilter $eventFilterFilter): array
    {
        return $this->repository->__list(
            $eventFilterFilter,
            $this->eventFilterDataMapper
        );
    }

    public function count(EventFilterFilter $eventFilterFilter): int
    {
        return $this->repository->__count(
            $eventFilterFilter,
            $this->eventFilterDataMapper
        );
    }

    public function getOne(EventFilterFilter $eventFilterFilter): EventFilter
    {
        return $this->repository->__getOne(
            $eventFilterFilter,
            $this->eventFilterDataMapper
        );
    }

    public function create(EventFilter $eventFilter): EventFilter
    {
        $id = $this->repository->__create($eventFilter, $this->eventFilterDataMapper);
        return $eventFilter->setIdEventFilter($id);
    }

    public function update(EventFilter $eventFilter): EventFilter
    {
        return $this->repository->__update(
            $eventFilter,
            $this->eventFilterDataMapper,
            $eventFilter->getIdEventFilter()
        );
    }

    public function delete(int $id): void
    {
        $this->repository->__delete($id, $this->eventFilterDataMapper);
    }

}
