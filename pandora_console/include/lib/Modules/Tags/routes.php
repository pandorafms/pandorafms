<?php

use PandoraFMS\Modules\Tags\Controllers\CreateTagController;
use PandoraFMS\Modules\Tags\Controllers\DeleteTagController;
use PandoraFMS\Modules\Tags\Controllers\GetTagController;
use PandoraFMS\Modules\Tags\Controllers\ListTagController;
use PandoraFMS\Modules\Tags\Controllers\UpdateTagController;
use Slim\App;

return function (App $app) {
    $app->map(['GET', 'POST'], '/tag/list', ListTagController::class);
    $app->get('/tag/{idTag}', GetTagController::class);
    $app->post('/tag', CreateTagController::class);
    $app->put('/tag/{idTag}', UpdateTagController::class);
    $app->delete('/tag/{idTag}', DeleteTagController::class);
};
