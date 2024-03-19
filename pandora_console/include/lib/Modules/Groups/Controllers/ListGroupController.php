<?php

namespace PandoraFMS\Modules\Groups\Controllers;

use PandoraFMS\Modules\Groups\Actions\ListGroupAction;
use PandoraFMS\Modules\Groups\Entities\GroupFilter;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListGroupController extends Controller
{
    public function __construct(
        private ListGroupAction $listGroupAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Groups"},
     *   path="/group/list",
     *   summary="List groups",
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyGroupFilter"),
     *   @OA\Response(
     *     response="200",
     *     description="List Groups Object",
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
     *               ref="#/components/schemas/Group",
     *               description="Array of group objects"
     *             )
     *           ),
     *         ),
     *       )
     *     }
     *   ),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     *  )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var GroupFilter $groupFilter.
        $groupFilter = $this->fromRequest($request, GroupFilter::class);

        $this->acl->validate(0, 'AR', ' tried to read agents for groups');

        $result = $this->listGroupAction->__invoke($groupFilter);
        return $this->getResponse($response, $result);
    }
}
