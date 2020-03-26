<?php
/**
 * Widget Agent module Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Widget Agent module
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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

global $config;

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_modules.php';

/**
 * Agent module Widgets.
 */
class AgentModuleWidget extends Widget
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

        // Options.
        $this->values = $this->getOptionsWidget();

        // Positions.
        $this->position = $this->getPositionWidget();

        // Page.
        $this->page = basename(__FILE__);

        // ClassName.
        $class = new \ReflectionClass($this);
        $this->className = $class->getShortName();

        // Title.
        $this->title = __('Agent/Module View');

        // Name.
        if (empty($this->name) === true) {
            $this->name = 'agent_module';
        }

        // This forces at least a first configuration.
        $this->configurationRequired = false;
        if (empty($this->values['moduleId']) === true) {
            $this->configurationRequired = true;
        }

        $this->overflow_scrollbars = false;
    }


        /*
            // DO NOT CHANGE THIS VALUE.
            $id_group = isset($this->options['search_group_'.$id]) ? $this->options['search_group_'.$id] : 0;

            $agent_conf_key = 'id_agent_'.$id;
            $id_agent = $this->options[$agent_conf_key];
            $module_conf_key = 'id_module_'.$id;
            $id_module = $this->options[$module_conf_key];
            $recursion_checked = $this->options['recursion'];

            $this->add_configuration(
            'search_group_'.$id,
            __('Group'),
            OPTION_TREE_GROUP_SELECT
            );

            $list_agents = agents_get_group_agents(
            $id_group,
            false,
            'lower',
            false,
            $recursion_checked
            );

            if (!isset($this->options['selection_agent_module_'.$id])
            || $this->options['selection_agent_module_'.$id] == ''
            || $this->options['selection_agent_module_'.$id] == null
            ) {
            $selection_agent_module = 'common';
            } else {
            $selection_agent_module = $this->options['selection_agent_module_'.$id];
            }

            $limit_common = '';
            $sql = false;
            if (!empty($id_agent)) {
            if ($selection_agent_module == 'common') {
                $limit_common = sprintf(
                    ' AND (SELECT count(nombre)
                    FROM tagente_modulo t2
                    WHERE t2.delete_pending = 0
                    AND t1.nombre = t2.nombre
                    AND t2.id_agente IN (%s)) = (%d)',
                    implode(',', (array) $id_agent),
                    count($id_agent)
                );
            }

            $sql = sprintf(
                'SELECT DISTINCT nombre
                    FROM tagente_modulo t1
                    WHERE id_agente IN (%s)
                        AND  delete_pending = 0 %s ORDER BY nombre',
                implode(', ', (array) $id_agent),
                $limit_common
            );
            }

            if (empty($id_module)) {
            $this->options[$module_conf_key] = index_array(
                db_get_all_rows_sql($sql),
                'nombre',
                'nombre'
            );
            }

            $this->add_configuration(
            'recursion',
            __('Recursion'),
            OPTION_BOOLEAN
            );

            $this->add_configuration(
            $agent_conf_key,
            __('Agent'),
            OPTION_SELECT_MULTISELECTION,
            ['values' => $list_agents]
            );

            $this->add_configuration(
            'selection_agent_module_'.$id,
            __('Show common modules'),
            OPTION_SINGLE_SELECT,
            [
                'values' => [
                    'common' => __('Show common modules'),
                    'all'    => __('Show all modules'),
                ],
            ]
            );

            $this->add_configuration(
            $module_conf_key,
            __('Module'),
            OPTION_SELECT_MULTISELECTION,
            [
                'values' => index_array(
                    db_get_all_rows_sql($sql),
                    'nombre',
                    'nombre'
                ),
            ]
            );

            $this->add_configuration(
            '',
            '',
            OPTION_CUSTOM_INPUT,
            [
                'widget'     => $this,
                'entire_row' => true,
                'update'     => false,
            ]
            );
        */


    function print_configuration_custom($return=true)
    {
        $id = $this->getId();
        ob_start();

        ?>
        <script>
            $(document).ready(function() {

                recalculate_modules_select_agent_module(
                    $("#selection_agent_module_<?php echo $id; ?>").val()
                );

                function recalculate_modules_select_agent_module (selection_mode) {
                    var idAgents = Array();
                    jQuery.each ($('#id_agent_<?php echo $id; ?> option:selected'), function (i, val) {
                        idAgents.push($(val).val());
                    });
                    jQuery.post ('ajax.php',
                        {"page": "operation/agentes/ver_agente",
                        "get_modules_group_value_name_json": 1,
                        "selection": selection_mode == 'all' ? 1 : 0,
                        "id_agents[]": idAgents
                        },
                        function (data, status) {
                            $('#id_module_<?php echo $id; ?>').empty ();
                            if (data) {
                                jQuery.each (data, function (id, value) {
                                    $('#id_module_<?php echo $id; ?>')
                                        .append ($('<option></option>')
                                        .html(value)
                                        .prop("value", value)
                                        .prop("selected", 'selected'));
                                });
                            }
                        },
                        "json"
                    );
                }

                $('#search_group_<?php echo $id; ?>').on('change',function() {
                    jQuery.post ("ajax.php",
                        {
                            "page" : "operation/agentes/ver_agente",
                            "get_agents_group_json" : 1,
                            "id_group" : this.value,
                            "recursion" : ($('#checkbox-recursion-<?php echo $id; ?>').is(':checked')) ? 1 : 0
                        },
                        function (data, status) {
                            $('#id_agent_<?php echo $id; ?>').html('');
                            jQuery.each (data, function(id, value) {
                                // Remove keys_prefix from the index.
                                option = $("<option></option>")
                                    .prop("value", id)
                                    .prop("selected", 'selected')
                                    .html(value);
                                $('#id_agent_<?php echo $id; ?>').append (option);
                            });

                            recalculate_modules_select_agent_module(
                                $("#selection_agent_module_<?php echo $id; ?>").val()
                            );
                        },
                        "json"
                    );
                });

                $('#checkbox-recursion-<?php echo $id; ?>').on('change', function() {
                    ($('#hidden-recursion_sent').val() === '1') ? $('#hidden-recursion_sent').val('0') : $('#hidden-recursion_sent').val('1');
                    jQuery.post ("ajax.php",
                        {
                            "page" : "operation/agentes/ver_agente",
                            "get_agents_group_json" : 1,
                            "id_group" : $('#search_group_<?php echo $id; ?>').val(),
                            "recursion" : ($('#checkbox-recursion-<?php echo $id; ?>').is(':checked')) ? 1 : 0
                        },
                        function (data, status) {
                            $('#id_agent_<?php echo $id; ?>').html('');
                            jQuery.each (data, function(id, value) {
                                // Remove keys_prefix from the index.
                                option = $("<option></option>")
                                    .prop("value", id)
                                    .prop("selected", 'selected')
                                    .html(value);
                                $('#id_agent_<?php echo $id; ?>').append (option);
                            });

                            recalculate_modules_select_agent_module(
                                $("#selection_agent_module_<?php echo $id; ?>").val()
                            );
                        },
                        "json"
                    );
                });

                $("#id_agent_<?php echo $id; ?>").on('change',function () {
                    recalculate_modules_select_agent_module(
                        $("#selection_agent_module_<?php echo $id; ?>").val()
                    );
                });

                $("#selection_agent_module_<?php echo $id; ?>").on('change',function(evt) {
                    recalculate_modules_select_agent_module(
                        $("#selection_agent_module_<?php echo $id; ?>").val()
                    );
                });
            });
        </script>
        <?php
        return ob_get_clean();
    }


    public function generate_data_agent_module($agents, $all_modules)
    {
        $return = [];
        $cont = 0;
        $name = '';

        foreach ($all_modules as $key => $module) {
            if ($module == $name) {
                $modules_by_name[($cont - 1)]['id'][] = $key;
            } else {
                $name = $module;
                $modules_by_name[$cont]['name'] = $name;
                $modules_by_name[$cont]['id'][] = $key;
                $cont ++;
            }
        }

        foreach ($agents as $agent) {
            if (!users_access_to_agent($agent['id_agente'])) {
                continue;
            }

            $row = [];
            $row['agent_status'] = agents_get_status($agent['id_agente'], true);
            $row['agent_name'] = $agent['nombre'];
            $row['agent_alias'] = $agent['alias'];
            $agent_modules = agents_get_modules($agent['id_agente']);

            $row['modules'] = [];
            foreach ($modules_by_name as $module) {
                $row['modules'][$module['name']] = null;
                foreach ($module['id'] as $module_id) {
                    if (array_key_exists($module_id, $agent_modules)) {
                        $row['modules'][$module['name']] = modules_get_agentmodule_status($module_id);
                        break;
                    }
                }
            }

            $return[] = $row;
        }

        return $return;
    }


    public function generate_view_agent_module($visual_data)
    {
        $table_data = '<div>';
        $table_data .= '<table class="widget_agent_module" cellpadding="1" cellspacing="0" border="0" style="background-color: #EEE;">';

        if (!empty($visual_data)) {
            $table_data .= '<th>'.__('Agents').' / '.__('Modules').'</th>';

            $array_names = [];

            foreach ($visual_data as $data) {
                foreach ($data['modules'] as $module_name => $module) {
                    if ($module === null || in_array($module_name, $array_names)) {
                        continue;
                    } else {
                        $array_names[] = $module_name;
                    }
                }
            }

            natcasesort($array_names);
            foreach ($array_names as $module_name) {
                $file_name = ui_print_truncate_text(
                    $module_name,
                    'module_small',
                    false,
                    true,
                    false,
                    '...'
                );
                $table_data .= '<th style="padding: 10px;">'.$file_name.'</th>';
            }

            foreach ($visual_data as $row) {
                $table_data .= "<tr style='height: 35px;'>";
                switch ($row['agent_status']) {
                    case AGENT_STATUS_ALERT_FIRED:
                        $rowcolor = COL_ALERTFIRED;
                        $textcolor = '#000';
                    break;

                    case AGENT_STATUS_CRITICAL:
                        $rowcolor = COL_CRITICAL;
                        $textcolor = '#FFF';
                    break;

                    case AGENT_STATUS_WARNING:
                        $rowcolor = COL_WARNING;
                        $textcolor = '#000';
                    break;

                    case AGENT_STATUS_NORMAL:
                        $rowcolor = COL_NORMAL;
                        $textcolor = '#FFF';
                    break;

                    case AGENT_STATUS_UNKNOWN:
                    case AGENT_STATUS_ALL:
                    default:
                        $rowcolor = COL_UNKNOWN;
                        $textcolor = '#FFF';
                    break;
                }

                $file_name = ui_print_truncate_text(
                    $row['agent_alias'],
                    'agent_small',
                    false,
                    true,
                    false,
                    '...'
                );
                $table_data .= "<td style='background-color: ".$rowcolor.";'>".$file_name.'</td>';

                foreach ($row['modules'] as $module_name => $module) {
                    if ($module === null) {
                        if (in_array($module_name, $array_names)) {
                            $table_data .= "<td style='background-color: #DDD;'></td>";
                        } else {
                            continue;
                        }
                    } else {
                        $table_data .= "<td style='text-align: center; background-color: #DDD;'>";
                        switch ($module) {
                            case AGENT_STATUS_NORMAL:
                                $table_data .= ui_print_status_image(
                                    'module_ok.png',
                                    __(
                                        '%s in %s : NORMAL',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;

                            case AGENT_STATUS_CRITICAL:
                                $table_data .= ui_print_status_image(
                                    'module_critical.png',
                                    __(
                                        '%s in %s : CRITICAL',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;

                            case AGENT_STATUS_WARNING:
                                $table_data .= ui_print_status_image(
                                    'module_warning.png',
                                    __(
                                        '%s in %s : WARNING',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;

                            case AGENT_STATUS_UNKNOWN:
                                $table_data .= ui_print_status_image(
                                    'module_unknown.png',
                                    __(
                                        '%s in %s : UNKNOWN',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;

                            case 4:
                                $table_data .= ui_print_status_image(
                                    'module_no_data.png',
                                    __(
                                        '%s in %s : Not initialize',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;

                            case AGENT_STATUS_ALERT_FIRED:
                            default:
                                $table_data .= ui_print_status_image(
                                    'module_alertsfired.png',
                                    __(
                                        '%s in %s : ALERTS FIRED',
                                        $module_name,
                                        $row['agent_alias']
                                    ),
                                    true,
                                    [
                                        'width'  => '20px',
                                        'height' => '20px',
                                    ]
                                );
                            break;
                        }

                        $table_data .= '</td>';
                    }
                }

                $table_data .= '</tr>';
            }
        } else {
            $table_data .= '<tr><td>'.__('Please configure this widget before usage').'</td></tr>';
        }

        $table_data .= '</table>';
        $table_data .= '</div>';

        return $table_data;
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

        // Autocomplete agents.
        $inputs[] = [
            'label'     => __('Agent'),
            'arguments' => [
                'type'               => 'autocomplete_agent',
                'name'               => 'agentAlias',
                'id_agent_hidden'    => $values['agentId'],
                'name_agent_hidden'  => 'agentId',
                'server_id_hidden'   => $values['metaconsoleId'],
                'name_server_hidden' => 'metaconsoleId',
                'return'             => true,
                'module_input'       => true,
                'module_name'        => 'moduleId',
                'module_none'        => false,
                'size'               => 0,
            ],
        ];

        // Autocomplete module.
        $inputs[] = [
            'label'     => __('Module'),
            'arguments' => [
                'type'           => 'autocomplete_module',
                'fields'         => $fields,
                'name'           => 'moduleId',
                'selected'       => $values['moduleId'],
                'return'         => true,
                'sort'           => false,
                'agent_id'       => $values['agentId'],
                'metaconsole_id' => $values['metaconsoleId'],
                'style'          => 'width: inherit;',
            ],
        ];

        return $inputs;
    }


    /**
     * Get Post for widget.
     *
     * @return array
     */
    public function getPost():array
    {
        // Retrieve global - common inputs.
        $values = parent::getPost();

        $values['agentId'] = \get_parameter('agentId', 0);
        $values['metaconsoleId'] = \get_parameter('metaconsoleId', 0);
        $values['moduleId'] = \get_parameter('moduleId', 0);
        $values['period'] = \get_parameter('period', 0);
        $values['showLegend'] = \get_parameter_switch('showLegend');

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

        // TODO:XXX WIP.
        return 'WIP in this widget';
        /*
            $this->body = '';
            $id_agent = $this->options['id_agent_'.$this->getId()];
            if (! check_acl($config['id_user'], 0, 'AR')) {
            $this->body = __('You don\'t have access');
            return;
            }

            $id_module = $this->options['id_module_'.$this->getId()];
            if ($id_agent) {
            $sql = 'SELECT id_agente,nombre,alias
                        FROM tagente
                        WHERE id_agente IN ('.implode(',', $id_agent).')
                        ORDER BY id_agente';
            $agents = db_get_all_rows_sql($sql);
            if ($agents === false) {
                $agents = [];
            }

            $sql = 'SELECT id_agente_modulo,nombre
                        FROM tagente_modulo
                        WHERE id_agente IN ('.implode(',', $id_agent).")
                        AND nombre IN ('".implode("','", $id_module)."')
                            AND  delete_pending = 0 ORDER BY nombre";
            $modules = index_array(db_get_all_rows_sql($sql), 'id_agente_modulo', 'nombre');
            if ($modules === false) {
                $modules = [];
            }
            } else {
            $agents = [];
            $modules = [];
            }

            $visual_data = $this->generate_data_agent_module($agents, $modules);

            $this->body .= $this->generate_view_agent_module($visual_data);
        */
    }


    /**
     * Get description.
     *
     * @return string.
     */
    public static function getDescription()
    {
        return __('Agent/Module View');
    }


    /**
     * Get Name.
     *
     * @return string.
     */
    public static function getName()
    {
        return 'agent_module';
    }


}

$instance = new AgentModuleWidget(false);
