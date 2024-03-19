<?php

namespace PandoraFMS\Modules\Shared\Exceptions;

use Exception;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;

class ForbiddenACLException extends Exception
{
    public function __construct(string $fails)
    {
        parent::__construct($fails, HttpCodesEnum::FORBIDDEN);
    }
}
