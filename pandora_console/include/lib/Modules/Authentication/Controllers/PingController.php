<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class PingController extends Controller
{
    public function __construct(
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/ping",
     *   tags={"Authentication"},
     *   summary="ping",
     *   @OA\Response(response=200, ref="#/components/responses/ResponsePing"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     *
     *  @OA\Response(
     *   response="ResponsePing",
     *   description="Ping",
     *   content={
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Property(
     *         property="valid",
     *         type="bool",
     *         nullable=false,
     *         description="Is valid token",
     *         readOnly=true
     *       )
     *     )
     *   }
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        return $this->getResponse($response, ['valid' => true]);
    }
}
