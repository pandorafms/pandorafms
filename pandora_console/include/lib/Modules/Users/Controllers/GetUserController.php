<?php

namespace PandoraFMS\Modules\Users\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\GetUserAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetUserController extends Controller
{
    public function __construct(
        private GetUserAction $getUserAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/user/{idUser}",
     *   tags={"Users"},
     *   summary="show users",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseUser"),
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

        $this->acl->validate(0, 'UM', ' tried to manage user');

        $result = $this->getUserAction->__invoke($idUser);

        return $this->getResponse($response, $result);
    }
}
