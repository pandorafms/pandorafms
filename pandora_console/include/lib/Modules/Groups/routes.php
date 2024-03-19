<?php

use PandoraFMS\Modules\Groups\Controllers\CreateGroupController;
use PandoraFMS\Modules\Groups\Controllers\DeleteGroupController;
use PandoraFMS\Modules\Groups\Controllers\GetGroupController;
use PandoraFMS\Modules\Groups\Controllers\ListGroupController;
use PandoraFMS\Modules\Groups\Controllers\UpdateGroupController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/group/list', ListGroupController::class);
    $app->get('/group/{idGroup}', GetGroupController::class);
    $app->post('/group', CreateGroupController::class);
    $app->put('/group/{idGroup}', UpdateGroupController::class);
    $app->delete('/group/{idGroup}', DeleteGroupController::class);
};
