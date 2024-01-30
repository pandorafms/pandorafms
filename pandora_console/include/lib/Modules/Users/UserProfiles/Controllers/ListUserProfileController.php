<?php

namespace PandoraFMS\Modules\Users\UserProfiles\Controllers;

use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;
use PandoraFMS\Modules\Users\Actions\GetUserAction;
use PandoraFMS\Modules\Users\UserProfiles\Actions\ListUserProfileAction;
use PandoraFMS\Modules\Users\UserProfiles\Entities\UserProfileFilter;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class ListUserProfileController extends Controller
{
    public function __construct(
        private ListUserProfileAction $listUserProfileAction,
        private GetUserAction $getUserAction,
        private ValidateAclSystem $acl,
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Users"},
     *   path="/user/{idUser}/profiles",
     *   summary="List user profiles",
     *   @OA\Parameter(ref="#/components/parameters/parameterIdUser"),
     *   @OA\Parameter(ref="#/components/parameters/parameterPage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSizePage"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortField"),
     *   @OA\Parameter(ref="#/components/parameters/parameterSortDirection"),
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyUserProfile"),
     *   @OA\Response(
     *     response="200",
     *     description="List data profiles user object",
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
     *               ref="#/components/schemas/UserProfile",
     *               description="Array of profiles"
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
        $idUser = $this->getParam($request, 'idUser');
        $this->getUserAction->__invoke($idUser);

        $this->acl->validate(0, 'UM', ' tried to manage user profile');

        // @var UserProfileFilter $userProfileFilter.
        $userProfileFilter = $this->fromRequest($request, UserProfileFilter::class);
        $userProfileFilter->getEntityFilter()->setIdUser($idUser);

        $result = $this->listUserProfileAction->__invoke($userProfileFilter);
        return $this->getResponse($response, $result);
    }
}
