<?php

namespace PandoraFMS\Modules\Events\Repositories;

use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Events\Entities\Event;

interface EventRepository
{
    /**
     * @return Event[],
     */
    public function list(EventFilter $eventFilter): array;

    public function count(EventFilter $eventFilter): int;

    public function getOne(EventFilter $eventFilter): Event;

    public function create(Event $event): Event;

    public function update(Event $event): Event;

    public function delete(string $id): void;
}
