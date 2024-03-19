<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Users\UserProfiles\Actions\DeleteUserProfileAction;
use PandoraFMS\Modules\Users\UserProfiles\Actions\GetUserProfileAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteUserProfileController extends Controller
{
    public function __construct(
        private DeleteUserProfileAction $deleteUserProfileAction,
        private ValidateAclSystem $acl,
        private GetUserAction $getUserAction,
        private GetProfileAction $getProfileAction,
        private GetUserProfileAction $getUserProfileAction,
        private Management $management
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user/{idUser}/profile/{idProfile}",
     *   summary="Deletes user profile.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     *   @OA\Parameter(ref="#/components/parameters/parameterIdProfile"),
     *   @OA\Response(response=200, ref="#/components/responses/successfullyDeleted"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idUser = $this->getParam($request, 'idUser');
        $user = $this->getUserAction->__invoke($idUser);

        $this->acl->validate(0, 'UM', ' tried to manage user profile');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('User profile', true);
        }

        $idProfile = $this->getParam($request, 'idProfile');
        $profile = $this->getProfileAction->__invoke($idProfile);

        $userProfile = $this->getUserProfileAction->__invoke($idUser, $idProfile);
        $result = $this->deleteUserProfileAction->__invoke($userProfile);
        return $this->getResponse($response, $result);
    }
}
