<?php

declare(strict_types=1);

namespace Models;

/**
 * This class should be extended to add functionalities to
 * fetch, validate, transform and represent data entities.
 */
abstract class Model
{

    /**
     * Internal data of the model.
     *
     * @var array
     */
    private $data;


    /**
     * Validate the received data structure to ensure if we can extract the
     * values required to build the model.
     *
     * @param array $data Input data.
     *
     * @return void
     *
     * @throws \InvalidArgumentException If any input value is considered
     * invalid.
     *
     * @abstract
     */
    abstract protected function validateData(array $data): void;


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @abstract
     */
    abstract protected function decode(array $data): array;


    /**
     * Return a valid representation of a record in database.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing a record in database.
     *
     * @abstract
     */
    abstract protected static function encode(array $data): array;


    /**
     * Inserts a new item model in the database
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @overrides Model::save.
     */
    public static function create(array $data=[]): int
    {
        // Insert.
        $save = static::encode($data);

        $result = \db_process_sql_insert('tlayout_data', $save);

        return $result;
    }


    /**
     * Insert or update an item in the database
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @abstract
     */
    abstract public function save(array $data=[]);


    /**
     * Delete an item in the database
     *
     * @param integer $itemId Identifier of the Item.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @abstract
     */
    abstract public function delete(int $itemId): bool;


    /**
     * Constructor of the model. It won't be public. The instances
     * will be created through factories which start with from*.
     *
     * @param array $unknownData Input data structure.
     */
    protected function __construct(array $unknownData)
    {
        $this->validateData($unknownData);
        $this->data = $this->decode($unknownData);
        // Sort alphabetically.
        ksort($this->data, (SORT_NATURAL | SORT_FLAG_CASE));
    }


    /**
     * Set data.
     *
     * @param array $data Data.
     *
     * @return void
     */
    public function setData(array $data)
    {
        $this->data = $data;
    }


    /**
     * Instance the class with the unknown input data.
     *
     * @param array $data Unknown data structure.
     *
     * @return self Instance of the model.
     */
    public static function fromArray(array $data): self
    {
        // The reserved word static refers to the invoked class at runtime.
        return new static($data);
    }


    /**
     * Obtain a data structure from the database using a filter.
     *
     * @param array      $filter     Filter to retrieve the modeled element.
     * @param float|null $ratio      Ratio.
     * @param float|null $widthRatio Width ratio.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @abstract
     */
    abstract protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    );


    /**
     * Obtain a model's instance from the database using a filter.
     *
     * @param array      $filter     Filter to retrieve the modeled element.
     * @param float|null $ratio      Ratio.
     * @param float|null $widthRatio Width ratio.
     *
     * @return self A modeled element's instance.
     */
    public static function fromDB(array $filter, ?float $ratio=0, ?float $widthRatio=0): self
    {
        // The reserved word static refers to the invoked class at runtime.
        return static::fromArray(static::fetchDataFromDB($filter, $ratio, $widthRatio));
    }


    /**
     * JSON representation of the model.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->data;
    }


    /**
     * JSON representation of the model.
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->data);
    }


    /**
     * Text representation of the model.
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->toJson();
    }


    /**
     * Calculate ratio for mobile.
     *
     * @param array  $size Size viewport.
     * @param string $mode Mode calculate (dashboard or mobile).
     *
     * @return float Ratio.
     */
    public function adjustToViewport($size, $mode='')
    {
        global $config;
        $ratio_visualconsole = $this->getRatio();
        $ratio_w = ($size['width'] / $this->data['width']);
        $ratio_h = ($size['height'] / $this->data['height']);
        $acum_height = $this->data['height'];
        $acum_width = $this->data['width'];

        $this->data['width'] = $size['width'];
        $this->data['height'] = ($size['width'] * $ratio_visualconsole);

        $ratio = $ratio_w;
        if ($mode === 'mobile') {
            if ((bool) $config['mobile_view_orientation_vc'] === true) {
                if ($this->data['height'] < $this->data['width']) {
                    if ($this->data['height'] > $size['height']) {
                        $ratio = $ratio_h;
                        $this->data['height'] = $size['height'];
                        $this->data['width'] = ($size['height'] / $ratio_visualconsole);
                    }
                } else {
                    $ratio = $ratio_w;
                    $height = (($acum_height * ($size['width'])) / $acum_width);
                    $this->data['height'] = $height;
                    $this->data['width'] = ($height / $ratio_visualconsole);
                }
            } else {
                if ($this->data['height'] > $this->data['width']) {
                    $ratio = $ratio_h;
                    $this->data['height'] = $size['height'];
                    $this->data['width'] = ($size['height'] / $ratio_visualconsole);
                }
            }
        } else {
            if ($this->data['height'] > $size['height']) {
                $ratio = $ratio_h;
                $this->data['height'] = $size['height'];
                $this->data['width'] = ($size['height'] / $ratio_visualconsole);
            }
        }

        return $ratio;
    }


    /**
     * Calculate ratio
     *
     * @return float Ratio.
     */
    public function getRatio()
    {
        if (isset($this->data['width']) === false
            || empty($this->data['width']) === true
        ) {
            return null;
        }

        return ($this->data['height'] / $this->data['width']);
    }


    /*
     * -------------
     * - UTILITIES -
     * -------------
     */


    /**
     * From a unknown value, it will try to extract a valid boolean value.
     *
     * @param mixed $value Unknown input.
     *
     * @return boolean Valid boolean value.
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
     * Return a not empty string or a default value from a unknown value.
     *
     * @param mixed $val Input value.
     * @param mixed $def Default value.
     *
     * @return mixed A valid string (not empty) extracted from the input
     * or the default value.
     */
    protected static function notEmptyStringOr($val, $def)
    {
        return (\is_string($val) === true && strlen($val) > 0) ? $val : $def;
    }


    /**
     * Return a valid integer or a default value from a unknown value.
     *
     * @param mixed $val Input value.
     * @param mixed $def Default value.
     *
     * @return mixed A valid int extracted from the input or the default value.
     */
    protected static function parseIntOr($val, $def)
    {
        return (is_numeric($val) === true) ? (int) $val : $def;
    }


    /**
     * Return a valid float or a default value from a unknown value.
     *
     * @param mixed $val Input value.
     * @param mixed $def Default value.
     *
     * @return mixed A valid float extracted from the input or the
     * default value.
     */
    protected static function parseFloatOr($val, $def)
    {
        return (is_numeric($val) === true) ? (float) $val : $def;
    }


    /**
     * Get a value from a dictionary from a possible pool of keys.
     *
     * @param array $dict Input array.
     * @param array $keys Possible keys.
     *
     * @return mixed The first value found with the pool of keys or null.
     */
    protected static function issetInArray(array $dict, array $keys)
    {
        foreach ($keys as $key => $value) {
            if (isset($dict[$value]) === true) {
                return $dict[$value];
            }
        }

        return null;
    }


}
