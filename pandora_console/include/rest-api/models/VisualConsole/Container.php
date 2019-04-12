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
            'backgroundImage' => $this->extractBackgroundImage($data),
            'backgroundColor' => $this->extractBackgroundColor($data),
            'isFavorite'      => $this->extractFavorite($data),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
            'backgroundURL'   => $this->extractBackgroundUrl($data),
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
     * Extract a image name value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image name (not empty) or null.
     */
    private function extractBackgroundImage(array $data)
    {
        $backgroundImage = static::notEmptyStringOr(
            static::issetInArray($data, ['background', 'backgroundURL']),
            null
        );

        return ($backgroundImage === 'None.png') ? null : $backgroundImage;
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
            static::issetInArray($data, ['backgroundURL']),
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

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_ui.php';

        $backgroundUrl = static::extractBackgroundUrl($row);
        $backgroundImage = static::extractBackgroundImage($row);

        if ($backgroundUrl === null && $backgroundImage !== null) {
            $backgroundUrl = ui_get_full_url(
                'images/console/background/'.$backgroundImage
            );

            $width = (int) $row['width'];
            $height = (int) $row['height'];

            if ($width > 0 && $height > 0) {
                $q = [
                    'getFile'    => 1,
                    'thumb'      => 1,
                    'thumb_size' => $width.'x'.$height,
                    'file'       => $backgroundUrl,
                ];
                $row['backgroundURL'] = ui_get_full_url(
                    'include/Image/image_functions.php?'.http_build_query($q)
                );
            } else {
                $row['backgroundURL'] = $backgroundUrl;
            }
        }

        return $row;
    }


    /**
     * Obtain a item's class.
     *
     * @param integer $type Type of the item of the Visual Console.
     *
     * @return mixed A reference to the item's class.
     */
    public static function getItemClass(int $type)
    {
        switch ($type) {
            case STATIC_GRAPH:
            return Items\StaticGraph::class;

            case MODULE_GRAPH:
            return Items\ModuleGraph::class;

            case SIMPLE_VALUE:
            case SIMPLE_VALUE_MAX:
            case SIMPLE_VALUE_MIN:
            case SIMPLE_VALUE_AVG:
            return Items\SimpleValue::class;

            case PERCENTILE_BAR:
            case PERCENTILE_BUBBLE:
            case CIRCULAR_PROGRESS_BAR:
            case CIRCULAR_INTERIOR_PROGRESS_BAR:
            return Items\Percentile::class;

            case LABEL:
            return Items\Label::class;

            case ICON:
            return Items\Icon::class;

            // Enterprise item. It may not exist.
            case SERVICE:
            return \class_exists('\Enterprise\Models\VisualConsole\Items\Service') ? \Enterprise\Models\VisualConsole\Items\Service::class : Item::class;

            case GROUP_ITEM:
            return Items\Group::class;

            case BOX_ITEM:
            return Items\Box::class;

            case LINE_ITEM:
            return Items\Line::class;

            case AUTO_SLA_GRAPH:
            return Items\EventsHistory::class;

            case DONUT_GRAPH:
            return Items\DonutGraph::class;

            case BARS_GRAPH:
            return Items\BarsGraph::class;

            case CLOCK:
            return Items\Clock::class;

            case COLOR_CLOUD:
            return Items\ColorCloud::class;

            default:
            return Item::class;
        }
    }


    /**
     * Obtain a list of items which belong to the Visual Console.
     *
     * @param integer $layoutId Identifier of the Visual Console.
     *
     * @return array A list of items.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    public static function getItemsFromDB(int $layoutId): array
    {
        $filter = ['id_layout' => $layoutId];
        $fields = [
            'id',
            'type',
        ];
        $rows = \db_get_all_rows_filter('tlayout_data', $filter, $fields);

        if ($rows === false) {
            $rows = [];
            // TODO: throw new \Exception('error fetching the data from the DB');.
        }

        $items = [];

        foreach ($rows as $data) {
            $itemId = (int) $data['id'];
            $itemType = (int) $data['type'];
            $class = static::getItemClass($itemType);

            try {
                \array_push($items, $class::fromDBWithId($itemId));
            } catch (\Throwable $e) {
                // TODO: Log this?
            }
        }

        return $items;
    }


}
