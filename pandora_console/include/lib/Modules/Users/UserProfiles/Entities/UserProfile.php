<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="UserProfile",
 *   type="object",
 *   @OA\Property(
 *     property="idUserProfile",
 *     type="integer",
 *     nullable=false,
 *     description="Id user profile",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idUser",
 *     type="string",
 *     nullable=false,
 *     readOnly=true,
 *     description="Id User"
 *   ),
 *   @OA\Property(
 *     property="idProfile",
 *     type="integer",
 *     nullable=false,
 *     readOnly=true,
 *     description="Id Profile"
 *   ),
 *   @OA\Property(
 *     property="idGroup",
 *     type="integer",
 *     nullable=false,
 *     description="Id Group"
 *   ),
 *   @OA\Property(
 *     property="assignedBy",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     readOnly=true,
 *     description="Create user profile by"
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseUserProfile",
 *   description="User Profile type object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/UserProfile",
 *         description="User Profile type object"
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterProfileId",
 *   name="idProfile",
 *   in="path",
 *   description="Profile id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyUserProfile",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/UserProfile")
 *   )
 * )
 */
final class UserProfile extends Entity
{

    private ?int $idUserProfile = null;

    private ?string $idUser = null;

    private ?int $idProfile = null;

    private ?int $idGroup = null;

    private ?string $assignedBy = null;


    public function __construct()
    {
    }


    public function fieldsReadOnly(): array
    {
        return ['idUserProfile' => 1];
    }


    public function jsonSerialize(): mixed
    {
        return [
            'idUserProfile' => $this->getIdUserProfile(),
            'idUser'        => $this->getIdUser(),
            'idProfile'     => $this->getIdProfile(),
            'idGroup'       => $this->getIdGroup(),
            'assignedBy'    => $this->getAssignedBy(),
        ];
    }


    public function getValidations(): array
    {
        return [
            'idUserProfile' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'idUser'        => Validator::STRING,
            'idProfile'     => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'idGroup'       => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'assignedBy'    => Validator::STRING,
        ];
    }


    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }


    /**
     * Get the value of idUserProfile.
     *
     * @return ?int
     */
    public function getIdUserProfile(): ?int
    {
        return $this->idUserProfile;
    }


    /**
     * Set the value of idUserProfile.
     *
     * @param integer $idUserProfile
     */
    public function setIdUserProfile(?int $idUserProfile): self
    {
        $this->idUserProfile = $idUserProfile;

        return $this;
    }


    /**
     * Get the value of idUser.
     *
     * @return ?string
     */
    public function getIdUser(): ?string
    {
        return $this->idUser;
    }


    /**
     * Set the value of idUser.
     *
     * @param string $idUser
     */
    public function setIdUser(?string $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }


    /**
     * Get the value of idProfile.
     *
     * @return ?int
     */
    public function getIdProfile(): ?int
    {
        return $this->idProfile;
    }


    /**
     * Set the value of idProfile.
     *
     * @param integer $idProfile
     */
    public function setIdProfile(?int $idProfile): self
    {
        $this->idProfile = $idProfile;

        return $this;
    }


    /**
     * Get the value of idGroup.
     *
     * @return ?int
     */
    public function getIdGroup(): ?int
    {
        return $this->idGroup;
    }


    /**
     * Set the value of idGroup.
     *
     * @param integer $idGroup
     */
    public function setIdGroup(?int $idGroup): self
    {
        $this->idGroup = $idGroup;

        return $this;
    }


    /**
     * Get the value of assignedBy.
     *
     * @return ?string
     */
    public function getAssignedBy(): ?string
    {
        return $this->assignedBy;
    }


    /**
     * Set the value of assignedBy.
     *
     * @param string $assignedBy
     */
    public function setAssignedBy(?string $assignedBy): self
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }


}
