<?php

// use PandoraFMS\Modules\Shared\Middlewares\UserTokenMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Log\LoggerInterface;
use Slim\App;

return function (App $app, ContainerInterface $container) {
    // Parse json, form data and xml.
    $app->addBodyParsingMiddleware();

    // Add the Slim built-in routing middleware.
    $app->addRoutingMiddleware();

    // Authenticate Integria.
    $beforeMiddleware = function (
        Request $request,
        RequestHandler $handler
    ) use (
        $app,
        $container
) {
        global $config;
        $authorization = $request->getHeader('Authorization');
        $user = false;
        if (empty($authorization) === false && empty($authorization[0]) === false) {
            $bearer = explode('Bearer ', $authorization[0]);
            if (empty($bearer) === false && isset($bearer[1]) === true) {
                // $user = \get_db_value(
                // 'id_usuario',
                // 'tusuario',
                // 'api_key',
                // $bearer[1]
                // );
                if ($user !== false) {
                    if (session_status() === PHP_SESSION_NONE) {
                        session_start();
                    }

                    $_SESSION['id_usuario'] = $user;
                    $config['id_user'] = $user;

                    if (session_status() === PHP_SESSION_ACTIVE) {
                        session_write_close();
                    }
                }
            }
        }

        $user = 'admin';
        $_SESSION['id_usuario'] = $user;
        $config['id_user'] = $user;

        if (empty($user) === true) {
            // $userTokenMiddleware = $container->get(UserTokenMiddleware::class);
            // if ($userTokenMiddleware->check($request) === false) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write(
                json_encode(['error' => 'You need to be authenticated to perform this action'])
            );

            $errorCode = 401;
            $newResponse = $response->withStatus($errorCode);
            return $newResponse;
        }

        $response = $handler->handle($request);
        return $response;
    };

    $app->add($beforeMiddleware);

    // Handle exceptions.
    // Define Custom Error Handler.
    $customErrorHandler = function (
        Request $request,
        Throwable $exception,
        bool $displayErrorDetails,
        bool $logErrors,
        bool $logErrorDetails,
        ?LoggerInterface $logger=null
    ) use ($app) {
        $logger?->error($exception->getMessage());
        $response = $app->getResponseFactory()->createResponse();
        $response->getBody()->write(
            json_encode(['error' => $exception->getMessage()])
        );

        $errorCode = 500;
        if (empty($exception->getCode()) === false) {
            $errorCode = $exception->getCode();
        }

        $newResponse = $response->withStatus($errorCode);
        return $newResponse;
    };

    // Add Error Middleware.
    // $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    // $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
};
