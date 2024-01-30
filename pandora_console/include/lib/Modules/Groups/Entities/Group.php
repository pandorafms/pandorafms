<?php

namespace PandoraFMS\Modules\Groups\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="Group",
 *   type="object",
 *   @OA\Property(
 *     property="idGroup",
 *     type="integer",
 *     nullable=false,
 *     description="Id group",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *      property="name",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Name group",
 *   ),
 *   @OA\Property(
 *      property="icon",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Path icon, by default: without-group@groups.svg",
 *      readOnly=true
 *   ),
 *   @OA\Property(
 *     property="parent",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id Group parent",
 *   ),
 *   @OA\Property(
 *     property="parentName",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="name group parent",
 *     readOnly="true"
 *   ),
 *   @OA\Property(
 *     property="hasChild",
 *     type="boolean",
 *     nullable=true,
 *     default=null,
 *     description="Group has child",
 *     readOnly="true"
 *   ),
 *   @OA\Property(
 *     property="isPropagate",
 *     type="boolean",
 *     nullable=false,
 *     default="false",
 *     description="Group propagate"
 *   ),
 *   @OA\Property(
 *     property="isAlertEnabled",
 *     type="boolean",
 *     nullable=false,
 *     default="true",
 *     description="Group is alert enabled"
 *   ),
 *   @OA\Property(
 *     property="customId",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Custom id",
 *   ),
 *   @OA\Property(
 *     property="idSkin",
 *     type="integer",
 *     nullable=true,
 *     default=null,
 *     description="Id skin"
 *   ),
 *   @OA\Property(
 *      property="description",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Description",
 *   ),
 *   @OA\Property(
 *      property="contact",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Contact",
 *   ),
 *   @OA\Property(
 *      property="other",
 *      type="string",
 *      nullable=true,
 *      default=null,
 *      description="Other things",
 *   ),
 *   @OA\Property(
 *     property="password",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     writeOnly=true
 *   ),
 *   @OA\Property(
 *     property="maxAgents",
 *     type="integer",
 *     nullable=false,
 *     default=0,
 *     description="Maximum number of agents per group",
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseGroup",
 *   description="Group object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/Group",
 *         description="Group object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdGroup",
 *   name="idGroup",
 *   in="path",
 *   description="Group id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   )
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyGroup",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/Group")
 *   )
 * )
 */
final class Group extends Entity
{
    private ?int $idGroup = null;
    private ?string $name = null;
    private ?string $icon = null;
    private ?int $parent = null;
    private ?string $parentName = null;
    private ?bool $haschild = null;
    private ?bool $isPropagate = null;
    private ?bool $isAlertEnabled = null;
    private ?string $customId = null;
    private ?int $idSkin = null;
    private ?string $description = null;
    private ?string $contact = null;
    private ?string $other = null;
    private ?string $password = null;
    private ?int $maxAgents = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return ['idGroup' => 1];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idGroup'        => $this->getIdGroup(),
            'name'           => $this->getName(),
            'icon'           => $this->getIcon(),
            'parent'         => $this->getParent(),
            'parentName'     => $this->getParentName(),
            'hasChild'       => $this->getHasChild(),
            'isPropagate'    => $this->getIsPropagate(),
            'isAlertEnabled' => $this->getIsAlertEnabled(),
            'customId'       => $this->getCustomId(),
            'idSkin'         => $this->getIdSkin(),
            'description'    => $this->getDescription(),
            'contact'        => $this->getContact(),
            'other'          => $this->getOther(),
            'maxAgents'      => $this->getMaxAgents(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idGroup' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'name'   => Validator::STRING,
            'icon'   => Validator::STRING,
            'parent' => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'isPropagate'    => Validator::BOOLEAN,
            'isAlertEnabled' => Validator::BOOLEAN,
            'customId'       => Validator::STRING,
            'idSkin'         => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
            'description' => Validator::STRING,
            'contact'     => Validator::STRING,
            'other'       => Validator::STRING,
            'maxAgents'   => [
                Validator::INTEGER,
                Validator::GREATEREQUALTHAN,
            ],
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
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

    public function getName(): ?string
    {
        return $this->name;
    }
    public function setName(?string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }
    public function setIcon(?string $icon): self
    {
        $this->icon = $icon;
        return $this;
    }

    public function getParent(): ?int
    {
        return $this->parent;
    }
    public function setParent(?int $parent): self
    {
        $this->parent = $parent;
        return $this;
    }

    public function getIsPropagate(): ?bool
    {
        return $this->isPropagate;
    }
    public function setIsPropagate(?bool $isPropagate): self
    {
        $this->isPropagate = $isPropagate;
        return $this;
    }

    public function getIsAlertEnabled(): ?bool
    {
        return $this->isAlertEnabled;
    }
    public function setIsAlertEnabled(?bool $isAlertEnabled): self
    {
        $this->isAlertEnabled = $isAlertEnabled;
        return $this;
    }

    public function getCustomId(): ?string
    {
        return $this->customId;
    }
    public function setCustomId(?string $customId): self
    {
        $this->customId = $customId;
        return $this;
    }

    public function getIdSkin(): ?int
    {
        return $this->idSkin;
    }
    public function setIdSkin(?int $idSkin): self
    {
        $this->idSkin = $idSkin;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getContact(): ?string
    {
        return $this->contact;
    }
    public function setContact(?string $contact): self
    {
        $this->contact = $contact;
        return $this;
    }

    public function getOther(): ?string
    {
        return $this->other;
    }
    public function setOther(?string $other): self
    {
        $this->other = $other;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }
    public function setPassword(?string $password): self
    {
        $this->password = $password;
        return $this;
    }

    public function getMaxAgents(): ?int
    {
        return $this->maxAgents;
    }
    public function setMaxAgents(?int $maxAgents): self
    {
        $this->maxAgents = $maxAgents;
        return $this;
    }

    public function getParentName(): ?string
    {
        return $this->parentName;
    }
    public function setParentName(?string $parentName): self
    {
        $this->parentName = $parentName;
        return $this;
    }

    public function getHaschild(): ?bool
    {
        return $this->haschild;
    }
    public function setHaschild(?bool $haschild): self
    {
        $this->haschild = $haschild;
        return $this;
    }
}
