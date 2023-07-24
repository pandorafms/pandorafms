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
    protected static function fetchDataFromDB(
        array $filter,
        ?float $ratio=0,
        ?float $widthRatio=0
    ): array {
        // Due to this DB call, this function cannot be unit tested without
        // a proper mock.
        $data = parent::fetchDataFromDB($filter, $ratio, $widthRatio);

        /*
         * Retrieve extra data.
         */

        // Load side libraries.
        global $config;
        include_once $config['homedir'].'/include/functions_ui.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';

        // Get the img src.
        $imagePath = \visual_map_get_image_status_element($data);
        $url = parse_url($imagePath);
        if (isset($url['scheme']) === false) {
            $data['imageSrc'] = \ui_get_full_url($imagePath, false, false, false);
        } else {
            $data['imageSrc'] = $imagePath;
        }

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
                '[Icon]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // List images VC.
            if (isset($values['imageSrc']) === false) {
                $values['imageSrc'] = 'appliance';
            } else {
                $explode_url = explode('/', $values['imageSrc']);
                $total = count($explode_url);
                $values['imageSrc'] = substr(
                    $explode_url[($total - 1)],
                    0,
                    -4
                );
            }

            $baseUrl = ui_get_full_url('/', false, false, false);

            $inputs[] = [
                'label'     => __('Image'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => self::getListImagesVC(),
                    'name'     => 'imageSrc',
                    'selected' => $values['imageSrc'],
                    'script'   => 'imageVCChange(\''.$baseUrl.'\',\''.$values['vCId'].'\',1)',
                    'return'   => true,
                ],
            ];

            $images = self::imagesElementsVC($values['imageSrc'], true);

            $inputs[] = [
                'block_id'      => 'image-item',
                'class'         => 'flex-row flex-end w100p',
                'direct'        => 1,
                'block_content' => [
                    [
                        'label'     => $images,
                        'arguments' => ['type' => 'image-item'],
                    ],
                ],
            ];

            // Inputs LinkedVisualConsole.
            $inputsLinkedVisualConsole = self::inputsLinkedVisualConsole(
                $values
            );
            foreach ($inputsLinkedVisualConsole as $key => $value) {
                $inputs[] = $value;
            }
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
        // Retrieve global - common inputs.
        $values = parent::getDefaultGeneralValues($values);

        return $values;
    }


}
