<?php

namespace PandoraFMS\Modules\EventFilters\Controllers;

use PandoraFMS\Modules\EventFilters\Actions\ListEventFilterAction;
use PandoraFMS\Modules\EventFilters\Entities\EventFilterFilter;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListEventFilterController extends Controller
{
    public function __construct(
        private ListEventFilterAction $listEventFilterAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"EventFilters"},
     *   path="/eventFilter/list",
     *   summary="List eventFilters",
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventFilter"),
     *   @OA\Response(
     *     response="200",
     *     description="List Incidence object",
     *     content={
     *       @OA\MediaType(
     *         mediaType="application/json",
     *         @OA\Schema(
     *           @OA\Property(
     *             property="paginationData",
     *             type="object",
     *             ref="#/components/schemas/paginationData",
     *             description="Page object",
     *           ),
     *           @OA\Property(
     *             property="data",
     *             type="array",
     *             @OA\Items(
     *               ref="#/components/schemas/EventFilter",
     *               description="Array of incidences Type objects"
     *             )
     *           ),
     *         ),
     *       )
     *     }
     *   ),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var EventFilterFilter $eventFilterFilter.
        $eventFilterFilter = $this->fromRequest($request, EventFilterFilter::class);

        $this->acl->validate(0, 'ER', ' tried to read event');

        $result = $this->listEventFilterAction->__invoke($eventFilterFilter);
        return $this->getResponse($response, $result);
    }
}
