<?php

namespace PandoraFMS\Modules\Events\Comments\Controllers;

use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Events\Comments\Actions\GetEventCommentAction;
use PandoraFMS\Modules\Events\Comments\Actions\UpdateEventCommentAction;
use PandoraFMS\Modules\Shared\Controllers\Controller;

use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Put(
 *   security={{ "bearerAuth": {}}},
 *   path="/event/{idEvent}/comment/{idComment}",
 *   tags={"Events"},
 *   summary="Updates comment for event type",
 *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
 *   @OA\Parameter(ref="#/components/parameters/parameterIdEventComment"),
 *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventComment"),
 *   @OA\Response(response=200, ref="#/components/responses/ResponseEventComment"),
 *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
 *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
 *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
 *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
 *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
 * )
 */
final class UpdateEventCommentController extends Controller
{
    public function __construct(
        private UpdateEventCommentAction $updateEventCommentAction,
        private ValidateAclSystem $acl,
        private GetEventAction $getEventAction,
        private GetEventCommentAction $getEventCommentAction
    ) {
    }

    public function __invoke(Request $request, Response $response): Response
    {
        $idEvent = $this->getParam($request, 'idEvent');
        $event = $this->getEventAction->__invoke($idEvent);

        $this->acl->validateUserGroups(
            $event->getIdGroup(),
            'EW',
            ' tried to manage Comments'
        );

        $idEventComment = $this->getParam($request, 'idComment');
        $eventComment = $this->getEventCommentAction->__invoke($idEvent, $idEventComment);

        $oldEventComment = clone $eventComment;

        $params = $this->extractParams($request);
        $eventComment->fromArray($params);

        $result = $this->updateEventCommentAction->__invoke($eventComment, $oldEventComment);

        return $this->getResponse($response, $result);
    }
}
