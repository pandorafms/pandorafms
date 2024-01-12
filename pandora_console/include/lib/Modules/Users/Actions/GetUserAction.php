<?php

namespace PandoraFMS\Modules\Users\Actions;

use PandoraFMS\Modules\Users\Entities\User;
use PandoraFMS\Modules\Users\Services\GetUserService;

final class GetUserAction
{


    public function __construct(
        private GetUserService $getUserService
    ) {
    }


    public function __invoke(string $idUser): User
    {
        return $this->getUserService->__invoke($idUser);
    }


}
