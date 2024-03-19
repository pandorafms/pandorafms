<?php

namespace PandoraFMS\Modules\Profiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\DeleteProfileAction;
use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteProfileController extends Controller
{
    public function __construct(
        private DeleteProfileAction $deleteProfileAction,
        private ValidateAclSystem $acl,
        private GetProfileAction $getProfileAction,
        private Management $management
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Profiles"},
     *   path="/profile/{idProfile}",
     *   summary="Deletes an profile object.",
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
        $idProfile = $this->getParam($request, 'idProfile');
        $profile = $this->getProfileAction->__invoke($idProfile);

        $this->acl->validateUserAdmin();
        $this->acl->validate(0, 'UM', ' tried to manage profile');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('Profile', true);
        }

        $result = $this->deleteProfileAction->__invoke($profile);
        return $this->getResponse($response, $result);
    }
}
