<?php

namespace PandoraFMS\Modules\Groups\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="GroupFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Group"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idGroup",
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
 *   request="requestBodyGroupFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/GroupFilter")
 *   ),
 * )
 */
final class GroupFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(GroupDataMapper::NAME);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new Group());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idGroup'     => GroupDataMapper::ID_GROUP,
            'name'        => GroupDataMapper::NAME,
            'icon'        => GroupDataMapper::ICON,
            'parent'      => GroupDataMapper::PARENT,
            'isPropagate' => GroupDataMapper::IS_PROPAGATE,
            'isAlertEnabled'  => GroupDataMapper::IS_DISABLED,
            'customId'    => GroupDataMapper::CUSTOM_ID,
            'idSkin'      => GroupDataMapper::ID_SKIN,
            'description' => GroupDataMapper::DESCRIPTION,
            'contact'     => GroupDataMapper::CONTACT,
            'other'       => GroupDataMapper::OTHER,
            'password'    => GroupDataMapper::PASSWORD,
            'maxAgents'   => GroupDataMapper::MAX_AGENTS,
        ];
    }

    public function fieldsReadOnly(): array
    {
        return ['password' => 1];
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
        return [GroupDataMapper::NAME, GroupDataMapper::DESCRIPTION];
    }
}
