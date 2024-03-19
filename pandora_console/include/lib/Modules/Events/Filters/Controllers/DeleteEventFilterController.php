<?php

namespace PandoraFMS\Modules\Events\Filters\Controllers;

use PandoraFMS\Modules\Events\Filters\Actions\DeleteEventFilterAction;
use PandoraFMS\Modules\Events\Filters\Actions\GetEventFilterAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class DeleteEventFilterController extends Controller
{
    public function __construct(
        private DeleteEventFilterAction $deleteEventFilterAction,
        private ValidateAclSystem $acl,
        private GetEventFilterAction $getEventFilterAction
    ) {
    }

    /**
     * @OA\Delete(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event/filter/{idEventFilter}",
     *   summary="Deletes an eventFilter object.",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEventFilter"),
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
        $idEventFilter = $this->getParam($request, 'idEventFilter');
        $eventFilter = $this->getEventFilterAction->__invoke($idEventFilter);

        $this->acl->validate(0, 'EM', ' tried to write event');

        $result = $this->deleteEventFilterAction->__invoke($eventFilter);
        return $this->getResponse($response, $result);
    }
}
