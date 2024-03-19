<?php

namespace PandoraFMS\Modules\Events\Filters\Entities;

use PandoraFMS\Modules\Events\Filters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterCustomDataEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterStatusEnum;
use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class EventFilterDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tevent_filter';
    public const ID_FILTER = 'id_filter';
    public const ID_GROUP_FILTER = 'id_group_filter';
    public const NAME = 'id_name';
    public const ID_GROUP = 'id_group';
    public const EVENT_TYPE = 'event_type';
    public const SEVERITY = 'severity';
    public const STATUS = 'status';
    public const SEARCH = 'search';
    public const NOT_SEARCH = 'not_search';
    public const TEXT_AGENT = 'text_agent';
    public const ID_AGENT = 'id_agent';
    public const ID_AGENT_MODULE = 'id_agent_module';
    public const PAGINATION = 'pagination';
    public const SLICE = 'event_view_hr';
    public const ID_USER_ACK = 'id_user_ack';
    public const ORDER_BY = 'group_rep';
    public const TAG_WITH = 'tag_with';
    public const TAG_WITHOUT = 'tag_without';
    public const FILTER_ONLY_ALERT = 'filter_only_alert';
    public const SEARCH_SECONDARY_GROUPS = 'search_secondary_groups';
    public const SEARCH_RECURSIVE_GROUPS = 'search_recursive_groups';
    public const DATE_FROM = 'date_from';
    public const DATE_TO = 'date_to';
    public const SOURCE = 'source';
    public const ID_EXTRA = 'id_extra';
    public const USER_COMMENT = 'user_comment';
    public const ID_SOURCE_EVENT = 'id_source_event';
    public const SERVER_ID = 'server_id';
    public const TIME_FROM = 'time_from';
    public const TIME_TO = 'time_to';
    public const CUSTOM_DATA = 'custom_data';
    public const CUSTOM_DATA_FILTER_TYPE = 'custom_data_filter_type';
    public const OWNER_USER = 'owner_user';
    public const PRIVATE_FILTER_USER = 'private_filter_user';
    public const REGEX = 'regex';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_FILTER,
        );
    }

    public function getClassName(): string
    {
        return EventFilter::class;
    }

    public function fromDatabase(array $data): EventFilter
    {
        $severity = null;
        if(empty($data[self::SEVERITY]) === false) {
            $severities = explode(',', $data[self::SEVERITY]);
            foreach ($severities as $value) {
                $severity[] = EventSeverityEnum::get($value, 'value')?->name;
            }
        }

        return $this->builder->build(new EventFilter(), [
            'idEventFilter'         => $data[self::ID_FILTER],
            'idGroupFilter'         => $data[self::ID_GROUP_FILTER],
            'name'                  => $this->repository->safeOutput($data[self::NAME]),
            'idGroup'               => $data[self::ID_GROUP],
            'eventType'             => EventTypeEnum::get($data[self::EVENT_TYPE]),
            'severity'              => $severity,
            'status'                => EventFilterStatusEnum::get($data[self::STATUS]),
            'search'                => $this->repository->safeOutput($data[self::SEARCH]),
            'isNotSearch'           => $data[self::NOT_SEARCH],
            'textAgent'             => $this->repository->safeOutput($data[self::TEXT_AGENT]),
            'idAgent'               => $data[self::ID_AGENT],
            'idAgentModule'         => $data[self::ID_AGENT_MODULE],
            'pagination'            => $data[self::PAGINATION],
            'slice'                 => $data[self::SLICE],
            'idUserAck'             => $data[self::ID_USER_ACK],
            'groupBy'               => EventFilterGroupByEnum::get($data[self::ORDER_BY]),
            'tagWith'               => (empty($data[self::TAG_WITH]) === false) ? explode(',', $this->repository->safeOutput($data[self::TAG_WITH])) : null,
            'tagWithout'            => (empty($data[self::TAG_WITHOUT]) === false) ? explode(',', $this->repository->safeOutput($data[self::TAG_WITHOUT])) : null,
            'filterOnlyAlert'       => EventFilterAlertEnum::get($data[self::FILTER_ONLY_ALERT]),
            'searchSecondaryGroups' => $data[self::SEARCH_SECONDARY_GROUPS],
            'searchRecursiveGroups' => $data[self::SEARCH_RECURSIVE_GROUPS],
            'dateFrom'              => $data[self::DATE_FROM],
            'dateTo'                => $data[self::DATE_TO],
            'source'                => $this->repository->safeOutput($this->repository->safeOutput($data[self::SOURCE])),
            'idExtra'               => $this->repository->safeOutput($data[self::ID_EXTRA]),
            'userComment'           => $this->repository->safeOutput($data[self::USER_COMMENT]),
            'idSourceEvent'         => $data[self::ID_SOURCE_EVENT],
            'serverId'              => (empty($data[self::SERVER_ID]) === false) ? explode(',', $data[self::SERVER_ID]) : null,
            'timeFrom'              => $data[self::TIME_FROM],
            'timeTo'                => $data[self::TIME_TO],
            'customData'            => $this->repository->safeOutput($data[self::CUSTOM_DATA]),
            'customDataFilterType'  => EventFilterCustomDataEnum::get($data[self::CUSTOM_DATA_FILTER_TYPE]),
            'ownerUser'             => $data[self::OWNER_USER],
            'privateFilterUser'     => $data[self::PRIVATE_FILTER_USER],
            'regex'                 => $this->repository->safeOutput($data[self::REGEX]),
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var EventFilter $data */
        $severity = null;
        if(empty($data->getSeverity()) === false) {
            $severities = [];
            foreach ($data->getSeverity() as $value) {
                $severities[] = EventSeverityEnum::get($value)?->value;
            }
            $severity = implode(',', $severities);
        }

        return [
            self::ID_FILTER               => $data->getIdEventFilter(),
            self::ID_GROUP_FILTER         => $data->getIdGroupFilter(),
            self::NAME                    => $this->repository->safeInput($data->getName()),
            self::ID_GROUP                => $data->getIdGroup(),
            self::EVENT_TYPE              => $data->getEventType()?->value,
            self::SEVERITY                => $severity,
            self::STATUS                  => $data->getStatus()?->value,
            self::SEARCH                  => $this->repository->safeInput($data->getSearch()),
            self::NOT_SEARCH              => $data->getIsNotSearch(),
            self::TEXT_AGENT              => $this->repository->safeInput($data->getTextAgent()),
            self::ID_AGENT                => $data->getIdAgent(),
            self::ID_AGENT_MODULE         => $data->getIdAgentModule(),
            self::PAGINATION              => $data->getPagination(),
            self::SLICE                   => $data->getSlice(),
            self::ID_USER_ACK             => $data->getIdUserAck(),
            self::ORDER_BY                => $data->getGroupBy()?->value,
            self::TAG_WITH                => (empty($data->getTagWith()) === false) ? implode(',', $data->getTagWith()) : null,
            self::TAG_WITHOUT             => (empty($data->getTagWithout()) === false) ? implode(',', $data->getTagWithout()) : null,
            self::FILTER_ONLY_ALERT       => $data->getFilterOnlyAlert()?->value,
            self::SEARCH_SECONDARY_GROUPS => $data->getSearchSecondaryGroups(),
            self::SEARCH_RECURSIVE_GROUPS => $data->getSearchRecursiveGroups(),
            self::DATE_FROM               => $data->getDateFrom(),
            self::DATE_TO                 => $data->getDateTo(),
            self::SOURCE                  => $this->repository->safeInput($data->getSource()),
            self::ID_EXTRA                => $this->repository->safeInput($data->getIdExtra()),
            self::USER_COMMENT            => $this->repository->safeInput($data->getUserComment()),
            self::ID_SOURCE_EVENT         => $data->getIdSourceEvent(),
            self::SERVER_ID               => (empty($data->getServerId()) === false) ? $this->repository->safeInput('"'.implode('","', $data->getServerId()).'"') : null,
            self::TIME_FROM               => $data->getTimeFrom(),
            self::TIME_TO                 => $data->getTimeTo(),
            self::CUSTOM_DATA             => $this->repository->safeInput($data->getCustomData()),
            self::CUSTOM_DATA_FILTER_TYPE => $data->getCustomDataFilterType()?->value,
            self::OWNER_USER              => $data->getOwnerUser(),
            self::PRIVATE_FILTER_USER     => $data->getPrivateFilterUser(),
            self::REGEX                   => $this->repository->safeInput($data->getRegex()),
        ];
    }
}
