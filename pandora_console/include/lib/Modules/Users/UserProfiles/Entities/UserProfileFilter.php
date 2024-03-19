<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Entities;

use PandoraFMS\Modules\Shared\Core\FilterAbstract;
use PandoraFMS\Modules\Shared\Validators\Validator;

/**
 * @OA\Schema(
 *   schema="UserProfileFilter",
 *   type="object",
 *   allOf={
 *     @OA\Schema(ref="#/components/schemas/UserProfile"),
 *     @OA\Schema(
 *       @OA\Property(
 *         property="idUserProfile",
 *         default=null,
 *         readOnly=false
 *       )
 *     )
 *   }
 * )
 *
 * @OA\RequestBody(
 *   request="requestBodyUserProfileFilter",
 *   required=true,
 *   @OA\MediaType(
 *     mediaType="application/json",
 *     @OA\Schema(ref="#/components/schemas/UserProfileFilter")
 *   ),
 * )
 */
final class UserProfileFilter extends FilterAbstract
{
    public function __construct()
    {
        $this->setDefaultFieldOrder(UserProfileDataMapper::ID_USER_PROFILE);
        $this->setDefaultDirectionOrder($this::ASC);
        $this->setEntityFilter(new UserProfile());
    }

    public function fieldsTranslate(): array
    {
        return [
            'idUserProfile' => UserProfileDataMapper::ID_USER_PROFILE,
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
