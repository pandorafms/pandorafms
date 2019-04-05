<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a static graph item of the Visual Console.
 */
final class StaticGraph extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked visual console.
     *
     * @var boolean
     */
    protected static $useLinkedVisualConsole = true;

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked module.
     *
     * @var boolean
     */
    protected static $useLinkedModule = true;


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @overrides Item->decode.
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = STATIC_GRAPH;
        $return['imageSrc'] = $this->extractImageSrc($data);
        $return['showLastValueTooltip'] = $this->extractShowLastValueTooltip($data);
        $return['statusImageSrc'] = static::notEmptyStringOr(
            static::issetInArray($data, ['statusImageSrc']),
            null
        );

        return $return;
    }


    /**
     * Extract a image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     *
     * @throws \InvalidArgumentException When a valid image src can't be found.
     */
    private function extractImageSrc(array $data): string
    {
        $imageSrc = static::notEmptyStringOr(
            static::issetInArray($data, ['imageSrc', 'image']),
            null
        );

        if ($imageSrc === null) {
            throw new \InvalidArgumentException(
                'the image src property is required and should be a non empty string'
            );
        }

        return $imageSrc;
    }


    /**
     * Extract the value of showLastValueTooltip and
     * return 'default', 'enabled' or 'disabled'.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     */
    private function extractShowLastValueTooltip(array $data): string
    {
        $showLastValueTooltip = static::notEmptyStringOr(
            static::issetInArray($data, ['showLastValueTooltip']),
            null
        );

        if ($showLastValueTooltip === null) {
            $showLastValueTooltip = static::parseIntOr(
                static::issetInArray($data, ['show_last_value']),
                null
            );
            switch ($showLastValueTooltip) {
                case 1:
                return 'enabled';

                case 2:
                return 'disabled';

                default:
                return 'default';
            }
        } else {
            switch ($showLastValueTooltip) {
                case 'enabled':
                return 'enabled';

                case 'disabled':
                return 'disabled';

                default:
                return 'default';
            }
        }
    }


    /**
     * Fetch a vc item data structure from the database using a filter.
     *
     * @param array $filter Filter of the Visual Console Item.
     *
     * @return array The Visual Console Item data structure stored into the DB.
     * @throws \InvalidArgumentException When an agent Id cannot be found.
     *
     * @override Item::fetchDataFromDB.
     */
    protected static function fetchDataFromDB(array $filter): array
    {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $data = parent::fetchDataFromDB($filter);

        /*
         * Retrieve extra data.
         */

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_visual_map.php';

        // Get the linked module Id.
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        // Maybe connect to node.
        $nodeConnected = false;
        if (\is_metaconsole() === true && $metaconsoleId !== null) {
            $nodeConnected = \metaconsole_connect(
                null,
                $metaconsoleId
            ) === NOERR;

            if ($nodeConnected === false) {
                throw new \InvalidArgumentException(
                    'error connecting to the node'
                );
            }
        }

        // Get the img src.
        $data['statusImageSrc'] = \visual_map_get_image_status_element($data);

        // Restore connection.
        if ($nodeConnected === true) {
            \metaconsole_restore_db();
        }

        return $data;
    }


}
