<?php

namespace PandoraFMS\Modules\Users\Services;

use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\NotFoundException;
use PandoraFMS\Modules\Users\Entities\User;

final class CheckOldPasswordUserService
{
    public function __construct(
    ) {
    }

    public function __invoke(User $user): void
    {
        try {
            // TODO: change to service.
            if (!\process_user_login($user->getIdUser(), $user->getOldPassword())) {
                throw new BadRequestException(__('User or the old password is not correct'));
            }
        } catch (NotFoundException) {
            throw new BadRequestException(__('User or the old password is not correct'));
        }
    }
}
