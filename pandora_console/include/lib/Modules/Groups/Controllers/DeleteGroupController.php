<?php

namespace PandoraFMS\Modules\Groups\Controllers;

use PandoraFMS\Modules\Groups\Actions\DeleteGroupAction;
use PandoraFMS\Modules\Groups\Actions\GetGroupAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteGroupController extends Controller
{
    public function __construct(
        private DeleteGroupAction $deleteGroupAction,
        private ValidateAclSystem $acl,
        private GetGroupAction $getGroupAction,
        private Management $management
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Groups"},
     *   path="/group/{idGroup}",
     *   summary="Deletes an group object.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdGroup"),
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
        $idGroup = $this->getParam($request, 'idGroup');
        $group = $this->getGroupAction->__invoke($idGroup);

        $this->acl->validate(0, 'UM', ' tried to manage user for groups');

        $this->management->isManagementAllowed('Group', true);

        $result = $this->deleteGroupAction->__invoke($group);
        return $this->getResponse($response, $result);
    }
}
