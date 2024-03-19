<?php

use PandoraFMS\Modules\Authentication\Repositories\TokenRepository;
use PandoraFMS\Modules\Authentication\Repositories\TokenRepositoryMySQL;
use PandoraFMS\Modules\Events\Repositories\EventRepository;
use PandoraFMS\Modules\Events\Repositories\EventRepositoryMySQL;
use PandoraFMS\Modules\Groups\Repositories\GroupRepository;
use PandoraFMS\Modules\Groups\Repositories\GroupRepositoryMySQL;
use PandoraFMS\Modules\Shared\Repositories\Repository;
use PandoraFMS\Modules\Shared\Repositories\RepositoryMySQL;
use PandoraFMS\Modules\Users\Repositories\UserRepository;
use PandoraFMS\Modules\Users\Repositories\UserRepositoryMySQL;
use Psr\Container\ContainerInterface;
use Slim\App;
use Slim\Factory\AppFactory;

return [
    'settings'             => function () {
        return include __DIR__.'/settings.php';
    },
    App::class             => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        $app = AppFactory::create();

        $basePath = rtrim(
            preg_replace(
                '/(.*)public\/.*/',
                '$1',
                $_SERVER['SCRIPT_NAME']
            ),
            '/'
        );

        $app->setBasePath($basePath);

        // Register middleware.
        (include __DIR__.'/middleware.php')($app, $container);

        return $app;
    },
    Repository::class      => function (ContainerInterface $container) {
        return $container->get(RepositoryMySQL::class);
    },
    TokenRepository::class => function (ContainerInterface $container) {
        return $container->get(TokenRepositoryMySQL::class);
    },
    UserRepository::class  => function (ContainerInterface $container) {
        return $container->get(UserRepositoryMySQL::class);
    },
    GroupRepository::class => function (ContainerInterface $container) {
        return $container->get(GroupRepositoryMySQL::class);
    },
    EventRepository::class => function (ContainerInterface $container) {
        return $container->get(EventRepositoryMySQL::class);
    },
];
