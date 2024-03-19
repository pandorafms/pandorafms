<?php

namespace PandoraFMS\Modules\Shared\Exceptions;

use Exception;
use PandoraFMS\Modules\Shared\Enums\HttpCodesEnum;

class InvalidFilterException extends Exception
{
    public function __construct(array $fails)
    {
        $str = '';
        foreach ($fails as $fail) {
            $str .= $fail['message'];
        }

        parent::__construct(__($str), HttpCodesEnum::BAD_REQUEST);
    }
}
