<?php

namespace PandoraFMS\Modules\Profiles\Controllers;

use PandoraFMS\Modules\Profiles\Actions\CreateProfileAction;
use PandoraFMS\Modules\Profiles\Entities\Profile;
use PandoraFMS\Modules\Shared\Controllers\Controller;
use PandoraFMS\Modules\Shared\Services\Management;
use PandoraFMS\Modules\Shared\Services\ValidateAclSystem;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class CreateProfileController extends Controller
{
    public function __construct(
        private CreateProfileAction $createProfileAction,
        private ValidateAclSystem $acl,
        private Management $management
    ) {
    }

    /**
     * @OA\Post(
     *   security={{ "bearerAuth": {}}},
     *   tags={"Profiles"},
     *   path="/profile",
     *   summary="Creates a new profiles",
     *   @OA\RequestBody(ref="#/components/requestBodies/requestBodyProfile"),
     *   @OA\Response(response=200, ref="#/components/responses/ResponseProfile"),
     *   @OA\Response(response=400, ref="#/components/responses/BadRequest"),
     *   @OA\Response(response=401, ref="#/components/responses/Unauthorized"),
     *   @OA\Response(response=403, ref="#/components/responses/Forbidden"),
     *   @OA\Response(response=404, ref="#/components/responses/NotFound"),
     *   @OA\Response(response=500, ref="#/components/responses/InternalServerError")
     * )
     */
    public function __invoke(Request $request, Response $response): Response
    {
        // @var Profile $profile.
        $profile = $this->fromRequest($request, Profile::class);

        $this->acl->validateUserAdmin();
        $this->acl->validate(0, 'UM', ' tried to manage profile');

        if (\is_metaconsole() === false) {
            $this->management->isManagementAllowed('Profile', true);
        }

        $result = $this->createProfileAction->__invoke($profile);

        return $this->getResponse($response, $result);
    }
}
