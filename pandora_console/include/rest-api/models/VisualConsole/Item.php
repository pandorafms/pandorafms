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
     * Used to decide wether to fetch information about
     * the linked agent or not.
     *
     * @var boolean
     */
    protected static $fetchLinkedAgent = false;

    /**
     * Used to decide wether to fetch information about
     * the linked module or not.
     *
     * @var boolean
     */
    protected static $fetchLinkedModule = false;

    /**
     * Used to decide wether to fetch information about
     * the linked visual console or not.
     *
     * @var boolean
     */
    protected static $fetchLinkedVisualConsole = false;


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
                'the width property is required and should greater than 0'
            );
        }

        if (isset($data['height']) === false
            || \is_numeric($data['height']) === false
            || $data['height'] < 0
        ) {
            throw new \InvalidArgumentException(
                'the height property is required and should greater than 0'
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
     * @overrides Model::decode.
     */
    protected function decode(array $data): array
    {
        return [
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
        return static::notEmptyStringOr($data['label'], null);
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
     * @return  array Data structure of the linked agent info.
     * @example [
     *   'metaconsole' => int | null,
     *   'agentId'     => int | null,
     *   'agentName'   => string | null
     * ]
     */
    private function extractLinkedAgent(array $data): array
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

        // The agent Id sohuld be a valid int or a null value.
        $agentData['agentId'] = static::parseIntOr(
            static::issetInArray($data, ['agentId', 'id_agent']),
            null
        );

        // The agent name sohuld be a valid string or a null value.
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
     * @return  array Data structure of the linked module info.
     * @example [
     *   'metaconsole' => int | null,
     *   'agentId'     => int | null,
     *   'agentName'   => string | null,
     *   'moduleId'    => int | null,
     *   'moduleName'  => string | null,
     * ]
     */
    private function extractLinkedModule(array $data): array
    {
        // Initialize the data with the agent data and then expand it.
        $moduleData = static::extractLinkedAgent($data);

        // The module Id sohuld be a valid int or a null value.
        $moduleData['moduleId'] = static::parseIntOr(
            static::issetInArray($data, ['moduleId', 'id_agente_modulo']),
            null
        );

        // The module name sohuld be a valid string or a null value.
        $moduleData['moduleName'] = static::notEmptyStringOr(
            static::issetInArray($data, ['moduleName', 'module_name']),
            null
        );

        return $moduleData;
    }


    /**
     * Obtain a vc item data structure from the database using a filter.
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
        if (static::$fetchLinkedModule === true) {
            $row = \array_merge($row, static::fetchModuleDataFromDB($row));
        } else if (static::$fetchLinkedAgent === true) {
            $row = \array_merge($row, static::fetchAgentDataFromDB($row));
        }

        if (static::$fetchLinkedVisualConsole === true) {
            // TODO: Implement fetchLinkedVisualConsoleDataFromDB.
            // $row = \array_merge(
            // $row,
            // static::fetchLinkedVisualConsoleDataFromDB($row)
            // );
        }

        return $row;
    }


    /**
     * Obtain a data structure of an agent from the database using the
     * vs item's data.
     *
     * @param array $itemData Visual Console Item's data structure.
     *
     * @return array The Visual Console Item data structure stored into the DB.
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
            $agentName = static::notEmptyStringOr($agentName, null);
        }

        // The agent name sohuld be a valid string or a null value.
        $agentData['agentName'] = $agentName;

        return $agentData;
    }


    /**
     * Obtain a data structure of an module from the database using the
     * vs item's data.
     *
     * @param array $itemData Visual Console Item's data structure.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \InvalidArgumentException When the input module Id is invalid.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    protected static function fetchModuleDataFromDB(array $itemData): array
    {
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
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \Exception When the data cannot be retrieved from the DB.
     */
    protected static function fromDBWithId(int $id): array
    {
        return static::fromDB(['id' => $id]);
    }


}
