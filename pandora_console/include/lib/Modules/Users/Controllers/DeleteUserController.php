<?php

namespace PandoraFMS\Modules\Users\Controllers;

use PandoraFMS\Modules\Users\Actions\DeleteUserAction;
use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteUserController extends Controller
{


    public function __construct(
        private DeleteUserAction $deleteUserAction,
        private ValidateAclSystem $acl,
        private GetUserAction $getUserAction
    ) {
    }


    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user/{id}",
     *   summary="Deletes an user object.",
     * @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     * @OA\Response(response=200,                                    ref="#/components/responses/successfullyDeleted"),
     * @OA\Response(response=400,                                    ref="#/components/responses/BadRequest"),
     * @OA\Response(response=401,                                    ref="#/components/responses/Unauthorized"),
     * @OA\Response(response=403,                                    ref="#/components/responses/Forbidden"),
     * @OA\Response(response=404,                                    ref="#/components/responses/NotFound"),
     * @OA\Response(response=500,                                    ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idUser = $this->getParam($request, 'id');
        $user = $this->getUserAction->__invoke($idUser);

        $this->acl->validate(0, 'UM', ' tried to manage user');

        $result = $this->deleteUserAction->__invoke($user);
        return $this->getResponse($response, $result);
    }


}
