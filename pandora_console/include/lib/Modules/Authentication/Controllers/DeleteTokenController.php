<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Authentication\Actions\DeleteTokenAction;
use PandoraFMS\Modules\Authentication\Actions\GetTokenAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteTokenController extends Controller
{
    public function __construct(
        private DeleteTokenAction $deleteTokenAction,
        private GetTokenAction $getTokenAction
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Authentication"},
     *   path="/token/{id}",
     *   summary="Deletes an token object.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdToken"),
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
        $idToken = $this->getParam($request, 'id');
        $token = $this->getTokenAction->__invoke($idToken);

        $result = $this->deleteTokenAction->__invoke($token);
        return $this->getResponse($response, $result);
    }
}
