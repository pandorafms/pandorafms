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
 *     property="isNoHierarchy",
 *     type="boolean",
 *     nullable=true,
 *     default=false,
 *     description="No hierarchy"
 *   ),
 *   @OA\Property(
 *     property="assignedBy",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     readOnly=true,
 *     description="Create user profile by"
 *   ),
 *   @OA\Property(
 *     property="idPolicy",
 *     type="integer",
 *     nullable=false,
 *     description="Id Policy"
 *   ),
 *   @OA\Property(
 *     property="tags",
 *     type="array",
 *     nullable=true,
 *     default=null,
 *     description="Tags to which a user belongs",
 *     @OA\Items(
 *       @OA\Property(
 *         property="idTags",
 *         type="integer",
 *         nullable=true,
 *         default=null,
 *         description="Tags id",
 *       )
 *     )
 *   ),
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
 *   parameter="parameterIdUserProfile",
 *   name="idUserProfile",
 *   in="path",
 *   description="User profile id",
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
    private ?bool $isNoHierarchy = null;
    private ?string $assignedBy = null;
    private ?int $idPolicy = null;
    private ?array $tags = null;

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
            'isNoHierarchy' => $this->getIsNoHierarchy(),
            'assignedBy'    => $this->getAssignedBy(),
            'idPolicy'      => $this->getIdPolicy(),
            'tags'          => $this->getTags(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idUserProfile' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'idUser'    => Validator::STRING,
            'idProfile' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'idGroup' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'isNoHierarchy' => Validator::BOOLEAN,
            'assignedBy'    => Validator::STRING,
            'idPolicy'      => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'tags' => Validator::ARRAY,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    public function getIdUserProfile(): ?int
    {
        return $this->idUserProfile;
    }
    public function setIdUserProfile(?int $idUserProfile): self
    {
        $this->idUserProfile = $idUserProfile;

        return $this;
    }

    public function getIdUser(): ?string
    {
        return $this->idUser;
    }
    public function setIdUser(?string $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    public function getIdProfile(): ?int
    {
        return $this->idProfile;
    }
    public function setIdProfile(?int $idProfile): self
    {
        $this->idProfile = $idProfile;

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

    public function getAssignedBy(): ?string
    {
        return $this->assignedBy;
    }
    public function setAssignedBy(?string $assignedBy): self
    {
        $this->assignedBy = $assignedBy;

        return $this;
    }

    public function getIsNoHierarchy(): ?bool
    {
        return $this->isNoHierarchy;
    }
    public function setIsNoHierarchy(?bool $isNoHierarchy): self
    {
        $this->isNoHierarchy = $isNoHierarchy;
        return $this;
    }

    public function getIdPolicy(): ?int
    {
        return $this->idPolicy;
    }
    public function setIdPolicy(?int $idPolicy): self
    {
        $this->idPolicy = $idPolicy;
        return $this;
    }

    public function getTags(): ?array
    {
        return $this->tags;
    }
    public function setTags(array|string|null $tags): self
    {
        if (is_string($tags) === true) {
            $tags = json_decode($tags);
        }

        $this->tags = $tags;

        return $this;
    }
}
