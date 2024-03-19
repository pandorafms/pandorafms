<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Authentication\Actions\GetTokenAction;
use PandoraFMS\Modules\Authentication\Actions\UpdateTokenAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;


use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/token/{id}",
 *   tags={"Authentication"},
 *   summary="Updates an token",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdToken"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyToken"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseToken"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateTokenController extends Controller
{
    public function __construct(
        private UpdateTokenAction $updateTokenAction,
        private GetTokenAction $getTokenAction
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idToken = $this->getParam($request, 'id');
        $token = $this->getTokenAction->__invoke($idToken);

        $oldToken = clone $token;
        $params = $this->extractParams($request);
        $token->fromArray($params);

        $result = $this->updateTokenAction->__invoke($token, $oldToken);
        return $this->getResponse($response, $result);
    }
}
