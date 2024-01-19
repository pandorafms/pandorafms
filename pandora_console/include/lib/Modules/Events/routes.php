<?php

use PandoraFMS\Modules\Events\Controllers\CreateEventController;
use PandoraFMS\Modules\Events\Controllers\DeleteEventController;
use PandoraFMS\Modules\Events\Controllers\GetEventController;
use PandoraFMS\Modules\Events\Controllers\ListEventController;
use PandoraFMS\Modules\Events\Controllers\UpdateEventController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/event/list', ListEventController::class);
    $app->get('/event/{idEvent}', GetEventController::class);
    $app->post('/event', CreateEventController::class);
    $app->put('/event/{idEvent}', UpdateEventController::class);
    $app->delete('/event/{idEvent}', DeleteEventController::class);
};
