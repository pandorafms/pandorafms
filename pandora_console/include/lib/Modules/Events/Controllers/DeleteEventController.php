<?php

namespace PandoraFMS\Modules\Events\Controllers;

use PandoraFMS\Modules\Events\Actions\DeleteEventAction;
use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteEventController extends Controller
{
    public function __construct(
        private DeleteEventAction $deleteEventAction,
        private ValidateAclSystem $acl,
        private GetEventAction $getEventAction
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event/{idEvent}",
     *   summary="Deletes an event object.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
     *   @OA\Response(response=200, ref="#/components/responses/successfullyDeleted"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idEvent = $this->getParam($request, 'idEvent');
        $event = $this->getEventAction->__invoke($idEvent);

        $this->acl->validate(0, 'EM', ' tried to manage event');

        $result = $this->deleteEventAction->__invoke($event);
        return $this->getResponse($response, $result);
    }
}
