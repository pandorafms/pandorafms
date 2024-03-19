<?php

namespace PandoraFMS\Modules\Profiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\GetProfileAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetProfileController extends Controller
{
    public function __construct(
        private GetProfileAction $getProfileAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/profile/{idProfile}",
     *   tags={"Profiles"},
     *   summary="Show profile",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdProfile"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseProfile"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idProfile = $this->getParam($request, 'idProfile');

        $this->acl->validateUserAdmin();
        $this->acl->validate(0, 'UM', ' tried to manage profile');

        $result = $this->getProfileAction->__invoke($idProfile);
        return $this->getResponse($response, $result);
    }
}
