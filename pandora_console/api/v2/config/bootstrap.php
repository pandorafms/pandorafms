<?php

use DI\ContainerBuilder;
use Slim\App;

require_once __DIR__.'/includeDependencies.php';

$containerBuilder = new ContainerBuilder();

// Add DI container definitions.
$containerBuilder->addDefinitions(__DIR__.'/container.php');

// Create DI container instance.
$container = $containerBuilder->build();

// Create Slim App instance.
$app = $container->get(App::class);

// Set attachment directory.
$config['attachment_directory'] = __DIR__.'/../../../attachment';

// Register routes.
(require __DIR__.'/routes.php')($app);

return $app;
