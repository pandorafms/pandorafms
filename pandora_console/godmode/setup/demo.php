<?php
/**
 * Enterprise Main Setup.
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Enterprise
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Pandora FMS, http://www.pandorafms.com
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;
global $table;

check_login();

if (users_is_admin() === false) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access demo data manager'
    );
    include 'general/noaccess.php';
    return;
}

// Same styles as tactical view is required.
ui_require_css_file('general_tactical_view');

html_print_input_hidden('demo_items_count', 0);

$display_loading = (bool) get_parameter('display_loading', 0);

$agents_num = (int) get_parameter('agents_num', 30);
$create_data = (bool) get_parameter('create_data', false);
$delete_data = (bool) get_parameter('delete_data', false);

$def_value = 0;
if ($create_data === false) {
    $def_value = 1;
}

$adv_options_is_enabled = get_parameter('toggle_adv_opts', 0);
$days_hist_data = (int) get_parameter('days_hist_data', 15);
$interval = get_parameter('interval', 300);
$service_agent_name = get_parameter('service_agent_name', 'demo-global-agent-1');

// Map directory and demo item ID.
$dir_item_id_map = [
    DEMO_CUSTOM_GRAPH   => 'graphs',
    DEMO_NETWORK_MAP    => 'network_maps',
    DEMO_GIS_MAP        => 'gis_maps',
    DEMO_SERVICE        => 'services',
    DEMO_REPORT         => 'reports',
    DEMO_DASHBOARD      => 'dashboards',
    DEMO_VISUAL_CONSOLE => 'visual_consoles',
];

$enabled_items = [
    'graphs'          => (int) get_parameter('enable_cg', $def_value),
    'network_maps'    => (int) get_parameter('enable_nm', $def_value),
    'gis_maps'        => (int) get_parameter('enable_gis', $def_value),
    'services'        => (int) get_parameter('enable_services', $def_value),
    'reports'         => (int) get_parameter('enable_rep', $def_value),
    'dashboards'      => (int) get_parameter('enable_dashboards', $def_value),
    'visual_consoles' => (int) get_parameter('enable_vc', $def_value),
    'enable_history'  => (int) get_parameter('enable_history', 0),
];

$generate_hist = (int) get_parameter('enable_history', $def_value);

$plugin_agent = get_parameter('plugin_agent', 'demo-global-agent-1');
$traps_target_ip = get_parameter('traps_target_ip', '127.0.0.1');
$traps_community = get_parameter('traps_community', 'public');
$tentacle_target_ip = get_parameter('tentacle_target_ip', '127.0.0.1');
$tentacle_port = get_parameter('tentacle_port', '41121');
$tentacle_extra_options = get_parameter('tentacle_extra_options', '');

$demo_items_count = (int) db_get_value('count(*)', 'tdemo_data');

$current_progress_val = db_get_value_filter(
    'value',
    'tconfig',
    ['token' => 'demo_data_load_progress'],
    'AND',
    false,
    false
);

$current_progress_val_delete = db_get_value_filter(
    'value',
    'tconfig',
    ['token' => 'demo_data_delete_progress'],
    'AND',
    false,
    false
);

$running_create = ($current_progress_val > 0 && $current_progress_val < 100);
$running_delete = ($current_progress_val_delete > 0 && $current_progress_val_delete < 100);

// Real time loading.
if ($display_loading === true || $running_create === true || $running_delete === true) {
    $operation = 'cleanup';
    $progress_val = (int) $current_progress_val_delete;

    if ($create_data === true || $running_create === true) {
        $operation = 'create';
        $progress_val = (int) $current_progress_val;
    }

    $load_mkp = ui_progress(
        0,
        '100%',
        '2.5',
        '#C0CCDC',
        true,
        $progress_val.' %',
        [
            'page'     => 'include/ajax/demo_data.ajax',
            'interval' => 1,
            'simple'   => 1,
            'data'     => [
                'action'                => 'get_progress',
                'operation'             => $operation,
                'demo_items_to_cleanup' => $demo_items_count,
            ],
        ],
        'line-height: 17pt;'
    );


    $load_mkp .= html_print_input_hidden('js_timer', 0, true);

    $table_mkup = '<div id="load-info" class="container">
                    <div class="title">'.__('Progress').'</div>
                    <div class="content br-t">
                        <div class="row">
                            <div class="col-12">
                                <div class="br-t">
                                    <div class="padding20">
                                        '.$load_mkp.'
                                    </div>
                                </div>';

    if ($create_data === true || $running_create === true) {
        // Map demo item ID to display name in page.
        $items_ids_text_map = [
            DEMO_AGENT          => 'agents',
            DEMO_SERVICE        => 'services',
            DEMO_NETWORK_MAP    => 'network maps',
            DEMO_GIS_MAP        => 'GIS maps',
            DEMO_CUSTOM_GRAPH   => 'custom graphs',
            DEMO_REPORT         => 'custom reports',
            DEMO_VISUAL_CONSOLE => 'visual consoles',
            DEMO_DASHBOARD      => 'dashboards',
        ];

        if ((bool) $adv_options_is_enabled === true) {
            $enabled_keys = array_keys(array_filter($enabled_items));
            $items_ids_text_map = array_filter(
                $items_ids_text_map,
                function ($k) use ($dir_item_id_map, $enabled_keys) {
                    return in_array($dir_item_id_map[$k], $enabled_keys);
                },
                ARRAY_FILTER_USE_KEY
            );
        }

        if (enterprise_installed() === false) {
            unset($items_ids_text_map[DEMO_SERVICE]);
        }

        $items_ids_text_map[DEMO_PLUGIN] = 'plugin';
        $items_ids_text_map = ([DEMO_AGENT => 'agents'] + $items_ids_text_map);

        foreach ($items_ids_text_map as $item_id => $item_text) {
            $table_mkup .= '<div data-item-id="'.$item_id.'" class="br-t">
                                <div class="pdd_l_15px pdd_t_7px">
                                    <div class="inline vertical_middle w20px h20px" style="margin-right: 10px;">
                                        <div class="loader-mini">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </div>
                                        <div class="inline vertical_middle w20px h20px" style="margin-right: 10px;">'.html_print_image(
                'images/icono-unknown.png',
                true,
                [
                    'title' => __('View'),
                    'class' => 'icon invisible w100p h100p',
                ]
            ).'
                                        </div>
                                    </div>
                                    <span class="inline vertical_middle" style="padding-left: 15px;">Create demo '.$item_text.'</span>
                                    <ul class="error-list color_888 margin-bottom-10" style="margin-left: 32px;"></ul>
                                </div>
                            </div>';
        }
    }

    $table_mkup .= '</div>
            </div>
        </div>
    </div>';

    echo '<form class="max_floating_element_size" style="max-width: 810px;" method="post">';

    echo $table_mkup;

    $btn_span = __('Back');
    $icon = 'back';

    if ($create_data === true || $running_create === true) {
        $btn_span = __('View summary');
        $icon = 'next';
    }

    $action_btns = html_print_action_buttons(
        html_print_submit_button(
            $btn_span,
            'redirect_button',
            false,
            ['icon' => $icon],
            true
        ),
        [],
        true
    );

    // Only rendered when data creation has been completed.
    html_print_div(
        [
            'id'      => 'action-btns-loading-done',
            'class'   => 'invisible',
            'content' => $action_btns,
        ]
    );

    echo '</form>';
} else {
    // Configuration.
    if ($demo_items_count === 0) {
        $table_aux = new stdClass();
        $table_aux->id = 'table-demo';
        $table_aux->class = 'filter-table-adv';
        $table_aux->width = '100%';
        $table_aux->data = [];
        $table_aux->size = [];
        $table_aux->size[0] = '50%';
        $table_aux->size[1] = '50%';

        $agent_sel_values = [
            30   => '30',
            50   => '50',
            500  => '500',
            1000 => '1000',
            2000 => '2000',
        ];

        $agent_num = (int) get_parameter('agents_num');

        $otherData = [];
        $table_aux->data['row1'][] = html_print_label_input_block(
            __('Agents').ui_print_help_tip(__('You may need to increase the value of the plugin_timeout parameter in your server configuration to get all your agents data updated'), true),
            html_print_div(
                [
                    'class'   => '',
                    'content' => html_print_select(
                        $agent_sel_values,
                        'agents_num',
                        $agents_num,
                        '',
                        '',
                        30,
                        true,
                        false,
                        true,
                        'w80px'
                    ),
                ],
                true
            )
        );

        $table_aux->data['row2'][] = html_print_label_input_block(
            __('Advanced options'),
            html_print_checkbox_switch(
                'toggle_adv_opts',
                1,
                false,
                true
            )
        );

        $table_adv = new stdClass();
        $table_adv->id = 'table-adv';
        $table_adv->class = 'filter-table-adv';
        $table_adv->width = '100%';
        $table_adv->data = [];
        $table_adv->size = [];
        $table_adv->size[0] = '50%';
        $table_adv->size[1] = '50%';

        $interval_select = html_print_extended_select_for_time(
            'interval',
            $interval,
            '',
            '',
            '0',
            10,
            true,
            false,
            true,
            'w20p'
        );

        $table_adv->data['row0'][] = html_print_label_input_block(
            __('Agents interval'),
            $interval_select
        );

        $table_adv->data['row1'][] = html_print_label_input_block(
            __('Generate historical data for all agents'),
            html_print_checkbox_switch(
                'enable_history',
                1,
                (bool) $generate_hist,
                true
            )
        );

        $table_adv->data['row2'][] = html_print_label_input_block(
            __('Days of historical data to insert in the agent data'),
            html_print_input_text(
                'days_hist_data',
                $days_hist_data,
                '',
                10,
                20,
                true,
                false,
                false,
                '',
                'w80px'
            )
        );

        if (enterprise_installed() === true) {
            $table_adv->data['row3'][] = html_print_label_input_block(
                __('Create services'),
                html_print_checkbox_switch(
                    'enable_services',
                    1,
                    $enabled_items['services'],
                    true
                )
            );
        }

        $table_adv->data['row5'][] = html_print_label_input_block(
            __('Create network maps'),
            html_print_checkbox_switch(
                'enable_nm',
                1,
                $enabled_items['network_maps'],
                true
            )
        );

        $table_adv->data['row6'][] = html_print_label_input_block(
            __('Create GIS maps'),
            html_print_checkbox_switch(
                'enable_gis',
                1,
                $enabled_items['gis_maps'],
                true
            )
        );

        $table_adv->data['row7'][] = html_print_label_input_block(
            __('Create custom graphs'),
            html_print_checkbox_switch(
                'enable_cg',
                1,
                $enabled_items['graphs'],
                true
            )
        );

        $table_adv->data['row8'][] = html_print_label_input_block(
            __('Create reports'),
            html_print_checkbox_switch(
                'enable_rep',
                1,
                $enabled_items['reports'],
                true
            )
        );

        $table_adv->data['row9'][] = html_print_label_input_block(
            __('Create visual consoles'),
            html_print_checkbox_switch(
                'enable_vc',
                1,
                $enabled_items['visual_consoles'],
                true
            )
        );

        $table_adv->data['row10'][] = html_print_label_input_block(
            __('Create dashboards'),
            html_print_checkbox_switch(
                'enable_dashboards',
                1,
                $enabled_items['dashboards'],
                true
            )
        );

        $table_adv->data['row12'][] = html_print_label_input_block(
            __('Traps target IP').ui_print_help_tip(__('All demo traps are generated using version 1'), true),
            html_print_input_text(
                'traps_target_ip',
                $traps_target_ip,
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'w300px'
            )
        );

        $table_adv->data['row13'][] = html_print_label_input_block(
            __('Traps community'),
            html_print_input_text(
                'traps_community',
                $traps_community,
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'w300px'
            )
        );

        $table_adv->data['row14'][] = html_print_label_input_block(
            __('Tentacle target IP'),
            html_print_input_text(
                'tentacle_target_ip',
                $tentacle_target_ip,
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'w300px'
            )
        );

        $table_adv->data['row15'][] = html_print_label_input_block(
            __('Tentacle port'),
            html_print_input_text(
                'tentacle_port',
                $tentacle_port,
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'w300px'
            )
        );

        $table_adv->data['row16'][] = html_print_label_input_block(
            __('Tentacle extra options'),
            html_print_input_text(
                'tentacle_extra_options',
                $tentacle_extra_options,
                '',
                50,
                255,
                true,
                false,
                false,
                '',
                'w300px'
            )
        );

        echo '<form class="max_floating_element_size" id="form_setup" method="post">';
        echo '<fieldset>';
        echo '<legend>'.__('Configure demo data').'</legend>';
        html_print_input_hidden('create_data', 1);
        html_print_input_hidden('display_loading', 1);
        html_print_table($table_aux);
        html_print_div(
            [
                'class'   => 'invisible',
                'content' => html_print_table($table_adv),
            ],
            true
        );
        echo '</fieldset>';

        $actionButtons = [];

        $actionButtons[] = html_print_submit_button(
            __('Create demo data'),
            'create_button',
            false,
            [
                'icon'     => 'update',
                'fixed_id' => 'btn-create-demo-data',
            ],
            true
        );

        html_print_action_buttons(
            implode('', $actionButtons)
        );

        echo '</form>';
    } else {
        // Summary data.
        $demo_agents_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tagente');
        $demo_services_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tservice');
        $demo_nm_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tmap');
        $demo_gis_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tgis_map');
        $demo_cg_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tgraph');
        $demo_rep_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'treport');
        $demo_vc_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tlayout');
        $demo_dashboards_count = (int) db_get_value('count(*)', 'tdemo_data', 'table_name', 'tdashboard');

        $table_summary = new stdClass();
        $table_summary->id = 'table-summary';
        $table_summary->class = 'filter-table-adv';
        $table_summary->width = '100%';
        $table_summary->data = [];
        $table_summary->size = [];
        $table_summary->size[0] = '50%';
        $table_summary->size[1] = '50%';

        $i = 0;
        $table_summary->data[$i][0] = __('Agents');
        $table_summary->data[$i][1] = ($demo_agents_count > 0) ? $demo_agents_count : '-';
        $i++;

        if (enterprise_installed() === true) {
            $table_summary->data[$i][0] = __('Services');
            $table_summary->data[$i][1] = ($demo_services_count > 0) ? $demo_services_count : '-';
            $i++;
        }

        $i++;
        $table_summary->data[$i][0] = __('Network maps');
        $table_summary->data[$i][1] = ($demo_nm_count > 0) ? $demo_nm_count : '-';
        $i++;
        $table_summary->data[$i][0] = __('GIS maps');
        $table_summary->data[$i][1] = ($demo_gis_count > 0) ? $demo_gis_count : '-';
        $i++;
        $table_summary->data[$i][0] = __('Custom graphs');
        $table_summary->data[$i][1] = ($demo_cg_count > 0) ? $demo_cg_count : '-';
        $i++;
        $table_summary->data[$i][0] = __('Custom reports');
        $table_summary->data[$i][1] = ($demo_rep_count > 0) ? $demo_rep_count : '-';
        $i++;
        $table_summary->data[$i][0] = __('Visual consoles');
        $table_summary->data[$i][1] = ($demo_vc_count > 0) ? $demo_vc_count : '-';
        $i++;
        $table_summary->data[$i][0] = __('Dashboards');
        $table_summary->data[$i][1] = ($demo_dashboards_count > 0) ? $demo_dashboards_count : '-';

        echo '<form class="max_floating_element_size" method="post">';
        $table_mkup = '<div id="load-info" class="container">
            <div class="title">'.__('Active demo data summary').'</div>
            <div class="content br-t">
                <div class="row">
                    <div class="col-6 br-r br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Agents').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_agents_count > 0) ? $demo_agents_count : '-').'</div>
                        </div>
                    </div>
                    <div class="col-6 br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Services').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_services_count > 0) ? $demo_services_count : '-').'</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 br-r br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Network maps').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_nm_count > 0) ? $demo_nm_count : '-').'</div>
                        </div>
                    </div>
                    <div class="col-6 br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('GIS maps').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_gis_count > 0) ? $demo_gis_count : '-').'</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 br-r br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Custom graphs').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_cg_count > 0) ? $demo_cg_count : '-').'</div>
                        </div>
                    </div>
                    <div class="col-6 br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Custom reports').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_rep_count > 0) ? $demo_rep_count : '-').'</div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-6 br-r br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Visual consoles').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_vc_count > 0) ? $demo_vc_count : '-').'</div>
                        </div>
                    </div>
                    <div class="col-6 br-b">
                        <div class="padding10 flex-row">
                            <div>'.__('Dashboards').'</div>
                            <div class="font_w600 font_12pt">'.(($demo_dashboards_count > 0) ? $demo_dashboards_count : '-').'</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>';

        echo $table_mkup;

        html_print_input_hidden('delete_data', 1);
        html_print_input_hidden('display_loading', 1);

        html_print_action_buttons(
            html_print_submit_button(
                __('Delete all demo data'),
                'delete_button',
                false,
                [
                    'icon'     => 'delete',
                    'mode'     => 'secondary',
                    'fixed_id' => 'btn-delete-demo-data',
                ],
                true
            )
        );
        echo '</form>';
    }
}


?>

<script type="text/javascript">
    function active_button_add_agent() {
        $("#button-add_agent").prop("disabled", false);
    }

    $(document).ready (function () {
        var demo_items_count = <?php echo $demo_items_count; ?>;
        var agent_count_span_str = '<?php echo __('demo agents currently in the system'); ?>';
        var agents_str = '<?php echo __('agents'); ?>';

        $("#table-adv").hide();

        $('#checkbox-toggle_adv_opts').change(function() {
            if ($(this).is(':checked') === true) {
                $("#table-adv").show();
            } else {
                $("#table-adv").hide();
            }
        });

        $('#table-adv-row2').hide();
        if ($('#checkbox-enable_history').is(':checked') === true) {
            $('#table-adv-row2').show();
        }

        $('#checkbox-enable_history').change(function() {
            if ($(this).is(':checked') === true) {
                $('#table-adv-row2').show();
            } else {
                $('#table-adv-row2').hide();
            }
        });

        $('#table-adv-row4').hide();
        if ($('#checkbox-enable_services').is(':checked') === true) {
            $('#table-adv-row4').show();
        }

        $('#checkbox-enable_services').change(function() {
            if ($(this).is(':checked') === true) {
                $('#table-adv-row4').show();
            } else {
                $('#table-adv-row4').hide();
            }
        });

        var create_data = '<?php echo $create_data; ?>';
        var delete_data = '<?php echo $delete_data; ?>';
        var running_create = '<?php echo $running_create; ?>';
        var running_delete = '<?php echo $running_delete; ?>';

        if (create_data == true || running_create == true) {
            init_progress_checker('create');
        }

        // Creation operation must be done via AJAX in order to be able to run the operations in background
        // and keep it running even if we quit the page.
        if (create_data == true) {
            var params = {};
            params["action"] = "create_demo_data";
            params["page"] = "include/ajax/demo_data.ajax";
            params["agents_num"] = <?php echo $agents_num; ?>;
            params["adv_options_is_enabled"] = <?php echo $adv_options_is_enabled; ?>;
            params["enabled_items"] = <?php echo json_encode($enabled_items); ?>;
            params["days_hist_data"] = <?php echo $days_hist_data; ?>;
            params["interval"] = <?php echo $interval; ?>;
            params["plugin_agent"] = "<?php echo $plugin_agent; ?>";
            params["traps_target_ip"] = "<?php echo $traps_target_ip; ?>";
            params["traps_community"] = "<?php echo $traps_community; ?>";
            params["tentacle_target_ip"] = "<?php echo $tentacle_target_ip; ?>";
            params["tentacle_port"] = <?php echo $tentacle_port; ?>;
            params["tentacle_extra_options"] = "<?php echo $tentacle_extra_options; ?>";
            params["service_agent_name"] = "<?php echo $service_agent_name; ?>";

            jQuery.ajax({
                data: params,
                type: "POST",
                url: "ajax.php",
                dataType: 'json'
            });
        }

        if (delete_data == true || running_delete == true) {
           init_progress_checker('cleanup');
        }

        // Delete operation must be done via AJAX in order to be able to run the operations in background
        // and keep it running even if we quit the page.
        if (delete_data == true) {
            var params = {};
            params["action"] = "cleanup_demo_data";
            params["page"] = "include/ajax/demo_data.ajax";

            jQuery.ajax({
                data: params,
                type: "POST",
                url: "ajax.php",
                success: function(data) {
                    //$('#action-btns-loading-done').show();
                }
            });
        }
    });

    var items_checked = [];

    function demo_load_progress(operation) {
        var params = {};
        params["action"] = "get_load_status";
        params["operation"] = operation;
        if (operation == 'cleanup') {
            var demo_items_count = '<?php echo $demo_items_count; ?>';
            params["demo_items_to_cleanup"] = demo_items_count;
        }
        params["page"] = "include/ajax/demo_data.ajax";

        jQuery.ajax({
            data: params,
            type: "POST",
            url: "ajax.php",
            dataType: "json",
            success: function(data) {
                if (data.current_progress_val == 100) {
                    clearInterval($("#hidden-js_timer").val());
                    $('#action-btns-loading-done').show();
                }

                if (operation == 'create') {
                    var status_data = data?.demo_data_load_status;
                    status_data.checked_items?.forEach(function(item_id, idx) {
                        if (items_checked.includes(item_id)) {
                            return;
                        }

                        if (typeof status_data !== 'undefined'
                            && typeof status_data.errors !== 'undefined'
                            && typeof status_data.errors[item_id] !== 'undefined'
                            && status_data.errors[item_id].length > 0
                        ) {
                            update_demo_status_icon(item_id, 'images/status_error@svg.svg');

                            status_data.errors[item_id].forEach(function(error_msg) {                                
                                print_error(item_id, error_msg);
                            });
                        } else {
                            update_demo_status_icon(item_id, 'images/status_check@svg.svg');
                        }

                        $('div[data-item-id="' + item_id + '"] .loader-mini').hide();
                        $('div[data-item-id="' + status_data.checked_items[idx + 1] + '"] .loader-mini').show();
                        items_checked.push(item_id);
                    });
                }
            }
        });

    }

    function init_progress_checker(operation) {
        clearInterval($("#hidden-js_timer").val());

        /* 1 seconds between ajax request */
        var id_interval = setInterval("demo_load_progress('"+operation+"')", (1 * 1000));
        /* This will keep timer info */
        $("#hidden-js_timer").val(id_interval);
    }

    function update_demo_status_icon(itemId, iconName) {
        var $listItem = $(`[data-item-id="${itemId}"]`);
        var $icon = $listItem.find('.icon');

        $icon.attr('src', iconName);
        $icon.show();
    }

    function print_error(item_id, error_msg) {
        var error_list_item = $('<li>', {
            text: error_msg
        });

        // Append the new item to the corresponding error-list ul.
        $('#load-info div[data-item-id="' + item_id + '"] .error-list').append(error_list_item);
    }
</script>