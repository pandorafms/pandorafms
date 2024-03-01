<?php

namespace PandoraFMS\Modules\Events\Controllers;

use PandoraFMS\Modules\Events\Actions\ListEventAction;
use PandoraFMS\Modules\Events\Filters\Entities\EventFilter;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListEventController extends Controller
{
    public function __construct(
        private ListEventAction $listEventAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event/list",
     *   summary="List events",
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventFilter"),
     *   @OA\Response(
     *     response="200",
     *     description="List Events Object",
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
     *               ref="#/components/schemas/Event",
     *               description="Array of event objects"
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
        // @var EventFilter $eventFilter.
        $eventFilter = $this->fromRequest($request, EventFilter::class);

        $this->acl->validate(0, 'ER', ' tried to read event');

        $result = $this->listEventAction->__invoke($eventFilter);
        return $this->getResponse($response, $result);
    }
}
