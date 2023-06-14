<?php
/**
 * Alerts Status
 *
 * @category   Alerts
 * @package    Pandora FMS
 * @subpackage Alert Status View
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

global $config;

// Login check.
check_login();

if (is_ajax()) {
    include_once 'include/functions_reporting.php';

    $get_alert_fired = get_parameter('get_alert_fired', 0);

    if ($get_alert_fired) {
        // Calculate alerts fired.
        $data_reporting = reporting_get_group_stats();
        echo $data_reporting['monitor_alerts_fired'];
    }

    return;
}

require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/operation/agentes/alerts_status.functions.php';
require_once $config['homedir'].'/include/functions_users.php';

$isFunctionPolicies = enterprise_include_once('include/functions_policies.php');

$strict_user = db_get_value('strict_acl', 'tusuario', 'id_user', $config['id_user']);

$disabled = get_parameter('disabled', 'all_enabled');
$filter_standby = get_parameter('standby', 'all');
$id_group = (int) get_parameter('ag_group', 0);
// 0 is the All group (selects all groups)
$free_search = get_parameter('free_search', '');

$user_tag_array = tags_get_user_tags($config['id_user'], 'AR', true);

if ($user_tag_array) {
    $user_tag_array = array_values(array_keys($user_tag_array));

    $user_tag = '';

    foreach ($user_tag_array as $key => $value) {
        if ($value === end($user_tag_array)) {
            $user_tag .= $value;
        } else {
            $user_tag .= $value.',';
        }
    }

    $tag_filter = get_parameter('tag_filter', $user_tag);

    $tag_param_validate = explode(',', $tag_filter);

    foreach ($tag_param_validate as $key => $value) {
        if (!in_array($value, $user_tag_array)) {
            db_pandora_audit(
                AUDIT_LOG_ACL_VIOLATION,
                'Trying to access Alert view'
            );
            include 'general/noaccess.php';
            exit;
        }
    }
} else {
    $tag_filter = get_parameter('tag_filter', 0);
}

if ($tag_filter) {
    if ($id_group && $strict_user) {
        $tag_filter = 0;
    }
}

$action_filter = get_parameter('action', 0);

$sec2 = get_parameter_get('sec2');
$sec2 = safe_url_extraclean($sec2);

$sec = get_parameter_get('sec');
$sec = safe_url_extraclean($sec);

$flag_alert = (bool) get_parameter('force_execution', 0);
$alert_validate = (bool) get_parameter('alert_validate', 0);
$tab = get_parameter_get('tab', null);

$refr = (int) get_parameter('refr', 0);
$pure = get_parameter('pure', 0);

$url = 'index.php?sec='.$sec.'&sec2='.$sec2.'&refr='.$refr.'&disabled='.$disabled.'&filter_standby='.$filter_standby.'&ag_group='.$id_group.'&tag_filter='.$tag_filter.'&action_filter='.$action_filter;

if ($flag_alert == 1 && check_acl($config['id_user'], $id_group, 'AW')) {
    forceExecution($id_group);
}


$idAgent = get_parameter_get('id_agente', 0);

// Show alerts for specific agent.
if ($idAgent != 0) {
    $url = $url.'&id_agente='.$idAgent;

    $id_group = agents_get_agent_group($idAgent);

    // All groups is calculated in ver_agente.php. Avoid to calculate it again.
    if (!isset($all_groups)) {
        $all_groups = agents_get_all_groups_agent($idAgent, $id_group);
    }

    if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') && !check_acl_one_of_groups($config['id_user'], $id_group, 'AW')) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access alert view'
        );
        include 'general/noaccess.php';
        exit;
    }

    $idGroup = false;

    $print_agent = false;

    $tab = get_parameter('tab', 'main');

    ob_start();

    if ($tab == 'main') {
        $agent_view_page = true;
    }
} else {
    $agent_a = check_acl($config['id_user'], 0, 'AR');
    $agent_w = check_acl($config['id_user'], 0, 'AW');
    $access = ($agent_a == true) ? 'AR' : (($agent_w == true) ? 'AW' : 'AR');

    if (!$agent_a && !$agent_w) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access alert view'
        );
        include 'general/noaccess.php';
        return;
    }

    $agents = array_keys(
        agents_get_group_agents(
            array_keys(
                users_get_groups($config['id_user'], $access, false)
            ),
            false,
            'lower',
            true
        )
    );

    $idGroup = $id_group;
    // If there is no agent defined, it means that it cannot search for the secondary groups.
    $all_groups = [$id_group];

    $print_agent = true;

    // Header.
    ui_print_standard_header(
        __('Alert detail'),
        'images/op_alerts.png',
        false,
        '',
        false,
        [],
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
}

$alerts = [];

if ($tab != null) {
    $url = $url.'&tab='.$tab;
}

if ($pure) {
    $url .= '&pure='.$pure;
}

if (empty($free_search) === false) {
    $url .= '&free_search='.$free_search;
}

$columns = [];
$column_names = [];


if ((bool) check_acl($config['id_user'], $id_group, 'LW') === true || (bool) check_acl($config['id_user'], $id_group, 'LM') === true) {
    array_unshift(
        $column_names,
        [
            'title' => __('Standby'),
            'text'  => __('Standby'),
        ],
        [
            'title' => __('Operations'),
            'text'  => __('Operations'),
            'class' => 'left pdd_l_0px pdd_r_0px w100p',
        ],
    );

    $columns = array_merge(
        ['standby'],
        ['force'],
        $columns
    );

    if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
        array_unshift(
            $column_names,
            [
                'title' => __('Policy'),
                'text'  => __('Policy'),
            ]
        );

        $columns = array_merge(
            ['policy'],
            $columns
        );
    }

    if ($print_agent === true) {
        array_push(
            $column_names,
            ['text' => 'Agent']
        );

        $columns = array_merge(
            $columns,
            ['agent_name']
        );
    }
}

array_push(
    $column_names,
    ['text' => 'Module'],
    ['text' => 'Template'],
    [
        'title' => __('Action'),
        'text'  => __('Action'),
        'style' => 'min-width: 15%;',
    ],
    ['text' => 'Last fired'],
    ['text' => 'Status']
);

$columns = array_merge(
    $columns,
    ['agent_module_name'],
    ['template_name'],
    ['action'],
    ['last_fired'],
    ['status']
);

if (is_metaconsole() === false) {
    if ((bool) check_acl($config['id_user'], $id_group, 'LW') === true || (bool) check_acl($config['id_user'], $id_group, 'LM') === true) {
        array_unshift(
            $column_names,
            [
                'title' => __('Validate'),
                'text'  => html_print_checkbox('all_validate', 0, false, true, false),
                'class' => 'dt-left',
                'style' => 'max-width: 5%;',
            ]
        );

        $columns = array_merge(
            ['validate'],
            $columns
        );
    }
}

if (is_metaconsole() === true) {
    $no_sortable_columns = [
        0,
        1,
        2,
        5,
    ];
} else {
    if (enterprise_installed() === true) {
        $no_sortable_columns = [
            0,
            1,
            2,
            3,
            -3,
            -1,
        ];
    } else {
        $no_sortable_columns = [
            0,
            1,
            2,
            -3,
            -1,
        ];
    }
}


$alert_action = empty(alerts_get_alert_actions_filter()) === false
    ? alerts_get_alert_actions_filter()
    : ['' => __('No actions')];


ob_start();

if ($agent_view_page === true) {
    ui_print_datatable(
        [
            'id'                  => 'alerts_status_datatable',
            'class'               => 'info_table',
            'style'               => 'width: 99%',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'no_sortable_columns' => $no_sortable_columns,
            'ajax_url'            => 'include/ajax/alert_list.ajax',
            'dom_elements'        => 'pfrti',
            'ajax_data'           => [
                'get_agent_alerts_datatable' => 1,
                'id_agent'                   => $idAgent,
                'url'                        => $url,
                'agent_view_page'            => true,
                'all_groups'                 => $all_groups,
            ],
            'drawCallback'        => 'alerts_table_controls()',
            'order'               => [
                'field'     => 'agent_module_name',
                'direction' => 'asc',
            ],
            'zeroRecords'         => __('No alerts found'),
            'emptyTable'          => __('No alerts found'),
            'search_button_class' => 'sub filter float-right secondary',
            'form'                => [
                'inputs'    => [
                    [
                        'label'     => __('Free text for search (*):').ui_print_help_tip(
                            __('Filter by module name, template name or action name'),
                            true
                        ),
                        'type'      => 'text',
                        'name'      => 'free_search_alert',
                        'value'     => $free_search,
                        'size'      => 20,
                        'maxlength' => 100,
                    ],
                ],
                'no_toggle' => true,
                'class'     => 'flex',
            ],
        ]
    );
} else {
    ui_print_datatable(
        [
            'id'                  => 'alerts_status_datatable',
            'class'               => 'info_table',
            'style'               => 'width: 99%;',
            'columns'             => $columns,
            'column_names'        => $column_names,
            'no_sortable_columns' => $no_sortable_columns,
            'ajax_url'            => 'include/ajax/alert_list.ajax',
            'ajax_data'           => [
                'get_agent_alerts_datatable' => 1,
                'id_agent'                   => $idAgent,
                'url'                        => $url,
            ],
            'drawCallback'        => 'alerts_table_controls()',
            'order'               => [
                'field'     => 'agent_module_name',
                'direction' => 'asc',
            ],
            'zeroRecords'         => __('No alerts found'),
            'emptyTable'          => __('No alerts found'),
            'search_button_class' => 'sub filter float-right secondary',
            'filter_main_class'   => 'box-flat white_table_graph fixed_filter_bar',
            'form'                => [
                'html' => printFormFilterAlert(
                    $id_group,
                    $disabled,
                    $free_search,
                    $url,
                    $filter_standby,
                    $tag_filter,
                    true,
                    true,
                    $strict_user
                ),
            ],
        ]
    );
}

if (is_metaconsole() === false) {
    if (((bool) check_acl($config['id_user'], $id_group, 'AW') === true || (bool) check_acl($config['id_user'], $id_group, 'LM') === true)) {
        if ($agent_view_page === true) {
            html_print_div(
                [
                    'class'   => 'action-buttons pdd_r_5px w100p',
                    'content' => html_print_submit_button(
                        __('Validate'),
                        'alert_validate',
                        false,
                        [
                            'icon' => 'wand',
                            'mode' => 'secondary mini',
                        ],
                        true
                    ),
                ]
            );
        } else {
            html_print_action_buttons(
                html_print_submit_button(
                    __('Validate'),
                    'alert_validate',
                    false,
                    [ 'icon' => 'wand' ],
                    true
                ),
                ['type' => 'form_action']
            );
        }
    }
} else {
    html_print_action_buttons('');
}

$html_content = ob_get_clean();

if ($agent_view_page === true) {
    // Create controlled toggle content.
    html_print_div(
        [
            'class'   => 'agent_details_line',
            'content' => ui_toggle(
                $html_content,
                '<span class="subsection_header_title">'.__('Full list of alerts').'</span>',
                'status_monitor_agent',
                !$alerts_defined,
                false,
                true,
                '',
                '',
                'box-flat white_table_graph w100p'
            ),
        ],
    );
} else {
    // Dump entire content.
    echo $html_content;
}

// Strict user hidden.
echo '<div id="strict_hidden" class="invisible">';
html_print_input_text('strict_user_hidden', $strict_user);

html_print_input_text('is_meta_hidden', (int) is_metaconsole());
echo '</div>';

ui_require_css_file('cluetip', 'include/styles/js/');
ui_require_jquery_file('cluetip');
?>

<script type="text/javascript">

function alerts_table_controls() {
    
        $("button.template_details").cluetip ({
            arrows: true,
            attribute: 'href',
            cluetipClass: 'default'
        }).click (function () {
            return false;
        });

        $("a.template_details").cluetip ({
                    arrows: true,
                    attribute: 'href',
                    cluetipClass: 'default'
                });

        $('[id^=checkbox-all_validate]').change(function(){    
            if ($("#checkbox-all_validate").prop("checked")) {
                $("input[id^=checkbox-validate]").prop('checked', true);
            }
            else{
                $('[id^=checkbox-validate]').parent().parent().removeClass('checkselected');
                $('[name^=validate]').prop("checked", false);                
            }    
        });

    }
    
    $(document).ready ( function () {
        alerts_table_controls();
        $('#button-alert_validate').on('click', function () {
            validateAlerts();
        });
    });

    $('table.alert-status-filter #ag_group').change (function () {
        var strict_user = $("#text-strict_user_hidden").val();
        var is_meta = $("#text-is_meta_hidden").val();

        if (($(this).val() != 0) && (strict_user != 0)) {
            $("table.alert-status-filter #tag_filter").hide();
            if (is_meta) {
                $("table.alert-status-filter #table1-0-4").hide();
            } else {
                $("table.alert-status-filter #table2-0-4").hide();
            }
        } else {
            $("#tag_filter").show();
            if (is_meta) {
                $("table.alert-status-filter #table1-0-4").show();
            } else {
                $("table.alert-status-filter #table2-0-4").show();
            }
        }
    }).change();
    

    function validateAlerts() {
        var alert_ids = [];

        $('[id^=checkbox-validate]:checked').each(function() {
            alert_ids.push($(this).val());
        });

        if (alert_ids.length === 0) { 
            confirmDialog({
                title: "<?php echo __('No alert selected'); ?>",
                message: "<?php echo __('You must select at least one alert.'); ?>",
                hideCancelButton: true
            });
        }

        $.ajax({
            type: "POST",
            url: "ajax.php",
            data: {
                alert_ids: alert_ids,
                page: "include/ajax/alert_list.ajax",
                alert_validate: 1,
                all_groups: <?php echo json_encode($all_groups); ?>,
            },
            dataType: "json",
            success: function (data) {
                $("#menu_tab_frame_view").after(data);
                var table = $('#alerts_status_datatable').DataTable({
                    ajax: "data.json"
                });

                table.ajax.reload();
            }, 
        });

        
    }
</script>
