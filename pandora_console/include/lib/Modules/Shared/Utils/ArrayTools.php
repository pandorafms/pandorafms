<?php

namespace PandoraFMS\Modules\Shared\Utils;

final class ArrayTools
{
    public function extractFromObjectByAttribute(string $attribute, array $list): array
    {
        return array_reduce(
            $list,
            static function ($carry, $item) use ($attribute) {
                $carry[] = $item->{$attribute}();
                return $carry;
            },
            []
        );
    }

    public function indexObjectByAttribute(string $attribute, array $list): array
    {
        return array_reduce(
            $list,
            static function ($carry, $item) use ($attribute) {
                $carry[$item->{$attribute}()][] = $item;
                return $carry;
            },
            []
        );
    }
}
