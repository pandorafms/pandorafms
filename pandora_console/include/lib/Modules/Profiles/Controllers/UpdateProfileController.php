<?php

namespace PandoraFMS\Modules\Profiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Profiles\Actions\UpdateProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/profile/{idProfile}",
 *   tags={"Profiles"},
 *   summary="Updates an profile",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdProfile"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyProfile"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseProfile"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateProfileController extends Controller
{
    public function __construct(
        private UpdateProfileAction $updateProfileAction,
        private ValidateAclSystem $acl,
        private GetProfileAction $getProfileAction,
        private Management $management
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idProfile = $this->getParam($request, 'idProfile');
        $profile = $this->getProfileAction->__invoke($idProfile);

        $oldProfile = clone $profile;
        $params = $this->extractParams($request);
        $profile->fromArray($params);

        $this->acl->validateUserAdmin();
        $this->acl->validate(0, 'UM', ' tried to manage profile');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('Profile', true);
        }

        $result = $this->updateProfileAction->__invoke($profile, $oldProfile);
        return $this->getResponse($response, $result);
    }
}
