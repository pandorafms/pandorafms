<?php

declare(strict_types=1);

namespace Models\VisualConsole;
use Models\Model;

/**
 * Model of a generic Visual Console Item.
 */
class Item extends Model
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
            'label'         => $this->extractLabel($data),
            'labelPosition' => $this->extractLabelPosition($data),
            'isLinkEnabled' => $this->extractIsLinkEnabled($data),
            'isOnTop'       => $this->extractIsOnTop($data),
            'parentId'      => $this->extractParentId($data),
            'aclGroupId'    => $this->extractAclGroupId($data),
            'width'         => (int) $data['width'],
            'height'        => (int) $data['height'],
            'x'             => $this->extractX($data),
            'y'             => $this->extractY($data),
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

        return $decodedData;
    }


    /**
     * Extract x y axis value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid x axis position of the item.
     */
    private function extractX(array $data): int
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
    private function extractY(array $data): int
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
    private function extractAclGroupId(array $data)
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
    private function extractParentId(array $data)
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
    private function extractIsOnTop(array $data): bool
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
    private function extractIsLinkEnabled(array $data): bool
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
    private function extractLabel(array $data)
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
    private function extractLabelPosition(array $data): string
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

        // We should add the metaconsole Id if we can. If not,
        // it doesn't have to be into the structure.
        $metaconsoleId = static::issetInArray(
            $data,
            [
                'metaconsoleId',
                'id_metaconsole',
            ]
        );
        if ($metaconsoleId !== null) {
            $metaconsoleId = static::parseIntOr($metaconsoleId, null);
            if ($metaconsoleId !== null) {
                $agentData['metaconsoleId'] = $metaconsoleId;
            }
        }

        // The agent Id should be a valid int or a null value.
        $agentData['agentId'] = static::parseIntOr(
            static::issetInArray($data, ['agentId', 'id_agent']),
            null
        );

        // The agent name should be a valid string or a null value.
        $agentData['agentName'] = static::notEmptyStringOr(
            static::issetInArray($data, ['agentName', 'agent_name']),
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
        $moduleData['moduleId'] = static::parseIntOr(
            static::issetInArray($data, ['moduleId', 'id_agente_modulo']),
            null
        );

        // The module name should be a valid string or a null value.
        $moduleData['moduleName'] = static::notEmptyStringOr(
            static::issetInArray($data, ['moduleName', 'module_name']),
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
    private function extractLinkedVisualConsole(array $data): array
    {
        $vcData = [];

        // We should add the metaconsole Id if we can. If not,
        // it doesn't have to be into the structure.
        $metaconsoleId = static::issetInArray(
            $data,
            [
                'metaconsoleId',
                'id_metaconsole',
            ]
        );
        if ($metaconsoleId !== null) {
            $metaconsoleId = static::parseIntOr($metaconsoleId, null);
            if ($metaconsoleId !== null) {
                $vcData['metaconsoleId'] = $metaconsoleId;
            }
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
            return \base64_encode($data['html']);
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
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $row = \db_get_row_filter('tlayout_data', $filter);

        if ($row === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        /*
         * Retrieve extra data.
         */

        // The linked module includes the agent data.
        if (static::$useLinkedModule === true) {
            $row = \array_merge($row, static::fetchModuleDataFromDB($row));
        } else if (static::$useLinkedAgent === true) {
            $row = \array_merge($row, static::fetchAgentDataFromDB($row));
        }

        return $row;
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
        $metaconsoleId = static::issetInArray(
            $itemData,
            [
                'metaconsoleId',
                'id_metaconsole',
            ]
        );
        if ($metaconsoleId !== null) {
            $metaconsoleId = static::parseIntOr($metaconsoleId, null);
        }

        // Can't fetch an agent with a missing Id.
        $agentId = static::issetInArray($itemData, ['agentId', 'id_agent']);
        if ($agentId === null) {
            throw new \InvalidArgumentException('missing agent Id');
        }

        // Can't fetch an agent with a invalid Id.
        $agentId = static::parseIntOr($agentId, null);
        if ($agentId === null) {
            throw new \InvalidArgumentException('invalid agent Id');
        }

        // TODO: Should we make a connection to the metaconsole node?
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $agentName = \db_get_value('nombre', 'tagente', 'id_agente', $agentId);
        if ($agentName === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        // The agent name should be a valid string or a null value.
        $agentData['agentName'] = $agentName;

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
    protected static function fetchModuleDataFromDB(
        array $itemData
    ): array {
        // Initialize with the agent data.
        $moduleData = static::fetchAgentDataFromDB($itemData);

        // Can't fetch an module with a missing Id.
        $moduleId = static::issetInArray(
            $itemData,
            [
                'moduleId',
                'id_agente_modulo',
            ]
        );
        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        // Can't fetch an module with a invalid Id.
        $moduleId = static::parseIntOr($moduleId, null);
        if ($moduleId === null) {
            throw new \InvalidArgumentException('invalid module Id');
        }

        // TODO: Should we make a connection to the metaconsole node?
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $moduleName = \db_get_value(
            'nombre',
            'tagente_modulo',
            'id_agente_modulo',
            $moduleId
        );
        if ($moduleName === false) {
            throw new \Exception('error fetching the data from the DB');
        }

        $moduleData['moduleName'] = $moduleName;

        return $moduleData;
    }


    /**
     * Obtain a vc item instance from the database using an identifier.
     *
     * @param integer $id Identifier of the Visual Console Item.
     *
     * @return mixed The Visual Console Item data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    public static function fromDBWithId(int $id)
    {
        return static::fromDB(['id' => $id]);
    }


}
