<?php

use PandoraFMS\Modules\Profiles\Controllers\CreateProfileController;
use PandoraFMS\Modules\Profiles\Controllers\DeleteProfileController;
use PandoraFMS\Modules\Profiles\Controllers\GetProfileController;
use PandoraFMS\Modules\Profiles\Controllers\ListProfileController;
use PandoraFMS\Modules\Profiles\Controllers\UpdateProfileController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/profile/list', ListProfileController::class);
    $app->get('/profile/{idProfile}', GetProfileController::class);
    $app->post('/profile', CreateProfileController::class);
    $app->put('/profile/{idProfile}', UpdateProfileController::class);
    $app->delete('/profile/{idProfile}', DeleteProfileController::class);
};
