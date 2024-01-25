<?php

use PandoraFMS\Modules\EventFilters\Controllers\CreateEventFilterController;
use PandoraFMS\Modules\EventFilters\Controllers\DeleteEventFilterController;
use PandoraFMS\Modules\EventFilters\Controllers\GetEventFilterController;
use PandoraFMS\Modules\EventFilters\Controllers\ListEventFilterController;
use PandoraFMS\Modules\EventFilters\Controllers\UpdateEventFilterController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/eventFilter/list', ListEventFilterController::class);
    $app->get('/eventFilter/{idEventFilter}', GetEventFilterController::class);
    $app->post('/eventFilter', CreateEventFilterController::class);
    $app->put('/eventFilter/{idEventFilter}', UpdateEventFilterController::class);
    $app->delete('/eventFilter/{idEventFilter}', DeleteEventFilterController::class);
};
