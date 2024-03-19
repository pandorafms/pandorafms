<?php

namespace PandoraFMS\Modules\Tags\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="TagFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Tag"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idTag",
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
 *   request="requestBodyTagFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/TagFilter")
 *   ),
 * )
 */
final class TagFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(TagDataMapper::NAME);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new Tag());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idTag'        => TagDataMapper::ID_TAG,
            'name'         => TagDataMapper::NAME,
            'description'  => TagDataMapper::DESCRIPTION,
            'url'          => TagDataMapper::URL,
            'mail'         => TagDataMapper::MAIL,
            'phone'        => TagDataMapper::PHONE,
            'previousName' => TagDataMapper::PREVIOUS_NAME,
        ];
    }

    public function fieldsReadOnly(): array
    {
        return ['previousName' => 1];
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
        return [TagDataMapper::NAME, TagDataMapper::DESCRIPTION];
    }

}
