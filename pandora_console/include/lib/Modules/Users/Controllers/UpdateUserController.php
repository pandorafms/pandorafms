<?php

namespace PandoraFMS\Modules\Users\Controllers;

use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Users\Actions\UpdateUserAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;

use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/user/{id}",
 *   tags={"Users"},
 *   summary="Updates an user",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyUser"),
 *   @OA\Response(response=200,                                         ref="#/components/responses/ResponseUser"),
 *   @OA\Response(response=400,                                         ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401,                                         ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403,                                         ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404,                                         ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500,                                         ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateUserController extends Controller
{


    public function __construct(
        private UpdateUserAction $updateUserAction,
        private ValidateAclSystem $acl,
        private GetUserAction $getUserAction
    ) {
    }


    public function __invoke(Request $request, Response $response): Response
    {
        $idUser = $this->getParam($request, 'id');
        $user = $this->getUserAction->__invoke($idUser);

        $oldUser = clone $user;
        $params = $this->extractParams($request);
        $user->fromArray($params);

        $this->acl->validate(0, 'UM', ' tried to manage user');

        $result = $this->updateUserAction->__invoke($user, $oldUser);
        return $this->getResponse($response, $result);
    }


}
