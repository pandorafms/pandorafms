<?php

declare(strict_types=1);

namespace Models;

abstract class Model
{

    private $data;


    protected abstract function validateData(array $data): void;


    protected abstract function decode(array $data): array;


    private function __construct(array $unknownData)
    {
        $this->validateData($unknownData);
        $this->data = $this->decode($unknownData);
    }


    public static function fromArray(array $data): self
    {
        return new self($data);
    }


    public function toJson(): string
    {
        return \json_encode($this->data);
    }


    public function __toString(): string
    {
        return $this->toJson();
    }


    protected static function parseBool(mixed $value): boolean
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


    protected static function notEmptyStringOr(mixed $val, string $def): mixed
    {
        return (\is_string($val) === true && count($val) > 0) ? $val : $def;
    }


}
