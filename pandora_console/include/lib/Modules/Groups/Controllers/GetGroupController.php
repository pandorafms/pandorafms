<?php

namespace PandoraFMS\Modules\Groups\Controllers;

use PandoraFMS\Modules\Groups\Actions\GetGroupAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetGroupController extends Controller
{
    public function __construct(
        private GetGroupAction $getGroupAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/group/{idGroup}",
     *   tags={"Groups"},
     *   summary="Show group",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdGroup"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseGroup"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idGroup = $this->getParam($request, 'idGroup');

        $this->acl->validate(0, 'AR', ' tried to read agents for groups');

        $result = $this->getGroupAction->__invoke($idGroup);
        return $this->getResponse($response, $result);
    }
}
