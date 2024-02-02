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
    public function __construct()
    {
        $this->setDefaultFieldOrder(TokenDataMapper::LABEL);
        $this->setDefaultDirectionOrder($this::DESC);
        $this->setEntityFilter(new Token());
    }


    public function fieldsTranslate(): array
    {
        return [
            'idToken' => TokenDataMapper::ID_TOKEN,
            'label'   => TokenDataMapper::LABEL,
        ];
    }


    public function fieldsReadOnly(): array
    {
        return [];
    }


    public function jsonSerialize(): mixed
    {
        return [];
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


}
