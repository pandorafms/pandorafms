<?php

namespace PandoraFMS\Modules\Shared\Controllers;

use Nyholm\Psr7\Stream;

use PandoraFMS\Modules\Shared\Core\SerializableAbstract;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;
use PandoraFMS\Modules\Shared\Exceptions\BadRequestException;
use PandoraFMS\Modules\Shared\Exceptions\ForbiddenActionException;
use PandoraFMS\Modules\Shared\Exceptions\InvalidClassException;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UploadedFileInterface;

use Slim\Routing\RouteContext;

abstract class Controller
{
    public function __construct()
    {
    }

    public function getParam(Request $request, string $param): mixed
    {
        $routeContext = RouteContext::fromRequest($request);
        $route = $routeContext->getRoute();
        $value = $route->getArgument($param);

        if (empty($value) === true) {
            throw new BadRequestException(__('Parameter %s is required as an argument', $param));
        }

        return $value;
    }

    public function getFile(Request $request, string $file): UploadedFileInterface
    {
        $files = $request->getUploadedFiles();
        if (isset($files[$file]) === false || empty($files[$file]) === true) {
            throw new BadRequestException(__('File %s is required as an argument', $file));
        }

        if ($files[$file]->getError() !== UPLOAD_ERR_OK) {
            throw new BadRequestException(__('Error upload file'));
        }

        return $files[$file];
    }

    public function extractParams(Request $request): array
    {
        $queryParams = ($request->getQueryParams() ?? []);
        $parsedBody = ($request->getParsedBody() ?? []);
        if (JSON_ERROR_NONE !== json_last_error()) {
            throw new \UnexpectedValueException(
                sprintf(
                    'Cannot decoded body JSON, error: %s',
                    json_last_error_msg()
                )
            );
        }

        $params = array_merge($queryParams, $parsedBody);

        return $params;
    }

    public function fromRequest(Request $request, string $className): mixed
    {
        $params = $this->extractParams($request);

        if (class_exists($className) === false) {
            throw new InvalidClassException(__('Class %s is not defined', $className));
        }

        $class = new $className();

        if (!$class instanceof SerializableAbstract) {
            throw new InvalidClassException(__('Class %s is not instance of Serializable abstract', $className));
        }

        return $class->fromArray($params);
    }

    public function getResponse(
        Response $response,
        mixed $result,
        ?string $contentType = 'application/json'
    ): Response {
        if ($contentType === 'application/json') {
            $result = json_encode($result);
        }

        $response->getBody()->write($result);
        return $response->withHeader('Content-Type', $contentType);
    }

    public function getResponseAttachment(Response $response, string $path, string $fileName)
    {
        try {
            ob_clean();
            $stream = fopen($path, 'r');
            $file_stream = Stream::create($stream);
        } catch (\Throwable $th) {
            throw new ForbiddenActionException(
                __('Error download file: ').$th->getMessage(),
                empty($th->getCode()) === true ? HttpCodesEnum::BAD_REQUEST : $th->getCode()
            );
        }

        return $response->withBody($file_stream)->withHeader('Content-Disposition', 'attachment; filename='.$fileName)->withHeader('Content-Type', mime_content_type($path));
    }
}
