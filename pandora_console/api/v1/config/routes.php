<?php

use Slim\App;

return function (App $app) {
    (include __DIR__.'/../../../include/lib/Modules/Users/routes.php')($app);
};
