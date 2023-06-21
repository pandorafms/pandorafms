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

        static::extractGroupId($data);
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
            'id'                => (int) $data['id'],
            'name'              => $data['name'],
            'groupId'           => static::extractGroupId($data),
            'backgroundImage'   => static::extractBackgroundImage($data),
            'backgroundColor'   => static::extractBackgroundColor($data),
            'isFavorite'        => static::extractFavorite($data),
            'autoAdjust'        => static::extractAutoAdjust($data),
            'width'             => (int) $data['width'],
            'height'            => (int) $data['height'],
            'backgroundURL'     => static::extractBackgroundUrl($data),
            'relationLineWidth' => (int) $data['relationLineWidth'],
            'hash'              => static::extractHash($data),
            'maintenanceMode'   => static::extractMaintenanceMode($data),
        ];
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
        return $result;
    }


    /**
     * Insert or update an item in the database
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @overrides Model::save.
     */
    public function save(array $data=[]): bool
    {
        return true;
    }


    /**
     * Delete an item in the database
     *
     * @param integer $itemId Identifier of the Item.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @overrides Model::delete.
     */
    public function delete(int $itemId): bool
    {
        return true;
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
    private static function extractGroupId(array $data): int
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
    private static function extractBackgroundImage(array $data)
    {
        $backgroundImage = static::notEmptyStringOr(
            static::issetInArray($data, ['background', 'backgroundURL']),
            null
        );

        return ($backgroundImage === 'None.png' || $backgroundImage === null) ? null : str_replace(' ', '%20', $backgroundImage);
    }


    /**
     * Extract a image url value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     */
    private static function extractBackgroundUrl(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['backgroundURL']),
            null
        );
    }


    /**
     * Extract a hash.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing hash (not empty) or null.
     */
    private static function extractHash(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['hash']),
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
    private static function extractBackgroundColor(array $data)
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
     * Extract a background color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the color (not empty) or null.
     */
    private static function extractMaintenanceMode(array $data)
    {
        global $config;
        $maintenance_mode = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'maintenanceMode',
                    'maintenance_mode',
                ]
            ),
            null
        );

        $result = null;
        if ($maintenance_mode !== null) {
            $result = json_decode($maintenance_mode, true);

            $result['date'] = date(
                $config['date_format'],
                $result['timestamp']
            );

            $result['timestamp'] = human_time_description_raw(
                (time() - $result['timestamp'])
            );
        }

        return $result;
    }


    /**
     * Extract the "is favorite" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item is favorite or not.
     */
    private static function extractFavorite(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['is_favourite', 'isFavorite'])
        );
    }


    /**
     * Extract the "auto adjust" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item is favorite or not.
     */
    private static function extractAutoAdjust(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['auto_adjust', 'autoAdjust'])
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
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ) {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_io.php';
        include_once $config['homedir'].'/include/functions_ui.php';

        // Clean HTML entities.
        $row = \io_safe_output($row);

        $row['relationLineWidth'] = (int) $config['vc_line_thickness'];

        $backgroundUrl = static::extractBackgroundUrl($row);
        $backgroundImage = static::extractBackgroundImage($row);

        if ($backgroundUrl === null && $backgroundImage !== null) {
            $row['backgroundURL'] = \ui_get_full_url(
                'images/console/background/'.$backgroundImage,
                false,
                false,
                false
            );
        }

        $row['hash'] = md5(
            $config['dbpass'].$row['id'].$config['id_user']
        );

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

            case NETWORK_LINK:
            return Items\NetworkLink::class;

            case ODOMETER:
            return Items\Odometer::class;

            case BASIC_CHART:
            return Items\BasicChart::class;

            default:
            return Item::class;
        }
    }


    /**
     * Obtain a list of items which belong to the Visual Console.
     *
     * @param integer $layoutId     Identifier of the Visual Console.
     * @param array   $groupsFilter Groups can access user.
     *
     * @return array A list of items.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    public static function getItemsFromDB(
        int $layoutId,
        array $groupsFilter=[],
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Default filter.
        $filter = ['id_layout' => $layoutId];
        $fields = [
            'DISTINCT(id) AS id',
            'type',
            'cache_expiration',
            'id_layout',
        ];

        // Override the filter if the groups filter is not empty.
        if (count($groupsFilter) > 0) {
            // Filter group for elements groups.
            $filter = [];
            $filter[] = \db_format_array_where_clause_sql(
                [
                    'id_layout'     => $layoutId,
                    'element_group' => $groupsFilter,
                ]
            );

            // Filter groups for type groups.
            // Only true condition if type is GROUP_ITEM.
            $filter[] = '('.\db_format_array_where_clause_sql(
                [
                    'id_layout' => $layoutId,
                    'type'      => GROUP_ITEM,
                    'id_group'  => $groupsFilter,
                ]
            ).')';
        }

        $rows = \db_get_all_rows_filter(
            'tlayout_data',
            $filter,
            $fields,
            'OR'
        );

        if ($rows === false) {
            $rows = [];
        }

        $items = [];

        foreach ($rows as $data) {
            $itemId = (int) $data['id'];
            $class = static::getItemClass((int) $data['type']);

            try {
                array_push($items, $class::fromDB($data, $ratio, $widthRatio));
            } catch (\Throwable $e) {
                error_log('VC[Container]: '.$e->getMessage());
            }
        }

        return $items;
    }


    /**
     * Obtain an item which belong to the Visual Console.
     *
     * @param integer $itemId Identifier of the Item.
     *
     * @return Model Item or Line.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    public static function getItemFromDB(int $itemId): Model
    {
        // Default filter.
        $filter = ['id' => $itemId];
        $fields = [
            'DISTINCT(id) AS id',
            'type',
            'cache_expiration',
            'id_layout',
        ];

        $row = \db_get_row_filter(
            'tlayout_data',
            $filter,
            $fields,
            'OR'
        );

        if ($row === false) {
            return '';
        }

        $class = static::getItemClass((int) $row['type']);

        try {
            $item = $class::fromDB($row);
        } catch (\Throwable $e) {
            // TODO: Log this?
            error_log(obhd($e));
        }

        return $item;
    }


}
