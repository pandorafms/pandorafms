<?php

namespace PandoraFMS\Modules\Events\Filters\Controllers;

use PandoraFMS\Modules\Events\Filters\Actions\GetEventFilterAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class GetEventFilterController extends Controller
{
    public function __construct(
        private GetEventFilterAction $getEventFilterAction,
        private ValidateAclSystem $acl
    ) {
    }

    /**
     * @OA\Get(
     *   security={{ "bearerAuth": {}}},
     *   path="/event/filter/{idEventFilter}",
     *   tags={"Events"},
     *   summary="Show eventFilter",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEventFilter"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseEventFilter"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        $idEventFilter = $this->getParam($request, 'idEventFilter');

        $this->acl->validate(0, 'ER', ' tried to read event');

        $result = $this->getEventFilterAction->__invoke($idEventFilter);
        return $this->getResponse($response, $result);
    }
}
