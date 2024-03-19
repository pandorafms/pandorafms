<?php

namespace PandoraFMS\Modules\Authentication\Services;

use Ramsey\Uuid\Uuid;

final class GenerateUserUUIDService
{
    public function __construct(
    ) {
    }

    public function __invoke(): string
    {
        return Uuid::uuid4()->toString();
    }
}
