<?php

use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Middlewares\AclListMiddleware;
use PandoraFMS\Modules\Shared\Middlewares\UserTokenMiddleware;
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
        $ipOrigin = $_SERVER['REMOTE_ADDR'];
        $aclListMiddleware = $container->get(AclListMiddleware::class);
        if ($aclListMiddleware->check($ipOrigin) === false) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write(
                json_encode(['error' => __('IP %s is not in ACL list', $ipOrigin)])
            );

            $errorCode = HttpCodesEnum::UNAUTHORIZED;
            $newResponse = $response->withStatus($errorCode);
            return $newResponse;
        }

        $userTokenMiddleware = $container->get(UserTokenMiddleware::class);
        if ($userTokenMiddleware->check($request) === false) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write(
                json_encode(['error' => __('You need to be authenticated to perform this action')])
            );

            $errorCode = HttpCodesEnum::UNAUTHORIZED;
            $newResponse = $response->withStatus($errorCode);
            return $newResponse;
        }

        try {
            include_once __DIR__.'/includeEnterpriseDependencies.php';
        } catch (\Throwable $th) {
            $response = $app->getResponseFactory()->createResponse();
            $response->getBody()->write(
                json_encode(['error' => __('Invalid License')])
            );

            $errorCode = HttpCodesEnum::UNAUTHORIZED;
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
    $errorMiddleware = $app->addErrorMiddleware(true, true, true);
    $errorMiddleware->setDefaultErrorHandler($customErrorHandler);
};
