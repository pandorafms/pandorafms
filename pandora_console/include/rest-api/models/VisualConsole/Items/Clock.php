<?php

declare(strict_types=1);

namespace Models\VisualConsole\Items;
use Models\VisualConsole\Item;

/**
 * Model of a Clock item of the Visual Console.
 */
final class Clock extends Item
{


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
        $clockData = parent::decode($data);
        $clockData['type'] = CLOCK;
        $clockData['clockType'] = $this->extractClockType($data);
        $clockData['clockFormat'] = $this->extractClockFormat($data);
        $clockData['clockTimezone'] = $this->extractClockTimezone($data);

        $dateTimeZoneUTC = new DateTimeZone('UTC');
        $dateTimeZoneClock = new DateTimeZone($clockData['clockTimezone']);
        $dateTime = new DateTime('now', $dateTimeZoneClock);
        $clockData['clockTimezoneOffset'] = $dateTimeZoneUTC->getOffset($dateTime);

        $clockData['showClockTimezone'] = $this->extractShowClockTimezone($data);
        $clockData['color'] = $this->extractColor($data);
        return $clockData;
    }


    /**
     * Extract a clock type value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return string Digital or analogic. analogic by default.
     */
    private function extractClockType(array $data): string
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
     * @return string Time or datetime. datetime by default.
     */
    private function extractClockFormat(array $data): string
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
     */
    private function extractClockTimezone(array $data): string
    {
        $clockTimezone = static::notEmptyStringOr(
            static::issetInArray($data, ['clockTimezone', 'timezone']),
            null
        );

        if ($clockTimezone === null) {
            throw new \InvalidArgumentException(
                'the clockTimezone property is required and should be string'
            );
        } else {
            return $clockTimezone;
        }
    }


    /**
     * Extract a clock timezone value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean
     */
    private function extractShowClockTimezone(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['showClockTimezone'])
        );
    }


    /**
     * Extract the color value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed returns a color or null
     */
    private function extractColor(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['color', 'fill_color']),
            null
        );
    }


}
