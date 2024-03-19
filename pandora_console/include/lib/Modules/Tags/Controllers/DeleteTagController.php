<?php

namespace PandoraFMS\Modules\Tags\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Tags\Actions\DeleteTagAction;
use PandoraFMS\Modules\Tags\Actions\GetTagAction;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteTagController extends Controller
{
    public function __construct(
        private DeleteTagAction $deleteTagAction,
        private ValidateAclSystem $acl,
        private GetTagAction $getTagAction,
        private Management $management
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Tags"},
     *   path="/tag/{idTag}",
     *   summary="Deletes an tag object.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdTag"),
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
        $idTag = $this->getParam($request, 'idTag');
        $tag = $this->getTagAction->__invoke($idTag);

        $this->acl->validate(0, 'PM', ' tried to manage tag');

        $this->management->isManagementAllowed('Tag', true);

        $result = $this->deleteTagAction->__invoke($tag);
        return $this->getResponse($response, $result);
    }
}
