<?php

namespace PandoraFMS\Modules\Tags\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="Tag",
 *   type="object",
 *   @OA\Property(
 *     property="idTag",
 *     type="integer",
 *     nullable=false,
 *     description="Id Tag",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="name",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="Name of the tag"
 *   ),
 *   @OA\Property(
 *     property="description",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Description of the tag"
 *   ),
 *   @OA\Property(
 *     property="url",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Url of the tag"
 *   ),
 *   @OA\Property(
 *     property="phone",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Phone of the tag"
 *   ),
 *   @OA\Property(
 *     property="previousName",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Previous name of the tag",
 *     readOnly=true,
 *     deprecated=true
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseTag",
 *   description="Tag object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/Tag",
 *         description="Tag object"
 *       )
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdTag",
 *   name="idTag",
 *   in="path",
 *   description="Tag id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   )
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyTag",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/Tag")
 *   )
 * )
 */
final class Tag extends Entity
{
    private ?int $idTag = null;
    private ?string $name = null;
    private ?string $description = null;
    private ?string $url = null;
    private ?string $mail = null;
    private ?string $phone = null;
    private ?string $previousName = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return [
            'idTag'        => 1,
            'previousName' => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idTag'       => $this->getIdTag(),
            'name'        => $this->getName(),
            'description' => $this->getDescription(),
            'url'         => $this->getUrl(),
            'mail'        => $this->getMail(),
            'phone'       => $this->getPhone(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idTag' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'name'         => Validator::STRING,
            'description'  => Validator::STRING,
            'url'          => Validator::STRING,
            'mail'         => Validator::MAIL,
            'phone'        => Validator::STRING,
            'previousName' => Validator::STRING,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    public function getIdTag(): ?int
    {
        return $this->idTag;
    }
    public function setIdTag(?int $idTag): self
    {
        $this->idTag = $idTag;
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

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }
    public function setUrl(?string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function getMail(): ?string
    {
        return $this->mail;
    }
    public function setMail(?string $mail): self
    {
        $this->mail = $mail;
        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }
    public function setPhone(?string $phone): self
    {
        $this->phone = $phone;
        return $this;
    }

    public function getPreviousName(): ?string
    {
        return $this->previousName;
    }
    public function setPreviousName(?string $previousName): self
    {
        $this->previousName = $previousName;
        return $this;
    }
}
