<?php

namespace PandoraFMS\Modules\Events\Comments\Controllers;

use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Events\Comments\Actions\ListEventCommentAction;
use PandoraFMS\Modules\Events\Comments\Entities\EventCommentFilter;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListEventCommentController extends Controller
{
    public function __construct(
        private ListEventCommentAction $listEventCommentAction,
        private GetEventAction $getEventAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event/{idEvent}/comment/list",
     *   summary="List comments event",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventCommentFilter"),
     *   @OA\Response(
     *     response="200",
     *     description="List Comments event object",
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
     *               ref="#/components/schemas/EventComment",
     *               description="Array of fields for comments event object"
     *             )
     *           ),
     *         ),
     *       )
     *     }
     *   ),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {

        $idEvent = $this->getParam($request, 'idEvent');
        $event = $this->getEventAction->__invoke($idEvent);

        $this->acl->validateUserGroups(
            $event->getIdGroup(),
            'ER',
            ' tried to manage commments events'
        );

        // @var EventCommentFilter $eventCommentFilter.
        $eventCommentFilter = $this->fromRequest($request, EventCommentFilter::class);
        $eventCommentFilter->getEntityFilter()->setIdEvent($idEvent);

        $result = $this->listEventCommentAction->__invoke($eventCommentFilter);

        return $this->getResponse($response, $result);
    }
}
