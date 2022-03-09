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
     * Encode type item.
     *
     * @param array $data Data for encode.
     *
     * @return string Return color.
     */
    protected static function encodeColor(array $data): ?string
    {
        $color = null;
        if (isset($data['color']) === true) {
            if (empty($data['color']) === true) {
                $color = '#F0F0F0';
            } else {
                $color = $data['color'];
            }
        }

        return $color;
    }


    /**
     * Return a valid representation of a record in database.
     *
     * @param array $data Input data.
     *
     * @return array Data structure representing a record in database.
     *
     * @overrides Item->encode.
     */
    protected static function encode(array $data): array
    {
        $return = parent::encode($data);

        $color = static::encodeColor($data);
        if ($color !== null) {
            $return['fill_color'] = $color;
        }

        $clock_animation = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'clockType',
                    'clock_animation',
                    'clockAnimation',
                ]
            ),
            null
        );
        if ($clock_animation !== null) {
            $return['clock_animation'] = $clock_animation;
        }

        $time_format = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'clockFormat',
                    'time_format',
                    'timeFormat',
                ]
            ),
            null
        );
        if ($time_format !== null) {
            $return['time_format'] = $time_format;
        }

        $timezone = static::notEmptyStringOr(
            static::issetInArray(
                $data,
                [
                    'timezone',
                    'timeZone',
                    'time_zone',
                    'clockTimezone',
                ]
            ),
            null
        );
        if ($timezone !== null) {
            $return['timezone'] = $timezone;
        }

        return $return;
    }


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


    /**
     * Generates inputs for form (specific).
     *
     * @param array $values Default values.
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public static function getFormInputs(array $values): array
    {
        // Default values.
        $values = static::getDefaultGeneralValues($values);

        // Retrieve global - common inputs.
        $inputs = Item::getFormInputs($values);

        if (is_array($inputs) !== true) {
            throw new Exception(
                '[Clock]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // Time zone.
            $baseUrl = ui_get_full_url('/', false, false, false);
            $fields = [
                'Africa'     => __('Africa'),
                'America'    => __('America'),
                'Antarctica' => __('Antarctica'),
                'Arctic'     => __('Arctic'),
                'Asia'       => __('Asia'),
                'Atlantic'   => __('Atlantic'),
                'Australia'  => __('Australia'),
                'Europe'     => __('Europe'),
                'Indian'     => __('Indian'),
                'Pacific'    => __('Pacific'),
                'UTC'        => __('UTC'),
            ];

            if (isset($values['clockTimezone']) === false
                && empty($values['clockTimezone']) === true
            ) {
                $values['zone'] = 'Europe';
                $values['clockTimezone'] = 'Europe/Amsterdam';
            } else {
                $zone = explode('/', $values['clockTimezone']);
                $values['zone'] = $zone[0];
            }

            $zones = self::zonesVC($values['zone']);

            $inputs[] = [
                'block_id'      => 'timeZone-item',
                'class'         => 'flex-row flex-start w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label' => __('Time zone'),
                    ],
                    [
                        'arguments' => [
                            'type'     => 'select',
                            'fields'   => $fields,
                            'name'     => 'zone',
                            'selected' => $values['zone'],
                            'script'   => 'timeZoneVCChange(\''.$baseUrl.'\',\''.$values['vCId'].'\')',
                            'return'   => true,
                        ],
                    ],
                    [
                        'arguments' => [
                            'type'     => 'select',
                            'fields'   => $zones,
                            'name'     => 'clockTimezone',
                            'selected' => $values['clockTimezone'],
                            'return'   => true,
                        ],
                    ],
                ],
            ];

            // Clock animation.
            $fields = [
                'analogic' => __('Simple analogic'),
                'digital'  => __('Simple digital'),
            ];

            $inputs[] = [
                'label'     => __('Clock animation'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'clockType',
                    'selected' => $values['clockType'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // Time format.
            $fields = [
                'time'     => __('Only time'),
                'datetime' => __('Time and date'),
            ];

            $inputs[] = [
                'label'     => __('Time format'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => $fields,
                    'name'     => 'clockFormat',
                    'selected' => $values['clockFormat'],
                    'return'   => true,
                    'sort'     => false,
                ],
            ];

            // Element color.
            $inputs[] = [
                'label'     => __('Fill color'),
                'arguments' => [
                    'wrapper' => 'div',
                    'name'    => 'color',
                    'type'    => 'color',
                    'value'   => $values['color'],
                    'return'  => true,
                ],
            ];
        }

        return $inputs;
    }


    /**
     * Default values.
     *
     * @param array $values Array values.
     *
     * @return array Array with default values.
     *
     * @overrides Item->getDefaultGeneralValues.
     */
    public static function getDefaultGeneralValues(array $values): array
    {
        if (isset($values['isLinkEnabled']) === false) {
            $values['isLinkEnabled'] = false;
        }

        // Retrieve global - common inputs.
        $values = parent::getDefaultGeneralValues($values);

        // Default values.
        if (isset($values['width']) === false) {
            $values['width'] = 100;
        }

        if (isset($values['height']) === false) {
            $values['height'] = 50;
        }

        return $values;
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
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $data = parent::fetchDataFromDB($filter, $ratio, $widthRatio);

        // Default values.
        if ((int) $data['width'] === 0) {
            $data['width'] = 100;
            if ($ratio != 0) {
                $data['width'] *= $ratio;
            }
        }

        if ((int) $data['height'] === 0) {
            $data['height'] = 50;
            if ($ratio != 0) {
                $data['height'] *= $ratio;
            }
        }

        return $data;
    }


}
