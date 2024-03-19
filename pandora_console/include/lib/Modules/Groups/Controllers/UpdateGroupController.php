<?php

namespace PandoraFMS\Modules\Groups\Controllers;

use PandoraFMS\Modules\Groups\Actions\GetGroupAction;
use PandoraFMS\Modules\Groups\Actions\UpdateGroupAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/group/{idGroup}",
 *   tags={"Groups"},
 *   summary="Updates an group",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdGroup"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyGroup"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseGroup"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateGroupController extends Controller
{
    public function __construct(
        private UpdateGroupAction $updateGroupAction,
        private ValidateAclSystem $acl,
        private GetGroupAction $getGroupAction,
        private Management $management
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idGroup = $this->getParam($request, 'idGroup');
        $group = $this->getGroupAction->__invoke($idGroup);

        $oldGroup = clone $group;
        $params = $this->extractParams($request);
        $group->fromArray($params);

        $this->acl->validate(0, 'UM', ' tried to manage user for groups');

        $this->management->isManagementAllowed('Group', true);

        $result = $this->updateGroupAction->__invoke($group, $oldGroup);
        return $this->getResponse($response, $result);
    }
}
