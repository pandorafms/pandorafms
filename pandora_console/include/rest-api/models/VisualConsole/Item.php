<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\VisualConsole\Container as VC;
use Models\CachedModel;

/**
 * Model of a generic Visual Console Item.
 */
class Item extends CachedModel
{

    /**
     * Used to decide wether to use information about the linked agent or not.
     *
     * @var boolean
     */
    protected static $useLinkedAgent = false;

    /**
     * Used to decide wether to use information about the linked module or not.
     *
     * @var boolean
     */
    protected static $useLinkedModule = false;

    /**
     * Used to decide wether to use information about
     * the linked visual console or not.
     *
     * @var boolean
     */
    protected static $useLinkedVisualConsole = false;

    /**
     * Used to decide wether to validate, extract and encode HTML output or not.
     *
     * @var boolean
     */
    protected static $useHtmlOutput = false;

    /**
     * Enable the cache index by user id.
     *
     * @var boolean
     */
    protected static $indexCacheByUser = true;


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

        if (isset($data['type']) === false
            || \is_numeric($data['type']) === false
        ) {
            throw new \InvalidArgumentException(
                'the Type property is required and should be integer'
            );
        }

        if (isset($data['width']) === false
            || \is_numeric($data['width']) === false
            || $data['width'] < 0
        ) {
            throw new \InvalidArgumentException(
                'the width property is required and should be equal or greater than 0'
            );
        }

        if (isset($data['height']) === false
            || \is_numeric($data['height']) === false
            || $data['height'] < 0
        ) {
            throw new \InvalidArgumentException(
                'the height property is required and should be equal or greater than 0'
            );
        }

        // The item has a linked Visual Console.
        if (static::$useLinkedVisualConsole === true) {
            $linkedLayoutStatusType = static::notEmptyStringOr(
                static::issetInArray(
                    $data,
                    [
                        'linkedLayoutStatusType',
                        'linked_layout_status_type',
                    ]
                ),
                null
            );

            // The types weight and service require extra data
            // which should be validated.
            if ($linkedLayoutStatusType === 'weight') {
                $weight = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeWeight',
                            'id_layout_linked_weight',
                        ]
                    ),
                    null
                );
                if ($weight === null || $weight < 0) {
                    throw new \InvalidArgumentException(
                        'the linked layout status weight property is required and should be greater than 0'
                    );
                }
            } else if ($linkedLayoutStatusType === 'service') {
                $wThreshold = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeWarningThreshold',
                            'linked_layout_status_as_service_warning',
                        ]
                    ),
                    null
                );
                if ($wThreshold === null || $wThreshold < 0) {
                    throw new \InvalidArgumentException(
                        'the linked layout status warning threshold property is required and should be greater than 0'
                    );
                }

                $cThreshold = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeCriticalThreshold',
                            'linked_layout_status_as_service_critical',
                        ]
                    ),
                    null
                );
                if ($cThreshold === null || $cThreshold < 0) {
                    throw new \InvalidArgumentException(
                        'the linked layout status critical threshold property is required and should be greater than 0'
                    );
                }
            }
        }

        // The item uses HTML output.
        if (static::$useHtmlOutput === true) {
            if (static::notEmptyStringOr(
                static::issetInArray($data, ['encodedHtml']),
                null
            ) === null
                && static::notEmptyStringOr(
                    static::issetInArray($data, ['html']),
                    null
                ) === null
            ) {
                throw new \InvalidArgumentException(
                    'the html property is required and should be a not empty string'
                );
            }
        }
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
        global $config;

        $decodedData = [
            'id'              => (int) $data['id'],
            'colorStatus'     => (string) COL_UNKNOWN,
            'type'            => (int) $data['type'],
            'label'           => static::extractLabel($data),
            'labelPosition'   => static::extractLabelPosition($data),
            'isLinkEnabled'   => static::extractIsLinkEnabled($data),
            'isOnTop'         => static::extractIsOnTop($data),
            'parentId'        => static::extractParentId($data),
            'aclGroupId'      => static::extractAclGroupId($data),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
            'x'               => static::extractX($data),
            'y'               => static::extractY($data),
            'cacheExpiration' => static::extractCacheExpiration($data),
        ];

        if ((bool) $config['display_item_frame'] === true) {
            $decodedData['alertOutline'] = static::checkLayoutAlertsRecursive($data);
        }

        if (static::$useLinkedModule === true) {
            $decodedData = array_merge(
                $decodedData,
                static::extractLinkedModule($data)
            );
        } else if (static::$useLinkedAgent === true) {
            $decodedData = array_merge(
                $decodedData,
                static::extractLinkedAgent($data)
            );
        }

        if (static::$useLinkedVisualConsole === true) {
            $decodedData = array_merge(
                $decodedData,
                static::extractLinkedVisualConsole($data)
            );
        }

        if (static::$useHtmlOutput === true) {
            $decodedData['encodedHtml'] = static::extractEncodedHtml($data);
        }

        // Conditionally add the item link.
        if ($decodedData['isLinkEnabled'] === true) {
            $decodedData['link'] = static::notEmptyStringOr(
                static::issetInArray($data, ['link']),
                null
            );
        }

        $decodedData['agentDisabled'] = static::parseBool(
            ($data['agentDisabled'] ?? false)
        );
        $decodedData['moduleDisabled'] = static::parseBool(
            ($data['moduleDisabled'] ?? false)
        );

        return $decodedData;
    }


    /**
     * Extract x y axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid x axis position of the item.
     */
    private static function extractX(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['x', 'pos_x', 'posX', 'startX']),
            0
        );
    }


    /**
     * Extract a y axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid y axis position of the item.
     */
    private static function extractY(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['y', 'pos_y', 'posY', 'startY']),
            0
        );
    }


    /**
     * Extract a group Id (for ACL) value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a group.
     */
    private static function extractAclGroupId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'element_group',
                    'aclGroupId',
                    'elementGroup',
                ]
            ),
            null
        );
    }


    /**
     * Extract a parent Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of the item's parent.
     */
    private static function extractParentId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'parentId',
                    'parent_item',
                    'parentItem',
                ]
            ),
            null
        );
    }


    /**
     * Extract the "is on top" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item is on top or not.
     */
    private static function extractIsOnTop(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['isOnTop', 'show_on_top', 'showOnTop'])
        );
    }


    /**
     * Extract the "is link enabled" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the item has the link enabled or not.
     */
    private static function extractIsLinkEnabled(array $data): bool
    {
        return static::parseBool(
            static::issetInArray(
                $data,
                [
                    'isLinkEnabled',
                    'enable_link',
                    'enableLink',
                ]
            )
        );
    }


    /**
     * Extract a label value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the label (not empty) or null.
     */
    private static function extractLabel(array $data)
    {
        return static::issetInArray($data, ['label']);
    }


    /**
     * Extract a label position value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed One string of up|right|left|down. down by default.
     */
    private static function extractLabelPosition(array $data): string
    {
        $labelPosition = static::notEmptyStringOr(
            static::issetInArray($data, ['labelPosition', 'label_position']),
            null
        );

        switch ($labelPosition) {
            case 'up':
            case 'right':
            case 'left':
            return $labelPosition;

            default:
            return 'down';
        }
    }


    /**
     * Extract an agent Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of an agent.
     */
    private static function extractAgentId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'agentId',
                    'id_agent',
                    'id_agente',
                    'idAgent',
                    'idAgente',
                ]
            ),
            null
        );
    }


    /**
     * Extract the cache expiration value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Cache expiration time.
     */
    private static function extractCacheExpiration(array $data)
    {
        return static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'cacheExpiration',
                    'cache_expiration',
                ]
            ),
            null
        );
    }


    /**
     * Extract an module Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a module.
     */
    private static function extractModuleId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'moduleId',
                    'id_agente_modulo',
                    'id_modulo',
                    'idModulo',
                    'idAgenteModulo',
                    'idAgentModule',
                ]
            ),
            null
        );
    }


    /**
     * Extract an metaconsole node Id value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a metaconsole node.
     */
    private static function extractMetaconsoleId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['metaconsoleId', 'id_metaconsole']),
            null
        );
    }


    /**
     * Extract the values of a linked agent.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array Data structure of the linked agent info.
     *
     * @example [
     *   'metaconsoleId' => 1,
     *   'agentId'       => 2,
     *   'agentName'     => 'Foo',
     * ]
     * @example [
     *   'agentId'     => 20,
     *   'agentName'   => 'Bar',
     * ]
     * @example [
     *   'agentId'     => null,
     *   'agentName'   => null,
     * ]
     */
    protected static function extractLinkedAgent(array $data): array
    {
        $agentData = [];

        // We should add the metaconsole Id if we can.
        $metaconsoleId = static::extractMetaconsoleId($data);
        if ($metaconsoleId !== null && $metaconsoleId <= 0) {
            $agentData['metaconsoleId'] = null;
        } else {
            $agentData['metaconsoleId'] = $metaconsoleId;
        }

        // The agent Id should be a valid int or a null value.
        $agentData['agentId'] = static::extractAgentId($data);

        // The agent name should be a valid string or a null value.
        $agentData['agentName'] = static::notEmptyStringOr(
            static::issetInArray($data, ['agentName', 'agent_name']),
            null
        );

        // The agent alias should be a valid string or a null value.
        $agentData['agentAlias'] = static::notEmptyStringOr(
            static::issetInArray($data, ['agentAlias', 'agent_alias']),
            null
        );

        // The agent description should be a valid string or a null value.
        $agentData['agentDescription'] = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'agentDescription',
                    'agent_description',
                ]
            ),
            null
        );

        // The agent address should be a valid string or a null value.
        $agentData['agentAddress'] = static::notEmptyStringOr(
            static::issetInArray($data, ['agentAddress', 'agent_address']),
            null
        );

        return $agentData;
    }


    /**
     * Extract the values of a linked module.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array Data structure of the linked module info.
     *
     * @example [
     *   'metaconsoleId' => 1,
     *   'agentId'       => 2,
     *   'agentName'     => 'Foo',
     *   'moduleId'      => 1,
     *   'moduleName'    => 'cpu',
     * ]
     * @example [
     *   'agentId'    => 4,
     *   'agentName'  => 'Bar',
     *   'moduleId'   => null,
     *   'moduleName' => null,
     * ]
     * @example [
     *   'agentId'    => null,
     *   'agentName'  => null,
     *   'moduleId'   => null,
     *   'moduleName' => null,
     * ]
     */
    protected static function extractLinkedModule(array $data): array
    {
        // Initialize the data with the agent data and then expand it.
        $moduleData = static::extractLinkedAgent($data);

        // The module Id should be a valid int or a null value.
        $moduleData['moduleId'] = static::extractModuleId($data);

        // The module name should be a valid string or a null value.
        $moduleData['moduleName'] = static::notEmptyStringOr(
            static::issetInArray($data, ['moduleName', 'module_name']),
            null
        );

        // The module description should be a valid string or a null value.
        $moduleData['moduleDescription'] = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'moduleDescription',
                    'module_description',
                ]
            ),
            null
        );

        return $moduleData;
    }


    /**
     * Extract the values of a linked visual console.
     *
     * @param array $data Unknown input data structure.
     *
     * @return array Data structure of the linked visual console info.
     *
     * @example [
     *   'linkedLayoutId'         => 12,
     *   'linkedLayoutNodeId'     => 2,
     *   'linkedLayoutStatusType' => 'default',
     * ]
     * @example [
     *   'linkedLayoutId'               => 11,
     *   'linkedLayoutNodeId'           => null,
     *   'linkedLayoutStatusType'       => 'weight',
     *   'linkedLayoutStatusTypeWeight' => 80,
     * ]
     * @example [
     *   'linkedLayoutId'                          => 10,
     *   'linkedLayoutNodeId'                      => 2,
     *   'linkedLayoutStatusType'                  => 'service',
     *   'linkedLayoutStatusTypeWarningThreshold'  => 50,
     *   'linkedLayoutStatusTypeCriticalThreshold' => 80,
     * ]
     */
    private static function extractLinkedVisualConsole(array $data): array
    {
        $vcData = [];

        // The linked vc Id should be a valid int or a null value.
        $vcData['linkedLayoutId'] = static::parseIntOr(
            static::issetInArray($data, ['linkedLayoutId', 'id_layout_linked']),
            null
        );

        // The linked vc's remote node Id should be a valid int or a null value.
        $vcData['linkedLayoutNodeId'] = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutNodeId',
                    'linked_layout_node_id',
                ]
            ),
            null
        );

        // The linked vc status type should be a enum value.
        $linkedLayoutStatusType = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutStatusType',
                    'linked_layout_status_type',
                ]
            ),
            null
        );

        // Extract data for the calculation of the linked visual console status.
        switch ($linkedLayoutStatusType) {
            case 'default':
            default:
                $vcData['linkedLayoutStatusType'] = 'default';
            break;

            case 'weight':
                $vcData['linkedLayoutStatusType'] = 'weight';
                $vcData['linkedLayoutStatusTypeWeight'] = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeWeight',
                            'id_layout_linked_weight',
                        ]
                    ),
                    0
                );
            break;

            case 'service':
                $vcData['linkedLayoutStatusType'] = 'service';
                $vcData['linkedLayoutStatusTypeWarningThreshold'] = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeWarningThreshold',
                            'linked_layout_status_as_service_warning',
                        ]
                    ),
                    0
                );
                $vcData['linkedLayoutStatusTypeCriticalThreshold'] = static::parseIntOr(
                    static::issetInArray(
                        $data,
                        [
                            'linkedLayoutStatusTypeCriticalThreshold',
                            'linked_layout_status_as_service_critical',
                        ]
                    ),
                    0
                );
            break;
        }

        return $vcData;
    }


    /**
     * Extract a encoded HTML representation of the item.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string The HTML representation in base64 encoding.
     */
    private static function extractEncodedHtml(array $data): string
    {
        if (isset($data['encodedHtml']) === true) {
            return $data['encodedHtml'];
        } else if (isset($data['html']) === true) {
            return base64_encode($data['html']);
        }

        return '';
    }


    /**
     * Fetch a vc item data structure from the database using a filter.
     *
     * @param array      $filter     Filter of the Visual Console Item.
     * @param float      $ratio      Ratio resize view.
     * @param float|null $widthRatio Unknown.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override Model::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_io.php';

        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout_data', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        // Clean up to two levels of HTML entities.
        $row = \io_safe_output(\io_safe_output($row));

        /*
         * Retrieve extra data.
         */

        // The linked module includes the agent data.
        if (static::$useLinkedModule === true) {
            $row = array_merge($row, static::fetchModuleDataFromDB($row));
        } else if (static::$useLinkedAgent === true) {
            $row = array_merge($row, static::fetchAgentDataFromDB($row));
        }

        // Build the item link if needed.
        if (static::extractIsLinkEnabled($row) === true) {
            $row['link'] = static::buildLink($row);
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
     * Fetch a cache item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override CachedModel::fetchCachedData.
     */
    protected static function fetchCachedData(array $filter)
    {
        global $config;

        $filter = [
            'vc_id'      => (int) $filter['id_layout'],
            'vc_item_id' => (int) $filter['id'],
            '(UNIX_TIMESTAMP(`created_at`) + `expiration`) > UNIX_TIMESTAMP()'
        ];

        if (static::$indexCacheByUser === true) {
            $filter['user_id'] = $config['id_user'];
        }

        $data = \db_get_value_filter(
            'data',
            'tvisual_console_elements_cache',
            $filter
        );

        if ($data === false) {
            // Invalid entry, clean it.
            self::clearCachedData(
                [
                    'vc_id'      => $filter['vc_id'],
                    'vc_item_id' => $filter['vc_item_id'],
                    'user_id'    => $filter['user_id'],
                ]
            );
            return null;
        }

        return json_decode(base64_decode($data), true);
    }


    /**
     * Stores the data structure obtained.
     *
     * @param array $filter Filter to save the modeled element.
     * @param array $data   Modeled element to save.
     *
     * @return boolean The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override CachedModel::saveCachedData.
     */
    protected static function saveCachedData(array $filter, array $data): bool
    {
        global $config;
        if (static::$indexCacheByUser === true) {
            $filter['user_id'] = $config['id_user'];
        }

        return \db_process_sql_insert(
            'tvisual_console_elements_cache',
            [
                'vc_id'      => $data['id_layout'],
                'vc_item_id' => $data['id'],
                'user_id'    => $filter['user_id'],
                'data'       => base64_encode(json_encode($data)),
                'expiration' => $data['cache_expiration'],
            ]
        ) > 0;
    }


    /**
     * Deletes previous data that are not useful.
     *
     * @param array $filter Filter to retrieve the modeled element.
     *
     * @return array The modeled element data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override CachedModel::clearCachedData.
     */
    protected static function clearCachedData(array $filter): int
    {
        return \db_process_sql_delete(
            'tvisual_console_elements_cache',
            $filter
        );
    }


    /**
     * Fetch a data structure of an agent from the database using the
     * vs item's data.
     *
     * @param array $itemData Visual Console Item's data structure.
     *
     * @return array The agent data structure stored into the DB.
     *
     * @throws \InvalidArgumentException When the input agent Id is invalid.
     */
    protected static function fetchAgentDataFromDB(array $itemData): array
    {
        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_io.php';

        $agentData = [];

        // We should add the metaconsole Id if we can.
        $metaconsoleId = static::extractMetaconsoleId($itemData);

        // Can't fetch an agent with an invalid Id.
        $agentId = static::extractAgentId($itemData);
        if ($agentId === null) {
            $agentId = 0;
        }

        // Staticgraph don't need to have an agent.
        if ($agentId === 0) {
            return $agentData;
        }

        if (\is_metaconsole() === true && $metaconsoleId === null) {
            throw new \InvalidArgumentException('missing metaconsole node Id');
        }

        $agent = false;

        if (\is_metaconsole() === true) {
            $sql = sprintf(
                'SELECT nombre, alias, direccion, comentarios, `disabled`
                FROM tmetaconsole_agent
                WHERE id_tagente = %s and id_tmetaconsole_setup = %s',
                $agentId,
                $metaconsoleId
            );
        } else {
            $sql = sprintf(
                'SELECT nombre, alias, direccion, comentarios, `disabled`
                FROM tagente
                WHERE id_agente = %s',
                $agentId
            );
        }

        $agent = \db_get_row_sql($sql);

        if ($agent === false) {
            $agentData['agentDisabled'] = true;
            return $agentData;
        }

        // The agent name should be a valid string or a null value.
        $agentData['agentName'] = $agent['nombre'];
        $agentData['agentAlias'] = $agent['alias'];
        $agentData['agentDescription'] = $agent['comentarios'];
        $agentData['agentAddress'] = $agent['direccion'];
        $agentData['agentDisabled'] = $agent['disabled'];

        return \io_safe_output($agentData);
    }


    /**
     * Fetch a data structure of an module from the database using the
     * vs item's data.
     *
     * @param array $itemData Visual Console Item's data structure.
     *
     * @return array The module data structure stored into the DB.
     * @throws \InvalidArgumentException When the input module Id is invalid.
     */
    protected static function fetchModuleDataFromDB(array $itemData): array
    {
        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_io.php';

        // Load side libraries.
        if (\is_metaconsole() === true) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Initialize with the agent data.
        $moduleData = static::fetchAgentDataFromDB($itemData);

        // Can't fetch an module with a invalid Id.
        $moduleId = static::extractModuleId($itemData);
        if ($moduleId === null) {
            $moduleId = 0;
        }

        // Staticgraph don't need to have a module.
        if ($moduleId === 0) {
            return $moduleData;
        }

        // We should add the metaconsole Id if we can.
        $metaconsoleId = static::extractMetaconsoleId($itemData);

        if (\is_metaconsole() === true && $metaconsoleId === null) {
            throw new \InvalidArgumentException('missing metaconsole node Id');
        }

        $moduleName = false;

        // Connect to node.
        if (\is_metaconsole() === true
            && \metaconsole_connect(null, $metaconsoleId) !== NOERR
        ) {
            throw new \InvalidArgumentException(
                'error connecting to the node'
            );
        }

        $sql = sprintf(
            'SELECT nombre, descripcion, `disabled`
            FROM tagente_modulo
            WHERE id_agente_modulo = %s',
            $moduleId
        );

        $moduleName = \db_get_row_sql($sql);

        // Restore connection.
        if (\is_metaconsole() === true) {
            \metaconsole_restore_db();
        }

        if ($moduleName === false) {
            $agentData['moduleDisabled'] = true;
            return $moduleData;
        }

        $moduleData['moduleName'] = $moduleName['nombre'];
        $moduleData['moduleDescription'] = $moduleName['descripcion'];
        $moduleData['moduleDisabled'] = $moduleName['disabled'];

        return \io_safe_output($moduleData);
    }


    /**
     * Generate a link to something related with the item.
     *
     * @param array $data Visual Console Item's data structure.
     *
     * @return mixed The link or a null value.
     * @throws \Exception Not really. It's controlled.
     */
    protected static function buildLink(array $data)
    {
        global $config;

        $mobile_navigation = false;

        if (strstr(($_SERVER['PHP_SELF'] ?? ''), 'mobile/') !== false
            || strstr(($_SERVER['HTTP_REFERER'] ?? ''), 'mobile/') !== false
        ) {
            $mobile_navigation = true;
        }

        // Load side libraries.
        include_once $config['homedir'].'/include/functions_ui.php';
        if (\is_metaconsole() === true) {
            \enterprise_include_once('include/functions_metaconsole.php');
            \enterprise_include_once('meta/include/functions_ui_meta.php');
        }

        $linkedVisualConsole = static::extractLinkedVisualConsole($data);
        $linkedModule = static::extractLinkedModule($data);
        $linkedAgent = static::extractLinkedAgent($data);

        $baseUrl = \ui_get_full_url('index.php');
        $mobileUrl = \ui_get_full_url('mobile/index.php');

        if ((bool) ($data['agentDisabled'] ?? null) === true
            || (bool) ($data['moduleDisabled'] ?? null) === true
        ) {
            return null;
        }

        if (static::$useLinkedVisualConsole === true
            && $linkedVisualConsole['linkedLayoutId'] !== null
            && $linkedVisualConsole['linkedLayoutId'] > 0
        ) {
            // Linked Visual Console.
            $vcId = $linkedVisualConsole['linkedLayoutId'];
            // The layout can be from another node.
            $linkedLayoutNodeId = $linkedVisualConsole['linkedLayoutNodeId'];

            if (empty($linkedLayoutNodeId) === false && \is_metaconsole() === true) {
                $db_connector = metaconsole_get_connection_by_id($linkedLayoutNodeId);
                metaconsole_load_external_db($db_connector);
            }

            $visualConsole = VC::fromDB(['id' => $vcId]);

            if (empty($linkedLayoutNodeId) === false && \is_metaconsole() === true) {
                metaconsole_restore_db();
            }

            $visualConsoleData = $visualConsole->toArray();
            $vcGroupId = $visualConsoleData['groupId'];

            // Check ACL.
            $aclRead = \check_acl($config['id_user'], $vcGroupId, 'VR');
            // To build the link to another visual console
            // you must have read permissions of the visual console
            // with which it is linked.
            if ($aclRead === 0) {
                return null;
            }

            if (empty($linkedLayoutNodeId) === true
                && \is_metaconsole() === true
            ) {
                /*
                 * A Visual Console from this console.
                 * We are in a metaconsole.
                 */

                return $baseUrl.'?'.http_build_query(
                    [
                        'sec'    => 'screen',
                        'sec2'   => 'screens/screens',
                        'action' => 'visualmap',
                        'id'     => $vcId,
                        'pure'   => (int) (isset($config['pure']) === true) ? $config['pure'] : 0,
                    ]
                );
            } else if (empty($linkedLayoutNodeId) === true
                && \is_metaconsole() === false
            ) {
                /*
                 * A Visual Console from this console.
                 * We are in a regular console.
                 */

                if ($mobile_navigation === true) {
                    return $mobileUrl.'?'.http_build_query(
                        [
                            'page' => 'visualmap',
                            'id'   => $vcId,
                        ]
                    );
                }

                return $baseUrl.'?'.http_build_query(
                    [
                        'sec'  => 'network',
                        'sec2' => 'operation/visual_console/view',
                        'id'   => $vcId,
                        'pure' => (int) (isset($config['pure']) === true) ? $config['pure'] : 0,
                    ]
                );
            } else if (\is_metaconsole() === true
                && (bool) \can_user_access_node() === true
            ) {
                /*
                 * A Visual Console from a meta node.
                 * We are in a metaconsole.
                 */

                try {
                    $node = \metaconsole_get_connection_by_id(
                        $linkedLayoutNodeId
                    );

                    return \ui_meta_get_node_url(
                        $node,
                        'network',
                        'operation/visual_console/view',
                        ['id' => $vcId],
                        // No autologin from the public view.
                        !$config['public_view'],
                        $mobile_navigation,
                        [
                            'page' => 'visualmap',
                            'id'   => $vcId,
                        ]
                    );
                } catch (\Throwable $ignored) {
                    return null;
                }
            }
        } else {
            if (static::$useLinkedModule === true
                && $linkedModule['moduleId'] !== null
                && $linkedModule['moduleId'] > 0
            ) {
                // Module Id.
                $moduleId = $linkedModule['moduleId'];
                // The module can be from another node.
                $metaconsoleId = $linkedModule['metaconsoleId'];

                if (is_metaconsole() === false
                    || empty($metaconsoleId) === true
                ) {
                    /*
                     * A module from this console.
                     */

                    // Check if the module is from a service.
                    $serviceId = (int) \db_get_value_filter(
                        'custom_integer_1',
                        'tagente_modulo',
                        [
                            'id_agente_modulo'  => $moduleId,
                            'prediction_module' => 1,
                        ]
                    );

                    if (empty($serviceId) === false) {
                        // A service.
                        $queryParams = [
                            'sec'        => 'services',
                            'sec2'       => 'enterprise/operation/services/services',
                            'id_service' => $serviceId,
                        ];
                    } else {
                        // A regular module.
                        $queryParams = [
                            'sec'       => 'view',
                            'sec2'      => 'operation/agentes/status_monitor',
                            'id_module' => $moduleId,
                        ];

                        if ($mobile_navigation === true) {
                            return $mobileUrl.'?'.http_build_query(
                                [
                                    'page' => 'module_graph',
                                    'id'   => $moduleId,
                                ]
                            );
                        }
                    }

                    return $baseUrl.'?'.http_build_query($queryParams);
                } else if (\is_metaconsole() === true
                    && (bool) \can_user_access_node() === true
                ) {
                    /*
                     * A module from a meta node.
                     * We are in a metaconsole.
                     */

                    try {
                        $node = \metaconsole_get_connection_by_id(
                            $metaconsoleId
                        );

                        // Connect to node.
                        if (\metaconsole_connect($node) !== NOERR) {
                            // Will be catched below.
                            throw new \Exception(
                                'error connecting to the node'
                            );
                        }

                        // Check if the module is a service.
                        $serviceId = (int) \db_get_value_filter(
                            'custom_integer_1',
                            'tagente_modulo',
                            [
                                'id_agente_modulo'  => $moduleId,
                                'prediction_module' => 1,
                            ]
                        );

                        // Restore connection.
                        \metaconsole_restore_db();

                        if (empty($serviceId) === false) {
                            // A service.
                            return \ui_meta_get_node_url(
                                $node,
                                'services',
                                'enterprise/operation/services/services',
                                ['id_service' => $serviceId],
                                // No autologin from the public view.
                                !$config['public_view']
                            );
                        } else {
                            // A regular module.
                            return \ui_meta_get_node_url(
                                $node,
                                'view',
                                'operation/agentes/status_monitor',
                                ['id_module' => $moduleId],
                                // No autologin from the public view.
                                !((isset($config['public_view']) === true) ? $config['public_view'] : false),
                                $mobile_navigation,
                                [
                                    'id'   => $moduleId,
                                    'page' => 'module_graph',
                                ]
                            );
                        }
                    } catch (\Throwable $ignored) {
                        return null;
                    }
                }
            } else if ((static::$useLinkedAgent === true
                || static::$useLinkedModule === true)
                && $linkedAgent['agentId'] !== null
                && $linkedAgent['agentId'] > 0
            ) {
                // Linked agent.
                // Agent Id.
                $agentId = $linkedAgent['agentId'];
                // The agent can be from another node.
                $metaconsoleId = $linkedAgent['metaconsoleId'];

                if (is_metaconsole() === false
                    || empty($metaconsoleId) === true
                ) {
                    /*
                     * An agent from this console.
                     * We are in a regular console.
                     */

                    if ($mobile_navigation === true) {
                        return $mobileUrl.'?'.http_build_query(
                            [
                                'page' => 'agent',
                                'id'   => $agentId,
                            ]
                        );
                    }

                    return $baseUrl.'?'.http_build_query(
                        [
                            'sec'       => 'estado',
                            'sec2'      => 'operation/agentes/ver_agente',
                            'id_agente' => $agentId,
                        ]
                    );
                } else if (\is_metaconsole() === true
                    && (bool) \can_user_access_node() === true
                ) {
                    /*
                     * An agent from a meta node.
                     * We are in a metaconsole.
                     */

                    try {
                        $node = \metaconsole_get_connection_by_id(
                            $metaconsoleId
                        );
                        return \ui_meta_get_node_url(
                            $node,
                            'estado',
                            'operation/agentes/ver_agente',
                            ['id_agente' => $agentId],
                            // No autologin from the public view.
                            !$config['public_view'],
                            $mobile_navigation,
                            [
                                'id'   => $agentId,
                                'page' => 'agent',
                            ]
                        );
                    } catch (\Throwable $ignored) {
                        return null;
                    }
                }
            }
        }

        return null;
    }


    /**
     * TODO: CRITICAL. This function contains values which belong to its
     * subclasses. This function should be overrided there to add them.
     *
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

        $id = static::getId($data);
        if (isset($id) === true) {
            $result['id'] = $id;
        }

        $id_layout = static::getIdLayout($data);
        if (isset($id_layout) === true) {
            $result['id_layout'] = $id_layout;
        }

        $pos_x = static::parseIntOr(
            static::issetInArray($data, ['x', 'pos_x', 'posX']),
            null
        );
        if ($pos_x !== null) {
            $result['pos_x'] = $pos_x;
        }

        $pos_y = static::parseIntOr(
            static::issetInArray($data, ['y', 'pos_y', 'posY']),
            null
        );
        if ($pos_y !== null) {
            $result['pos_y'] = $pos_y;
        }

        $height = static::getHeight($data);
        if ($height !== null) {
            $result['height'] = $height;
        }

        $width = static::getWidth($data);
        if ($width !== null) {
            $result['width'] = $width;
        }

        $label = static::extractLabel($data);
        if ($label !== null) {
            $result['label'] = $label;
        }

        // TODO change.
        $image = static::getImageSrc($data);
        if ($image !== null) {
            $result['image'] = $image;
        }

        $type = static::parseIntOr(
            static::issetInArray($data, ['type']),
            null
        );
        if ($type !== null) {
            $result['type'] = $type;
        }

        // TODO change.
        $period = static::parseIntOr(
            static::issetInArray($data, ['period', 'maxTime']),
            null
        );
        if ($period !== null) {
            $result['period'] = $period;
        }

        $id_agente_modulo = static::extractModuleId($data);
        if ($id_agente_modulo !== null) {
            $result['id_agente_modulo'] = $id_agente_modulo;
        }

        $id_agent = static::extractAgentId($data);
        if ($id_agent !== null) {
            $result['id_agent'] = $id_agent;
        }

        $id_layout_linked = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutId',
                    'id_layout_linked',
                    'idLayoutLinked',
                ]
            ),
            null
        );
        if ($id_layout_linked !== null) {
            $result['id_layout_linked'] = $id_layout_linked;
        }

        $parent_item = static::extractParentId($data);
        if ($parent_item !== null) {
            $result['parent_item'] = $parent_item;
        }

        $enable_link = static::issetInArray(
            $data,
            [
                'isLinkEnabled',
                'enable_link',
                'enableLink',
            ]
        );

        if ($enable_link !== null) {
            $result['enable_link'] = static::parseBool($enable_link);
        }

        $id_metaconsole = static::extractMetaconsoleId($data);
        if ($id_metaconsole !== null) {
            $result['id_metaconsole'] = $id_metaconsole;
        }

        $element_group = static::extractAclGroupId($data);
        if ($element_group !== null) {
            $result['element_group'] = $element_group;
        }

        $label_position = static::notEmptyStringOr(
            static::issetInArray($data, ['labelPosition', 'label_position']),
            null
        );
        if ($label_position !== null) {
            $result['label_position'] = $label_position;
        }

        // TODO change.
        $border_color = static::getBorderColor($data);
        if ($border_color !== null) {
            $result['border_color'] = $border_color;
        }

        $id_custom_graph = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'customGraphId',
                    'id_custom_graph',
                ]
            ),
            null
        );
        if ($id_custom_graph !== null) {
            $result['id_custom_graph'] = $id_custom_graph;
        }

        $linked_layout_node_id = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutNodeId',
                    'linked_layout_node_id',
                ]
            ),
            null
        );
        if ($linked_layout_node_id !== null) {
            $result['linked_layout_node_id'] = $linked_layout_node_id;
        }

        $linked_layout_status_type = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutStatusType',
                    'linked_layout_status_type',
                ]
            ),
            null
        );
        if ($linked_layout_status_type !== null) {
            $result['linked_layout_status_type'] = $linked_layout_status_type;
        }

        $id_layout_linked_weight = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutStatusTypeWeight',
                    'id_layout_linked_weight',
                ]
            ),
            null
        );
        if ($id_layout_linked_weight !== null) {
            $result['id_layout_linked_weight'] = $id_layout_linked_weight;
        }

        $linked_layout_status_as_service_warning = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutStatusTypeWarningThreshold',
                    'linked_layout_status_as_service_warning',
                ]
            ),
            null
        );
        if ($linked_layout_status_as_service_warning !== null) {
            $result['linked_layout_status_as_service_warning'] = $linked_layout_status_as_service_warning;
        }

        $linked_layout_status_as_service_critical = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutStatusTypeCriticalThreshold',
                    'linked_layout_status_as_service_critical',
                ]
            ),
            null
        );
        if ($linked_layout_status_as_service_critical !== null) {
            $result['linked_layout_status_as_service_critical'] = $linked_layout_status_as_service_critical;
        }

        $element_group = static::parseIntOr(
            static::issetInArray($data, ['elementGroup', 'element_group']),
            null
        );
        if ($element_group !== null) {
            $result['element_group'] = $element_group;
        }

        $show_on_top = static::issetInArray(
            $data,
            [
                'isOnTop',
                'show_on_top',
                'showOnTop',
            ]
        );
        if ($show_on_top !== null) {
            $result['show_on_top'] = static::parseBool($show_on_top);
        }

        // TODO change.
        $show_last_value = static::notEmptyStringOr(
            static::issetInArray($data, ['showLastValueTooltip']),
            null
        );
        if ($show_last_value === null) {
            $show_last_value = static::parseIntOr(
                static::issetInArray(
                    $data,
                    [
                        'show_last_value',
                        'showLastValue',
                    ]
                ),
                null
            );
        }

        if ($show_last_value !== null) {
            if (\is_numeric($show_last_value) === true) {
                $result['show_last_value'] = $show_last_value;
            } else {
                switch ($show_last_value) {
                    case 'enabled':
                        $result['show_last_value'] = 1;
                    break;

                    case 'disabled':
                        $result['show_last_value'] = 2;
                    break;

                    default:
                        $result['show_last_value'] = 0;
                    break;
                }
            }
        }

        $cacheExpiration = static::extractCacheExpiration($data);
        if ($cacheExpiration !== null) {
            $result['cache_expiration'] = $cacheExpiration;
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
     * Extract a image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     */
    protected static function getImageSrc(array $data)
    {
        $imageSrc = static::issetInArray(
            $data,
            [
                'imageSrc',
                'image',
                'backgroundColor',
                'backgroundType',
                'valueType',
            ]
        );

        return $imageSrc;
    }


    /**
     * Extract a border width value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid border width.
     */
    protected static function getBorderWidth(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['border_width', 'borderWidth']),
            null
        );
    }


    /**
     * Extract a border color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the border color (not empty) or null.
     */
    protected static function getBorderColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'borderColor',
                    'border_color',
                    'gridColor',
                    'color',
                    'legendBackgroundColor',
                    'legendColor',
                    'titleColor',
                    'moduleNameColor',
                ]
            ),
            null
        );
    }


    /**
     * Update an item in the database
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean The modeled element data structure stored into the DB.
     *
     * @overrides Model::save.
     */
    public function save(array $data=[]): int
    {
        $data = ($data ?? $this->toArray());

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
                // Exception colorcloud...
                $dataEncode = static::encode(
                    array_merge($this->toArray(), $data)
                );

                $save = array_merge($dataModelEncode, $dataEncode);

                if (!empty($save['label'])) {
                    $save['label'] = io_safe_output(io_safe_input(str_replace("'", "\'", $save['label'])));
                }

                $result = \db_process_sql_update(
                    'tlayout_data',
                    $save,
                    ['id' => $save['id']]
                );

                // Invalidate the item's cache.
                if ($result !== false && $result > 0) {
                    // TODO: Invalidate the cache with the function clearCachedData.
                    \db_process_sql_delete(
                        'tvisual_console_elements_cache',
                        [
                            'vc_item_id' => (int) $save['id'],
                        ]
                    );

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
        $result = db_process_sql_delete(
            'tlayout_data',
            ['id' => $itemId]
        );

        if ($result) {
            // TODO: Invalidate the cache with the function clearCachedData.
            db_process_sql_delete(
                'tvisual_console_elements_cache',
                ['vc_item_id' => $itemId]
            );
        }

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

        switch ($values['tabSelected']) {
            case 'label':
                $inputs[] = [
                    'arguments' => [
                        'type'  => 'hidden',
                        'name'  => 'tabLabel',
                        'value' => true,
                    ],
                ];

                // Label.
                $inputs[] = ['label' => __('Label')];

                $inputs[] = [
                    'id'        => 'div-textarea-label',
                    'arguments' => [
                        'type'   => 'textarea',
                        'name'   => 'label',
                        'value'  => $values['label'],
                        'return' => true,
                    ],
                ];

                // Label Position.
                $fields = [
                    'down'  => __('Bottom'),
                    'up'    => __('Top'),
                    'right' => __('Right'),
                    'left'  => __('Left'),
                ];

                $inputs[] = [
                    'label'     => __('Label position'),
                    'arguments' => [
                        'type'     => 'select',
                        'fields'   => $fields,
                        'name'     => 'labelPosition',
                        'selected' => $values['labelPosition'],
                        'return'   => true,
                    ],
                ];
            break;

            case 'general':
                $inputs[] = [
                    'arguments' => [
                        'type'  => 'hidden',
                        'name'  => 'tabGeneral',
                        'value' => true,
                    ],
                ];

                // Size.
                $inputs[] = [
                    'block_id'      => 'size-item',
                    'class'         => 'flex-row flex-start w100p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label' => __('Size'),
                        ],
                        [
                            'label'     => __('width'),
                            'arguments' => [
                                'name'   => 'width',
                                'type'   => 'number',
                                'value'  => $values['width'],
                                'return' => true,
                                'min'    => 0,
                            ],
                        ],
                        [
                            'label'     => __('height'),
                            'arguments' => [
                                'name'   => 'height',
                                'type'   => 'number',
                                'value'  => $values['height'],
                                'return' => true,
                                'min'    => 0,
                            ],
                        ],
                    ],
                ];

                // Position.
                $inputs[] = [
                    'block_id'      => 'position-item',
                    'class'         => 'flex-row flex-start w100p',
                    'direct'        => 1,
                    'block_content' => [
                        [
                            'label' => __('Position'),
                        ],
                        [
                            'label'     => __('X'),
                            'arguments' => [
                                'name'   => 'x',
                                'type'   => 'number',
                                'value'  => $values['x'],
                                'return' => true,
                                'min'    => 0,
                            ],
                        ],
                        [
                            'label'     => __('Y'),
                            'arguments' => [
                                'name'   => 'y',
                                'type'   => 'number',
                                'value'  => $values['y'],
                                'return' => true,
                                'min'    => 0,
                            ],
                        ],
                    ],
                ];

                if ($values['type'] !== LABEL) {
                    // Link enabled.
                    $inputs[] = [
                        'label'     => __('Link enabled'),
                        'arguments' => [
                            'name'  => 'isLinkEnabled',
                            'id'    => 'isLinkEnabled',
                            'type'  => 'switch',
                            'value' => $values['isLinkEnabled'],
                        ],
                    ];
                }

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

                // Parent.
                // Check groups can access user.
                $aclUserGroups = [];
                if (!\users_can_manage_group_all('AR')) {
                    $aclUserGroups = array_keys(
                        \users_get_groups(false, 'AR')
                    );
                }

                $vcItems = VC::getItemsFromDB(
                    $values['vCId'],
                    $aclUserGroups
                );

                $fields = [];
                $fields[0] = __('None');
                foreach ($vcItems as $key => $value) {
                    $text = '';
                    $data = $value->toArray();
                    switch ($data['type']) {
                        case STATIC_GRAPH:
                            $text = __('Static graph');
                            $text .= ' - ';
                            $text .= $data['imageSrc'];
                        break;

                        case MODULE_GRAPH:
                            $text = __('Module graph');
                        break;

                        case CLOCK:
                            $text = __('Clock');
                        break;

                        case BARS_GRAPH:
                            $text = __('Bars graph');
                        break;

                        case AUTO_SLA_GRAPH:
                            $text = __('Event History Graph');
                        break;

                        case PERCENTILE_BAR:
                            $text = __('Percentile bar');
                        break;

                        case PERCENTILE_BUBBLE:
                            $text = __('Percentile bubble');
                        break;

                        case CIRCULAR_PROGRESS_BAR:
                            $text = __('Circular progress bar');
                        break;

                        case CIRCULAR_INTERIOR_PROGRESS_BAR:
                            $text = __('Circular progress bar (interior)');
                        break;

                        case SIMPLE_VALUE:
                            $text = __('Simple Value');
                        break;

                        case LABEL:
                            $text = __('Label');
                        break;

                        case GROUP_ITEM:
                            $text = __('Group');
                        break;

                        case COLOR_CLOUD:
                            $text = __('Color cloud');
                        break;

                        case ICON:
                            $text = __('Icon');
                        break;

                        case ODOMETER:
                            $text = __('Odometer');
                        break;

                        case BASIC_CHART:
                            $text = __('Basic chart');
                        break;

                        default:
                            // Lines could not be parents.
                        continue 2;
                    }

                    if (isset($data['agentAlias']) === true
                        && empty($data['agentAlias']) === false
                    ) {
                        $text .= ' ('.$data['agentAlias'].')';
                    }

                    if ($data['id'] !== $values['id']) {
                        $fields[$data['id']] = $text;
                    }
                }

                $inputs[] = [
                    'label'     => __('Parent'),
                    'arguments' => [
                        'type'     => 'select',
                        'fields'   => $fields,
                        'name'     => 'parentId',
                        'selected' => $values['parentId'],
                        'return'   => true,
                        'sort'     => false,
                    ],
                ];

                // Restrict access to group.
                $inputs[] = [
                    'label'     => __('Restrict access to group'),
                    'arguments' => [
                        'type'           => 'select_groups',
                        'name'           => 'aclGroupId',
                        'returnAllGroup' => true,
                        'privilege'      => $values['access'],
                        'selected'       => $values['aclGroupId'],
                        'return'         => true,
                    ],
                ];

                // Cache expiration.
                $inputs[] = [
                    'label'     => __('Cache expiration'),
                    'arguments' => [
                        'name'          => 'cacheExpiration',
                        'type'          => 'interval',
                        'value'         => $values['cacheExpiration'],
                        'nothing'       => __('None'),
                        'nothing_value' => 0,
                    ],
                ];
            break;

            case 'specific':
                // Override.
                $inputs = [];
            break;

            default:
                // Not possible.
            break;
        }

        return $inputs;
    }


    /**
     * Default values.
     *
     * @param array $values Array values.
     *
     * @return array Array with default values.
     */
    public static function getDefaultGeneralValues(array $values): array
    {
        global $config;

        // Default values.
        if (isset($values['x']) === false) {
            $values['x'] = 0;
        }

        if (isset($values['y']) === false) {
            $values['y'] = 0;
        }

        if (isset($values['parentId']) === false) {
            $values['parentId'] = 0;
        }

        if (isset($values['aclGroupId']) === false) {
            $values['aclGroupId'] = 0;
        }

        if (isset($values['isLinkEnabled']) === false) {
            $values['isLinkEnabled'] = true;
        }

        if (isset($values['isOnTop']) === false) {
            $values['isOnTop'] = false;
        }

        if (isset($values['cacheExpiration']) === false) {
            $values['cacheExpiration'] = $config['vc_default_cache_expiration'];
        }

        return $values;
    }


    /**
     * List images for Vc Icons.
     *
     * @param boolean|null $service If service item.
     *
     * @return array
     */
    public static function getListImagesVC(?bool $service=false):array
    {
        global $config;

        $result = [];

        // Extract images.
        $all_images = \list_files(
            $config['homedir'].'/images/console/icons/',
            'png',
            1,
            0
        );

        if (isset($all_images) === true && is_array($all_images) === true) {
            $base_url = \ui_get_full_url(
                '/images/console/icons/',
                false,
                false,
                false
            );

            $aux_images = $all_images;
            foreach ($all_images as $image_file) {
                $image_file = substr($image_file, 0, (strlen($image_file) - 4));

                if (strpos($image_file, '_bad') !== false) {
                    continue;
                }

                if (strpos($image_file, '_ok') !== false) {
                    continue;
                }

                if (strpos($image_file, '_warning') !== false) {
                    continue;
                }

                // Check the 4 images.
                $array_images = preg_grep('/'.$image_file.'(_ok|_bad|_warning)*\./', $aux_images);
                if (count($array_images) >= 4) {
                    $result[$image_file] = $image_file;
                }
            }
        }

        if ($service === true) {
            \array_unshift($result, ['name' => __('None')]);
        }

        return $result;
    }


    /**
     * Get all VC except own.
     *
     * @param integer $id Id Visual Console.
     *
     * @return array Array all VCs.
     */
    public static function getAllVisualConsole(int $id):array
    {
        // Extract all VC except own.
        $result = db_get_all_rows_filter(
            'tlayout',
            'id != '.(int) $id,
            [
                'id',
                'name',
            ]
        );

        // Extract all VC for each node.
        if (is_metaconsole() === true) {
            enterprise_include_once('include/functions_metaconsole.php');
            $meta_servers = (array) metaconsole_get_servers();
            foreach ($meta_servers as $server) {
                if (metaconsole_load_external_db($server) !== NOERR) {
                    metaconsole_restore_db();
                    continue;
                }

                $node_visual_maps = db_get_all_rows_filter(
                    'tlayout',
                    [],
                    [
                        'id',
                        'name',
                    ]
                );

                if (isset($node_visual_maps) === true
                    && is_array($node_visual_maps) === true
                ) {
                    foreach ($node_visual_maps as $node_visual_map) {
                        // ID.
                        $id = $node_visual_map['id'];
                        $id .= '|';
                        $id .= $server['id'];

                        // Name = vc_name - (node).
                        $name = $node_visual_map['name'];
                        $name .= ' - (';
                        $name .= $server['server_name'].')';

                        $result[$id] = $name;
                    }
                }

                metaconsole_restore_db();
            }
        }

        if ($result === false || $result === '') {
            $result = [];
        }

        return $result;
    }


    /**
     * Inputs for Linked Visual Console.
     *
     * @param array $values Array values item.
     *
     * @return array Inputs.
     */
    public static function inputsLinkedVisualConsole(array $values):array
    {
        // LinkConsoleInputGroup.
        $fields = self::getAllVisualConsole($values['vCId']);

        if ($fields === false) {
            $fields = [];
        } else {
            $rs = [];
            foreach ($fields as $k => $v) {
                if (isset($v['id']) === true && isset($v['name']) === true) {
                    // Modern environments use id-name format.
                    $rs[$v['id']] = $v;
                } else {
                    // In MC environments is key-value.
                    $rs[$k] = $v;
                }
            }

            $fields = $rs;
        }

        $getAllVisualConsoleValue = $values['linkedLayoutId'];
        if (\is_metaconsole() === true) {
            $getAllVisualConsoleValue = $values['linkedLayoutId'];
            if ($values['linkedLayoutNodeId'] !== 0) {
                $getAllVisualConsoleValue .= '|';
                $getAllVisualConsoleValue .= $values['linkedLayoutNodeId'];
            }
        }

        $inputs[] = [
            'label'     => __('Linked visual console'),
            'arguments' => [
                'type'          => 'select',
                'fields'        => $fields,
                'name'          => 'getAllVisualConsole',
                'selected'      => $getAllVisualConsoleValue,
                'script'        => 'linkedVisualConsoleChange()',
                'return'        => true,
                'nothing'       => __('None'),
                'nothing_value' => 0,
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'linkedLayoutId',
                'value' => $values['linkedLayoutId'],
            ],
        ];

        $inputs[] = [
            'arguments' => [
                'type'  => 'hidden',
                'name'  => 'linkedLayoutNodeId',
                'value' => $values['linkedLayoutNodeId'],
            ],
        ];

        // Initial hidden.
        $hiddenType = true;
        $hiddenWeight = true;
        $hiddenCritical = true;
        $hiddenWarning = true;
        if (isset($values['linkedLayoutId']) === true
            && $values['linkedLayoutId'] !== 0
        ) {
            $hiddenType = false;
            if ($values['linkedLayoutStatusType'] === 'service') {
                $hiddenCritical = false;
                $hiddenWarning = false;
            }

            if ($values['linkedLayoutStatusType'] === 'weight') {
                $hiddenWeight = false;
            }
        }

        // Type of the status calculation of the linked visual console.
        $fields = [
            'default' => __('By default'),
            'weight'  => __('By status weight'),
            'service' => __('By critical elements'),
        ];

        $inputs[] = [
            'id'        => 'li-linkedLayoutStatusType',
            'hidden'    => $hiddenType,
            'label'     => __(
                'Type of the status calculation of the linked visual console'
            ),
            'arguments' => [
                'type'     => 'select',
                'fields'   => $fields,
                'name'     => 'linkedLayoutStatusType',
                'selected' => $values['linkedLayoutStatusType'],
                'script'   => 'linkedVisualConsoleTypeChange()',
                'return'   => true,
            ],
        ];

        // Linked visual console weight.
        $inputs[] = [
            'id'        => 'li-linkedLayoutStatusTypeWeight',
            'hidden'    => $hiddenWeight,
            'label'     => __('Linked visual console weight'),
            'arguments' => [
                'name'   => 'linkedLayoutStatusTypeWeight',
                'type'   => 'number',
                'value'  => $values['linkedLayoutStatusTypeWeight'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Critical weight.
        $inputs[] = [
            'id'        => 'li-linkedLayoutStatusTypeCriticalThreshold',
            'hidden'    => $hiddenCritical,
            'label'     => __('Critical weight'),
            'arguments' => [
                'name'   => 'linkedLayoutStatusTypeCriticalThreshold',
                'type'   => 'number',
                'value'  => $values['linkedLayoutStatusTypeCriticalThreshold'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        // Warning weight.
        $inputs[] = [
            'id'        => 'li-linkedLayoutStatusTypeWarningThreshold',
            'hidden'    => $hiddenWarning,
            'label'     => __('Warning weight'),
            'arguments' => [
                'name'   => 'linkedLayoutStatusTypeWarningThreshold',
                'type'   => 'number',
                'value'  => $values['linkedLayoutStatusTypeWarningThreshold'],
                'return' => true,
                'min'    => 0,
            ],
        ];

        return $inputs;
    }


    /**
     * Return html images.
     *
     * @param string       $image Name image.
     * @param boolean|null $only  Only normal image.
     *
     * @return string Html images.
     */
    public static function imagesElementsVC(
        string $image,
        ?bool $only=false
    ):string {
        $images = '';
        if ($image !== '0') {
            $type_image = [''];
            if ($only === false) {
                $type_image = [
                    'bad',
                    'ok',
                    'warning',
                    '',
                ];
            }

            foreach ($type_image as $k => $v) {
                $type = '';
                if ($v !== '') {
                    $type = '_'.$v;
                }

                $images .= html_print_image(
                    'images/console/icons/'.$image.$type.'.png',
                    true,
                    [
                        'title' => __('Image Vc'),
                        'alt'   => __('Image Vc'),
                        'style' => 'max-width:70px; max-height:70px;',
                    ]
                );
            }
        }

        return $images;
    }


    /**
     * Return zones.
     *
     * @param string $zone Name zone.
     *
     * @return array Zones.
     */
    public static function zonesVC(string $zone):array
    {
        $result = [];
        $timezones = timezone_identifiers_list();
        foreach ($timezones as $timezone) {
            if (strpos($timezone, $zone) !== false) {
                $result[$timezone] = $timezone;
            }
        }

        return $result;
    }


    /**
     * Recursively check for alerts in a Visual Console item and its linked layout.
     *
     * @param array $item           The Visual Console item to check for alerts.
     * @param array $visitedLayouts A list of layouts that have already been visited to avoid circular references.
     *
     * @return boolean True if an alert has been found, false otherwise.
     */
    public static function checkLayoutAlertsRecursive(array $item, array $visitedLayouts=[])
    {
        if (isset($item['type']) === true) {
            $excludedItemTypes = [
                22,
                17,
                18,
                1,
                23,
                15,
                14,
                10,
                4,
            ];

            if (in_array($item['type'], $excludedItemTypes) === true) {
                return false;
            }
        }

        $agentID = (int) $item['id_agent'];
        $agentModuleID = (int) $item['id_agente_modulo'];
        $linkedLayoutID = (int) $item['id_layout_linked'];
        $metaconsoleID = (int) $item['id_metaconsole'];

        $visitedLayouts[] = $item['id_layout'];

        if ($agentModuleID !== 0 && $agentID !== 0) {
            $alerts_sql = sprintf(
                'SELECT talert_template_modules.id
                FROM talert_template_modules
                INNER JOIN tagente_modulo t2
                    ON talert_template_modules.id_agent_module = t2.id_agente_modulo
                INNER JOIN tagente t3
                    ON t2.id_agente = t3.id_agente AND t3.id_agente = %d
                INNER JOIN talert_templates t4
                    ON talert_template_modules.id_alert_template = t4.id
                WHERE `id_agent_module` = %d AND talert_template_modules.times_fired > 0',
                $agentID,
                $agentModuleID
            );

            // Connect to node.
            if (\is_metaconsole() === true
                && \metaconsole_connect(null, $metaconsoleID) !== NOERR
            ) {
                throw new \InvalidArgumentException(
                    'error connecting to the node'
                );
            }

            $firedAlert = db_get_sql($alerts_sql);

            // Restore connection.
            if (\is_metaconsole() === true) {
                \metaconsole_restore_db();
            }

            // Item has a triggered alert.
            if ($firedAlert !== false) {
                return true;
            }
        }

        if ($linkedLayoutID === 0 || in_array($linkedLayoutID, $visitedLayouts) === true) {
            // Item has no linked layout or it has already been visited (avoid infinite loop caused by circular references).
            return false;
        }

        $filter = ['id_layout' => $linkedLayoutID];

        $linkedLayoutItems = \db_get_all_rows_filter(
            'tlayout_data',
            $filter,
            [
                'id_layout',
                'id_agent',
                'id_agente_modulo',
                'id_layout_linked',
                'id_metaconsole',
            ]
        );

        if ($linkedLayoutItems === false) {
            // There are no items in the linked visual console. Nothing to check.
            return false;
        }

        foreach ($linkedLayoutItems as $linkedLayoutItem) {
            if (self::checkLayoutAlertsRecursive($linkedLayoutItem, $visitedLayouts)) {
                return true;
            }
        }

        return false;
    }


}
