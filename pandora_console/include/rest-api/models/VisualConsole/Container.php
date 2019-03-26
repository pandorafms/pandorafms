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
            'backgroundURL'   => $this->extractBackgroundUrl($data),
            'backgroundColor' => $this->extractBackgroundColor($data),
            'isFavorite'      => $this->extractFavorite($data),
            'width'           => (int) $data['width'],
            'height'          => (int) $data['height'],
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
     * Extract a image url value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     */
    private function extractBackgroundUrl(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['background', 'backgroundURL']),
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

        return $row;
    }


}
