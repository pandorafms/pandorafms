<?php

namespace PandoraFMS\Modules\Events\Comments\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="EventCommentFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/EventComment"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idEventComment",
 *         default=null,
 *         readOnly=false
 *       ),
 *       @OA\Property(
 *         property="freeSearch",
 *         type="string",
 *         nullable=true,
 *         default=null,
 *         description="Find word in label fields."
 *       )
 *     )
 *   }
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyEventCommentFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/EventCommentFilter")
 *   ),
 * )
 */
final class EventCommentFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(EventCommentDataMapper::UTIMESTAMP);
        $this->setDefaultDirectionOrder($this::DESC);
        $this->setEntityFilter(new EventComment());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idEventComment' => EventCommentDataMapper::ID_EVENT_COMMENT,
            'idEvent'        => EventCommentDataMapper::ID_EVENT,
            'idUser'         => EventCommentDataMapper::ID_USER,
            'utimestamp'     => EventCommentDataMapper::UTIMESTAMP,
            'comment'        => EventCommentDataMapper::COMMENT,
            'action'         => EventCommentDataMapper::ACTION,
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
    public function getCommentsFreeSearch(): ?array
    {
        return [EventCommentDataMapper::COMMENT];
    }

}
