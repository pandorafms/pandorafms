<?php

namespace PandoraFMS\Modules\Events\Filters\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="EventFilterFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/EventFilter"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idEventFilter",
 *         default=null,
 *         readOnly=false
 *       ),
 *       @OA\Property(
 *         property="freeSearch",
 *         type="string",
 *         nullable=true,
 *         default=null,
 *         description="Find word in name field."
 *       )
 *     )
 *   }
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyEventFilterFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/EventFilterFilter")
 *   ),
 * )
 */
final class EventFilterFilter extends FilterAbstract
{
    private ?string $freeSearch = null;
    private ?string $fieldAclGroupMysql = EventFilterDataMapper::ID_GROUP;

    public function __construct()
    {
        $this->setDefaultFieldOrder(EventFilterDataMapper::NAME);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new EventFilter());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idEventFilter'         => EventFilterDataMapper::ID_FILTER,
            'idEvent'               => 'id_event',
            'name'                  => EventFilterDataMapper::NAME,
            'idEventFilter'         => EventFilterDataMapper::ID_FILTER,
            'idGroupFilter'         => EventFilterDataMapper::ID_GROUP_FILTER,
            'name'                  => EventFilterDataMapper::NAME,
            'idGroup'               => EventFilterDataMapper::ID_GROUP,
            'eventType'             => EventFilterDataMapper::EVENT_TYPE,
            'severity'              => EventFilterDataMapper::SEVERITY,
            'status'                => EventFilterDataMapper::STATUS,
            'search'                => EventFilterDataMapper::SEARCH,
            'isNotSearch'           => EventFilterDataMapper::NOT_SEARCH,
            'textAgent'             => EventFilterDataMapper::TEXT_AGENT,
            'idAgent'               => EventFilterDataMapper::ID_AGENT,
            'idAgentModule'         => EventFilterDataMapper::ID_AGENT_MODULE,
            'pagination'            => EventFilterDataMapper::PAGINATION,
            'slice'                 => EventFilterDataMapper::SLICE,
            'idUserAck'             => EventFilterDataMapper::ID_USER_ACK,
            'groupBy'               => EventFilterDataMapper::ORDER_BY,
            'tagWith'               => EventFilterDataMapper::TAG_WITH,
            'tagWithout'            => EventFilterDataMapper::TAG_WITHOUT,
            'filterOnlyAlert'       => EventFilterDataMapper::FILTER_ONLY_ALERT,
            'searchSecondaryGroups' => EventFilterDataMapper::SEARCH_SECONDARY_GROUPS,
            'searchRecursiveGroups' => EventFilterDataMapper::SEARCH_RECURSIVE_GROUPS,
            'dateFrom'              => EventFilterDataMapper::DATE_FROM,
            'dateTo'                => EventFilterDataMapper::DATE_TO,
            'source'                => EventFilterDataMapper::SOURCE,
            'idExtra'               => EventFilterDataMapper::ID_EXTRA,
            'userComment'           => EventFilterDataMapper::USER_COMMENT,
            'idSourceEvent'         => EventFilterDataMapper::ID_SOURCE_EVENT,
            'serverId'              => EventFilterDataMapper::SERVER_ID,
            'timeFrom'              => EventFilterDataMapper::TIME_FROM,
            'timeTo'                => EventFilterDataMapper::TIME_TO,
            'customData'            => EventFilterDataMapper::CUSTOM_DATA,
            'customDataFilterType'  => EventFilterDataMapper::CUSTOM_DATA_FILTER_TYPE,
            'ownerUser'             => EventFilterDataMapper::OWNER_USER,
            'privateFilterUser'     => EventFilterDataMapper::PRIVATE_FILTER_USER,
            'regex'                 => EventFilterDataMapper::REGEX,
        ];
    }

    public function fieldsReadOnly(): array
    {
        return [
            'tagWith'    => 1,
            'tagWithout' => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'freeSearch' => $this->getFreeSearch(),
        ];
    }

    public function getValidations(): array
    {
        $validations = [];
        if($this->getEntityFilter() !== null) {
            $validations = $this->getEntityFilter()->getValidations();
        }
        $validations['freeSearch'] = Validator::STRING;
        return $validations;
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    /**
     * Get the value of freeSearch.
     *
     * @return ?string
     */
    public function getFreeSearch(): ?string
    {
        return $this->freeSearch;
    }

    /**
     * Set the value of freeSearch.
     *
     * @param ?string $freeSearch
     *
     */
    public function setFreeSearch(?string $freeSearch): self
    {
        $this->freeSearch = $freeSearch;
        return $this;
    }

    /**
     * Get the value of fieldsFreeSearch.
     *
     * @return ?array
     */
    public function getFieldsFreeSearch(): ?array
    {
        return [EventFilterDataMapper::NAME];
    }

    /**
     * Get the value of fieldAclGroupMysql.
     *
     * @return ?string
     */
    public function getFieldAclGroupMysql(): ?string
    {
        return $this->fieldAclGroupMysql;
    }

    /**
     * Set the value of fieldAclGroupMysql.
     *
     * @param ?string $fieldAclGroupMysql
     *
     */
    public function setFieldAclGroupMysql(?string $fieldAclGroupMysql): self
    {
        $this->fieldAclGroupMysql = $fieldAclGroupMysql;

        return $this;
    }

    /**
     * Get the value of mode for check ACL.
     *
     * @return ?string
     */
    public function getModeAclGroupMysql(): ?string
    {
        return '';
    }
}
