<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\Model;

/**
 * Model of a line item of the Visual Console.
 */
final class Line extends Model
{


    /**
     * Validate the received data structure to ensure if we can extract the
     * values required to build the model.
     *
     * @param array $data Input data.
     *
     * @return void
     * @throws \InvalidArgumentException If any input value is considered
     * invalid.
     *
     * @overrides Model->validateData.
     */
    protected function validateData(array $data): void
    {
        if (isset($data['id']) === false
            || \is_numeric($data['id']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Id property is required and should be integer'
            );
        }

        if (isset($data['type']) === false
            || \is_numeric($data['type']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Id property is required and should be integer'
            );
        }
    }


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @overrides Model->decode.
     */
    protected function decode(array $data): array
    {
        return [
            'id'          => (int) $data['id'],
            'type'        => LINE_ITEM,
            'startX'      => static::extractStartX($data),
            'startY'      => static::extractStartY($data),
            'endX'        => static::extractEndX($data),
            'endY'        => static::extractEndY($data),
            'isOnTop'     => static::extractIsOnTop($data),
            'borderWidth' => static::extractBorderWidth($data),
            'borderColor' => static::extractBorderColor($data),
        ];
    }


    /**
     * Extract a x axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid x axis of the start position of the line.
     */
    private static function extractStartX(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['startX', 'pos_x']),
            0
        );
    }


    /**
     * Extract a y axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid y axis of the start position of the line.
     */
    private static function extractStartY(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['startY', 'pos_y']),
            0
        );
    }


    /**
     * Extract a x axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid x axis of the end position of the line.
     */
    private static function extractEndX(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['endX', 'width']),
            0
        );
    }


    /**
     * Extract a y axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid y axis of the end position of the line.
     */
    private static function extractEndY(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['endY', 'height']),
            0
        );
    }


    /**
     * Extract a conditional value which tells if the item has visual priority.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item is on top or not.
     */
    private static function extractIsOnTop(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['isOnTop', 'show_on_top'])
        );
    }


    /**
     * Extract a border width value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid border width. 0 by default and minimum value.
     */
    private static function extractBorderWidth(array $data): int
    {
        $borderWidth = static::parseIntOr(
            static::issetInArray($data, ['borderWidth', 'border_width']),
            0
        );

        return ($borderWidth >= 0) ? $borderWidth : 0;
    }


    /**
     * Extract a border color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the border color (not empty) or null.
     */
    private static function extractBorderColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['borderColor', 'border_color']),
            null
        );
    }


    /**
     * Obtain a vc item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console line data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override Model::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(array $filter): array
    {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout_data', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        return $row;
    }


}
