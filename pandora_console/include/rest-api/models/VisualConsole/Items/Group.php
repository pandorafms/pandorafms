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
     * Enable the validation, extraction and encoding of HTML output.
     *
     * @var boolean
     */
    protected static $useHtmlOutput = true;

    /**
     * Enable the cache index by user id.
     *
     * @var boolean
     */
    protected static $indexCacheByUser = true;


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
        $return['imageSrc'] = static::extractImageSrc($data);
        $return['statusImageSrc'] = static::extractStatusImageSrc($data);
        $return['showStatistics'] = static::extractShowStatistics($data);

        return $return;
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
     * Extract a image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the image url (not empty) or null.
     */
    private static function extractImageSrc(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['imageSrc', 'image']),
            null
        );
    }


    /**
     * Extract a status image src value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed String representing the status image url (not empty)
     * or null.
     */
    private static function extractStatusImageSrc(array $data)
    {
        return static::notEmptyStringOr(
            static::issetInArray($data, ['statusImageSrc']),
            null
        );
    }


    /**
     * Extract the "show statistics" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return boolean If the statistics should be shown or not.
     */
    private static function extractShowStatistics(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['showStatistics', 'show_statistics'])
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
        include_once $config['homedir'].'/include/functions_agents.php';
        include_once $config['homedir'].'/include/functions_users.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        $groupId = static::extractGroupId($data);
        $showStatistics = static::extractShowStatistics($data);

        if ($showStatistics === true) {
            $isMetaconsole = \is_metaconsole();
            // Retrieve the agent stats.
            $agentsCritical = \agents_get_agents(
                [
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_CRITICAL,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false,
                false,
                true,
                $isMetaconsole
            );
            $numCritical = $agentsCritical[0]['total'];
            $agentsWarning = \agents_get_agents(
                [
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_WARNING,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false,
                false,
                true,
                $isMetaconsole
            );
            $numWarning = $agentsWarning[0]['total'];
            $agentsUnknown = \agents_get_agents(
                [
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_UNKNOWN,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false,
                false,
                true,
                $isMetaconsole
            );
            $numUnknown = $agentsUnknown[0]['total'];
            $agentsOk = \agents_get_agents(
                [
                    'id_grupo' => $groupId,
                    'status'   => AGENT_STATUS_OK,
                ],
                ['COUNT(*) AS total'],
                'AR',
                false,
                false,
                true,
                $isMetaconsole
            );
            $numNormal = $agentsOk[0]['total'];

            $numTotal = ($numCritical + $numWarning + $numUnknown + $numNormal);
            $agentStats = [
                'critical' => ($numCritical / $numTotal * 100),
                'warning'  => ($numWarning / $numTotal * 100),
                'normal'   => ($numNormal / $numTotal * 100),
                'unknown'  => ($numUnknown / $numTotal * 100),
            ];

            $groupName = \groups_get_name($groupId, true);
            $data['html'] = static::printStatsTable(
                $groupName,
                $agentStats,
                (int) $data['width'],
                (int) $data['height']
            );
        } else {
            if (\is_metaconsole()) {
                $groupFilter = $groupId;
                if ($groupId === 0) {
                    $groupFilter = implode(
                        ',',
                        array_keys(\users_get_groups())
                    );
                }

                $sql = sprintf(
                    'SELECT
                        SUM(fired_count) AS fired,
                        SUM(critical_count) AS critical,
                        SUM(warning_count) AS warning,
                        SUM(unknown_count) AS unknown
                    FROM tmetaconsole_agent
                    LEFT JOIN tmetaconsole_agent_secondary_group tasg
                        ON id_agente = tasg.id_agent
                    WHERE id_grupo IN (%s)
                        OR tasg.id_group IN (%s)',
                    $groupFilter,
                    $groupFilter
                );

                $countStatus = \db_get_row_sql($sql);

                if ($countStatus['fired'] > 0) {
                    $status = AGENT_STATUS_ALERT_FIRED;
                } else if ($countStatus['critical'] > 0) {
                    $status = AGENT_STATUS_CRITICAL;
                } else if ($countStatus['warning'] > 0) {
                    $status = AGENT_STATUS_WARNING;
                } else if ($countStatus['unknown'] > 0) {
                    $status = AGENT_STATUS_UNKNOWN;
                } else {
                    $status = AGENT_STATUS_NORMAL;
                }
            } else {
                // Get the status img src.
                $status = \groups_get_status($groupId);
            }

            $imagePath = \visual_map_get_image_status_element($data, $status);
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

            $data['html'] = '<img src="'.$data['statusImageSrc'].'">';
        }

        return $data;
    }


    /**
     * HTML representation for the agent stats of a group.
     *
     * @param string  $groupName  Group name.
     * @param array   $agentStats Data structure with the agent statistics.
     * @param integer $width      Width.
     * @param integer $height     Height.
     *
     * @return string HTML representation.
     */
    private static function printStatsTable(
        string $groupName,
        array $agentStats,
        int $width=520,
        int $height=80
    ): string {
        $width = ($width > 0) ? $width : 520;
        $height = ($height > 0) ? $height : 80;

        $tableStyle = \join(
            [
                'width:'.$width.'px;',
                'height:'.$height.'px;',
                'text-align:center;',
            ]
        );
        $headStyle = \join(
            [
                'text-align:center;',
                'background-color:#9d9ea0;',
                'color:black;',
                'font-weight:bold;',
            ]
        );
        $valueStyle = \join(
            [
                'margin-left: 2%;',
                'color: #FFF;',
                'font-size: 12px;',
                'display: inline;',
                'background-color: #e63c52;',
                'position: relative;',
                'height: 80%;',
                'width: 9.4%;',
                'height: 80%;',
                'border-radius: 2px;',
                'text-align: center;',
                'padding: 5px;',
            ]
        );
        $nameStyle = \join(
            [
                'background-color: white;',
                'color: black;',
                'font-size: 12px;',
                'display: inline;',
                'display: inline;',
                'position:relative;',
                'width: 9.4%;',
                'height: 80%;',
                'border-radius: 2px;',
                'text-align: center;',
                'padding: 5px;',
            ]
        );

        $html = '<table class="databox" style="'.$tableStyle.'">';
        $html .= '<tr style="height:10%;">';
        $html .= '<th style="'.$headStyle.'">'.$groupName.'</th>';
        $html .= '</tr>';
        $html .= '<tr style="background-color:whitesmoke;height:90%;">';
        $html .= '<td>';

        // Critical.
        $html .= '<div style="'.$valueStyle.'background-color: #e63c52;">';
        $html .= \number_format($agentStats['critical'], 2).'%';
        $html .= '</div>';
        $html .= '<div style="'.$nameStyle.'">'.__('Critical').'</div>';
        // Warning.
        $html .= '<div style="'.$valueStyle.'background-color: #f8db3f;">';
        $html .= \number_format($agentStats['warning'], 2).'%';
        $html .= '</div>';
        $html .= '<div style="'.$nameStyle.'">'.__('Warning').'</div>';
        // Normal.
        $html .= '<div style="'.$valueStyle.'background-color: #84b83c;">';
        $html .= \number_format($agentStats['normal'], 2).'%';
        $html .= '</div>';
        $html .= '<div style="'.$nameStyle.'">'.__('Normal').'</div>';
        // Unknown.
        $html .= '<div style="'.$valueStyle.'background-color: #9d9ea0;">';
        $html .= \number_format($agentStats['unknown'], 2).'%';
        $html .= '</div>';
        $html .= '<div style="'.$nameStyle.'">'.__('Unknown').'</div>';

        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';

        return $html;
    }


    /**
     * Generate a link to something related with the item.
     *
     * @param array $data Visual Console Item's data structure.
     *
     * @return mixed The link or a null value.
     *
     * @override Item::buildLink.
     */
    protected static function buildLink(array $data)
    {
        // This will return the link to a linked VC if this item has one.
        $link = parent::buildLink($data);
        if ($link !== null) {
            return $link;
        }

        global $config;

        $groupId = static::extractGroupId($data);
        $baseUrl = $config['homeurl'].'index.php';

        if (\is_metaconsole()) {
            return $baseUrl.'?'.http_build_query(
                [
                    'sec'      => 'monitoring',
                    'sec2'     => 'operation/tree',
                    'group_id' => $groupId,
                ]
            );
        }

        return $baseUrl.'?'.http_build_query(
            [
                'sec'      => 'estado',
                'sec2'     => 'operation/agentes/estado_agente',
                'group_id' => $groupId,
            ]
        );
    }


}
