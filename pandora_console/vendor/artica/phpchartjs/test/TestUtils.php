<?php

namespace Test;

use Laminas\Json\Expr;
use RuntimeException;

/**
 * Class TestUtils
 * @package Test
 */
class TestUtils
{
    /**
     * this method sets all defined attributes from the input array
     * $input_data in the $obj and calls the setter.
     *
     * @param       $obj
     * @param array $data
     */
    public static function setAttributes($obj, array $data)
    {

        if (! is_object($obj)) {
            throw new RuntimeException("First param should be an object. ");
        }

        foreach ($data as $key => $value) {
            $function = 'set' . ucfirst($key);
            if (! is_null($value) && method_exists($obj, $function)) {
                $obj->$function($value);
            }
        }
    }

    /**
     * this method reads all defined attributes from the input array
     * $input_data and calls the getter. It returns the resulting array.
     *
     * @param       $obj
     * @param array $dataTypes  is an associative array that refers fieldnames to values.
     *                          The values could be any primitive type, including an array.
     *
     * @return array
     */
    public static function getAttributes($obj, array $dataTypes)
    {

        if (! is_object($obj)) {
            throw new RuntimeException("First param should be an object. ");
        }

        $array = [];
        foreach ($dataTypes as $key => $value) {
            $function = ( gettype($value) == "boolean" ? 'is' : 'get' ) . ucfirst($key);
            if (method_exists($obj, $function)) {
                $getResult     = $obj->$function($value);
                $getResult     = $getResult instanceof Expr ? $getResult->__toString() : $getResult;
                $array[ $key ] = $getResult;
            }
        }

        return $array;
    }

    /**
     * @param $input_array
     *
     * @return mixed
     */
    public static function removeNullsFromArray($input_array)
    {
        $array = $input_array;
        $keys  = array_keys($array);
        foreach ($keys as $key) {
            if (is_null($array[ $key ])) {
                unset($array[ $key ]);
            }
        }

        return $array;
    }
}
