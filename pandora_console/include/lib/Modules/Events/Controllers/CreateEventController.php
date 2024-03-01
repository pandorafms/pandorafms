<?php

namespace PandoraFMS\Modules\Events\Controllers;

use PandoraFMS\Modules\Events\Actions\CreateEventAction;
use PandoraFMS\Modules\Events\Entities\Event;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateEventController extends Controller
{
    public function __construct(
        private CreateEventAction $createEventAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Events"},
     *   path="/event",
     *   summary="Creates a new events",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyEvent"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseEvent"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var Event $event.
        $event = $this->fromRequest($request, Event::class);

        $this->acl->validate(0, 'EW', ' tried to write event');

        $result = $this->createEventAction->__invoke($event);

        return $this->getResponse($response, $result);
    }
}
