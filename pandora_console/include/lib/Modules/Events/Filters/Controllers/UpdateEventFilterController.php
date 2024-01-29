<?php

namespace PandoraFMS\Modules\Events\Filters\Controllers;

use PandoraFMS\Modules\Events\Filters\Actions\GetEventFilterAction;
use PandoraFMS\Modules\Events\Filters\Actions\UpdateEventFilterAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/event/filter/{idEventFilter}",
 *   tags={"Events"},
 *   summary="Updates an eventFilter",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdEventFilter"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventFilter"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseEventFilter"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateEventFilterController extends Controller
{
    public function __construct(
        private UpdateEventFilterAction $updateEventFilterAction,
        private ValidateAclSystem $acl,
        private GetEventFilterAction $getEventFilterAction
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idEventFilter = $this->getParam($request, 'idEventFilter');
        $eventFilter = $this->getEventFilterAction->__invoke($idEventFilter);

        $oldEventFilter = clone $eventFilter;
        $params = $this->extractParams($request);
        $eventFilter->fromArray($params);

        $this->acl->validate(0, 'EW', ' tried to write event');

        $result = $this->updateEventFilterAction->__invoke($eventFilter, $oldEventFilter);
        return $this->getResponse($response, $result);
    }
}
