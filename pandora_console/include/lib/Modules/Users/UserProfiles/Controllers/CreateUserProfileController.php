<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Users\UserProfiles\Actions\CreateUserProfileAction;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfile;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateUserProfileController extends Controller
{
    public function __construct(
        private CreateUserProfileAction $createUserProfileAction,
        private ValidateAclSystem $acl,
        private GetUserAction $getUserAction,
        private GetProfileAction $getProfileAction,
        private Management $management
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user/{idUser}/profile/{idProfile}",
     *   summary="Create user profile",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     *   @OA\Parameter(ref="#/components/parameters/parameterIdProfile"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyUserProfile"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseUserProfile"),
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
        $this->getUserAction->__invoke($idUser);

        $this->acl->validate(0, 'UM', ' tried to manage user profile');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('User profile', true);
        }

        $idProfile = $this->getParam($request, 'idProfile');
        $this->getProfileAction->__invoke($idProfile);

        // @var UserProfile $userProfile.
        $userProfile = $this->fromRequest($request, UserProfile::class);
        $userProfile->setIdUser($idUser);
        $userProfile->setIdProfile($idProfile);

        $result = $this->createUserProfileAction->__invoke($userProfile);
        return $this->getResponse($response, $result);
    }
}
