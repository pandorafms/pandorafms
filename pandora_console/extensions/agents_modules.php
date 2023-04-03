<?php
/**
 * Agents/Modules Monitoring view.
 *
 * @category   Operations
 * @package    Pandora FMS
 * @subpackage Opensource
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
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

// Begin.


/**
 * Extra JS.
 *
 * @return void
 */
function agents_modules_load_js()
{
    $ignored_params['refresh'] = '';

    ?>
    <script type='text/javascript'>
        $(document).ready(function () {
            //Get max width of name of modules
            max_width = 0;
            $.each($('.th_class_module_r'), function (i, elem) {
                id = $(elem).attr('id').replace('th_module_r_', '');
                
                width = $("#div_module_r_" + id).width();
                
                if (max_width < width) {
                    max_width = width;
                } 
            });
            
            $.each($('.th_class_module_r'), function (i, elem) {
                id = $(elem).attr('id').replace('th_module_r_', '');
                $("#th_module_r_" + id).height(($("#div_module_r_" + id).width() + 10) + 'px');
                $("#div_module_r_" + id).css('margin-top', (max_width - 20) + 'px');
                $("#div_module_r_" + id).show();
            });

            var refr = '<?php echo get_parameter('refresh', 0); ?>';
            var pure = '<?php echo get_parameter('pure', 0); ?>';
            var href =' <?php echo ui_get_url_refresh($ignored_params); ?>';

            if (pure) {
                var startCountDown = function (duration, cb) {
                    $('div.vc-countdown').countdown('destroy');
                    if (!duration) return;
                    var t = new Date();
                    t.setTime(t.getTime() + duration * 1000);
                    $('div.vc-countdown').countdown({
                        until: t,
                        format: 'MS',
                        layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>) ',
                        alwaysExpire: true,
                        onExpiry: function () {
                            $('div.vc-countdown').countdown('destroy');
                            url = js_html_entity_decode( href ) + duration;
                            $(document).attr ("location", url);
                        }
                    });
                }

                if(refr>0){
                    startCountDown(refr, false);
                }

                var controls = document.getElementById('vc-controls');
                autoHideElement(controls, 1000);

                $('select#refresh').change(function (event) {
                    refr = Number.parseInt(event.target.value, 10);
                    startCountDown(refr, false);
                });
            }
            else {
                var agentes_id = $("#id_agents2").val();
                var id_agentes = getQueryParam("full_agents_id");
                if (agentes_id === null && id_agentes !== null) {
                    id_agentes = id_agentes.split(";")
                    id_agentes.forEach(function(element) {
                        $("#id_agents2 option[value="+ element +"]").attr("selected",true);
                    });

                    selection_agent_module();
                }

                $('#refresh').change(function () {
                    $('#hidden-vc_refr').val($('#refresh option:selected').val());
                });
            }

            $("#group_id").change (function () {
                jQuery.post ("ajax.php",
                    {"page" : "operation/agentes/ver_agente",
                        "get_agents_group_json" : 1,
                        "id_group" : this.value,
                        "privilege" : "AW",
                        "keys_prefix" : "_",
                        "recursion" : $('#checkbox-recursion').is(':checked')
                    },
                    function (data, status) {
                        $("#id_agents2").html('');
                        $("#module").html('');
                        jQuery.each (data, function (id, value) {
                            // Remove keys_prefix from the index
                            id = id.substring(1);
                            option = $("<option></option>")
                                .attr ("value", value["id_agente"])
                                .html (value["alias"]);
                            $("#id_agents").append (option);
                            $("#id_agents2").append (option);
                        });
                    },
                    "json"
                );
            });

            $("#checkbox-recursion").change (function () {
                jQuery.post ("ajax.php",
                    {"page" : "operation/agentes/ver_agente",
                        "get_agents_group_json" : 1,
                        "id_group" :     $("#group_id").val(),
                        "privilege" : "AW",
                        "keys_prefix" : "_",
                        "recursion" : $('#checkbox-recursion').is(':checked')
                    },
                    function (data, status) {
                        $("#id_agents2").html('');
                        $("#module").html('');
                        jQuery.each (data, function (id, value) {
                            // Remove keys_prefix from the index
                            id = id.substring(1);
                            option = $("<option></option>")
                                .attr ("value", value["id_agente"])
                                .html (value["alias"]);
                            $("#id_agents").append (option);
                            $("#id_agents2").append (option);
                        });
                    },
                    "json"
                );
            });

            $("#modulegroup").change (function () {
                jQuery.post ("ajax.php",
                    {"page" : "operation/agentes/ver_agente",
                        "get_modules_group_json" : 1,
                        "id_module_group" : this.value,
                        "id_agents" : $("#id_agents2").val(),
                        "selection" : $("#selection_agent_module").val()
                    },
                    function (data, status) {
                        $("#module").html('');
                        if(data){
                            jQuery.each (data, function (id, value) {
                                option = $("<option></option>")
                                    .attr ("value", id)
                                    .html (value);
                                $("#module").append (option);
                            });
                        }
                    },
                    "json"
                );
            });

            $("#id_agents2").click (function(){
                selection_agent_module();
            });

            $("#selection_agent_module").change(function() {
                jQuery.post ("ajax.php",
                    {"page" : "operation/agentes/ver_agente",
                        "get_modules_group_json" : 1,
                        "id_module_group" : $("#modulegroup").val(),
                        "id_agents" : $("#id_agents2").val(),
                        "selection" : $("#selection_agent_module").val()
                    },
                    function (data, status) {
                        $("#module").html('');
                        if(data){
                            jQuery.each (data, function (id, value) {
                                option = $("<option></option>")
                                    .attr ("value", id)
                                    .html (value);
                                $("#module").append (option);
                            });
                        }
                    },
                    "json"
                );
            });
        });

        function selection_agent_module() {
            jQuery.post ("ajax.php",
                {"page" : "operation/agentes/ver_agente",
                    "get_modules_group_json" : 1,
                    "id_module_group" : $("#modulegroup").val(),
                    "id_agents" : $("#id_agents2").val(),
                    "selection" : $("#selection_agent_module").val()
                },
                function (data, status) {
                    $("#module").html('');
                    if(data){
                        jQuery.each (data, function (id, value) {
                            option = $("<option></option>")
                                .attr ("value", id)
                                .html (value);
                            $("#module").append (option);
                        });

                        var id_modules = getQueryParam("full_modules_selected");
                        if(id_modules !== null) {
                            id_modules = id_modules.split(";");
                            id_modules.forEach(function(element) {
                                $("#module option[value="+ element +"]").attr("selected",true);
                            });
                        }
                    }
                },
                "json"
            );
        }

        function getQueryParam (key) {
            key = key.replace(/[[]/, '[');
            key = key.replace(/[]]/, ']');
            var pattern = "[?&]" + key + "=([^&#]*)";
            var regex = new RegExp(pattern);
            var url = unescape(window.location.href);
            var results = regex.exec(url);
            if (results === null) {
                return null;
            } else {
                return results[1];
            }
        }

        function select_selected () {
            // $('#id_agents2 option').each(function(){
            //     if($(this).attr('selected') === 'selected'){
            //         $(this).prop('selected', true);
            //     }
            // });
        }
    </script>
    <?php
}


/**
 * Main method.
 *
 * @return void
 */
function mainAgentsModules()
{
    global $config;

    // Load global vars.
    include_once 'include/config.php';
    include_once 'include/functions_reporting.php';
    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions_users.php';

    check_login();
    // ACL Check.
    if (! check_acl($config['id_user'], 0, 'AR')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Agent view (Grouped)'
        );
        include 'general/noaccess.php';
        exit;
    }

    // JS.
    agents_modules_load_js();

    // Update network modules for this group
    // Check for Network FLAG change request
    // Made it a subquery, much faster on both the database and server side.
    if (isset($_GET['update_netgroup'])) {
        $group = get_parameter_get('update_netgroup', 0);
        if (check_acl($config['id_user'], $group, 'AW')) {
            $where = ['id_agente' => 'ANY(SELECT id_agente FROM tagente WHERE id_grupo = '.$group];

            db_process_sql_update('tagente_modulo', ['flag' => 1], $where);
        } else {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to set flag for groups'
            );
            include 'general/noaccess.php';
            exit;
        }
    }

    if ($config['realtimestats'] == 0) {
        $updated_info = __('Last update').' : '.ui_print_timestamp(db_get_sql('SELECT min(utimestamp) FROM tgroup_stat'), true);
    } else {
        // $updated_info = __("Updated at realtime");
        $updated_info = '';
    }

    $updated_time = $updated_info;

    $modulegroup = get_parameter('modulegroup', 0);
    $refr = (int) get_parameter('refresh', 0);
    // By default 30 seconds.
    $recursion = get_parameter('recursion', 0);
    $group_id = (int) get_parameter('group_id', 0);
    $offset = (int) get_parameter('offset', 0);
    $hor_offset = (int) get_parameter('hor_offset', 0);
    $block = $config['block_size'];
    if (intval($block) > 15) {
        $block = '15';
    }

    if (get_parameter('modulegroup') != null) {
        $agents_id = (array) get_parameter('id_agents2', null);
    }

    $selection_a_m = (int) get_parameter('selection_agent_module');
    $modules_selected = (array) get_parameter('module', 0);
    $update_item = (string) get_parameter('edit_item', '');
    $save_serialize = (int) get_parameter('save_serialize', 0);
    $full_modules_selected = explode(';', get_parameter('full_modules_selected', 0));
    $full_agents_id = explode(';', get_parameter('full_agents_id', 0));
    $show_type = (int) get_parameter('show_type', 0);

    // In full screen there is no pagination neither filters.
    if (( ($config['pure'] == 0 && $save_serialize) && $update_item == '' ) || ( ($config['pure'] == 1 && $save_serialize == 0) && $update_item == '' )) {
        $unserialize_modules_selected  = unserialize_in_temp($config['id_user'].'_agent_module', true, 1);
        $unserialize_agents_id         = unserialize_in_temp($config['id_user'].'_agents', true, 1);
        if ($unserialize_modules_selected) {
            $modules_selected = $unserialize_modules_selected;
        }

        if ($unserialize_agents_id) {
            $agents_id = $unserialize_agents_id;
        }
    } else {
        unserialize_in_temp($config['id_user'].'_agent_module', true, 1);
        unserialize_in_temp($config['id_user'].'_agents', true, 1);
    }

    if (isset($modules_selected[0]) === true && $modules_selected[0]) {
        serialize_in_temp($modules_selected, $config['id_user'].'_agent_module', 1);
    }

    if (isset($agents_id[0]) === true && $agents_id[0] != -1) {
        serialize_in_temp($agents_id, $config['id_user'].'_agents', 1);
    }

    if ($config['pure'] == 0) {
        if ($modules_selected[0] && $agents_id[0]) {
            $full_modules = urlencode(implode(';', $modules_selected));
            $full_agents = urlencode(implode(';', $agents_id));

            $fullscreen['text'] = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_modules&amp;pure=1&amp;
			offset='.$offset.'&group_id='.$group_id.'&modulegroup='.$modulegroup.'&refresh='.$refr.'&full_modules_selected='.$full_modules.'&show_type='.$show_type.'
			&full_agents_id='.$full_agents.'&selection_agent_module='.$selection_a_m.'">'.html_print_image('images/fullscreen@svg.svg', true, ['title' => __('Full screen mode'), 'class' => 'invert_filter']).'</a>';
        } else if ($full_modules_selected[0] && $full_agents_id[0]) {
            $full_modules = urlencode(implode(';', $full_modules_selected));
            $full_agents = urlencode(implode(';', $full_agents_id));

            $fullscreen['text'] = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_modules&amp;pure=1&amp;
			offset='.$offset.'&group_id='.$group_id.'&modulegroup='.$modulegroup.'&refresh='.$refr.'&full_modules_selected='.$full_modules.'&show_type='.$show_type.'
			&full_agents_id='.$full_agents.'&selection_agent_module='.$selection_a_m.'">'.html_print_image('images/fullscreen@svg.svg', true, ['title' => __('Full screen mode'), 'class' => 'invert_filter']).'</a>';
        } else {
            $fullscreen['text'] = '<a href="index.php?extension_in_menu=estado&amp;sec=extensions&amp;sec2=extensions/agents_modules&amp;pure=1&amp;
			offset='.$offset.'&group_id='.$group_id.'&modulegroup='.$modulegroup.'&refresh='.$refr.'&show_type='.$show_type.'">'.html_print_image('images/fullscreen@svg.svg', true, ['title' => __('Full screen mode'), 'class' => 'invert_filter']).'</a>';
        }
    }

    // Type show.
    $show_select = [
        0 => __('Show module status'),
        1 => __('Show module data'),
    ];

    $filter_type = html_print_label_input_block(
        __('Information to be shown'),
        html_print_select(
            $show_select,
            'show_type',
            $show_type,
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            false,
            'width: 100%;'
        )
    );

    $filter_groups = html_print_label_input_block(
        __('Group'),
        html_print_select_groups(
            false,
            'AR',
            true,
            'group_id',
            $group_id,
            '',
            '',
            '',
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    $filter_groups .= html_print_label_input_block(
        __('Recursion'),
        html_print_checkbox_switch('recursion', 1, 0, true),
        [
            'div_class'   => 'add-input-reverse',
            'label_class' => 'label-thin',
        ]
    );

    $filter_module_groups = html_print_label_input_block(
        __('Module group'),
        html_print_select_from_sql(
            'SELECT * FROM tmodule_group ORDER BY name',
            'modulegroup',
            $modulegroup,
            '',
            __('All'),
            0,
            true,
            false,
            true,
            false,
            'width: 100%;'
        )
    );

    $agents = agents_get_group_agents($group_id);
    if ((empty($agents)) || $agents == -1) {
        $agents = [];
    }

    $filter_agents = html_print_label_input_block(
        __('Agents'),
        html_print_select(
            $agents,
            'id_agents2[]',
            $agents_id,
            '',
            '',
            0,
            true,
            true,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    // Type show.
    $selection = [
        0 => __('Show common modules'),
        1 => __('Show all modules'),
    ];
    $filter_type_show = html_print_label_input_block(
        __('Show common modules'),
        html_print_select(
            $selection,
            'selection_agent_module',
            $selection_a_m,
            '',
            '',
            0,
            true,
            false,
            true,
            '',
            false,
            'width: 100%;'
        )
    );

    // Modules.
    $all_modules = select_modules_for_agent_group($group_id, $agents_id, $selection_a_m, false);
    $filter_modules = html_print_label_input_block(
        __('Module'),
        html_print_select(
            $all_modules,
            'module[]',
            $modules_selected,
            '',
            '',
            0,
            true,
            true,
            false,
            '',
            false,
            'width: 100%;'
        )
    );

    $onheader = [
        'updated_time' => $updated_time,
        'fullscreen'   => $fullscreen,
    ];

    /*
     * Old style table, we need a lot of special formatting,don't use table function.
     * Prepare old-style table.
     */

    if ($config['pure'] == 0) {
        // Header.
        ui_print_standard_header(
            __('Agents/Modules'),
            'images/module.png',
            false,
            '',
            false,
            $onheader,
            [
                [
                    'link'  => '',
                    'label' => __('Monitoring'),
                ],
                [
                    'link'  => '',
                    'label' => __('Views'),
                ],
            ]
        );
    } else {
        if ($full_agents_id[0]) {
            $full_modules = urlencode(implode(';', $full_modules_selected));
            $full_agents = urlencode(implode(';', $full_agents_id));

            $url = sprintf(
                'index.php?sec=view&sec2=extensions/agents_modules&pure=0&offset=%s
			    &group_id=%s&modulegroup=%s&refresh=%s&full_modules_selected=%s
			    &full_agents_id=%s&selection_agent_module=%s&show_type=%s',
                $offset,
                $group_id,
                $modulegroup,
                $refr,
                $full_modules,
                $full_agents,
                $selection_a_m,
                $show_type
            );
        } else {
            $url = sprintf(
                'index.php?sec=view&sec2=extensions/agents_modules&pure=0&offset=%s&group_id=%s
                &modulegroup=%s&refresh=%s&show_type=%s',
                $offset,
                $group_id,
                $modulegroup,
                $refr,
                $show_type
            );
        }

        // Floating menu - Start.
        echo '<div id="vc-controls" class="zindex999">';

        echo '<div id="menu_tab">';
        echo '<ul class="mn">';

        // Quit fullscreen.
        echo '<li class="nomn">';
        echo '<a target="_top" href="'.$url.'">';
        echo html_print_image(
            'images/exit_fullscreen@svg.svg',
            true,
            [
                'title' => __('Back to normal mode'),
                'class' => 'main_menu_icon invert_filter',
            ]
        );
        echo '</a>';
        echo '</li>';

        // Countdown.
        echo '<li class="nomn">';
        echo '<div class="vc-refr">';
        echo '<div class="vc-countdown inline_line"></div>';
        echo '<div id="vc-refr-form">';
        echo __('Refresh').':';
        echo html_print_select(
            get_refresh_time_array(),
            'refresh',
            $refr,
            '',
            '',
            0,
            true,
            false,
            false,
            '',
            false,
            'margin-top: 3px;'
        );
        echo '</div>';
        echo '</div>';
        echo '</li>';

        // Console name.
        echo '<li class="nomn">';
        echo '<div class="vc-title">'.__('Agent/module view').'</div>';
        echo '</li>';

        echo '</ul>';
        echo '</div>';

        echo '</div>';
        // Floating menu - End.
        ui_require_jquery_file('countdown');
    }

    if ($config['pure'] != 1) {
        $show_filters = '<form method="post" action="index.php?sec=view&sec2=extensions/agents_modules" class="w100p">';
        $show_filters .= '<table class="filter-table-adv w100p no-border" cellpadding="4" cellspacing="4">';
            $show_filters .= '<tr>';
                $show_filters .= '<td width="33%">'.$filter_type.'</td>';
                $show_filters .= '<td width="33%">'.$filter_groups.'</td>';
                $show_filters .= '<td width="33%">'.$filter_module_groups.'</td>';
            $show_filters .= '</tr>';
            $show_filters .= '<tr>';
                $show_filters .= '<td>'.$filter_agents.'</td>';
                $show_filters .= '<td>'.$filter_type_show.'</td>';
                $show_filters .= '<td>'.$filter_modules.'</td>';
            $show_filters .= '</tr>';
        $show_filters .= '</table>';
        $show_filters .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Filter'),
                    'srcbutton',
                    false,
                    [
                        'icon'    => 'search',
                        'mode'    => 'mini',
                        'onclick' => 'select_selected()',
                    ],
                    true
                ),
            ],
            true
        );
        $show_filters .= '</form>';

        ui_toggle(
            $show_filters,
            '<span class="subsection_header_title">'.__('Filters ').'</span>'.ui_print_help_tip(__('Secondary groups and agent subgroups will be taken into account.'), true),
            'filter_form',
            '',
            true,
            false,
            '',
            'white-box-content',
            'box-flat white_table_graph fixed_filter_bar'
        );
    }

    if (isset($agents_id[0]) === true && $agents_id[0] != -1) {
        $agents = $agents_id;
    } else {
        if (isset($full_agents_id[0]) === true && $full_agents_id[0]) {
            $agents = $full_agents_id;
        } else {
            $agents = '';
            $agents = agents_get_group_agents($group_id, ['disabled' => 0]);
            $agents = array_keys($agents);
        }
    }

    $filter_module_group = ['disabled' => 0];

    if ($modulegroup > 0) {
        $filter_module_group['id_module_group'] = $modulegroup;
    }

    $count = 0;
    foreach ($agents as $agent) {
        // TODO TAGS agents_get_modules.
        $module = agents_get_modules(
            $agent,
            false,
            $filter_module_group,
            true,
            true
        );
        if ($module == false) {
            unset($agents[$count]);
        }

        $count++;
    }

    $total_pagination = count($agents);
    if (isset($agents_id[0]) === true && $agents_id[0] != -1) {
        if (isset($modules_selected[0]) === true && $modules_selected[0]) {
            $all_modules = [];
            foreach ($modules_selected as $key => $value) {
                if ((int) $value > 0) {
                    $name = modules_get_agentmodule_name($value);
                    $sql = "SELECT id_agente_modulo
                            FROM tagente_modulo
                            WHERE nombre = '".$name."';";

                    $result_sql = db_get_all_rows_sql($sql);

                    if (is_array($result_sql)) {
                        foreach ($result_sql as $key => $value) {
                            $all_modules[$value['id_agente_modulo']] = io_safe_output($name);
                        }
                    }
                } else {
                    $name = $value;
                    $sql = "SELECT id_agente_modulo
                            FROM tagente_modulo
                            WHERE nombre = '".$name."';";

                    $result_sql = db_get_all_rows_sql($sql);

                    if (is_array($result_sql)) {
                        foreach ($result_sql as $key => $value) {
                            $all_modules[$value['id_agente_modulo']] = io_safe_output($name);
                        }
                    }
                }
            }
        } else {
            $all_modules = agents_get_modules(
                $agents,
                false,
                $filter_module_group,
                true,
                true
            );
        }
    } else {
        if (isset($full_modules_selected[0]) === true && $full_modules_selected[0]) {
            foreach ($full_modules_selected as $key => $value) {
                $name = modules_get_agentmodule_name($value);
                $sql = "SELECT id_agente_modulo 
						FROM tagente_modulo 
						WHERE nombre = '".$name."';";

                $result_sql = db_get_all_rows_sql($sql);
                if (is_array($result_sql)) {
                    foreach ($result_sql as $key => $value) {
                        $all_modules[$value['id_agente_modulo']] = io_safe_output($name);
                    }
                }
            }
        } else {
            // TODO TAGS agents_get_modules.
            $all_modules = agents_get_modules(
                $agents,
                false,
                $filter_module_group,
                true,
                true
            );
        }
    }

    $modules_by_name = [];
    $name = '';
    $cont = 0;
    foreach ($all_modules as $key => $module) {
        if ($module == $name) {
            $modules_by_name[($cont - 1)]['id'][] = $key;
        } else {
            $name = $module;
            $modules_by_name[$cont]['name'] = $name;
            $modules_by_name[$cont]['id'][] = $key;
            $cont++;
        }
    }

    if ($config['pure'] == 1) {
        $block = count($modules_by_name);
    }

    if ($update_item == '') {
        $filter_groups = [
            'offset'    => (int) $offset,
            'limit'     => (int) $config['block_size'],
            'disabled'  => 0,
            'id_agente' => $agents,
        ];
    } else {
        $filter_groups = [
            'offset'    => 0,
            'limit'     => (int) $config['block_size'],
            'disabled'  => 0,
            'id_agente' => $agents,
        ];
    }

    if ($group_id > 0) {
        if ($recursion) {
            $filter_groups['id_grupo'] = array_merge(
                $group_id,
                groups_get_children_ids($group_id, true)
            );
        } else {
            $filter_groups['id_grupo'] = $group_id;
        }
    }

    if (!empty($filter_groups['id_agente'])) {
        $agents = agents_get_agents($filter_groups);
    }

    $nagents = count($agents);

    if ($all_modules == false || $agents == false) {
        ui_print_info_message(['no_close' => true, 'message' => __('There are no agents with modules') ]);
        return;
    }

    echo '<table cellpadding="4" cellspacing="4" border="0" class="info_table mrgn_btn_20px">';

    echo '<tr>';

    echo "<th width='40px' class='pdd_r_10px align_left'>".__('Agents').' / '.__('Modules').'</th>';

    if ($hor_offset > 0) {
        $new_hor_offset = ($hor_offset - $block);
        echo "<th width='20px' class='vertical_middle center' rowspan='".($nagents + 1)."'><a href='index.php?".'extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&refr=0&save_serialize=1&show_type='.$show_type.'&selection_a_m='.$selection_a_m.'&hor_offset='.$new_hor_offset.'&offset='.$offset."'>".html_print_image(
            'images/arrow_left_green.png',
            true,
            ['title' => __('Previous modules')]
        ).'</a></th>';
    }

    $nmodules = 0;
    foreach ($modules_by_name as $module) {
        $nmodules++;

        if ($nmodules <= $hor_offset || $nmodules > ($hor_offset + $block)) {
            continue;
        }

        $text = ui_print_truncate_text(io_safe_output($module['name']), 'module_small');

        echo '<th align="center" width="20px" id="th_module_r_'.$nmodules.'" class="th_class_module_r">
				<div class="w30px">
					<div id="div_module_r_'.$nmodules.'"  class="rotate_text_module invisible padding-lft-10">'.$text.'</div>
				</div>
			</th>';
    }

    if (($hor_offset + $block) < $nmodules) {
        $new_hor_offset = ($hor_offset + $block);
        echo "<th width='20px' class='vertical_middle center' rowspan='".($nagents + 1)."'><a href='index.php?".'extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&save_serialize=1&show_type='.$show_type.'&selection_a_m='.$selection_a_m.'&hor_offset='.$new_hor_offset.'&offset='.$offset."'>".html_print_image(
            'images/arrow_right_green.png',
            true,
            ['title' => __('More modules')]
        ).'</a></th>';
    }

    echo '</tr>';

    $filter_agents = [
        'offset'   => (int) $offset,
        'disabled' => 0,
    ];
    if ($group_id > 0) {
        $filter_agents['id_grupo'] = $group_id;
    }

    // Prepare pagination.
    $url = 'index.php?extension_in_menu=estado&sec=extensions&sec2=extensions/agents_modules&save_serialize=1&hor_offset='.$hor_offset.'&selection_a_m='.$selection_a_m;
    $tablePagination = ui_pagination(
        $total_pagination,
        $url,
        0,
        0,
        true,
        'offset',
        false
    );

    html_print_action_buttons(
        '',
        [ 'right_content' => $tablePagination ]
    );

    foreach ($agents as $agent) {
        // Get stats for this group.
        $agent_status = agents_get_status($agent['id_agente']);
        $alias = db_get_row('tagente', 'id_agente', $agent['id_agente']);
        if (empty($alias['alias'])) {
            $alias['alias'] = $agent['nombre'];
        }

        switch ($agent_status) {
            case 4:
                // Alert fired status.
                $rowcolor = 'group_view_alrm';
            break;

            case 1:
                // Critical status.
                $rowcolor = 'group_view_crit';
            break;

            case 2:
                // Warning status.
                $rowcolor = 'group_view_warn';
            break;

            case 0:
                // Normal status.
                $rowcolor = 'group_view_ok';
            break;

            case 3:
            case -1:
            default:
                // Unknown status.
                $rowcolor = 'group_view_unk';
            break;
        }

        echo "<tr class='height_25px'>";

        echo "<td class='$rowcolor'>
			<a class='$rowcolor' href='index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=".$agent['id_agente']."'>".$alias['alias'].'</a></td>';
        // TODO TAGS agents_get_modules.
        $agent_modules = agents_get_modules($agent['id_agente'], false, $filter_module_group, true, true);

        $nmodules = 0;
        foreach ($modules_by_name as $module) {
            $nmodules++;

            if ($nmodules <= $hor_offset || $nmodules > ($hor_offset + $block)) {
                continue;
            }

            $match = false;

            foreach ($module['id'] as $module_id) {
                if (!$match && array_key_exists($module_id, $agent_modules)) {
                    echo "<td class='center' style='text-align:left;'>";
                    $win_handle = dechex(crc32($module_id.$module['name']));
                    $graph_type = return_graphtype(modules_get_agentmodule_type($module_id));
                    $link = "winopeng_var('".'operation/agentes/stat_win.php?'."type=$graph_type&".'period='.SECONDS_1DAY.'&'.'id='.$module_id.'&'.'refresh='.SECONDS_10MINUTES."', 'day_".$win_handle."', 800, 480)";

                    echo '<a href="javascript:'.$link.'">';

                    $module_last_value = modules_get_last_value($module_id);
                    if (!is_numeric($module_last_value)) {
                        $module_last_value = htmlspecialchars($module_last_value);
                    }

                    if ($show_type === 0) {
                        $status = modules_get_agentmodule_status($module_id);
                        switch ($status) {
                            case AGENT_MODULE_STATUS_NORMAL:
                                ui_print_status_image('module_ok.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_CRITICAL_BAD:
                                ui_print_status_image('module_critical.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_WARNING:
                                ui_print_status_image('module_warning.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_UNKNOWN:
                                ui_print_status_image('module_unknown.png', $module_last_value, false);
                            break;

                            case AGENT_MODULE_STATUS_NORMAL_ALERT:
                            case AGENT_MODULE_STATUS_WARNING_ALERT:
                            case AGENT_MODULE_STATUS_CRITICAL_ALERT:
                                ui_print_status_image('module_alertsfired.png', $module_last_value, false);
                            break;

                            case 4:
                                ui_print_status_image('module_no_data.png', $module_last_value, false);
                            break;
                        }
                    } else {
                        $module = modules_get_agentmodule($module_id);
                        $module['datos'] = $module_last_value;

                        $module_last_value = modules_get_agentmodule_data_for_humans($module);

                        echo $module_last_value;
                    }

                    echo '</a>';
                    echo '</td>';
                    $match = true;
                }
            }

            if (!$match) {
                echo '<td></td>';
            }
        }

        echo '</tr>';
    }

    echo '</table>';

    if ($show_type === 0) {
        $show_legend = "<div class='legend_white'>";
        $show_legend .= "<div class='center flex'>
                <div class='legend_square_simple'><div style='background-color: ".COL_ALERTFIRED.";'></div></div>".__('Orange cell when the module has fired alerts').'</div>';
        $show_legend .= "<div class='center flex'>
                <div class='legend_square_simple'><div style='background-color: ".COL_CRITICAL.";'></div></div>".__('Red cell when the module has a critical status').'
            </div>';
        $show_legend .= "<div class='center flex'>
            <div class='legend_square_simple'><div style='background-color: ".COL_WARNING.";'></div></div>".__('Yellow cell when the module has a warning status').'</div>';
        $show_legend .= "<div class='center flex'>
            <div class='legend_square_simple'><div style='background-color: ".COL_NORMAL.";'></div></div>".__('Green cell when the module has a normal status').'</div>';
        $show_legend .= "<div class='center flex'>
            <div class='legend_square_simple'><div style='background-color: ".COL_UNKNOWN.";'></div></div>".__('Grey cell when the module has an unknown status').'</div>';
        $show_legend .= "<div class='center flex'>
            <div class='legend_square_simple'><div style='background-color: ".COL_NOTINIT.";'></div></div>".__("Cell turns blue when the module is in 'not initialize' status").'</div>';
        $show_legend .= '</div>';
        ui_toggle($show_legend, __('Legend'));
    }

    $pure_var = $config['pure'];
    if ($pure_var) {
        $pure_var = 1;
    } else {
        $pure_var = 0;
    }
}


extensions_add_operation_menu_option(__('Agents/Modules view'), 'estado', 'agents_modules/icon_menu.png', 'v1r1', 'view');
extensions_add_main_function('mainAgentsModules');
