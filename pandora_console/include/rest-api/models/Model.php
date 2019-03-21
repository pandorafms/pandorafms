<?php

declare(strict_types=1);

namespace Models;

abstract class Model
{

    private $data;


    protected abstract function validateData(array $data): void;


    protected abstract function decode(array $data): array;


    public function __construct(array $unknownData)
    {
        $this->validateData($unknownData);
        $this->data = $this->decode($unknownData);
    }


    /**
     * Returns the JSON representation of the given value.
     *
     * @return string
     */
    public function toJson(): string
    {
        return \json_encode($this->data);
    }


    /**
     * Returns the text representation of this class.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }


    /**
     * Returns a Boolean of a mixed value.
     *
     * @param mixed $value
     *
     * @return boolean
     */
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


    /**
     * Return a not empty string or a default value from a mixed value.
     *
     * @param mixed $val
     * @param mixed $def Default value to use if we cannot extract a non empty string.
     *
     * @return mixed
     */
    protected static function notEmptyStringOr($val, $def)
    {
        return (\is_string($val) === true && strlen($val) > 0) ? $val : $def;
    }


    /**
     * Return a integer or a default value from a mixed value.
     *
     * @param mixed $val
     * @param mixed $def
     *
     * @return mixed
     */
    protected static function parseIntOr($val, $def)
    {
        return is_numeric($val) ? (int) $val : $def;
    }


    /**
     * Returns the value if it exists in the array
     *
     * @param array $val  input array
     * @param array $keys array with the keys to search
     *
     * @return mixed
     */
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
