<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Authentication\Actions\GetTokenAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetTokenController extends Controller
{
    public function __construct(
        private GetTokenAction $getTokenAction
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/token/{id}",
     *   tags={"Authentication"},
     *   summary="show tokens",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdToken"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseToken"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idToken = $this->getParam($request, 'id');

        $result = $this->getTokenAction->__invoke($idToken);
        return $this->getResponse($response, $result);
    }
}
