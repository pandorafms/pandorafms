<?php

declare(strict_types=1);

namespace Models\VisualConsole;
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
            if (static::notEmptyStringOr(static::issetInArray($data, ['encodedHtml']), null) === null
                && static::notEmptyStringOr(static::issetInArray($data, ['html']), null) === null
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
        $decodedData = [
            'id'            => (int) $data['id'],
            'type'          => (int) $data['type'],
            'label'         => static::extractLabel($data),
            'labelPosition' => static::extractLabelPosition($data),
            'isLinkEnabled' => static::extractIsLinkEnabled($data),
            'isOnTop'       => static::extractIsOnTop($data),
            'parentId'      => static::extractParentId($data),
            'aclGroupId'    => static::extractAclGroupId($data),
            'width'         => (int) $data['width'],
            'height'        => (int) $data['height'],
            'x'             => static::extractX($data),
            'y'             => static::extractY($data),
        ];

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
            static::issetInArray($data, ['x', 'pos_x']),
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
            static::issetInArray($data, ['y', 'pos_y']),
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
            static::issetInArray($data, ['id_group', 'aclGroupId']),
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
            static::issetInArray($data, ['parentId', 'parent_item']),
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
            static::issetInArray($data, ['isOnTop', 'show_on_top'])
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
            static::issetInArray($data, ['isLinkEnabled', 'enable_link'])
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
        return static::notEmptyStringOr(
            static::issetInArray($data, ['label']),
            null
        );
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
            static::issetInArray($data, ['agentId', 'id_agent', 'id_agente']),
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
            static::issetInArray($data, ['agentDescription', 'agent_description']),
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
            static::issetInArray($data, ['moduleDescription', 'module_description']),
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
     *   'metaconsoleId'          => 2,
     *   'linkedLayoutId'         => 12,
     *   'linkedLayoutAgentId'    => 48,
     *   'linkedLayoutStatusType' => 'default',
     * ]
     * @example [
     *   'linkedLayoutId'               => 11,
     *   'linkedLayoutAgentId'          => null,
     *   'linkedLayoutStatusType'       => 'weight',
     *   'linkedLayoutStatusTypeWeight' => 80,
     * ]
     * @example [
     *   'metaconsoleId'                           => 2,
     *   'linkedLayoutId'                          => 10,
     *   'linkedLayoutAgentId'                     => 48,
     *   'linkedLayoutStatusType'                  => 'service',
     *   'linkedLayoutStatusTypeWarningThreshold'  => 50,
     *   'linkedLayoutStatusTypeCriticalThreshold' => 80,
     * ]
     */
    private static function extractLinkedVisualConsole(array $data): array
    {
        $vcData = [];

        // We should add the metaconsole Id if we can. If not,
        // it doesn't have to be into the structure.
        $metaconsoleId = static::extractMetaconsoleId($data);
        if ($metaconsoleId !== null) {
            $vcData['metaconsoleId'] = $metaconsoleId;
        }

        // The linked vc Id should be a valid int or a null value.
        $vcData['linkedLayoutId'] = static::parseIntOr(
            static::issetInArray($data, ['linkedLayoutId', 'id_layout_linked']),
            null
        );

        // The linked vc agent Id should be a valid int or a null value.
        $vcData['linkedLayoutAgentId'] = static::parseIntOr(
            static::issetInArray(
                $data,
                [
                    'linkedLayoutAgentId',
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
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     *
     * @override Model::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(array $filter): array
    {
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
        return \db_process_sql_insert(
            'tvisual_console_elements_cache',
            [
                'vc_id'      => $filter['vc_id'],
                'vc_item_id' => $filter['vc_item_id'],
                'user_id'    => $filter['user_id'],
                'data'       => base64_encode(json_encode($data)),
                'expiration' => $filter['expiration'],
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
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    protected static function fetchAgentDataFromDB(array $itemData): array
    {
        $agentData = [];

        // We should add the metaconsole Id if we can.
        $metaconsoleId = static::extractMetaconsoleId($itemData);

        // Can't fetch an agent with an invalid Id.
        $agentId = static::extractAgentId($itemData);
        if ($agentId === null) {
            throw new \InvalidArgumentException('invalid agent Id');
        }

        // Staticgraph don't need to have an agent.
        if ($agentId === 0) {
            return $agentData;
        }

        if (\is_metaconsole() && $metaconsoleId === null) {
            throw new \InvalidArgumentException('missing metaconsole node Id');
        }

        $agent = false;

        if (\is_metaconsole()) {
            $sql = sprintf(
                'SELECT nombre, alias, direccion, comentarios
                FROM tmetaconsole_agent
                WHERE id_tagente = %s and id_tmetaconsole_setup = %s',
                $agentId,
                $metaconsoleId
            );
        } else {
            $sql = sprintf(
                'SELECT nombre, alias, direccion, comentarios
                FROM tagente
                WHERE id_agente = %s',
                $agentId
            );
        }

        $agent = \db_get_row_sql($sql);

        if ($agent === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        // The agent name should be a valid string or a null value.
        $agentData['agentName'] = $agent['nombre'];
        $agentData['agentAlias'] = $agent['alias'];
        $agentData['agentDescription'] = $agent['comentarios'];
        $agentData['agentAddress'] = $agent['direccion'];

        return $agentData;
    }


    /**
     * Fetch a data structure of an module from the database using the
     * vs item's data.
     *
     * @param array $itemData Visual Console Item's data structure.
     *
     * @return array The module data structure stored into the DB.
     * @throws \InvalidArgumentException When the input module Id is invalid.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    protected static function fetchModuleDataFromDB(array $itemData): array
    {
        // Load side libraries.
        if (\is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Initialize with the agent data.
        $moduleData = static::fetchAgentDataFromDB($itemData);

        // Can't fetch an module with a invalid Id.
        $moduleId = static::extractModuleId($itemData);
        if ($moduleId === null) {
            throw new \InvalidArgumentException('invalid module Id');
        }

        // Staticgraph don't need to have a module.
        if ($moduleId === 0) {
            return $moduleData;
        }

        // We should add the metaconsole Id if we can.
        $metaconsoleId = static::extractMetaconsoleId($itemData);

        if (\is_metaconsole() && $metaconsoleId === null) {
            throw new \InvalidArgumentException('missing metaconsole node Id');
        }

        $moduleName = false;

        // Connect to node.
        if (\is_metaconsole()
            && \metaconsole_connect(null, $metaconsoleId) !== NOERR
        ) {
            throw new \InvalidArgumentException(
                'error connecting to the node'
            );
        }

        $sql = sprintf(
            'SELECT nombre, descripcion
            FROM tagente_modulo
            WHERE id_agente_modulo = %s',
            $moduleId
        );

        $moduleName = \db_get_row_sql($sql);

        // Restore connection.
        if (\is_metaconsole()) {
            \metaconsole_restore_db();
        }

        if ($moduleName === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        $moduleData['moduleName'] = $moduleName['nombre'];
        $moduleData['moduleDescription'] = $moduleName['descripcion'];

        return $moduleData;
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

        // Load side libraries.
        include_once $config['homedir'].'/include/functions_ui.php';
        if (\is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
            \enterprise_include_once('meta/include/functions_ui_meta.php');
        }

        $linkedVisualConsole = static::extractLinkedVisualConsole($data);
        $linkedModule = static::extractLinkedModule($data);
        $linkedAgent = static::extractLinkedAgent($data);

        $baseUrl = \ui_get_full_url('index.php');

        // TODO: There's a feature to get the link from the label.
        if (static::$useLinkedVisualConsole === true
            && $linkedVisualConsole['linkedLayoutId'] !== null
            && $linkedVisualConsole['linkedLayoutId'] > 0
        ) {
            // Linked Visual Console.
            $vcId = $linkedVisualConsole['linkedLayoutId'];
            // The layout can be from another node.
            $linkedLayoutAgentId = $linkedVisualConsole['linkedLayoutAgentId'];

            if (empty($linkedLayoutAgentId) === true && \is_metaconsole()) {
                /*
                 * A Visual Console from this console.
                 * We are in a metaconsole.
                 */

                return $baseUrl.'?'.http_build_query(
                    [
                        'sec'          => 'screen',
                        'sec2'         => 'screens/screens',
                        'action'       => 'visualmap',
                        'id_visualmap' => $vcId,
                        'pure'         => (int) $config['pure'],
                    ]
                );
            } else if (empty($linkedLayoutAgentId) === true
                && !\is_metaconsole()
            ) {
                /*
                 * A Visual Console from this console.
                 * We are in a regular console.
                 */

                return $baseUrl.'?'.http_build_query(
                    [
                        'sec'  => 'network',
                        'sec2' => 'operation/visual_console/view',
                        'id'   => $vcId,
                        'pure' => (int) $config['pure'],
                    ]
                );
            } else if (\is_metaconsole() && \can_user_access_node()) {
                /*
                 * A Visual Console from a meta node.
                 * We are in a metaconsole.
                 */

                try {
                    $node = \metaconsole_get_connection_by_id(
                        $linkedLayoutAgentId
                    );
                    return \ui_meta_get_node_url(
                        $node,
                        'network',
                        // TODO: Link to a public view.
                        'operation/visual_console/view',
                        [],
                        // No autologin from the public view.
                        !$config['public_view']
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

                if (empty($metaconsoleId) === true) {
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
                    }

                    return $baseUrl.'?'.http_build_query($queryParams);
                } else if (\is_metaconsole() && \can_user_access_node()) {
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
                                !$config['public_view']
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

                if (empty($metaconsoleId) === true) {
                    /*
                     * An agent from this console.
                     * We are in a regular console.
                     */

                    return $baseUrl.'?'.http_build_query(
                        [
                            'sec'       => 'estado',
                            'sec2'      => 'operation/agentes/ver_agente',
                            'id_agente' => $agentId,
                        ]
                    );
                } else if (\is_metaconsole() && \can_user_access_node()) {
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
                            ['id_agente' => $moduleId],
                            // No autologin from the public view.
                            !$config['public_view']
                        );
                    } catch (\Throwable $ignored) {
                        return null;
                    }
                }
            }
        }

        return null;
    }


}
