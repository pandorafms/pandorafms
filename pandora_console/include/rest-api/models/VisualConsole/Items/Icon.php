<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a group item of the Visual Console.
 */
final class Icon extends Item
{

    /**
     * Used to enable the fetching, validation and extraction of information
     * about the linked visual console.
     *
     * @var boolean
     */
    protected static $useLinkedVisualConsole = true;


    /**
     * Returns a valid representation of the model.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing the model.
     *
     * @overrides Item::decode.
     */
    protected function decode(array $data): array
    {
        $return = parent::decode($data);
        $return['type'] = ICON;
        $return['image'] = static::extractImage($data);
        $return['imageSrc'] = static::extractImageSrc($data);
        return $return;
    }


    /**
     * Extract a image value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     *
     * @throws \InvalidArgumentException When a valid image can't be found.
     */
    private static function extractImage(array $data)
    {
        $image = static::notEmptyStringOr(
            static::issetInArray($data, ['image']),
            null
        );

        if ($image === null) {
            throw new \InvalidArgumentException(
                'the image property is required and should be a non empty string'
            );
        }

        return $image;
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
    private static function extractImageSrc(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['imageSrc']),
            null
        );
    }


    // 'images/console/icons/'.$imageSrc.'.png'


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
        include_once $config['homedir'].'/include/functions_visual_map.php';

        // Get the img src.
        $imagePath = \visual_map_get_image_status_element($data);
        $data['imageSrc'] = \ui_get_full_url($imagePath, false, false, false);

        // If the width or the height are equal to 0 we will extract them
        // from the real image size.
        $width = (int) $data['width'];
        $height = (int) $data['height'];
        if ($width === 0 || $height === 0) {
            $sizeImage = getimagesize($config['homedir'].'/'.$imagePath);
            $data['width'] = $sizeImage[0];
            $data['height'] = $sizeImage[1];
        }

        return $data;
    }


}
