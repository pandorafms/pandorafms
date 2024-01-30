<?php

namespace PandoraFMS\Modules\Shared\Services;

use PandoraFMS\Modules\Shared\Exceptions\ForbiddenACLException;

class Management
{
    public function __construct(
    ) {
    }

    public function isManagementAllowed(string $class): void
    {
        // TODO: change service.
        if (\is_management_allowed() === false) {
            if (\is_metaconsole() === false) {
                $console = __('metaconsole');
            } else {
                $console = __('any node');
            }

            throw new ForbiddenACLException(
                __(
                    'This console is configured with centralized mode. All %s information is read only. Go to %s to manage it.',
                    $class,
                    $console
                )
            );
        }
    }
}
