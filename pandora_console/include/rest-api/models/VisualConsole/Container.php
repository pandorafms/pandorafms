<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\Model;

/**
 * Model of a Visual Console.
 */
final class Container extends Model
{


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
     * @overrides Model::validateData.
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

        if (isset($data['name']) === false
            || \is_string($data['name']) === false
            || empty($data['name']) === true
        ) {
            throw new \InvalidArgumentException(
                'the name property is required and should be string'
            );
        }

        if (isset($data['width']) === false
            || \is_numeric($data['width']) === false
            || $data['width'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'the width property is required and should greater than 0'
            );
        }

        if (isset($data['height']) === false
            || \is_numeric($data['height']) === false
            || $data['height'] <= 0
        ) {
            throw new \InvalidArgumentException(
                'the height property is required and should greater than 0'
            );
        }

        $this->extractGroupId($data);
    }


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @overrides Model::decode.
     */
    protected function decode(array $data): array
    {
        return [
            'id'              => (int) $data['id'],
            'name'            => $data['name'],
            'groupId'         => $this->extractGroupId($data),
            'backgroundURL'   => $this->extractBackgroundUrl($data),
            'backgroundColor' => $this->extractBackgroundColor($data),
            'isFavorite'      => $this->extractFavorite($data),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
        ];
    }


    /**
     * Extract a group Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a group.
     *
     * @throws \InvalidArgumentException When a valid group Id can't be found.
     */
    private function extractGroupId(array $data): int
    {
        $groupId = static::parseIntOr(
            static::issetInArray($data, ['id_group', 'groupId']),
            null
        );

        if ($groupId === null || $groupId < 0) {
            throw new \InvalidArgumentException(
                'the group Id property is required and should be integer'
            );
        }

        return $groupId;
    }


    /**
     * Extract a image url value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     */
    private function extractBackgroundUrl(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['background', 'backgroundURL']),
            null
        );
    }


    /**
     * Extract a background color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the color (not empty) or null.
     */
    private function extractBackgroundColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'backgroundColor',
                    'background_color',
                ]
            ),
            null
        );
    }


    /**
     * Extract the "is favorite" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item is favorite or not.
     */
    private function extractFavorite(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['is_favourite', 'isFavorite'])
        );
    }


    /**
     * Obtain a container data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console.
     *
     * @return self A Visual Console Container instance.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override Model::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(array $filter)
    {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        return $row;
    }


    /**
     * Obtain a container data structure from the database using layout id
     * and returns a valid representation of the model
     *
     * @param integer $id_layout
     *
     * @return array
     */
    public static function getItemsFromDB(int $id_layout): string
    {
        $layout_items = db_get_all_rows_filter('tlayout_data', ['id_layout' => $id_layout]);
        if (!empty($layout_items) === true) {
            $array_items = [];
            foreach ($layout_items as $key => $value) {
                switch ($value['type']) {
                    case STATIC_GRAPH:
                        // code...
                    break;

                    case MODULE_GRAPH:
                        // code...
                    break;

                    case SIMPLE_VALUE:
                    case SIMPLE_VALUE_MAX:
                    case SIMPLE_VALUE_MIN:
                    case SIMPLE_VALUE_AVG:
                        array_push(
                            $array_items,
                            (string) Items\SimpleValue::fromArray($value)
                        );
                    break;

                    case PERCENTILE_BAR:
                        // code...
                    break;

                    case LABEL:
                        array_push(
                            $array_items,
                            (string) Items\Label::fromArray($value)
                        );
                    break;

                    case ICON:
                        array_push(
                            $array_items,
                            (string) Items\Icon::fromArray($value)
                        );
                    break;

                    case PERCENTILE_BUBBLE:
                        // code...
                    break;

                    case SERVICE:
                        // code...
                    break;

                    case GROUP_ITEM:
                        array_push(
                            $array_items,
                            (string) Items\Group::fromArray($value)
                        );
                    break;

                    case BOX_ITEM:
                        array_push(
                            $array_items,
                            (string) Items\Box::fromArray($value)
                        );
                    break;

                    case LINE_ITEM:
                        array_push(
                            $array_items,
                            (string) Items\Line::fromArray($value)
                        );
                    break;

                    case AUTO_SLA_GRAPH:
                        array_push(
                            $array_items,
                            (string) Items\EventsHistory::fromArray($value)
                        );
                    break;

                    case CIRCULAR_PROGRESS_BAR:
                        // code...
                    break;

                    case CIRCULAR_INTERIOR_PROGRESS_BAR:
                        // code...
                    break;

                    case DONUT_GRAPH:
                        // code...
                    break;

                    case BARS_GRAPH:
                        // code...
                    break;

                    case CLOCK:
                        array_push(
                            $array_items,
                            (string) Items\Clock::fromArray($value)
                        );
                    break;

                    case COLOR_CLOUD:
                        array_push(
                            $array_items,
                            (string) Items\ColorCloud::fromArray($value)
                        );
                    break;
                }
            }
        }

        return json_encode($array_items);
    }


}
