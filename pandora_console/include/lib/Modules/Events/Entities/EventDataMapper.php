<?php

namespace PandoraFMS\Modules\Events\Entities;

use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Enums\EventStatusEnum;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Shared\Builders\Builder;
use PandoraFMS\Modules\Shared\Core\DataMapperAbstract;
use PandoraFMS\Modules\Shared\Core\MappeableInterface;
use PandoraFMS\Modules\Shared\Repositories\Repository;

final class EventDataMapper extends DataMapperAbstract
{
    public const TABLE_NAME = 'tevento';
    public const ID_EVENT = 'id_evento';
    public const ID_AGENT = 'id_agente';
    public const ID_USER = 'id_usuario';
    public const ID_GROUP = 'id_grupo';
    public const STATUS = 'estado';
    public const TIMESTAMP = 'timestamp';
    public const EVENT = 'evento';
    public const UTIMESTAMP = 'utimestamp';
    public const EVENT_TYPE = 'event_type';
    public const ID_AGENTMODULE = 'id_agentmodule';
    public const ID_ALERT_AM = 'id_alert_am';
    public const SEVERITY = 'criticity';
    public const TAGS = 'tags';
    public const SOURCE = 'source';
    public const ID_EXTRA = 'id_extra';
    public const CRITICAL_INSTRUCTIONS = 'critical_instructions';
    public const WARNING_INSTRUCTIONS = 'warning_instructions';
    public const UNKNOWN_INSTRUCTIONS = 'unknown_instructions';
    public const OWNER_USER = 'owner_user';
    public const ACK_UTIMESTAMP = 'ack_utimestamp';
    public const CUSTOM_DATA = 'custom_data';
    public const DATA = 'data';
    public const MODULE_STATUS = 'module_status';
    public const EVENT_CUSTOM_ID = 'event_custom_id';

    public function __construct(
        private Repository $repository,
        private Builder $builder,
    ) {
        parent::__construct(
            self::TABLE_NAME,
            self::ID_EVENT,
        );
    }

    public function getClassName(): string
    {
        return Event::class;
    }

    public function fromDatabase(array $data): Event
    {
        return $this->builder->build(new Event(), [
            'idEvent'              => $data[self::ID_EVENT],
            'idAgent'              => $data[self::ID_AGENT],
            'idUser'               => $data[self::ID_USER],
            'idGroup'              => $data[self::ID_GROUP],
            'status'               => EventStatusEnum::get($data[self::STATUS]),
            'timestamp'            => $data[self::TIMESTAMP],
            'event'                => $this->repository->safeOutput($data[self::EVENT]),
            'utimestamp'           => $data[self::UTIMESTAMP],
            'eventType'            => EventTypeEnum::get($data[self::EVENT_TYPE]),
            'idAgentModule'        => $data[self::ID_AGENTMODULE],
            'idAlertAm'            => $data[self::ID_ALERT_AM],
            'severity'             => EventSeverityEnum::get($data[self::SEVERITY]),
            'tags'                 => $data[self::TAGS],
            'source'               => $data[self::SOURCE],
            'idExtra'              => $data[self::ID_EXTRA],
            'criticalInstructions' => $this->repository->safeOutput($data[self::CRITICAL_INSTRUCTIONS]),
            'warningInstructions'  => $this->repository->safeOutput($data[self::WARNING_INSTRUCTIONS]),
            'unknownInstructions'  => $this->repository->safeOutput($data[self::UNKNOWN_INSTRUCTIONS]),
            'ownerUser'            => $data[self::OWNER_USER],
            'ackUtimestamp'        => $data[self::ACK_UTIMESTAMP],
            'customData'           => $this->repository->safeOutput($data[self::CUSTOM_DATA]),
            'data'                 => $this->repository->safeOutput($data[self::DATA]),
            'moduleStatus'         => $data[self::MODULE_STATUS],
            'eventCustomId'        => $this->repository->safeOutput($data[self::EVENT_CUSTOM_ID]),
        ]);
    }

    public function toDatabase(MappeableInterface $data): array
    {
        /** @var Event $data */
        return [
            self::ID_EVENT              => $data->getIdEvent(),
            self::ID_AGENT              => $data->getIdAgent(),
            self::ID_USER               => $data->getIdUser(),
            self::ID_GROUP              => $data->getIdGroup(),
            self::STATUS                => $data->getStatus()?->value,
            self::TIMESTAMP             => $data->getTimestamp(),
            self::EVENT                 => $this->repository->safeInput($data->getEvent()),
            self::UTIMESTAMP            => $data->getUtimestamp(),
            self::EVENT_TYPE            => $data->getEventType()?->value,
            self::ID_AGENTMODULE        => $data->getIdAgentModule(),
            self::ID_ALERT_AM           => $data->getIdAlertAm(),
            self::SEVERITY              => $data->getSeverity()?->value,
            self::TAGS                  => $data->getTags(),
            self::SOURCE                => $data->getSource(),
            self::ID_EXTRA              => $data->getIdExtra(),
            self::CRITICAL_INSTRUCTIONS => $this->repository->safeInput($data->getCriticalInstructions()),
            self::WARNING_INSTRUCTIONS  => $this->repository->safeInput($data->getWarningInstructions()),
            self::UNKNOWN_INSTRUCTIONS  => $this->repository->safeInput($data->getUnknownInstructions()),
            self::OWNER_USER            => $data->getOwnerUser(),
            self::ACK_UTIMESTAMP        => $data->getAckUtimestamp(),
            self::CUSTOM_DATA           => $this->repository->safeInput($data->getCustomData()),
            self::DATA                  => $this->repository->safeInput($data->getData()),
            self::MODULE_STATUS         => $data->getModuleStatus(),
            self::EVENT_CUSTOM_ID       => $this->repository->safeInput($data->getEventCustomId()),
        ];
    }
}
