<?php

use Slim\App;

return function (App $app) {
    (include __DIR__.'/../../../include/lib/Modules/Authentication/routes.php')($app);
    (include __DIR__.'/../../../include/lib/Modules/Events/routes.php')($app);
    (include __DIR__.'/../../../include/lib/Modules/Groups/routes.php')($app);
    (include __DIR__.'/../../../include/lib/Modules/Profiles/routes.php')($app);
    (include __DIR__.'/../../../include/lib/Modules/Tags/routes.php')($app);
    (include __DIR__.'/../../../include/lib/Modules/Users/routes.php')($app);
};
