<?php

namespace PandoraFMS\Modules\Authentication\Entities;

use PandoraFMS\Modules\Shared\Entities\Entity;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="Token",
 *   type="object",
 *   @OA\Property(
 *     property="idToken",
 *     type="integer",
 *     nullable=false,
 *     description="Id Token",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="label",
 *     type="string",
 *     nullable=false,
 *     default=null,
 *     description="label of the token"
 *   ),
 *   @OA\Property(
 *     property="uuid",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="uuid of the token",
 *     readOnly=true
 *   ),
 *   @OA\Property(
 *     property="idUser",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="id_user of the token"
 *   ),
 *   @OA\Property(
 *     property="validity",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="Date until which tocken is valid, if it is void it will never expire",
 *     example="2023-02-21 08:34:16",
 *   ),
 *   @OA\Property(
 *     property="lastUsage",
 *     type="string",
 *     nullable=true,
 *     default=null,
 *     description="last_usage of the token",
 *     example="2023-02-21 08:34:16",
 *     readOnly=true
 *   )
 * )
 *
 * @OA\Response(
 *   response="ResponseToken",
 *   description="Incidence type object",
 *   content={
 *     @OA\MediaType(
 *       mediaType="application/json",
 *       @OA\Schema(
 *         type="object",
 *         ref="#/components/schemas/Token",
 *         description="Incidence type object"
 *       ),
 *     )
 *   }
 * )
 *
 * @OA\Parameter(
 *   parameter="parameterIdToken",
 *   name="id",
 *   in="path",
 *   description="Token id",
 *   required=true,
 *   @OA\Schema(
 *     type="integer",
 *     default=1
 *   ),
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyToken",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/Token")
 *   ),
 * )
 */
final class Token extends Entity
{
    private ?int $idToken = null;
    private ?string $label = null;
    private ?string $uuid = null;
    private ?string $challenge = null;
    private ?string $idUser = null;
    private ?string $validity = null;
    private ?string $lastUsage = null;

    private ?string $token = null;

    public function __construct()
    {
    }

    public function fieldsReadOnly(): array
    {
        return [
            'idToken'   => 1,
            'uuid'      => 1,
            'challenge' => 1,
            'token'     => 1,
            'lastUsage' => 1,
        ];
    }

    public function jsonSerialize(): mixed
    {
        return [
            'idToken'   => $this->getIdToken(),
            'label'     => $this->getLabel(),
            'uuid'      => $this->getUuid(),
            'idUser'    => $this->getIdUser(),
            'validity'  => $this->getValidity(),
            'lastUsage' => $this->getLastUsage(),
            'token'     => $this->getToken(),
        ];
    }

    public function getValidations(): array
    {
        return [
            'idToken' => [
                Validator::INTEGER,
                Validator::GREATERTHAN,
            ],
            'label'     => Validator::STRING,
            'uuid'      => Validator::STRING,
            'challenge' => Validator::STRING,
            'idUser'    => Validator::STRING,
            'validity'  => Validator::DATETIME,
            'lastUsage' => Validator::DATETIME,
        ];
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    /**
     * Get the value of idToken.
     *
     * @return ?int
     */
    public function getIdToken(): ?int
    {
        return $this->idToken;
    }

    /**
     * Set the value of idToken.
     *
     * @param ?int $idToken
     *
     */
    public function setIdToken(?int $idToken): self
    {
        $this->idToken = $idToken;

        return $this;
    }


    /**
     * Get the value of label.
     *
     * @return ?string
     */
    public function getLabel(): ?string
    {
        return $this->label;
    }

    /**
     * Set the value of label.
     *
     * @param ?string $label
     *
     */
    public function setLabel(?string $label): self
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get the value of uuid.
     *
     * @return ?string
     */
    public function getUuid(): ?string
    {
        return $this->uuid;
    }

    /**
     * Set the value of uuid.
     *
     * @param ?string $uuid
     *
     */
    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * Get the value of challenge.
     *
     * @return ?string
     */
    public function getChallenge(): ?string
    {
        return $this->challenge;
    }

    /**
     * Set the value of challenge.
     *
     * @param ?string $challenge
     *
     */
    public function setChallenge(?string $challenge): self
    {
        $this->challenge = $challenge;

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
     * @param ?string $idUser
     *
     */
    public function setIdUser(?string $idUser): self
    {
        $this->idUser = $idUser;

        return $this;
    }

    /**
     * Get the value of validity.
     *
     * @return ?string
     */
    public function getValidity(): ?string
    {
        return $this->validity;
    }

    /**
     * Set the value of validity.
     *
     * @param ?string $validity
     *
     */
    public function setValidity(?string $validity): self
    {
        $this->validity = $validity;

        return $this;
    }

    /**
     * Get the value of lastUsage.
     *
     * @return ?string
     */
    public function getLastUsage(): ?string
    {
        return $this->lastUsage;
    }

    /**
     * Set the value of lastUsage.
     *
     * @param ?string $lastUsage
     *
     */
    public function setLastUsage(?string $lastUsage): self
    {
        $this->lastUsage = $lastUsage;

        return $this;
    }

    /**
     * Get the value of token.
     *
     * @return ?string
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * Set the value of token.
     *
     * @param ?string $token
     *
     */
    public function setToken(?string $token): self
    {
        $this->token = $token;

        return $this;
    }
}
