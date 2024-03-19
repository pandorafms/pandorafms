<?php

namespace PandoraFMS\Modules\Tags\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Tags\Actions\GetTagAction;
use PandoraFMS\Modules\Tags\Actions\UpdateTagAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/tag/{idTag}",
 *   tags={"Tags"},
 *   summary="Updates an tag",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdTag"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyTag"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseTag"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateTagController extends Controller
{
    public function __construct(
        private UpdateTagAction $updateTagAction,
        private ValidateAclSystem $acl,
        private GetTagAction $getTagAction,
        private Management $management
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idTag = $this->getParam($request, 'idTag');
        $tag = $this->getTagAction->__invoke($idTag);

        $oldTag = clone $tag;
        $params = $this->extractParams($request);
        $tag->fromArray($params);

        $this->acl->validate(0, 'PM', ' tried to manage tag');

        $this->management->isManagementAllowed('Tag', true);

        $result = $this->updateTagAction->__invoke($tag, $oldTag);
        return $this->getResponse($response, $result);
    }
}
