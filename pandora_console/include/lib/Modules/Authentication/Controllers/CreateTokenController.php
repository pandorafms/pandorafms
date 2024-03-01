<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Authentication\Actions\CreateTokenAction;
use PandoraFMS\Modules\Authentication\Entities\Token;
use PandoraFMS\Modules\Shared\Controllers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateTokenController extends Controller
{
    public function __construct(
        private CreateTokenAction $createTokenAction
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Authentication"},
     *   path="/token",
     *   summary="Creates a new tokens",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyToken"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseToken"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var Token $token.
        $token = $this->fromRequest($request, Token::class);

        $result = $this->createTokenAction->__invoke($token);

        return $this->getResponse($response, $result);
    }
}
