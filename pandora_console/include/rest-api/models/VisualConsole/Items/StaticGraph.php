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
        $return['imageSrc'] = static::extractImageSrc($data);
        $return['showLastValueTooltip'] = static::extractShowLastValueTooltip(
            $data
        );
        $return['statusImageSrc'] = static::notEmptyStringOr(
            static::issetInArray($data, ['statusImageSrc']),
            null
        );
        $return['lastValue'] = static::notEmptyStringOr(
            static::issetInArray($data, ['lastValue']),
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
    private static function extractImageSrc(array $data): string
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
    private static function extractShowLastValueTooltip(array $data): string
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
        include_once $config['homedir'].'/include/functions_ui.php';
        include_once $config['homedir'].'/include/functions_io.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';
        include_once $config['homedir'].'/include/functions_modules.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        // Get the linked module Id.
        $linkedModule = static::extractLinkedModule($data);
        $moduleId = $linkedModule['moduleId'];
        $metaconsoleId = $linkedModule['metaconsoleId'];

        if ($moduleId === null) {
            throw new \InvalidArgumentException('missing module Id');
        }

        // Get the img src.
        // There's no need to connect to the metaconsole before searching for
        // the image status cause the function itself does that for us.
        $imagePath = \visual_map_get_image_status_element($data);
        $data['statusImageSrc'] = \ui_get_full_url(
            $imagePath,
            false,
            false,
            false
        );

        // If the width or the height are equal to 0 we will extract them
        // from the real image size.
        $width = (int) $data['width'];
        $height = (int) $data['height'];
        if ($width === 0 || $height === 0) {
            $sizeImage = getimagesize($config['homedir'].'/'.$imagePath);
            $data['width'] = $sizeImage[0];
            $data['height'] = $sizeImage[1];
        }

        // Get last value.
        $showLastValueTooltip = static::extractShowLastValueTooltip($data);
        if ($showLastValueTooltip !== 'disabled' && $moduleId > 0) {
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

            $imgTitle = '';

            $unit = \trim(\io_safe_output(\modules_get_unit($moduleId)));
            $value = \modules_get_last_value($moduleId);

            $isBooleanModule = \modules_is_boolean($moduleId);
            if (!$isBooleanModule
                || ($isBooleanModule && $showLastValueTooltip !== 'default')
            ) {
                if (\is_numeric($value)) {
                    $imgTitle .= __('Last value: ').\remove_right_zeros(\number_format((float) $value, (int) $config['graph_precision']));
                } else {
                    $imgTitle .= __('Last value: ').$value;
                }

                if (empty($unit) === false && empty($imgTitle) === false) {
                    $imgTitle .= ' '.$unit;
                }

                $data['lastValue'] = $imgTitle;
            }

            // Restore connection.
            if ($nodeConnected === true) {
                \metaconsole_restore_db();
            }
        }

        return $data;
    }


}
