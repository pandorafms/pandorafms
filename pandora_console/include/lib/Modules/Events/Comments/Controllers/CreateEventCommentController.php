<?php

namespace PandoraFMS\Modules\Events\Comments\Controllers;

use PandoraFMS\Modules\Events\Actions\GetEventAction;
use PandoraFMS\Modules\Events\Comments\Actions\CreateEventCommentAction;
use PandoraFMS\Modules\Events\Comments\Entities\EventComment;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateEventCommentController extends Controller
{
    public function __construct(
        private CreateEventCommentAction $createEventCommentAction,
        private ValidateAclSystem $acl,
        private GetEventAction $getEventAction
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event/{idEvent}/comment",
     *   summary="Creates a new field into events comments",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdEvent"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEventComment"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseEventComment"),
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

        $this->acl->validateUserGroups(
            $event->getIdGroup(),
            'EW',
            ' tried to write event'
        );

        // @var EventComment $eventComment.
        $eventComment = $this->fromRequest($request, EventComment::class);
        $eventComment->setIdEvent($idEvent);

        $result = $this->createEventCommentAction->__invoke($eventComment);

        return $this->getResponse($response, $result);
    }
}
