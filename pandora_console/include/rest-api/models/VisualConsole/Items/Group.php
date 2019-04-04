<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a group item of the Visual Console.
 */
final class Group extends Item
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
        $return['type'] = GROUP_ITEM;
        $return['imageSrc'] = static::extractImageSrc($data);
        $return['groupId'] = static::extractGroupId($data);
        $return['statusImageSrc'] = static::extractStatusImageSrc($data);
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
     * Extract a status image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the status image url (not empty)
     * or null.
     *
     * @throws \InvalidArgumentException When a valid status image src
     * can't be found.
     */
    private static function extractStatusImageSrc(array $data): string
    {
        $statusImageSrc = static::notEmptyStringOr(
            static::issetInArray($data, ['statusImageSrc']),
            null
        );

        if ($statusImageSrc === null) {
            throw new \InvalidArgumentException(
                'the status image src property is required and should be a non empty string'
            );
        }

        return $statusImageSrc;
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
            static::issetInArray($data, ['groupId', 'id_group']),
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
        include_once $config['homedir'].'/include/functions_groups.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';

        // Get the status img src.
        $groupId = static::extractGroupId($data);
        $status = \groups_get_status($groupId);
        $data['statusImageSrc'] = \visual_map_get_image_status_element(
            $data,
            $status
        );

        return $data;
    }


}
