<?php

namespace PandoraFMS\Modules\Profiles\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="ProfileFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Profile"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idProfile",
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
 *   request="requestBodyProfileFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/ProfileFilter")
 *   ),
 * )
 */
final class ProfileFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(ProfileDataMapper::NAME);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new Profile());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idProfile' => ProfileDataMapper::ID_PROFILE,
            'name'      => ProfileDataMapper::NAME,
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
    public function getFieldsFreeSearch(): ?array
    {
        return [ProfileDataMapper::NAME];
    }

}
