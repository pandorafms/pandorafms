<?php

namespace PandoraFMS\Modules\Tags\Controllers;

use PandoraFMS\Modules\Tags\Actions\GetTagAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetTagController extends Controller
{
    public function __construct(
        private GetTagAction $getTagAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/tag/{idTag}",
     *   tags={"Tags"},
     *   summary="Show tag",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdTag"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseTag"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idTag = $this->getParam($request, 'idTag');

        $this->acl->validate(0, 'PM', ' tried to manage tag');

        $result = $this->getTagAction->__invoke($idTag);
        return $this->getResponse($response, $result);
    }
}
