<?php

namespace PandoraFMS\Modules\Users\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Users\Validators\UserValidator;

/**
 * @OA\Schema(
 *   schema="UserFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/User"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="freeSearch",
 *         type="string",
 *         nullable=true,
 *         default=null,
 *         description="Find word in fullname and comments fields."
 *       ),
 *       @OA\Property(
 *         property="multipleSearchString",
 *         type="string",
 *         nullable=true,
 *         default=null,
 *         description="search string in field."
 *       )
 *     )
 *   }
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyUserFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/UserFilter")
 *   ),
 * )
 */
final class UserFilter extends FilterAbstract
{
    private ?string $freeSearch = null;
    private ?array $multipleSearchString = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(UserDataMapper::ID_USER);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new User());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idUser'   => UserDataMapper::TABLE_NAME.'.'.UserDataMapper::ID_USER,
            'fullName' => UserDataMapper::TABLE_NAME.'.'.UserDataMapper::FULLNAME,
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
        if ($this->getEntityFilter() !== null) {
            $validations = $this->getEntityFilter()->getValidations();
        }

        $validations['freeSearch'] = UserValidator::STRING;
        return $validations;
    }

    public function validateFields(array $filters): array
    {
        return (new UserValidator())->validate($filters);
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
            UserDataMapper::TABLE_NAME.'.'.UserDataMapper::FULLNAME,
            UserDataMapper::TABLE_NAME.'.'.UserDataMapper::ID_USER,
        ];
    }

    /**
     * Get the value of multipleSearchString.
     *
     * @return ?array
     */
    public function getMultipleSearchString(): ?array
    {
        return $this->multipleSearchString;
    }

    /**
     * Set the value of multipleSearchString.
     *
     * @param ?array $multipleSearchString
     */
    public function setMultipleSearchString(?array $multipleSearchString): self
    {
        $this->multipleSearchString = $multipleSearchString;
        return $this;
    }
}
