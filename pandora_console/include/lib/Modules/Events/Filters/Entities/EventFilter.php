<?php

namespace PandoraFMS\Modules\Events\Filters\Entities;

use PandoraFMS\Modules\Events\Filters\Enums\EventFilterAlertEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterCustomDataEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterGroupByEnum;
use PandoraFMS\Modules\Events\Filters\Enums\EventFilterStatusEnum;
use PandoraFMS\Modules\Events\Filters\Validators\EventFilterValidator;
use PandoraFMS\Modules\Events\Enums\EventTypeEnum;
use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Traits\GroupByFilterTrait;
use PandoraFMS\Modules\Shared\Traits\OrderFilterTrait;
use PandoraFMS\Modules\Shared\Traits\PaginationFilterTrait;

/**
 * @OA\Schema(
 *   schema="EventFilter",
 *   type="object",
 *   @OA\Property(
 *     property="idEventFilter",
 *     type="integer",
 *     nullable=false,
 *     description="Id EventFilter",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idGroupFilter",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id group filter"
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="Name of the eventFilter"
 *   ),
 *   @OA\Property(
 *     property="idGroup",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id group"
 *   ),
 *   @OA\Property(
 *     property="severity",
 *     type="array",
 *     nullable=true,
 *     default=null,
 *     description="Severity event filter",
 *     @OA\Items(
 *       @OA\Property(
 *         property="severity",
 *         type="integer",
 *         nullable=true,
 *         enum={
 *           "maintenance",
 *           "informational",
 *           "normal",
 *           "warning",
 *           "critical",
 *           "minor",
 *           "major"
 *         },
 *         default="maintenance",
 *         description="Event severity, the available severity are: maintenance, informational, normal, warning, critical, minor, major"
 *       )
 *     )
 *   ),
 *   @OA\Property(
 *     property="status",
 *     type="integer",
 *     nullable=true,
 *     enum={
 *      "all",
 *      "new",
 *      "validated",
 *      "inprocess",
 *      "not_validated",
 *      "not_in_process"
 *     },
 *     default=null,
 *     description="Event status, the available status are: all, new, validated, inprocess, not_validated, not_in_process"
 *   ),
 *   @OA\Property(
 *     property="eventType",
 *     type="string",
 *     nullable=true,
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
 *     default=null,
 *     description="Event status, the available status are: going_unknown, unknown, alert_fired, alert_recovered, alert_ceased, alert_manual_validation, recon_host_detected, system, error, new_agent, going_up_critical, going_down_critical, going_up_warning, going_down_warning, going_up_normal, going_down_normal, configuration_change, ncm"
 *   ),
 *   @OA\Property(
 *      property="search",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search in fields id, event, extraId, source and custom_data"
 *   ),
 *   @OA\Property(
 *     property="isNotSearch",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Not search"
 *   ),
 *   @OA\Property(
 *      property="textAgent",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Name agent",
 *      readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idAgent",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id agent"
 *   ),
 *   @OA\Property(
 *     property="idAgentModule",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id agent module"
 *   ),
 *   @OA\Property(
 *     property="pagination",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Block size pagination"
 *   ),
 *   @OA\Property(
 *     property="slice",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Period search events 8h"
 *   ),
 *   @OA\Property(
 *     property="idUserAck",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Id user ack"
 *   ),
 *   @OA\Property(
 *     property="groupBy",
 *     type="integer",
 *     nullable=true,
 *     enum={
 *      "all",
 *      "events",
 *      "agents",
 *      "extra_ids"
 *     },
 *     default=null,
 *     description="Event filter group by, the available group by are: all, events, agents, extra_ids"
 *   ),
 *   @OA\Property(
 *     property="tagWith",
 *     type="array",
 *     nullable=true,
 *     default=null,
 *     description="Tags filter",
 *     @OA\Items(
 *       @OA\Property(
 *         property="idTags",
 *         type="integer",
 *         nullable=true,
 *         default=null,
 *         description="Tags id"
 *       )
 *     )
 *   ),
 *   @OA\Property(
 *     property="tagWithout",
 *     type="array",
 *     nullable=true,
 *     default=null,
 *     description="Tags not filter",
 *     @OA\Items(
 *       @OA\Property(
 *         property="idTags",
 *         type="integer",
 *         nullable=true,
 *         default=null,
 *         description="Tags id"
 *       )
 *     )
 *   ),
 *   @OA\Property(
 *     property="filterOnlyAlert",
 *     type="integer",
 *     nullable=true,
 *     enum={
 *      "all",
 *      "filter_alert_events",
 *      "only_alert_events"
 *     },
 *     default=null,
 *     description="Event filter event by, the available filter are: all, filter_alert_events, only_alert_events"
 *   ),
 *   @OA\Property(
 *     property="searchSecondaryGroups",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Search in secondary groups"
 *   ),
 *   @OA\Property(
 *     property="searchRecursiveGroups",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="Search in recursive groups"
 *   ),
 *   @OA\Property(
 *     property="dateFrom",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Search event registration date",
 *     example="2023-02-21"
 *   ),
 *   @OA\Property(
 *     property="dateTo",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Search event registration date",
 *     example="2023-02-21"
 *   ),
 *   @OA\Property(
 *      property="source",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search source field"
 *   ),
 *   @OA\Property(
 *      property="idExtra",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search source idExtra"
 *   ),
 *   @OA\Property(
 *      property="userComment",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search source userComment"
 *   ),
 *   @OA\Property(
 *     property="idSourceEvent",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Search id source event"
 *   ),
 *   @OA\Property(
 *     property="serverId",
 *     type="array",
 *     nullable=true,
 *     default=null,
 *     description="Server ids filter, only metaconsole",
 *     @OA\Items(
 *       @OA\Property(
 *         property="idNode",
 *         type="integer",
 *         nullable=true,
 *         default=null,
 *         description="Node id"
 *       )
 *     )
 *   ),
 *   @OA\Property(
 *     property="timeFrom",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Search event registration date",
 *     example="08:34:16"
 *   ),
 *   @OA\Property(
 *     property="timeTo",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Search event registration date",
 *     example="08:34:16"
 *   ),
 *   @OA\Property(
 *      property="customData",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search custom data"
 *   ),
 *   @OA\Property(
 *     property="customDataFilterType",
 *     type="integer",
 *     nullable=true,
 *     enum={
 *      "only_alert_events",
 *      "filter_alert_events"
 *     },
 *     default=null,
 *     description="Event filter event by, the available filter are: all, filter_alert_events, only_alert_events"
 *   ),
 *   @OA\Property(
 *      property="ownerUser",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search owner user"
 *   ),
 *   @OA\Property(
 *      property="privateFilterUser",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search private filter user"
 *   ),
 *   @OA\Property(
 *      property="regex",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Search regex"
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseEventFilter",
 *   description="EventFilter object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/EventFilter",
 *         description="EventFilter object"
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdEventFilter",
 *   name="idEventFilter",
 *   in="path",
 *   description="EventFilter id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   )
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyEventFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/EventFilter")
 *   )
 * )
 */
final class EventFilter extends Entity
{
    use PaginationFilterTrait;
    use OrderFilterTrait;
    use GroupByFilterTrait;

    private ?int $idEvent = null;

    private ?int $idEventFilter = null;
    private ?int $idGroupFilter = null;
    private ?string $name = null;
    private ?int $idGroup = null;
    private ?EventTypeEnum $eventType = null;
    private ?array $severity = null;
    private ?EventFilterStatusEnum $status = null;
    private ?string $search = null;
    private ?bool $isNotSearch = null;
    private ?string $textAgent = null;
    private ?int $idAgent = null;
    private ?int $idAgentModule = null;
    private ?int $pagination = null;
    private ?int $slice = null;
    private ?string $idUserAck = null;
    private ?EventFilterGroupByEnum $groupBy = null;
    private ?array $tagWith = null;
    private ?array $tagWithout = null;
    private ?EventFilterAlertEnum $filterOnlyAlert = null;
    private ?bool $searchSecondaryGroups = null;
    private ?bool $searchRecursiveGroups = null;
    private ?string $dateFrom = null;
    private ?string $dateTo = null;
    private ?string $source = null;
    private ?string $idExtra = null;
    private ?string $userComment = null;
    private ?int $idSourceEvent = null;
    private ?array $serverId = null;
    private ?string $timeFrom = null;
    private ?string $timeTo = null;
    private ?string $customData = null;
    private ?EventFilterCustomDataEnum $customDataFilterType = null;
    private ?string $ownerUser = null;
    private ?string $privateFilterUser = null;
    private ?string $regex = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return ['idEventFilter' => 1];
    }

    public function toTranslateFilters(): array
    {
        $eventFilterFilter = new EventFilterFilter();
        $filter_translate = $eventFilterFilter->fieldsTranslate();

        $result = [];
        foreach ($this->toArray() as $key => $value) {
            if (isset($filter_translate[$key]) === true
                && $value !== null
            ) {
                $result[$filter_translate[$key]] = $value;
            }
        }

        return $result;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idEventFilter'         => $this->getIdEventFilter(),
            'idEvent'               => $this->getIdEvent(),
            'idGroupFilter'         => $this->getIdGroupFilter(),
            'name'                  => $this->getName(),
            'idGroup'               => $this->getIdGroup(),
            'eventType'             => $this->getEventType()?->name,
            'severity'              => $this->getSeverity(),
            'status'                => $this->getStatus()?->name,
            'search'                => $this->getSearch(),
            'isNotSearch'           => $this->getIsNotSearch(),
            'textAgent'             => $this->getTextAgent(),
            'idAgent'               => $this->getIdAgent(),
            'idAgentModule'         => $this->getIdAgentModule(),
            'pagination'            => $this->getPagination(),
            'slice'                 => $this->getSlice(),
            'idUserAck'             => $this->getIdUserAck(),
            'groupBy'               => $this->getGroupBy()?->name,
            'tagWith'               => $this->getTagWith(),
            'tagWithout'            => $this->getTagWithout(),
            'filterOnlyAlert'       => $this->getFilterOnlyAlert()?->name,
            'searchSecondaryGroups' => $this->getSearchSecondaryGroups(),
            'searchRecursiveGroups' => $this->getSearchRecursiveGroups(),
            'dateFrom'              => $this->getDateFrom(),
            'dateTo'                => $this->getDateTo(),
            'source'                => $this->getSource(),
            'idExtra'               => $this->getIdExtra(),
            'userComment'           => $this->getUserComment(),
            'idSourceEvent'         => $this->getIdSourceEvent(),
            'serverId'              => $this->getServerId(),
            'timeFrom'              => $this->getTimeFrom(),
            'timeTo'                => $this->getTimeTo(),
            'customData'            => $this->getCustomData(),
            'customDataFilterType'  => $this->getCustomDataFilterType()?->name,
            'ownerUser'             => $this->getOwnerUser(),
            'privateFilterUser'     => $this->getPrivateFilterUser(),
            'regex'                 => $this->getRegex(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idEventFilter' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATERTHAN,
            ],
            'idEvent' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATERTHAN,
            ],
            'idGroupFilter' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'name'    => EventFilterValidator::STRING,
            'idGroup' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'eventType'   => EventFilterValidator::VALIDFILTERTYPE,
            'severity'    => EventFilterValidator::ARRAY,
            'status'      => EventFilterValidator::VALIDFILTERSTATUS,
            'search'      => EventFilterValidator::STRING,
            'isNotSearch' => EventFilterValidator::BOOLEAN,
            'textAgent'   => EventFilterValidator::STRING,
            'idAgent'     => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'idAgentModule' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'pagination' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'slice' => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'idUserAck'             => EventFilterValidator::STRING,
            'groupBy'               => EventFilterValidator::VALIDFILTERGROUPBY,
            'tagWith'               => EventFilterValidator::ARRAY,
            'tagWithout'            => EventFilterValidator::ARRAY,
            'filterOnlyAlert'       => EventFilterValidator::VALIDFILTERALERT,
            'searchSecondaryGroups' => EventFilterValidator::BOOLEAN,
            'searchRecursiveGroups' => EventFilterValidator::BOOLEAN,
            'dateFrom'              => EventFilterValidator::DATE,
            'dateTo'                => EventFilterValidator::DATE,
            'source'                => EventFilterValidator::STRING,
            'idExtra'               => EventFilterValidator::STRING,
            'userComment'           => EventFilterValidator::STRING,
            'idSourceEvent'         => [
                EventFilterValidator::INTEGER,
                EventFilterValidator::GREATEREQUALTHAN,
            ],
            'serverId'             => EventFilterValidator::ARRAY,
            'timeFrom'             => EventFilterValidator::TIME,
            'timeTo'               => EventFilterValidator::TIME,
            'customData'           => EventFilterValidator::STRING,
            'customDataFilterType' => EventFilterValidator::VALIDFILTERCUSTOMDATA,
            'ownerUser'            => EventFilterValidator::STRING,
            'privateFilterUser'    => EventFilterValidator::STRING,
            'regex'                => EventFilterValidator::STRING,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new EventFilterValidator())->validate($filters);
    }

    public function getIdEventFilter(): ?int
    {
        return $this->idEventFilter;
    }
    public function setIdEventFilter(?int $idEventFilter): self
    {
        $this->idEventFilter = $idEventFilter;
        return $this;
    }

    public function getIdGroupFilter(): ?int
    {
        return $this->idGroupFilter;
    }
    public function setIdGroupFilter(?int $idGroupFilter): self
    {
        $this->idGroupFilter = $idGroupFilter;
        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): self
    {
        $this->name = $name;
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

    public function getSeverity(): ?array
    {
        return $this->severity;
    }
    public function setSeverity(?array $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getStatus(): ?EventFilterStatusEnum
    {
        return $this->status;
    }
    public function setStatus(null|string|EventFilterStatusEnum $status): self
    {
        if (is_string($status) === true) {
            $this->status = EventFilterStatusEnum::get(strtoupper($status));
        } else {
            $this->status = $status;
        }

        return $this;
    }

    public function getSearch(): ?string
    {
        return $this->search;
    }
    public function setSearch(?string $search): self
    {
        $this->search = $search;
        return $this;
    }

    public function getIsNotSearch(): ?bool
    {
        return $this->isNotSearch;
    }
    public function setIsNotSearch(?bool $isNotSearch): self
    {
        $this->isNotSearch = $isNotSearch;
        return $this;
    }

    public function getTextAgent(): ?string
    {
        return $this->textAgent;
    }
    public function setTextAgent(?string $textAgent): self
    {
        $this->textAgent = $textAgent;
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

    public function getIdAgentModule(): ?int
    {
        return $this->idAgentModule;
    }
    public function setIdAgentModule(?int $idAgentModule): self
    {
        $this->idAgentModule = $idAgentModule;
        return $this;
    }

    public function getPagination(): ?int
    {
        return $this->pagination;
    }
    public function setPagination(?int $pagination): self
    {
        $this->pagination = $pagination;
        return $this;
    }

    public function getSlice(): ?int
    {
        return $this->slice;
    }
    public function setSlice(?int $slice): self
    {
        $this->slice = $slice;
        return $this;
    }

    public function getIdUserAck(): ?string
    {
        return $this->idUserAck;
    }
    public function setIdUserAck(?string $idUserAck): self
    {
        $this->idUserAck = $idUserAck;
        return $this;
    }

    public function getGroupBy(): ?EventFilterGroupByEnum
    {
        return $this->groupBy;
    }
    public function setGroupBy(null|string|EventFilterGroupByEnum $groupBy): self
    {
        if (is_string($groupBy) === true) {
            $this->groupBy = EventFilterGroupByEnum::get(strtoupper($groupBy));
        } else {
            $this->groupBy = $groupBy;
        }

        return $this;
    }

    public function getTagWith(): ?array
    {
        return $this->tagWith;
    }
    public function setTagWith(?array $tagWith): self
    {
        $this->tagWith = $tagWith;
        return $this;
    }

    public function getTagWithout(): ?array
    {
        return $this->tagWithout;
    }
    public function setTagWithout(?array $tagWithout): self
    {
        $this->tagWithout = $tagWithout;
        return $this;
    }

    public function getFilterOnlyAlert(): ?EventFilterAlertEnum
    {
        return $this->filterOnlyAlert;
    }
    public function setFilterOnlyAlert(null|string|EventFilterAlertEnum $filterOnlyAlert): self
    {
        if (is_string($filterOnlyAlert) === true) {
            $this->filterOnlyAlert = EventFilterAlertEnum::get(strtoupper($filterOnlyAlert));
        } else {
            $this->filterOnlyAlert = $filterOnlyAlert;
        }

        return $this;
    }

    public function getSearchSecondaryGroups(): ?bool
    {
        return $this->searchSecondaryGroups;
    }
    public function setSearchSecondaryGroups(?bool $searchSecondaryGroups): self
    {
        $this->searchSecondaryGroups = $searchSecondaryGroups;
        return $this;
    }

    public function getSearchRecursiveGroups(): ?bool
    {
        return $this->searchRecursiveGroups;
    }
    public function setSearchRecursiveGroups(?bool $searchRecursiveGroups): self
    {
        $this->searchRecursiveGroups = $searchRecursiveGroups;
        return $this;
    }

    public function getDateFrom(): ?string
    {
        return $this->dateFrom;
    }
    public function setDateFrom(?string $dateFrom): self
    {
        $this->dateFrom = $dateFrom;
        return $this;
    }

    public function getDateTo(): ?string
    {
        return $this->dateTo;
    }
    public function setDateTo(?string $dateTo): self
    {
        $this->dateTo = $dateTo;
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

    public function getUserComment(): ?string
    {
        return $this->userComment;
    }
    public function setUserComment(?string $userComment): self
    {
        $this->userComment = $userComment;
        return $this;
    }

    public function getIdSourceEvent(): ?int
    {
        return $this->idSourceEvent;
    }
    public function setIdSourceEvent(?int $idSourceEvent): self
    {
        $this->idSourceEvent = $idSourceEvent;
        return $this;
    }

    public function getServerId(): ?array
    {
        return $this->serverId;
    }
    public function setServerId(?array $serverId): self
    {
        $this->serverId = $serverId;
        return $this;
    }

    public function getTimeFrom(): ?string
    {
        return $this->timeFrom;
    }
    public function setTimeFrom(?string $timeFrom): self
    {
        $this->timeFrom = $timeFrom;
        return $this;
    }

    public function getTimeTo(): ?string
    {
        return $this->timeTo;
    }
    public function setTimeTo(?string $timeTo): self
    {
        $this->timeTo = $timeTo;
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

    public function getCustomDataFilterType(): ?EventFilterCustomDataEnum
    {
        return $this->customDataFilterType;
    }
    public function setCustomDataFilterType(null|string|EventFilterCustomDataEnum $customDataFilterType): self
    {
        if (is_string($customDataFilterType) === true) {
            $this->customDataFilterType = EventFilterCustomDataEnum::get(strtoupper($customDataFilterType));
        } else {
            $this->customDataFilterType = $customDataFilterType;
        }

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

    public function getPrivateFilterUser(): ?string
    {
        return $this->privateFilterUser;
    }
    public function setPrivateFilterUser(?string $privateFilterUser): self
    {
        $this->privateFilterUser = $privateFilterUser;
        return $this;
    }

    public function getRegex(): ?string
    {
        return $this->regex;
    }
    public function setRegex(?string $regex): self
    {
        $this->regex = $regex;
        return $this;
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
}
