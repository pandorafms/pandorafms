<?php

namespace PandoraFMS\Modules\Shared\Exceptions;

use Exception;

class ForbiddenActionException extends Exception
{
    public function __construct(string $fails, int $httpCodesEnum)
    {
        parent::__construct($fails, $httpCodesEnum);
    }
}
