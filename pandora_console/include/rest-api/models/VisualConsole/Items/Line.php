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
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout_data', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        if ($ratio != 0) {
            $row['width'] = ($row['width'] * $ratio);
            $row['height'] = ($row['height'] * $ratio);
            $row['pos_x'] = ($row['pos_x'] * $ratio);
            $row['pos_y'] = ($row['pos_y'] * $ratio);
        }

        if ($widthRatio != 0) {
            $row['width'] = ($row['width'] * $widthRatio);
            $row['pos_x'] = ($row['pos_x'] * $widthRatio);
        }

        return $row;
    }


    /**
     * Return a valid representation of a record in database.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing a record in database.
     *
     * @overrides Model::encode.
     */
    protected static function encode(array $data): array
    {
        $result = [];
        $result['type'] = LINE_ITEM;

        $id = static::getId($data);
        if ($id) {
            $result['id'] = $id;
        }

        $layoutId = static::getIdLayout($data);
        if ($layoutId > 0) {
            $result['id_layout'] = $layoutId;
        }

        $startX = static::parseIntOr(
            static::issetInArray($data, ['pos_x', 'startX']),
            null
        );
        if ($startX !== null) {
            $result['pos_x'] = $startX;
        }

        $startY = static::parseIntOr(
            static::issetInArray($data, ['pos_y', 'startY']),
            null
        );
        if ($startY !== null) {
            $result['pos_y'] = $startY;
        }

        $endX = static::parseIntOr(
            static::issetInArray($data, ['width', 'endX']),
            null
        );
        if ($endX !== null) {
            $result['width'] = $endX;
        }

        $endY = static::parseIntOr(
            static::issetInArray($data, ['height', 'endY']),
            null
        );
        if ($endY !== null) {
            $result['height'] = $endY;
        }

        $borderWidth = static::getBorderWidth($data);
        if ($borderWidth !== null) {
            if ($borderWidth < 1) {
                $borderWidth = 1;
            }

            $result['border_width'] = $borderWidth;
        }

        $borderColor = static::extractBorderColor($data);
        if ($borderColor !== null) {
            $result['border_color'] = $borderColor;
        }

        $showOnTop = static::issetInArray(
            $data,
            [
                'isOnTop',
                'show_on_top',
                'showOnTop',
            ]
        );
        if ($showOnTop !== null) {
            $result['show_on_top'] = static::parseBool($showOnTop);
        }

        return $result;
    }


    /**
     * Extract item id.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Item id. 0 by default.
     */
    private static function getId(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['id', 'itemId']),
            0
        );
    }


    /**
     * Extract layout id.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Item id. 0 by default.
     */
    private static function getIdLayout(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['id_layout', 'idLayout', 'layoutId']),
            0
        );
    }


    /**
     * Extract item width.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Item width. 0 by default.
     */
    private static function getWidth(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['width', 'endX']),
            null
        );
    }


    /**
     * Extract item height.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Item height. 0 by default.
     */
    private static function getHeight(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['height', 'endY']),
            null
        );
    }


    /**
     * Extract a border width value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid border width.
     */
    private static function getBorderWidth(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['border_width', 'borderWidth']),
            null
        );
    }


    /**
     * Insert or update an item in the database
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer The modeled element data structure stored into the DB.
     *
     * @overrides Model::save.
     */
    public function save(array $data=[]): int
    {
        if (empty($data) === false) {
            if (empty($data['id']) === true) {
                // Insert.
                $save = static::encode($data);
                $result = \db_process_sql_insert('tlayout_data', $save);
                if ($result !== false) {
                    $item = static::fromDB(['id' => $result]);
                    $item->setData($item->toArray());
                }
            } else {
                // Update.
                $dataModelEncode = static::encode($this->toArray());
                $dataEncode = static::encode($data);

                $save = array_merge($dataModelEncode, $dataEncode);

                $result = \db_process_sql_update(
                    'tlayout_data',
                    $save,
                    ['id' => $save['id']]
                );
                // Invalidate the item's cache.
                if ($result !== false && $result > 0) {
                    $item = static::fromDB(['id' => $save['id']]);
                    // Update the model.
                    if (empty($item) === false) {
                        $this->setData($item->toArray());
                    }
                }
            }
        }

        return $result;
    }


    /**
     * Delete a line in the database
     *
     * @param integer $itemId Identifier of the Item.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @overrides Model::delete.
     */
    public function delete(int $itemId): bool
    {
        $result = db_process_sql_delete(
            'tlayout_data',
            ['id' => $itemId]
        );

        return (bool) $result;
    }


    /**
     * Generates inputs for form (global, common).
     *
     * @param array $values Default values.
     *
     * @return array Of inputs.
     */
    public static function getFormInputs(array $values): array
    {
        $inputs = [];

        if ($values['tabSelected'] === 'specific') {
            // Width.
            if ($values['borderWidth'] < 1) {
                $values['borderWidth'] = 1;
            }

            $inputs[] = [
                'label'     => __('Width'),
                'arguments' => [
                    'name'   => 'borderWidth',
                    'type'   => 'number',
                    'value'  => $values['borderWidth'],
                    'return' => true,
                    'min'    => 1,
                ],
            ];

            // Color.
            $inputs[] = [
                'label'     => __('Color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'borderColor',
                    'type'    => 'color',
                    'value'   => $values['borderColor'],
                    'return'  => true,
                ],
            ];

            // Show on top.
            $inputs[] = [
                'label'     => __('Show on top'),
                'arguments' => [
                    'name'  => 'isOnTop',
                    'id'    => 'isOnTop',
                    'type'  => 'switch',
                    'value' => $values['isOnTop'],
                ],
            ];
        }

        return $inputs;
    }


}
