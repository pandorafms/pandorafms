<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Users\UserProfiles\Actions\GetUserProfileAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetUserProfileController extends Controller
{
    public function __construct(
        private GetUserProfileAction $getUserProfileAction,
        private GetUserAction $getUserAction,
        private GetProfileAction $getProfileAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/user/{idUser}/profile/{idProfile}",
     *   tags={"Users"},
     *   summary="show data field user profile",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     *   @OA\Parameter(ref="#/components/parameters/parameterIdProfile"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseUserProfile"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idUser = $this->getParam($request, 'idUser');
        $user = $this->getUserAction->__invoke($idUser);

        $this->acl->validate(0, 'UM', ' tried to manage user profile');

        $idProfile = $this->getParam($request, 'idProfile');
        $this->getProfileAction->__invoke($idProfile);

        $result = $this->getUserProfileAction->__invoke($idUser, $idProfile);
        return $this->getResponse($response, $result);
    }
}
