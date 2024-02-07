<?php

namespace PandoraFMS\Modules\Authentication\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="TokenFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/Token"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idToken",
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
 *   request="requestBodyTokenFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/TokenFilter")
 *   ),
 * )
 */
final class TokenFilter extends FilterAbstract
{
    private ?string $freeSearch = null;

    public function __construct()
    {
        $this->setDefaultFieldOrder(TokenDataMapper::LABEL);
        $this->setDefaultDirectionOrder($this::DESC);
        $this->setEntityFilter(new Token());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idToken'   => TokenDataMapper::ID_TOKEN,
            'label'     => TokenDataMapper::LABEL,
            'validity'  => TokenDataMapper::VALIDITY,
            'lastUsage' => TokenDataMapper::LAST_USAGE,
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

        return $validations;
    }

    public function validateFields(array $filters): array
    {
        return (new Validator())->validate($filters);
    }

    public function getFreeSearch(): ?string
    {
        return $this->freeSearch;
    }

    public function setFreeSearch(?string $freeSearch): self
    {
        $this->freeSearch = $freeSearch;
        return $this;
    }

    public function getFieldsFreeSearch(): ?array
    {
        return [TokenDataMapper::TABLE_NAME.'.'.TokenDataMapper::LABEL];
    }
}
