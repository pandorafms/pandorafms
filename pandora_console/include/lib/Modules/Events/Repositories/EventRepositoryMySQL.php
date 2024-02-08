<?php

namespace PandoraFMS\Modules\Events\Repositories;

use InvalidArgumentException;
use PandoraFMS\Modules\Shared\Services\Config;
use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Events\Entities\EventDataMapper;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Shared\Repositories\RepositoryMySQL;

class EventRepositoryMySQL extends RepositoryMySQL implements EventRepository
{
    public function __construct(
        private EventDataMapper $eventDataMapper,
        private Config $config
    ) {
    }

    /**
     * @return Event[],
     */
    public function list(EventFilter $eventFilter): array
    {
        try {
            $fields = ['te.*'];
            $filter = $eventFilter->toTranslateFilters();
            $offset = ($eventFilter->getPage() ?? null);
            $limit = ($eventFilter->getSizePage() ?? null);
            $order = ($eventFilter->getSortDirection() ?? null);
            $sort_field = ($eventFilter->getSortField() ?? null);

            $list = $this->getEvents(
                $fields,
                $filter,
                $offset,
                $limit,
                $order,
                $sort_field
            );

        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (is_array($list) === false) {
            throw new NotFoundException(__('%s not found', $this->eventDataMapper->getStringNameClass()));
        }

        $result = [];
        foreach ($list as $fields) {
            $result[] = $this->eventDataMapper->fromDatabase($fields);
        }

        return $result;
    }

    public function count(EventFilter $eventFilter): int
    {
        try {
            $fields = ['count'];
            $filter = $eventFilter->toTranslateFilters();
            $count = $this->getEvents(
                $fields,
                $filter
            );

            if (empty($count) === false
                && isset($count[0]) === true
                && isset($count[0]['nitems']) === true
                && empty($count[0]['nitems']) === false
            ) {
                $count = $count[0]['nitems'];
            }
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        return (int) $count;
    }

    public function getOne(EventFilter $eventFilter): Event
    {
        try {
            $fields = ['te.*'];
            $filter = $eventFilter->toTranslateFilters();
            $result = $this->getEvents(
                $fields,
                $filter
            );

            if (empty($result) === false
                && isset($result[0]) === true
            ) {
                $result = $result[0];
            }
        } catch (\Throwable $th) {
            // Capture errors mysql.
            throw new InvalidArgumentException(
                strip_tags($th->getMessage()),
                HttpCodesEnum::INTERNAL_SERVER_ERROR
            );
        }

        if (empty($result) === true) {
            throw new NotFoundException(__('%s not found', $this->eventDataMapper->getStringNameClass()));
        }

        return $this->eventDataMapper->fromDatabase($result);
    }

    public function create(Event $event): Event
    {
        $id = $this->__create($event, $this->eventDataMapper);
        return $event->setIdEvent($id);
    }

    public function update(Event $event): Event
    {
        return $this->__update(
            $event,
            $this->eventDataMapper,
            $event->getIdEvent()
        );
    }

    public function delete(string $id): void
    {
        $this->__delete($id, $this->eventDataMapper);
    }

    public function getEvents(
        $fields,
        $filter,
        $offset = null,
        $limit = null,
        $order = null,
        $sort_field = null
    ): array {
        ob_start();
        require_once $this->config->get('homedir').'/include/functions_events.php';
        $events = \events_get_all(
            $fields,
            $filter,
            $offset,
            $limit,
            $order,
            $sort_field,
            true
        );
        ob_get_clean();

        if ($events === false) {
            $events = [];
        }

        return $events;
    }
}
