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
            'idGroup'        => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::ID_GROUP,
            'name'           => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::NAME,
            'icon'           => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::ICON,
            'parent'         => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::PARENT,
            'isPropagate'    => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::IS_PROPAGATE,
            'isAlertEnabled' => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::IS_DISABLED,
            'customId'       => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::CUSTOM_ID,
            'idSkin'         => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::ID_SKIN,
            'description'    => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::DESCRIPTION,
            'contact'        => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::CONTACT,
            'other'          => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::OTHER,
            'password'       => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::PASSWORD,
            'maxAgents'      => GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::MAX_AGENTS,
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
        return [
            GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::NAME,
            GroupDataMapper::TABLE_NAME.'.'.GroupDataMapper::DESCRIPTION,
        ];
    }
}
