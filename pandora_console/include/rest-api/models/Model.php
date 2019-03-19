<?php

declare(strict_types=1);

namespace Models;

abstract class Model
{

    private $data;


    protected abstract function validateData(array $data): void;


    protected abstract function decode(array $data): array;


    protected function __construct(array $unknownData)
    {
        $this->validateData($unknownData);
        $this->data = $this->decode($unknownData);
    }


    public function toJson(): string
    {
        return \json_encode($this->data);
    }


    public function __toString(): string
    {
        return $this->toJson();
    }


    protected static function parseBool($value): bool
    {
        if (\is_bool($value) === true) {
            return $value;
        } else if (\is_numeric($value) === true) {
            return $value > 0;
        } else if (\is_string($value) === true) {
            return $value === '1' || $value === 'true';
        } else {
            return false;
        }
    }


    protected static function notEmptyStringOr($val, $def)
    {
        return (\is_string($val) === true && strlen($val) > 0) ? $val : $def;
    }


    protected static function issetInArray(array $val, array $keys)
    {
        foreach ($keys as $key => $value) {
            if (isset($val[$value])) {
                return $val[$value];
            }
        }

        return null;
    }


}
