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
     * Get the "recursive Group" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed If the recursive should be true or not.
     */
    private static function getRecursiveGroup(array $data)
    {
        return static::issetInArray(
            $data,
            [
                'recursiveGroup',
                'recursive_group',
            ]
        );
    }


    /**
     * Get the "show statistics" switch value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return mixed If the statistics should be shown or not.
     */
    private static function getShowStatistics(array $data)
    {
        return static::issetInArray(
            $data,
            [
                'showStatistics',
                'show_statistics',
            ]
        );
    }


    /**
     * Extract a group Id (for ACL) value.
     *
     * @param array $data Unknown input data structure.
     *
     * @return integer Valid identifier of a group.
     */
    private static function getGroupId(array $data)
    {
        return static::parseIntOr(
            static::issetInArray($data, ['id_group', 'groupId']),
            null
        );
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

        $id_group = static::getGroupId($data);
        if ($id_group !== null) {
            $return['id_group'] = $id_group;
        }

        $recursive_group = static::getRecursiveGroup($data);
        if ($recursive_group !== null) {
            $return['recursive_group'] = static::parseBool($recursive_group);
        }

        $show_statistics = static::getShowStatistics($data);
        if ($show_statistics !== null) {
            $return['show_statistics'] = static::parseBool($show_statistics);
        }

        return $return;
    }


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
        $return['recursiveGroup'] = static::extractRecursiveGroup($data);
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
    private static function extractRecursiveGroup(array $data): bool
    {
        return static::parseBool(
            static::issetInArray($data, ['recursiveGroup', 'recursive_group'])
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
        include_once $config['homedir'].'/include/functions_groups.php';
        include_once $config['homedir'].'/include/functions_visual_map.php';
        include_once $config['homedir'].'/include/functions_ui.php';
        include_once $config['homedir'].'/include/functions_agents.php';
        include_once $config['homedir'].'/include/functions_users.php';
        if (is_metaconsole()) {
            \enterprise_include_once('include/functions_metaconsole.php');
        }

        $groupId = static::extractGroupId($data);
        $recursiveGroup = static::extractrecursiveGroup($data);
        $showStatistics = static::extractShowStatistics($data);

        if ($showStatistics === true) {
            $isMetaconsole = \is_metaconsole();
            if ($recursiveGroup) {
                $childers_id = groups_get_children_ids($groupId);
            }

            // Retrieve the agent stats.
            if ($recursiveGroup) {
                $numCritical = 0;
                foreach ($childers_id as $id_group) {
                    $agentsCritical = \agents_get_agents(
                        [
                            'id_grupo' => $id_group,
                            'status'   => AGENT_STATUS_CRITICAL,
                        ],
                        ['COUNT(*) AS total'],
                        'AR',
                        false,
                        false,
                        true,
                        $isMetaconsole
                    );
                    $numCritical += $agentsCritical[0]['total'];
                }
            } else {
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
            }

            if ($recursiveGroup) {
                $numWarning = 0;
                foreach ($childers_id as $id_group) {
                    $agentsWarning = \agents_get_agents(
                        [
                            'id_grupo' => $id_group,
                            'status'   => AGENT_STATUS_WARNING,
                        ],
                        ['COUNT(*) AS total'],
                        'AR',
                        false,
                        false,
                        true,
                        $isMetaconsole
                    );
                    $numWarning += $agentsWarning[0]['total'];
                }
            } else {
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
            }

            if ($recursiveGroup) {
                $numUnknown = 0;
                foreach ($childers_id as $id_group) {
                    $agentsUnknown = \agents_get_agents(
                        [
                            'id_grupo' => $id_group,
                            'status'   => AGENT_STATUS_UNKNOWN,
                        ],
                        ['COUNT(*) AS total'],
                        'AR',
                        false,
                        false,
                        true,
                        $isMetaconsole
                    );
                    $numUnknown += $agentsUnknown[0]['total'];
                }
            } else {
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
            }

            if ($recursiveGroup) {
                $numNormal = 0;
                foreach ($childers_id as $id_group) {
                    $agentsOk = \agents_get_agents(
                        [
                            'id_grupo' => $id_group,
                            'status'   => AGENT_STATUS_NORMAL,
                        ],
                        ['COUNT(*) AS total'],
                        'AR',
                        false,
                        false,
                        true,
                        $isMetaconsole
                    );
                    $numNormal += $agentsOk[0]['total'];
                }
            } else {
                $agentsOk = \agents_get_agents(
                    [
                        'id_grupo' => $groupId,
                        'status'   => AGENT_STATUS_NORMAL,
                    ],
                    ['COUNT(*) AS total'],
                    'AR',
                    false,
                    false,
                    true,
                    $isMetaconsole
                );
                $numNormal = $agentsOk[0]['total'];
            }

            $numTotal = ($numCritical + $numWarning + $numUnknown + $numNormal);

            $agentStats = [
                'critical' => 0,
                'warning'  => 0,
                'normal'   => 0,
                'unknown'  => 0,
            ];
            if ($numTotal !== 0) {
                $agentStats = [
                    'critical' => ($numCritical / $numTotal * 100),
                    'warning'  => ($numWarning / $numTotal * 100),
                    'normal'   => ($numNormal / $numTotal * 100),
                    'unknown'  => ($numUnknown / $numTotal * 100),
                ];
            }

            $groupName = \groups_get_name($groupId, true);
            $data['html'] = static::printStatsTable(
                $groupName,
                $agentStats
            );

            if (isset($data['width']) === false
                || (int) $data['width'] === 0
            ) {
                $data['width'] = 500;
            }

            if (isset($data['height']) === false
                || (int) $data['height'] === 0
            ) {
                $data['height'] = 70;
            }
        } else {
            if (\is_metaconsole()) {
                $groupFilter = $groupId;
                if ($groupId === 0) {
                    $groupFilter = implode(
                        ',',
                        array_keys(\users_get_groups())
                    );

                    if ($recursiveGroup === true) {
                        $childers_id = groups_get_children_ids($groupId);
                        $groupFilter .= ','.implode(',', $childers_id);
                    }
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

                if ($countStatus['critical'] > 0) {
                    $status = AGENT_STATUS_CRITICAL;
                } else if ($countStatus['warning'] > 0) {
                    $status = AGENT_STATUS_WARNING;
                } else if ($countStatus['unknown'] > 0) {
                    $status = AGENT_STATUS_UNKNOWN;
                } else {
                    $status = AGENT_STATUS_NORMAL;
                }
            } else {
                if ($recursiveGroup === true) {
                    $childers_id = groups_get_children_ids($groupId);
                    $flag_stop_foreach = false;
                    foreach ($childers_id as $id_children) {
                        if ($flag_stop_foreach === true) {
                            // Stop if some child is not normal.
                            break;
                        }

                        // Get the status img src from all modules childs.
                        $status = \groups_get_status($id_children, true);
                        if ($status !== AGENT_STATUS_NORMAL) {
                            $flag_stop_foreach = true;
                        }
                    }
                } else {
                    // Get the status img src.
                    $status = \groups_get_status($groupId, true);
                }
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
     * @param string $groupName  Group name.
     * @param array  $agentStats Data structure with the agent statistics.
     *
     * @return string HTML representation.
     */
    private static function printStatsTable(
        string $groupName,
        array $agentStats
    ): string {
        global $config;

        $critical = \number_format($agentStats['critical'], 2, $config['decimal_separator'], $config['thousand_separator']).'%';
        $warning = \number_format($agentStats['warning'], 2, $config['decimal_separator'], $config['thousand_separator']).'%';
        $normal = \number_format($agentStats['normal'], 2, $config['decimal_separator'], $config['thousand_separator']).'%';
        $unknown = \number_format($agentStats['unknown'], 2, $config['decimal_separator'], $config['thousand_separator']).'%';

        $html = '<div class="group-container">';
        $html .= '<div class="group-item-title">';
        $html .= $groupName;
        $html .= '</div>';
        $html .= '<div class="group-item-info" style="padding:0%;width: 100%;justify-content:center">';
        $html .= '<div style="width:90%;display:flex;flex-direction:row;flex-wrap:wrap;padding:1%">';
        // Critical.
        $html .= '<div class="group-item-info-container">';
        $html .= '<div class="value-style red_background">';
        $html .= $critical;
        $html .= '</div>';
        $html .= '<div class="name-style">'.__('Critical').'</div>';
        $html .= '</div>';
        // Warning.
        $html .= '<div class="group-item-info-container">';
        $html .= '<div class="value-style yellow_background">';
        $html .= $warning;
        $html .= '</div>';
        $html .= '<div class="name-style">'.__('Warning').'</div>';
        $html .= '</div>';
        // Normal.
        $html .= '<div class="group-item-info-container">';
        $html .= '<div class="value-style green_background">';
        $html .= $normal;
        $html .= '</div>';
        $html .= '<div class="name-style">'.__('Normal').'</div>';
        $html .= '</div>';
        // Unknown.
        $html .= '<div class="group-item-info-container">';
        $html .= '<div class="value-style grey_background">';
        $html .= $unknown;
        $html .= '</div>';
        $html .= '<div class="name-style">'.__('Unknown').'</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</div>';
        $html .= '</div>';

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
                '[Group]::getFormInputs parent class return is not an array'
            );
        }

        if ($values['tabSelected'] === 'specific') {
            // List images VC.
            if (isset($values['imageSrc']) === false) {
                $values['imageSrc'] = 'appliance';
            }

            $baseUrl = ui_get_full_url('/', false, false, false);

            $inputs[] = [
                'label'     => __('Image'),
                'arguments' => [
                    'type'     => 'select',
                    'fields'   => self::getListImagesVC(),
                    'name'     => 'imageSrc',
                    'selected' => $values['imageSrc'],
                    'script'   => 'imageVCChange(\''.$baseUrl.'\',\''.$values['vCId'].'\')',
                    'return'   => true,
                ],
            ];

            $images = self::imagesElementsVC($values['imageSrc']);

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

            // Group.
            $inputs[] = [
                'label'     => __('Group'),
                'arguments' => [
                    'type'           => 'select_groups',
                    'name'           => 'groupId',
                    'returnAllGroup' => true,
                    'privilege'      => $values['access'],
                    'selected'       => $values['groupId'],
                    'return'         => true,
                ],
            ];

            if ((int) $values['type'] === GROUP_ITEM) {
                if (isset($values['recursiveGroup']) === false) {
                    $values['recursiveGroup'] = true;
                }

                // Recursive group.
                $inputs[] = [
                    'label'     => __('Recursive'),
                    'arguments' => [
                        'name'  => 'recursiveGroup',
                        'id'    => 'recursiveGroup',
                        'type'  => 'switch',
                        'value' => $values['recursiveGroup'],
                    ],
                ];
            }

            // Show statistics.
            $inputs[] = [
                'label'     => __('Show statistics'),
                'arguments' => [
                    'name'  => 'showStatistics',
                    'id'    => 'showStatistics',
                    'type'  => 'switch',
                    'value' => $values['showStatistics'],
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


}
