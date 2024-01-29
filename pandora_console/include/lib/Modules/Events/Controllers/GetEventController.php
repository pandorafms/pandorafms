<?php

namespace PandoraFMS\Modules\Events\Controllers;

use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetEventController extends Controller
{
    public function __construct(
        private GetEventAction $getEventAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/event/{idEvent}",
     *   tags={"Events"},
     *   summary="Show event",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseEvent"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idEvent = $this->getParam($request, 'idEvent');

        $this->acl->validate(0, 'ER', ' tried to manage event');

        $result = $this->getEventAction->__invoke($idEvent);
        return $this->getResponse($response, $result);
    }
}
