<?php

namespace PandoraFMS\Modules\Events\Entities;

use PandoraFMS\Modules\Events\Enums\EventSeverityEnum;
use PandoraFMS\Modules\Events\Enums\EventStatusEnum;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Events\Validators\EventValidator;
use PandoraFMS\Modules\Shared\Entities\Entity;

/**
 * @OA\Schema(
 *   schema="Event",
 *   type="object",
 *   @OA\Property(
 *     property="idEvent",
 *     type="integer",
 *     nullable=false,
 *     description="Id event"
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idAgent",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id agent"
 *   ),
 *   @OA\Property(
 *     property="idUser",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Id user"
 *   ),
 *   @OA\Property(
 *     property="idGroup",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id group"
 *   ),
 *   @OA\Property(
 *     property="status",
 *     type="integer",
 *     nullable=false,
 *     enum={
 *      "new",
 *      "validated",
 *      "inprocess",
 *     },
 *     default="new",
 *     description="Event status, the available status are: new, validated, inprocess"
 *   ),
 *   @OA\Property(
 *     property="timestamp",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Event registration date",
 *     example="2023-02-21 08:34:16",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *      property="event",
 *      type="string",
 *      nullable=false,
 *      default="Event created for api",
 *      description="Description event"
 *   ),
 *   @OA\Property(
 *     property="utimestamp",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Event registration date",
 *     example="1704898868",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="eventType",
 *     type="string",
 *     nullable=false,
 *     enum={
 *      "going_unknown",
 *      "unknown",
 *      "alert_fired",
 *      "alert_recovered",
 *      "alert_ceased",
 *      "alert_manual_validation",
 *      "recon_host_detected",
 *      "system",
 *      "error",
 *      "new_agent",
 *      "going_up_critical",
 *      "going_down_critical",
 *      "going_up_warning",
 *      "going_down_warning",
 *      "going_up_normal",
 *      "going_down_normal",
 *      "configuration_change",
 *      "ncm"
 *     },
 *     default="unknown",
 *     description="Event status, the available status are: going_unknown, unknown, alert_fired, alert_recovered, alert_ceased, alert_manual_validation, recon_host_detected, system, error, new_agent, going_up_critical, going_down_critical, going_up_warning, going_down_warning, going_up_normal, going_down_normal, configuration_change, ncm"
 *   ),
 *   @OA\Property(
 *     property="idAgentModule",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id agent module"
 *   ),
 *   @OA\Property(
 *     property="idAlertAm",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id alert action"
 *   ),
 *   @OA\Property(
 *     property="severity",
 *     type="integer",
 *     nullable=false,
 *     enum={
 *       "maintenance",
 *       "informational",
 *       "normal",
 *       "warning",
 *       "critical",
 *       "minor",
 *       "major"
 *     },
 *     default="maintenance",
 *     description="Event severity, the available severity are: maintenance, informational, normal, warning, critical, minor, major"
 *   ),
 *   @OA\Property(
 *      property="tags",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Tags"
 *   ),
 *   @OA\Property(
 *      property="source",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Source"
 *   ),
 *   @OA\Property(
 *      property="idExtra",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Extra id"
 *   ),
 *   @OA\Property(
 *      property="criticalInstructions",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Critical instructions"
 *   ),
 *   @OA\Property(
 *      property="warningInstructions",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Warning instructions"
 *   ),
 *   @OA\Property(
 *      property="unknownInstructions",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Unknows instructions"
 *   ),
 *   @OA\Property(
 *     property="ownerUser",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Id user"
 *   ),
 *   @OA\Property(
 *     property="ackUtimestamp",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Event ack utimestamp",
 *     example="1704898868",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *      property="customData",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Custom data"
 *   ),
 *   @OA\Property(
 *      property="data",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Data"
 *   ),
 *   @OA\Property(
 *     property="moduleStatus",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Module status",
 *     readonly=true
 *   ),
 *   @OA\Property(
 *      property="eventCustomId",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Events Custom Id"
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseEvent",
 *   description="Event object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/Event",
 *         description="Event object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdEvent",
 *   name="idEvent",
 *   in="path",
 *   description="Event id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyEvent",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/Event")
 *   ),
 * )
 */
final class Event extends Entity
{
    private ?int $idEvent = null;
    private ?int $idAgent = null;
    private ?string $idUser = null;
    private ?int $idGroup = null;
    private ?EventStatusEnum $status = null;
    private ?string $timestamp = null;
    private ?string $event = null;
    private ?int $utimestamp = null;
    private ?EventTypeEnum $eventType = null;
    private ?int $idAgentModule = null;
    private ?int $idAlertAm = null;
    private ?EventSeverityEnum $severity = null;
    private ?string $tags = null;
    private ?string $source = null;
    private ?string $idExtra = null;
    private ?string $criticalInstructions = null;
    private ?string $warningInstructions = null;
    private ?string $unknownInstructions = null;
    private ?string $ownerUser = null;
    private ?int $ackUtimestamp = null;
    private ?string $customData = null;
    private ?string $data = null;
    private ?int $moduleStatus = null;
    private ?string $eventCustomId = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return [
            'idEvent'       => 1,
            'timestamp'     => 1,
            'utimestamp'    => 1,
            'ackUtimestamp' => 1,
            'moduleStatus'  => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idEvent'              => $this->getIdEvent(),
            'idAgent'              => $this->getIdAgent(),
            'idUser'               => $this->getIdUser(),
            'idGroup'              => $this->getIdGroup(),
            'status'               => $this->getStatus()?->name,
            'timestamp'            => $this->getTimestamp(),
            'event'                => $this->getEvent(),
            'utimestamp'           => $this->getUtimestamp(),
            'eventType'            => $this->getEventType()?->name,
            'idAgentModule'        => $this->getIdAgentModule(),
            'idAlertAm'            => $this->getIdAlertAm(),
            'severity'             => $this->getSeverity()?->name,
            'tags'                 => $this->getTags(),
            'source'               => $this->getSource(),
            'idExtra'              => $this->getIdExtra(),
            'criticalInstructions' => $this->getCriticalInstructions(),
            'warningInstructions'  => $this->getWarningInstructions(),
            'unknownInstructions'  => $this->getUnknownInstructions(),
            'ownerUser'            => $this->getOwnerUser(),
            'ackUtimestamp'        => $this->getAckUtimestamp(),
            'customData'           => $this->getCustomData(),
            'data'                 => $this->getData(),
            'moduleStatus'         => $this->getModuleStatus(),
            'eventCustomId'        => $this->getEventCustomId(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idEvent' => [
                EventValidator::INTEGER,
                EventValidator::GREATERTHAN,
            ],
            'idAgent' => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'idUser'  => EventValidator::STRING,
            'idGroup' => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'status'     => EventValidator::VALIDSTATUS,
            'timestamp'  => EventValidator::DATETIME,
            'event'      => EventValidator::STRING,
            'utimestamp' => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'eventType'     => EventValidator::VALIDTYPE,
            'idAgentModule' => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'idAlertAm' => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'severity'             => EventValidator::VALIDSEVERITY,
            'tags'                 => EventValidator::STRING,
            'source'               => EventValidator::STRING,
            'idExtra'              => EventValidator::STRING,
            'criticalInstructions' => EventValidator::STRING,
            'warningInstructions'  => EventValidator::STRING,
            'unknownInstructions'  => EventValidator::STRING,
            'ownerUser'            => EventValidator::STRING,
            'ackUtimestamp'        => [
                EventValidator::INTEGER,
                EventValidator::GREATEREQUALTHAN,
            ],
            'customData'    => EventValidator::STRING,
            'data'          => EventValidator::STRING,
            'moduleStatus'  => EventValidator::INTEGER,
            'eventCustomId' => EventValidator::STRING,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new EventValidator())->validate($filters);
    }

    public function getIdEvent(): ?int
    {
        return $this->idEvent;
    }
    public function setIdEvent(?int $idEvent): self
    {
        $this->idEvent = $idEvent;
        return $this;
    }

    public function getIdAgent(): ?int
    {
        return $this->idAgent;
    }
    public function setIdAgent(?int $idAgent): self
    {
        $this->idAgent = $idAgent;
        return $this;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }
    public function setIdUser(?string $idUser): self
    {
        $this->idUser = $idUser;
        return $this;
    }

    public function getIdGroup(): ?int
    {
        return $this->idGroup;
    }
    public function setIdGroup(?int $idGroup): self
    {
        $this->idGroup = $idGroup;
        return $this;
    }

    public function getStatus(): ?EventStatusEnum
    {
        return $this->status;
    }
    public function setStatus(null|string|EventStatusEnum $status): self
    {
        if (is_string($status) === true) {
            $this->status = EventStatusEnum::get(strtoupper($status));
        } else {
            $this->status = $status;
        }

        return $this;
    }

    public function getTimestamp(): ?string
    {
        return $this->timestamp;
    }
    public function setTimestamp(?string $timestamp): self
    {
        $this->timestamp = $timestamp;
        return $this;
    }

    public function getEvent(): ?string
    {
        return $this->event;
    }
    public function setEvent(?string $event): self
    {
        $this->event = $event;
        return $this;
    }

    public function getUtimestamp(): ?int
    {
        return $this->utimestamp;
    }
    public function setUtimestamp(?int $utimestamp): self
    {
        $this->utimestamp = $utimestamp;
        return $this;
    }

    public function getEventType(): ?EventTypeEnum
    {
        return $this->eventType;
    }
    public function setEventType(null|string|EventTypeEnum $eventType): self
    {
        if (is_string($eventType) === true) {
            $this->eventType = EventTypeEnum::get(strtoupper($eventType));
        } else {
            $this->eventType = $eventType;
        }

        return $this;
    }

    public function getIdAgentModule(): ?int
    {
        return $this->idAgentModule;
    }
    public function setIdAgentModule(?int $idAgentModule): self
    {
        $this->idAgentModule = $idAgentModule;
        return $this;
    }

    public function getIdAlertAm(): ?int
    {
        return $this->idAlertAm;
    }
    public function setIdAlertAm(?int $idAlertAm): self
    {
        $this->idAlertAm = $idAlertAm;
        return $this;
    }

    public function getSeverity(): ?EventSeverityEnum
    {
        return $this->severity;
    }
    public function setSeverity(null|string|EventSeverityEnum $severity): self
    {
        if (is_string($severity) === true) {
            $this->severity = EventSeverityEnum::get(strtoupper($severity));
        } else {
            $this->severity = $severity;
        }

        return $this;
    }

    public function getTags(): ?string
    {
        return $this->tags;
    }
    public function setTags(?string $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }
    public function setSource(?string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getIdExtra(): ?string
    {
        return $this->idExtra;
    }
    public function setIdExtra(?string $idExtra): self
    {
        $this->idExtra = $idExtra;
        return $this;
    }

    public function getCriticalInstructions(): ?string
    {
        return $this->criticalInstructions;
    }
    public function setCriticalInstructions(?string $criticalInstructions): self
    {
        $this->criticalInstructions = $criticalInstructions;
        return $this;
    }

    public function getWarningInstructions(): ?string
    {
        return $this->warningInstructions;
    }
    public function setWarningInstructions(?string $warningInstructions): self
    {
        $this->warningInstructions = $warningInstructions;
        return $this;
    }

    public function getUnknownInstructions(): ?string
    {
        return $this->unknownInstructions;
    }
    public function setUnknownInstructions(?string $unknownInstructions): self
    {
        $this->unknownInstructions = $unknownInstructions;
        return $this;
    }

    public function getOwnerUser(): ?string
    {
        return $this->ownerUser;
    }
    public function setOwnerUser(?string $ownerUser): self
    {
        $this->ownerUser = $ownerUser;
        return $this;
    }

    public function getAckUtimestamp(): ?int
    {
        return $this->ackUtimestamp;
    }
    public function setAckUtimestamp(?int $ackUtimestamp): self
    {
        $this->ackUtimestamp = $ackUtimestamp;
        return $this;
    }

    public function getCustomData(): ?string
    {
        return $this->customData;
    }
    public function setCustomData(?string $customData): self
    {
        $this->customData = $customData;
        return $this;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
    public function setData(?string $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function getModuleStatus(): ?int
    {
        return $this->moduleStatus;
    }
    public function setModuleStatus(?int $moduleStatus): self
    {
        $this->moduleStatus = $moduleStatus;
        return $this;
    }

    public function getEventCustomId(): ?string
    {
        return $this->eventCustomId;
    }
    public function setEventCustomId(?string $eventCustomId): self
    {
        $this->eventCustomId = $eventCustomId;
        return $this;
    }
}
