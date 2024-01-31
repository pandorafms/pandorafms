<?php

namespace PandoraFMS\Modules\Tags\Controllers;

use PandoraFMS\Modules\Tags\Actions\CreateTagAction;
use PandoraFMS\Modules\Tags\Entities\Tag;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateTagController extends Controller
{
    public function __construct(
        private CreateTagAction $createTagAction,
        private ValidateAclSystem $acl,
        private Management $management
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Tags"},
     *   path="/tag",
     *   summary="Creates a new tags",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyTag"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseTag"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var Tag $tag.
        $tag = $this->fromRequest($request, Tag::class);

        $this->acl->validate(0, 'PM', ' tried to manage tag');

        $this->management->isManagementAllowed('Tag', true);

        $result = $this->createTagAction->__invoke($tag);

        return $this->getResponse($response, $result);
    }
}
