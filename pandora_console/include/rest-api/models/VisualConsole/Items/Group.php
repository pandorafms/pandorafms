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
     * Used to enable validation, extraction and encodeing of the HTML output.
     *
     * @var boolean
     */
    protected static $useHtmlOutput = true;


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
        $return['groupId'] = static::extractGroupId($data);
        if (!isset($return['encodedHtml']) === true) {
            $return['imageSrc'] = static::extractImageSrc($data);
            $return['statusImageSrc'] = static::extractStatusImageSrc($data);
        }

        global $config;
        $return['link'] = $config['homeurl'].'index.php?sec=estado&sec2=operation/agentes/estado_agente&group_id='.$return['groupId'];
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
     * Extract a show Statistics value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a group.
     *
     * @throws \InvalidArgumentException When a valid group Id can't be found.
     */
    private static function extractShowStatistics(array $data): int
    {
        return static::parseIntOr(
            static::issetInArray($data, ['showStatistics', 'show_statistics']),
            0
        );
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
        include_once $config['homedir'].'/include/functions_ui.php';

        $groupId = static::extractGroupId($data);
        $showStatistics = static::extractShowStatistics($data);

        if ($showStatistics) {
            $agents_critical = \agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_CRITICAL,
                ],
                ['COUNT(*) as total'],
                'AR',
                false
            );
            $agents_warning = \agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_WARNING,
                ],
                ['COUNT(*) as total'],
                'AR',
                false
            );
            $agents_unknown = \agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_UNKNOWN,
                ],
                ['COUNT(*) as total'],
                'AR',
                false
            );
            $agents_ok = \agents_get_agents(
                [
                    'disabled' => 0,
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_OK,
                ],
                ['COUNT(*) as total'],
                'AR',
                false
            );
            $total_agents = ($agents_critical[0]['total'] + $agents_warning[0]['total'] + $agents_unknown[0]['total'] + $agents_ok[0]['total']);
            $stat_agent_ok = ($agents_ok[0]['total'] / $total_agents * 100);
            $stat_agent_wa = ($agents_warning[0]['total'] / $total_agents * 100);
            $stat_agent_cr = ($agents_critical[0]['total'] / $total_agents * 100);
            $stat_agent_un = ($agents_unknown[0]['total'] / $total_agents * 100);
            if ($width == 0 || $height == 0) {
                $dyn_width = 520;
                $dyn_height = 80;
            } else {
                $dyn_width = $width;
                $dyn_height = $height;
            }

            // Print statistics table.
            $html = '<table cellpadding="0" cellspacing="0" border="0" class="databox" style="width:'.$dyn_width.'px;height:'.$dyn_height.'px;text-align:center;';

            if ($data['label_position'] === 'left') {
                $html .= 'float:right;';
            } else if ($data['label_position'] === 'right') {
                $html .= 'float:left;';
            }

            $html .= '">';
                $html .= '<tr style="height:10%;">';
                    $html .= '<th style="text-align:center;background-color:#9d9ea0;color:black;font-weight:bold;">'.\groups_get_name($layoutData['id_group'], true).'</th>';
                $html .= '</tr>';
                $html .= '<tr style="background-color:whitesmoke;height:90%;">';
                    $html .= '<td>';
                        $html .= '<div style="margin-left:2%;color: #FFF;font-size: 12px;display:inline;background-color:#FC4444;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">'.remove_right_zeros(number_format($stat_agent_cr, 2)).'%</div>';
                        $html .= '<div style="background-color:white;color: black ;font-size: 12px;display:inline;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">Critical</div>';
                        $html .= '<div style="margin-left:2%;color: #FFF;font-size: 12px;display:inline;background-color:#f8db3f;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">'.remove_right_zeros(number_format($stat_agent_wa, 2)).'%</div>';
                        $html .= '<div style="background-color:white;color: black ;font-size: 12px;display:inline;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">Warning</div>';
                        $html .= '<div style="margin-left:2%;color: #FFF;font-size: 12px;display:inline;background-color:#84b83c;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">'.remove_right_zeros(number_format($stat_agent_ok, 2)).'%</div>';
                        $html .= '<div style="background-color:white;color: black ;font-size: 12px;display:inline;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">Normal</div>';
                        $html .= '<div style="margin-left:2%;color: #FFF;font-size: 12px;display:inline;background-color:#9d9ea0;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">'.remove_right_zeros(number_format($stat_agent_un, 2)).'%</div>';
                        $html .= '<div style="background-color:white;color: black ;font-size: 12px;display:inline;position:relative;height:80%;width:9.4%;height:80%;border-radius:2px;text-align:center;padding:5px;">Unknown</div>';
                    $html .= '</td>';
                $html .= '</tr>';
            $html .= '</table>';

            $data['html'] = $html;
        } else {
            // Get the status img src.
            $status = \groups_get_status($groupId);
            $imagePath = \visual_map_get_image_status_element($data, $status);
            $data['statusImageSrc'] = \ui_get_full_url(
                $imagePath,
                false,
                false,
                false
            );

            // If the width or the height are equal to 0 we will extract them
            // from the real image size.
            if ((int) $data['width'] === 0 || (int) $data['height'] === 0) {
                $sizeImage = getimagesize($imagePath);
                $data['width'] = $sizeImage[0];
                $data['height'] = $sizeImage[1];
            }

            static::$useHtmlOutput = false;
        }

        return $data;
    }


}
