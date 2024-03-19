<?php

namespace PandoraFMS\Modules\Users\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\ListUserAction;
use PandoraFMS\Modules\Users\Entities\UserFilter;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListUserController extends Controller
{
    public function __construct(
        private ListUserAction $listUserAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user/list",
     *   summary="List user",
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyUserFilter"),
     *   @OA\Response(
     *     response="200",
     *     description="List Incidence object",
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
     *               ref="#/components/schemas/User",
     *               description="Array of user objects"
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
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var UserFilter $userFilter.
        $userFilter = $this->fromRequest($request, UserFilter::class);

        $this->acl->validate(0, 'UM', ' tried to manage user');

        $result = $this->listUserAction->__invoke($userFilter);

        return $this->getResponse($response, $result);
    }
}
