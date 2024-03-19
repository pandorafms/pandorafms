<?php

namespace PandoraFMS\Modules\Authentication\Controllers;

use PandoraFMS\Modules\Authentication\Actions\ListTokenAction;
use PandoraFMS\Modules\Authentication\Entities\TokenFilter;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListTokenController extends Controller
{
    public function __construct(
        private ListTokenAction $listTokenAction
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Authentication"},
     *   path="/token/list",
     *   summary="List tokens",
     * @OA\Parameter(ref="#/components/parameters/parameterPage"),
     * @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     * @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     * @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     * @OA\RequestBody(ref="#/components/requestBodies/requestBodyTokenFilter"),
     * @OA\Response(
     *   response="200",
     *   description="List Incidence object",
     *   content={
     *     @OA\MediaType(
     *       mediaType="application/json",
     *       @OA\Schema(
     *         @OA\Property(
     *           property="paginationData",
     *           type="object",
     *           ref="#/components/schemas/paginationData",
     *           description="Page object",
     *         ),
     *         @OA\Property(
     *           property="data",
     *           type="array",
     *           @OA\Items(
     *             ref="#/components/schemas/Token",
     *             description="Array of Token objects"
     *           )
     *         ),
     *       ),
     *     )
     *   }
     * ),
     * @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     * @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     * @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     * @OA\Response(response=404, ref="#/components/responses/NotFound"),
     * @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var TokenFilter $tokenFilter.
        $tokenFilter = $this->fromRequest($request, TokenFilter::class);

        $result = $this->listTokenAction->__invoke($tokenFilter);
        return $this->getResponse($response, $result);
    }
}
