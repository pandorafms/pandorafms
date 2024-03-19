<?php

namespace PandoraFMS\Modules\Shared\Traits;

trait EnumTrait
{
    public static function get(
        mixed $value,
        string $type = 'name'
    ): mixed {
        $cases = static::cases();
        $index = array_search($value, array_column($cases, $type));
        if ($index !== false) {
            return $cases[$index];
        }

        return null;
    }
}
