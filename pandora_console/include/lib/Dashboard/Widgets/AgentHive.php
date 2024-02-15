<?php

/**
 * Widget agent hive Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

namespace PandoraFMS\Dashboard;

use PandoraFMS\Enterprise\Metaconsole\Node;

global $config;

/**
 * URL Widgets
 */
class AgentHive extends Widget
{

    /**
     * Name widget.
     *
     * @var string
     */
    protected $name;

    /**
     * Title widget.
     *
     * @var string
     */
    protected $title;

    /**
     * Page widget;
     *
     * @var string
     */
    protected $page;

    /**
     * Class name widget.
     *
     * @var [type]
     */
    protected $className;

    /**
     * Values options for each widget.
     *
     * @var [type]
     */
    protected $values;

    /**
     * Configuration required.
     *
     * @var boolean
     */
    protected $configurationRequired;

    /**
     * Error load widget.
     *
     * @var boolean
     */
    protected $loadError;

    /**
     * Width.
     *
     * @var integer
     */
    protected $width;

    /**
     * Heigth.
     *
     * @var integer
     */
    protected $height;

    /**
     * Grid Width.
     *
     * @var integer
     */
    protected $gridWidth;

    /**
     * Cell ID.
     *
     * @var integer
     */
    protected $cellId;


    /**
     * Construct.
     *
     * @param integer      $cellId      Cell ID.
     * @param integer      $dashboardId Dashboard ID.
     * @param integer      $widgetId    Widget ID.
     * @param integer|null $width       New width.
     * @param integer|null $height      New height.
     * @param integer|null $gridWidth   Grid width.
     */
    public function __construct(
        int $cellId,
        int $dashboardId=0,
        int $widgetId=0,
        ?int $width=0,
        ?int $height=0,
        ?int $gridWidth=0
    ) {
        global $config;

        // WARNING: Do not edit. This chunk must be in the constructor.
        parent::__construct(
            $cellId,
            $dashboardId,
            $widgetId
        );

        // Width.
        $this->width = $width;

        // Height.
        $this->height = $height;

        // Grid Width.
        $this->gridWidth = $gridWidth;

        // Cell Id.
        $this->cellId = $cellId;

        // Options.
        $this->values = $this->decoders($this->getOptionsWidget());

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Agent hive');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'AgentHive';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['groups']) === true) {
            $this->configurationRequired = true;
        }
    }


    /**
     * Decoders hack for retrocompability.
     *
     * @param array $decoder Values.
     *
     * @return array Returns the values ​​with the correct key.
     */
    public function decoders(array $decoder): array
    {
        $values = [];
        // Retrieve global - common inputs.
        $values = parent::decoders($decoder);

        if (isset($decoder['groups']) === true) {
            $values['groups'] = $decoder['groups'];
        }

        return $values;
    }


    /**
     * Generates inputs for form (specific).
     *
     * @return array Of inputs.
     *
     * @throws Exception On error.
     */
    public function getFormInputs(): array
    {
        $values = $this->values;

        // Retrieve global - common inputs.
        $inputs = parent::getFormInputs();

        // Filters.
        $inputs[] = [
            'label'     => __('Groups'),
            'id'        => 'li_groups',
            'arguments' => [
                'type'           => 'select_groups',
                'name'           => 'groups[]',
                'returnAllGroup' => false,
                'privilege'      => 'AR',
                'selected'       => (isset($values['groups'][0]) === true) ? explode(',', $values['groups'][0]) : [],
                'return'         => true,
                'multiple'       => true,
                'required'       => true,
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost(): array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['groups'] = \get_parameter('groups', 0);

        return $values;
    }


    /**
     * Draw widget.
     *
     * @return string;
     */
    public function load()
    {
        global $config;

        $groups = $this->values['groups'];
        $groups = explode(',', $groups[0]);

        $user_groups = array_keys(
            users_get_groups(
                false,
                'AR',
                false,
                false,
                $groups
            )
        );

        foreach ($groups as $key => $group) {
            if (in_array($group, $user_groups) === false) {
                unset($groups[$key]);
            }
        }

        $table = 'tagente';
        if (is_metaconsole()) {
            $table = 'tmetaconsole_agent';
        }

        $sql = sprintf(
            'SELECT * FROM %s WHERE id_grupo IN('.implode(',', $groups).')',
            $table
        );
        $all_agents = db_get_all_rows_sql($sql);

        $output = '';
        $output .= '<div class="container-tabs">';
        foreach ($all_agents as $agent) {
            $output .= $this->drawSquare($agent);
        }

        $output .= '</div>';

        $output .= '<script type="text/javascript">
            $(document).ready (function () {
                $(".widget-agent-hive-square").click(function(e) {
                    if (typeof e.target.id === "string" && /img_.*/i.test(e.target.id) === false) {
                        var url = $(this).children("input").first().val();
                        window.open(url);
                    }
                });

                $("div [id*=hiveImg_] svg path").css("fill", "#99A3BE");
            });
        </script>';

        return $output;
    }


    /**
     * Draw square agent.
     *
     * @param array $data Info agent.
     *
     * @return string Output.
     */
    private function drawSquare(array $data): string
    {
        global $config;

        $id = (is_metaconsole() === true)
            ? $data['id_tagente']
            : $data['id_agente'];

        $status = agents_get_status_from_counts($data);
        switch ($status) {
            case 1:
            case 4:
            case 100:
                // Critical (BAD or ALERT).
                $color = '#e63c52';
            break;

            case 0:
            case 300:
                // Normal (OK).
                $color = '#82b92e';
            break;

            case 2:
            case 200:
                // Warning.
                $color = '#f3b200';
            break;

            case 5:
                // Not init.
                $color = '#4a83f3';
            break;

            default:
                // Default is Grey (Other).
                $color = '#b2b2b2';
            break;
        }

        $style_contact = 'flex-grow: 9; font-size: 8pt; display: flex;
            justify-content: start;align-items: start; color: #9FA5B1; font-weight: 600;';
        // Last contact.
        $lastContactDate = ui_print_timestamp(
            $data['ultimo_contacto'],
            true,
            ['style' => $style_contact]
        );

        // Url.
        $console_url = ui_get_full_url('/');
        if (is_metaconsole()) {
            $server = metaconsole_get_servers($data['id_tmetaconsole_setup']);
            $console_url = $server['server_url'].'/';
        }

        $url_view = $console_url.'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id;
        $url_manage = $console_url.'index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente='.$id;

        $output = '<div class="widget-agent-hive-square">';
            $output .= '<input type="hidden" name="test" value="'.$url_view.'" />';
            $output .= '<div class="widget-agent-hive-square-status"
                style="background-color:'.$color.'"></div>';
            $output .= '<div class="widget-agent-hive-square-info">';
                // Last contact and img.
                $output .= '<div class="widget-agent-hive-square-info-header">';
                    $output .= $lastContactDate;
                    $output .= '<a href="'.$url_manage.'" target="_blank">'.html_print_image(
                        'images/configuration@svg.svg',
                        true,
                        [
                            'title' => __('Operation view'),
                            'class' => 'main_menu_icon invert_filter',
                            'style' => 'flex-grow: 1',
                            'id'    => 'img_'.$id,
                        ]
                    ).'</a>';
                $output .= '</div>';

                // OS and alias.
                $output .= '<div class="widget-agent-hive-square-info-body">';
                    $icon = (string) db_get_value(
                        'icon_name',
                        'tconfig_os',
                        'id_os',
                        (int) $data['id_os']
                    );
                    $output .= '<div id="hiveImg_'.$id.'"
                        style="width:20px;height:20px;margin-right: 5px;">';
                        $output .= file_get_contents(
                            ui_get_full_url('images/'.$icon, false, false, false)
                        );
                    $output .= '</div>';
                    $output .= ui_print_truncate_text(
                        ucfirst(io_safe_output($data['alias'])),
                        12,
                        false,
                        true,
                        true,
                        '&hellip;',
                        'font-size: 11pt;color: #14524f;white-space: nowrap;
                            font-weight: 600;text-align: left;width: 80%;
                            overflow: hidden;',
                    );

                $output .= '</div>';

                $style = 'font-size: 6pt; display: flex; justify-content: start;
                    align-items: start; color: #9FA5B1; font-weight: 600;
                    line-height:normal; text-align:left;';
                $style_div = $style.' margin-bottom: 15px;';

                // OS description.
                $output .= html_print_div(
                    [
                        'content' => (empty($data['os_version']) === true)
                            ? ui_print_truncate_text(
                                get_os_name((int) $data['id_os']),
                                32,
                                false,
                                true,
                                true,
                                '&hellip;',
                                $style
                            )
                            : ui_print_truncate_text(
                                $data['os_version'],
                                32,
                                false,
                                true,
                                true,
                                '&hellip;',
                                $style
                            ),
                        'style'   => $style_div,
                    ],
                    true
                );

                // Description.
                $output .= html_print_div(
                    [
                        'content' => ui_print_truncate_text(
                            io_safe_output($data['comentarios']),
                            38,
                            false,
                            true,
                            true,
                            '&hellip;',
                        ),
                        'style'   => 'text-align: left;
                            min-height: 42px; font-size: 8pt;
                            max-height: 42px; line-height: normal;
                            margin: 2px 0px 2px 0px',
                    ],
                    true
                );

                // IP.
                $output .= html_print_div(
                    [
                        'content' => $data['direccion'],
                        'style'   => 'font-size: 10pt;color: #14524f;
                            font-weight: 600;
                            text-align: left;
                            margin-top: 5px',
                    ],
                    true
                );
            $output .= '</div>';
        $output .= '</div>';

        return $output;

    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Agents hive');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'AgentHive';
    }


    /**
     * Get size Modal Configuration.
     *
     * @return array
     */
    public function getSizeModalConfiguration(): array
    {
        $size = [
            'width'  => (is_metaconsole() === true) ? 700 : 600,
            'height' => 610,
        ];

        return $size;
    }


}
