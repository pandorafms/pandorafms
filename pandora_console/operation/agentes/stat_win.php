<?php
/**
 * View charts.
 *
 * @category   View charts
 * @package    Pandora FMS
 * @subpackage Community
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

require_once '../../include/config.php';
require_once $config['homedir'].'/include/auth/mysql.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_tags.php';
require_once $config['homedir'].'/include/php_to_js_values.php';
enterprise_include_once('include/functions_agents.php');

check_login();

// Metaconsole connection to the node.
$server_id = (int) get_parameter('server', 0);
if (is_metaconsole() === true && empty($server_id) === false) {
    $server = metaconsole_get_connection_by_id($server_id);
    // Error connecting.
    if (metaconsole_connect($server) !== NOERR) {
        echo '<html>';
            echo '<body>';
                ui_print_error_message(
                    __('There was a problem connecting with the node')
                );
            echo '</body>';
        echo '</html>';
        exit;
    }
}

$user_language = get_user_language($config['id_user']);
if (file_exists('../../include/languages/'.$user_language.'.mo')) {
    $l10n = new gettext_reader(
        new CachedFileReader('../../include/languages/'.$user_language.'.mo')
    );
    $l10n->load_tables();
}

echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css"/>';

$id = get_parameter('id');
$id_agent = db_get_value(
    'id_agente',
    'tagente_modulo',
    'id_agente_modulo',
    $id
);
$alias = db_get_value('alias', 'tagente', 'id_agente', $id_agent);
$label = db_get_value(
    'nombre',
    'tagente_modulo',
    'id_agente_modulo',
    $id
);

ui_require_css_file('register', 'include/styles/', true);
// Connection lost alert.
$conn_title = __('Connection with server has been lost');
$conn_text = __('Connection to the server has been lost. Please check your internet connection or contact with administrator.');
ui_require_javascript_file('connection_check');
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
ui_print_message_dialog(
    $conn_title,
    $conn_text,
    'connection',
    '/images/error_1.png'
);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <?php
        // Parsing the refresh before sending any header.
        $refresh = (int) get_parameter('refresh', -1);
        if ($refresh > 0) {
            $query = ui_get_url_refresh(false);
            echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
        }
        ?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php echo __('%s Graph', get_product_name()).' ('.$alias.' - '.$label; ?>)</title>
        <link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/js/jquery-ui.min.css" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/js/jquery-ui_custom.css" type="text/css" />
        <script type='text/javascript' src='../../include/javascript/pandora.js'></script>
        <script type='text/javascript' src='../../include/javascript/pandora_ui.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery-3.3.1.min.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery-ui.min.js'></script>
        <?php
        require_once $config['homedir'].'/include/graphs/functions_flot.php';
            echo include_javascript_dependencies_flot_graph(true, '../');
        ?>
        <script type='text/javascript'>
            window.onload = function() {
                // Hack to repeat the init process to period select
                var periodSelectId = $('[name="period"]').attr('class');

                period_select_init(periodSelectId);
            };
        </script>
    </head>
    <body style='background:#ffffff;'>
        <?php
        // Module id.
        $id = (int) get_parameter('id', 0);
        // Agent id.
        $agent_id = (int) modules_get_agentmodule_agent($id);

        if (empty($id) === true || empty($agent_id) === true) {
            ui_print_error_message(
                __('There was a problem locating the source of the graph')
            );
            exit;
        }

        // ACL.
        $all_groups = agents_get_all_groups_agent($agent_id);
        if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AR')) {
            include $config['homedir'].'/general/noaccess.php';
            exit;
        }

        $draw_alerts = get_parameter('draw_alerts', 0);

        $period = get_parameter('period');
        $id     = get_parameter('id', 0);
        $start_date = get_parameter('start_date', date('Y/m/d'));
        $start_time = get_parameter('start_time', date('H:i:s'));
        $draw_events = get_parameter('draw_events', 0);
        $graph_type = get_parameter('type', 'sparse');
        $zoom = get_parameter('zoom', $config['zoom_graph']);
        $baseline = get_parameter('baseline', 0);
        $show_events_graph = get_parameter('show_events_graph', 0);
        $show_percentil = get_parameter('show_percentil', 0);
        $time_compare_separated = get_parameter('time_compare_separated', 0);
        $time_compare_overlapped = get_parameter('time_compare_overlapped', 0);
        $unknown_graph = get_parameter_checkbox('unknown_graph', 1);

        $fullscale_sent = get_parameter('fullscale_sent', 0);
        if (!$fullscale_sent) {
            if (isset($config['full_scale_option']) === false
                || $config['full_scale_option'] == 0
            ) {
                $fullscale = 0;
            } else if ($config['full_scale_option'] == 1) {
                $fullscale = 1;
            } else if ($config['full_scale_option'] == 2) {
                if ($graph_type == 'boolean') {
                    $fullscale = 1;
                } else {
                    $fullscale = 0;
                }
            }
        } else {
            $fullscale = get_parameter('fullscale', 0);
        }

        $type_mode_graph = get_parameter_checkbox(
            'type_mode_graph',
            ($fullscale === 1) ? 0 : $config['type_mode_graph']
        );

        $time_compare = false;

        if ($time_compare_separated) {
            $time_compare = 'separated';
        } else if ($time_compare_overlapped) {
            $time_compare = 'overlapped';
        }

        if ($zoom > 1) {
            $height = ($height * ($zoom / 2.1));
            $width = ($width * ($zoom / 1.4));
        }

        // Build date.
        $date = strtotime($start_date.' '.$start_time);
        $now = time();

        if ($date > $now) {
            $date = $now;
        }

        $urlImage = ui_get_full_url(false, false, false, false);

        $unit = db_get_value(
            'unit',
            'tagente_modulo',
            'id_agente_modulo',
            $id
        );

        // FORM TABLE.
        $table = html_get_predefined_table('transparent', 2);
        $table->width = '100%';
        $table->id = 'stat_win_form_div';
        $table->style[0] = 'text-align:left;';
        $table->style[1] = 'text-align:left;';
        $table->style[2] = 'text-align:left;font-weight: bold;';
        $table->style[3] = 'text-align:left;';
        $table->class = 'table_modal_alternate';

        $table->data = [];
        $table->data[0][0] = __('Refresh time');
        $table->data[0][1] = html_print_extended_select_for_time(
            'refresh',
            $refresh,
            '',
            '',
            0,
            7,
            true
        );

        $table->data[0][2] = __('Show events');
        $disabled = false;
        if (isset($config['event_replication']) === true) {
            if ($config['event_replication']
                && !$config['show_events_in_local']
            ) {
                $disabled = true;
            }
        }

        $table->data[0][3] = html_print_checkbox_switch(
            'draw_events',
            1,
            (bool) $draw_events,
            true,
            $disabled
        );
        if ($disabled) {
            $table->data[1] .= ui_print_help_tip(
                __("'Show events' is disabled because this %s node is set to event replication.", get_product_name()),
                true
            );
        }

        $table->data[1][0] = __('Begin date');
        $table->data[1][1] = html_print_input_text(
            'start_date',
            $start_date,
            '',
            10,
            20,
            true
        );

        $table->data[1][2] = __('Show alerts');
        $table->data[1][3] = html_print_checkbox_switch(
            'draw_alerts',
            1,
            (bool) $draw_alerts,
            true
        );

        $table->data[2][0] = __('Begin time');
        $table->data[2][1] = html_print_input_text(
            'start_time',
            $start_time,
            '',
            10,
            10,
            true
        );

        $table->data[2][2] = __('Show unknown graph');
        $table->data[2][3] = html_print_checkbox_switch(
            'unknown_graph',
            1,
            (bool) $unknown_graph,
            true
        );

        $table->data[3][0] = __('Time range');
        $table->data[3][1] = html_print_extended_select_for_time(
            'period',
            $period,
            '',
            '',
            0,
            7,
            true
        );

        $table->data[3][2] = '';
        $table->data[3][3] = '';

        if (!modules_is_boolean($id)) {
            $table->data[4][0] = __('Zoom');
            $options = [];
            $options[$zoom] = 'x'.$zoom;
            $options[1] = 'x1';
            $options[2] = 'x2';
            $options[3] = 'x3';
            $options[4] = 'x4';
            $options[5] = 'x5';
            $table->data[4][1] = html_print_select(
                $options,
                'zoom',
                $zoom,
                '',
                '',
                0,
                true,
                false,
                false
            );

            $table->data[4][2] = __('Show percentil');
            $table->data[4][3] = html_print_checkbox_switch(
                'show_percentil',
                1,
                (bool) $show_percentil,
                true
            );
        }

        $table->data[5][0] = __('Time compare (Overlapped)');
        $table->data[5][1] = html_print_checkbox_switch(
            'time_compare_overlapped',
            1,
            (bool) $time_compare_overlapped,
            true
        );

        $table->data[5][2] = __('Time compare (Separated)');
        $table->data[5][3] = html_print_checkbox_switch(
            'time_compare_separated',
            1,
            (bool) $time_compare_separated,
            true
        );


        $table->data[6][0] = __('Show AVG/MAX/MIN data series in graph');
        $table->data[6][1] = html_print_checkbox_switch(
            'type_mode_graph',
            1,
            (bool) $type_mode_graph,
            true,
            false
        );

        $table->data[6][2] = __('Show full scale graph (TIP)');
        $table->data[6][2] .= ui_print_help_tip(
            __('TIP mode charts do not support average - maximum - minimum series, you can only enable TIP or average, maximum or minimum series'),
            true
        );
        $table->data[6][3] = html_print_checkbox_switch(
            'fullscale',
            1,
            (bool) $fullscale,
            true,
            false
        );

        $form_table = html_print_table($table, true);
        $form_table .= '<div style="width:100%; text-align:right; margin-top: 15px;">';
        $form_table .= html_print_submit_button(
            __('Reload'),
            'submit',
            false,
            'class="sub upd"',
            true
        );
        $form_table .= '</div>';

        // Menu.
        $menu_form = "<form method='get' action='stat_win.php' style='margin-top: 10px;'>";
        $menu_form .= html_print_input_hidden('id', $id, true);
        $menu_form .= html_print_input_hidden('label', $label, true);

        if (empty($server_id) === false) {
            $menu_form .= html_print_input_hidden('server', $server_id, true);
        }

        if (isset($_GET['type']) === true) {
            $type = get_parameter_get('type');
            $menu_form .= html_print_input_hidden('type', $type, true);
        }

        $menu_form .= '<div class="module_graph_menu_dropdown">';
        $menu_form .= '<div id="module_graph_menu_header" class="module_graph_menu_header">';
        $menu_form .= html_print_image(
            'images/arrow_down_green.png',
            true,
            [
                'class' => 'module_graph_menu_arrow',
                'float' => 'left',
            ],
            false,
            false,
            true
        );
        $menu_form .= '<span style="flex: 2; justify-content:center;" class="flex-row">';
        $menu_form .= '<b>'.__('Graph configuration menu').'</b>';
        $menu_form .= ui_print_help_tip(
            __('In Pandora FMS, data is stored compressed. The data visualization in database, charts or CSV exported data won\'t match, because is interpreted at runtime. Please check \'Pandora FMS Engineering\' chapter from documentation.'),
            true
        );
        $menu_form .= '</span>';
        $menu_form .= '</div>';
        $menu_form .= '<div class="module_graph_menu_content module_graph_menu_content_closed" style="display:none;">';
        $menu_form .= $form_table;
        $menu_form .= '</div>';
        $menu_form .= '</div>';
        $menu_form .= '</form>';

        echo $menu_form;

        // Hidden div to forced title.
        html_print_div(
            [
                'id'     => 'forced_title_layer',
                'class'  => 'forced_title_layer',
                'hidden' => true,
            ]
        );

        $params = [
            'agent_module_id' => $id,
            'period'          => $period,
            'show_events'     => $draw_events,
            'title'           => $label,
            'unit_name'       => $unit,
            'show_alerts'     => $draw_alerts,
            'date'            => $date,
            'unit'            => $unit,
            'baseline'        => $baseline,
            'homeurl'         => $urlImage,
            'adapt_key'       => 'adapter_'.$graph_type,
            'compare'         => $time_compare,
            'show_unknown'    => $unknown_graph,
            'percentil'       => (($show_percentil) ? $config['percentil'] : null),
            'type_graph'      => $config['type_module_charts'],
            'fullscale'       => $fullscale,
            'zoom'            => $zoom,
            'height'          => 300,
            'type_mode_graph' => $type_mode_graph,
        ];

        // Graph.
        $output = '<div id="stat-win-module-graph">';
        $output .= '<div id="stat-win-spinner" class="stat-win-spinner">';
        $output .= html_print_image('images/spinner_charts.gif', true);
        $output .= '</div>';
        $output .= '</div>';
        echo $output;

        if (is_metaconsole() === true && empty($server_id) === false) {
            metaconsole_restore_db();
        }
        ?>

    </body>
</html>

<?php
// Echo the script tags of the datepicker and the timepicker
// Modify the user language cause
// the ui.datepicker language files use - instead.
$custom_user_language = str_replace('_', '-', $user_language);
ui_require_jquery_file(
    'ui.datepicker-'.$custom_user_language,
    'include/javascript/i18n/',
    true
);
ui_include_time_picker(true);
?>

<script>
    $(document).ready (function () {
        $('#checkbox-time_compare_separated').click(function(e) {
            if(e.target.checked === true) {
                $('#checkbox-time_compare_overlapped').prop('checked', false);
            }
        });
        $('#checkbox-time_compare_overlapped').click(function(e) {
            if(e.target.checked === true) {
                $('#checkbox-time_compare_separated').prop('checked', false);
            }
        });

        $('#checkbox-fullscale').click(function(e) {
            if(e.target.checked === true) {
                $('#checkbox-type_mode_graph').prop('checked', false);
            }
        });

        $('#checkbox-type_mode_graph').click(function(e) {
            if(e.target.checked === true) {
                $('#checkbox-fullscale').prop('checked', false);
            }
        });

        // Add datepicker and timepicker
        $("#text-start_date").datepicker({
            dateFormat: "<?php echo DATE_FORMAT_JS; ?>"
        });
        $("#text-start_time").timepicker({
            showSecond: true,
            timeFormat: '<?php echo TIME_FORMAT_JS; ?>',
            timeOnlyTitle: '<?php echo __('Choose time'); ?>',
            timeText: '<?php echo __('Time'); ?>',
            hourText: '<?php echo __('Hour'); ?>',
            minuteText: '<?php echo __('Minute'); ?>',
            secondText: '<?php echo __('Second'); ?>',
            currentText: '<?php echo __('Now'); ?>',
            closeText: '<?php echo __('Close'); ?>'
        });

        $.datepicker.setDefaults(
            $.datepicker.regional["<?php echo $custom_user_language; ?>"]
        );

        // Menu.
        $('#module_graph_menu_header').on('click', function(){
            var arrow = $('#module_graph_menu_header .module_graph_menu_arrow');
            var arrow_up = 'arrow_up_green';
            var arrow_down = 'arrow_down_green';
            if( $('.module_graph_menu_content').hasClass(
                'module_graph_menu_content_closed')){
                $('.module_graph_menu_content').show();
                $('.module_graph_menu_content').removeClass(
                    'module_graph_menu_content_closed');
                arrow.attr('src',arrow.attr('src').replace(arrow_down, arrow_up));
            }
            else{
                $('.module_graph_menu_content').hide();
                $('.module_graph_menu_content').addClass(
                    'module_graph_menu_content_closed');
                arrow.attr('src',arrow.attr('src').replace(arrow_up, arrow_down));
            }
        });

        var graph_data = "<?php echo base64_encode(json_encode($params)); ?>";
        var url = "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>";
        var serverId = "<?php echo $server_id; ?>";
        get_ajax_module(url, graph_data, serverId);
    });


    function get_ajax_module(url, graph_data, serverId) {
        $.ajax({
            type: "POST",
            url: url,
            dataType: "html",
            data: {
                page: "include/ajax/module",
                get_graph_module: true,
                graph_data: graph_data,
                server_id: serverId
            },
            success: function (data) {
                $("#stat-win-spinner").hide();
                $("#stat-win-module-graph").append(data);
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
</script>
