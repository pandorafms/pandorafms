<?php

namespace PandoraFMS\Modules\Users\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\CreateUserAction;
use PandoraFMS\Modules\Users\Entities\User;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateUserController extends Controller
{
    public function __construct(
        private CreateUserAction $createUserAction,
        private ValidateAclSystem $acl,
        private Management $management
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user",
     *   summary="Creates a new users",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyUser"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseUser"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var User $user.
        $user = $this->fromRequest($request, User::class);

        $this->acl->validate(0, 'UM', ' tried to manage user');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('User', true);
        }

        $result = $this->createUserAction->__invoke($user);

        return $this->getResponse($response, $result);
    }
}
