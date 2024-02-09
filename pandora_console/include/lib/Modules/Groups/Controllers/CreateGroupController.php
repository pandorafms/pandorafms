<?php

namespace PandoraFMS\Modules\Groups\Controllers;

use PandoraFMS\Modules\Groups\Actions\CreateGroupAction;
use PandoraFMS\Modules\Groups\Entities\Group;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateGroupController extends Controller
{
    public function __construct(
        private CreateGroupAction $createGroupAction,
        private ValidateAclSystem $acl,
        private Management $management
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Groups"},
     *   path="/group",
     *   summary="Creates a new groups",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyGroup"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseGroup"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var Group $group.
        $group = $this->fromRequest($request, Group::class);

        $this->acl->validate(0, 'UM', ' tried to manage user for groups');

        $this->acl->validateUserCanManageAll('PM');

        $this->management->isManagementAllowed('Group', true);

        $result = $this->createGroupAction->__invoke($group);

        return $this->getResponse($response, $result);
    }
}
