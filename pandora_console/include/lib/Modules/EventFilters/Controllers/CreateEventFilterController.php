<?php

namespace PandoraFMS\Modules\EventFilters\Controllers;

use PandoraFMS\Modules\EventFilters\Actions\CreateEventFilterAction;
use PandoraFMS\Modules\EventFilters\Entities\EventFilter;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateEventFilterController extends Controller
{
    public function __construct(
        private CreateEventFilterAction $createEventFilterAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"EventFilters"},
     *   path="/eventFilter",
     *   summary="Creates a new eventFilters",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventFilter"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseEventFilter"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var EventFilter $eventFilter.
        $eventFilter = $this->fromRequest($request, EventFilter::class);

        $this->acl->validate(0, 'EW', ' tried to write event');

        $result = $this->createEventFilterAction->__invoke($eventFilter);

        return $this->getResponse($response, $result);
    }
}
