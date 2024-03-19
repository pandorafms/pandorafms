<?php

namespace PandoraFMS\Modules\Events\Controllers;

use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Events\Actions\UpdateEventAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/event/{idEvent}",
 *   tags={"Events"},
 *   summary="Updates an event",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEvent"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseEvent"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateEventController extends Controller
{
    public function __construct(
        private UpdateEventAction $updateEventAction,
        private ValidateAclSystem $acl,
        private GetEventAction $getEventAction
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idEvent = $this->getParam($request, 'idEvent');
        $event = $this->getEventAction->__invoke($idEvent);

        $oldEvent = clone $event;
        $params = $this->extractParams($request);
        $event->fromArray($params);

        $this->acl->validate(0, 'EW', ' tried to write event');

        $result = $this->updateEventAction->__invoke($event, $oldEvent);
        return $this->getResponse($response, $result);
    }
}
