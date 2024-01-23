<?php

namespace PandoraFMS\Modules\Events\Entities;

use PandoraFMS\Modules\Events\Validators\EventValidator;
use PandoraFMS\Modules\Shared\Core\FilterAbstract;

/**
 * @OA\Schema(
 *   schema="EventFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Event"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idEvent",
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
 *   request="requestBodyEventFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/EventFilter")
 *   ),
 * )
 */
final class EventFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(EventDataMapper::UTIMESTAMP);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new Event());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idEvent'    => EventDataMapper::ID_EVENT,
            'utimestamp' => EventDataMapper::UTIMESTAMP,
        ];
    }

    public function fieldsReadOnly(): array
    {
        return [];
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
        $validations['freeSearch'] = EventValidator::STRING;
        return $validations;
    }

    public function validateFields(array $filters): array
    {
        return (new EventValidator())->validate($filters);
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
        return [EventDataMapper::UTIMESTAMP];
    }

}
