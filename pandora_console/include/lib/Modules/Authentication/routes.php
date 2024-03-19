<?php

use PandoraFMS\Modules\Authentication\Controllers\CreateTokenController;
use PandoraFMS\Modules\Authentication\Controllers\DeleteTokenController;
use PandoraFMS\Modules\Authentication\Controllers\GetTokenController;
use PandoraFMS\Modules\Authentication\Controllers\ListTokenController;
use PandoraFMS\Modules\Authentication\Controllers\PingController;
use PandoraFMS\Modules\Authentication\Controllers\UpdateTokenController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/token/list', ListTokenController::class);
    $app->get('/token/{id}', GetTokenController::class);
    $app->post('/token', CreateTokenController::class);
    $app->put('/token/{id}', UpdateTokenController::class);
    $app->delete('/token/{id}', DeleteTokenController::class);
    $app->get('/ping', PingController::class);
};
