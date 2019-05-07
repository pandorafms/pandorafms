<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a clock item of the Visual Console.
 */
final class Clock extends Item
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
     * @throws \InvalidArgumentException When there is a problem with
     * the time management.
     *
     * @overrides Item::decode.
     */
    protected function decode(array $data): array
    {
        $clockData = parent::decode($data);
        $clockData['type'] = CLOCK;
        $clockData['clockType'] = static::extractClockType($data);
        $clockData['clockFormat'] = static::extractClockFormat($data);
        $clockData['clockTimezone'] = static::extractClockTimezone($data);

        try {
            $timezone = new \DateTimeZone($clockData['clockTimezone']);
            $timezoneUTC = new \DateTimeZone('UTC');
            $dateTimeUtc = new \DateTime('now', $timezoneUTC);
            $clockData['clockTimezoneOffset'] = $timezone->getOffset(
                $dateTimeUtc
            );
        } catch (\Throwable $e) {
            throw new \InvalidArgumentException($e->getMessage());
        }

        // $clockData['showClockTimezone'] = static::parseBool(
        // static::issetInArray($data, ['showClockTimezone'])
        // );
        // TODO: Remove the true by default when added into the editor.
        $clockData['showClockTimezone'] = true;
        $clockData['color'] = static::extractColor($data);
        return $clockData;
    }


    /**
     * Extract a clock type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'digital' or 'analogic'. 'analogic' by default.
     */
    private static function extractClockType(array $data): string
    {
        $clockType = static::notEmptyStringOr(
            static::issetInArray($data, ['clockType', 'clock_animation']),
            null
        );

        switch ($clockType) {
            case 'digital':
            case 'digital_1':
            return 'digital';

            default:
            return 'analogic';
        }
    }


    /**
     * Extract a clock format value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string One of 'time' or 'datetime'. 'datetime' by default.
     */
    private static function extractClockFormat(array $data): string
    {
        $clockFormat = static::notEmptyStringOr(
            static::issetInArray($data, ['clockFormat', 'time_format']),
            null
        );

        switch ($clockFormat) {
            case 'time':
            return 'time';

            default:
            return 'datetime';
        }
    }


    /**
     * Extract a clock timezone value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string
     * @throws \InvalidArgumentException When a valid clock timezone cannot be
     * extracted.
     */
    private static function extractClockTimezone(array $data): string
    {
        $clockTimezone = static::notEmptyStringOr(
            static::issetInArray($data, ['clockTimezone', 'timezone']),
            null
        );

        if ($clockTimezone === null) {
            throw new \InvalidArgumentException(
                'the clockTimezone property is required and should be string'
            );
        }

        return $clockTimezone;
    }


    /**
     * Extract the color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed returns a color or null.
     */
    private static function extractColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['color', 'fill_color']),
            null
        );
    }


}
