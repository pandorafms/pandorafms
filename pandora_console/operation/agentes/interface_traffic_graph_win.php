<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';
require_once $config['homedir'].'/include/auth/mysql.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_tags.php';
enterprise_include_once('include/functions_agents.php');

check_login();

$params_json = base64_decode((string) get_parameter('params'));
$params = json_decode($params_json, true);

// Metaconsole connection to the node
$server_id = (int) (isset($params['server']) ? $params['server'] : 0);
if ($config['metaconsole'] && !empty($server_id)) {
    $server = metaconsole_get_connection_by_id($server_id);

    // Error connecting
    if (metaconsole_connect($server) !== NOERR) {
        echo '<html>';
            echo '<body>';
                ui_print_error_message(__('There was a problem connecting with the node'));
            echo '</body>';
        echo '</html>';
        exit;
    }
}

$user_language = get_user_language($config['id_user']);
if (file_exists('../../include/languages/'.$user_language.'.mo')) {
    $l10n = new gettext_reader(new CachedFileReader('../../include/languages/'.$user_language.'.mo'));
    $l10n->load_tables();
}

echo '<link rel="stylesheet" href="../../include/styles/pandora.css" type="text/css"/>';

$interface_name = (string) $params['interface_name'];
$agent_id = (int) $params['agent_id'];
$interface_traffic_modules = [
    __('In')  => (int) $params['traffic_module_in'],
    __('Out') => (int) $params['traffic_module_out'],
];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
<?php
// Parsing the refresh before sending any header
        $refresh = (int) get_parameter('refresh', SECONDS_5MINUTES);
if ($refresh > 0) {
    $query = ui_get_url_refresh(false);

    echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
}
?>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title><?php echo __('%s Interface Graph', get_product_name()).' ('.agents_get_alias($agent_id).' - '.$interface_name; ?>)</title>
        <link rel="stylesheet" href="../../include/styles/pandora_minimal.css" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/js/jquery-ui.min.css" type="text/css" />
        <script type='text/javascript' src='../../include/javascript/pandora.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery-3.3.1.min.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery.pandora.js'></script>
        <script type='text/javascript' src='../../include/javascript/jquery-ui.min.js'></script>
        <?php
        require_once $config['homedir'].'/include/graphs/functions_flot.php';
            echo include_javascript_dependencies_flot_graph(true, '../');
        ?>
            <script type='text/javascript'>
            <!--
            window.onload = function() {
                // Hack to repeat the init process to period select
                var periodSelectId = $('[name="period"]').attr('class');

                period_select_init(periodSelectId);
            };
            -->
        </script>
    </head>
    <body style='background:#ffffff;'>
<?php
// ACL
        $all_groups = agents_get_all_groups_agent($agent_id);
if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AR')) {
    include $config['homedir'].'/general/noaccess.php';
    exit;
}

        // Get input parameters
        $period = get_parameter('period', SECONDS_1DAY);
        $width = (int) get_parameter('width', 555);
        $height = (int) get_parameter('height', 245);
        $start_date = (string) get_parameter('start_date', date('Y-m-d'));
        $start_time = get_parameter('start_time', date('H:i:s'));
        $zoom = (int) get_parameter('zoom', $config['zoom_graph']);
        $baseline = get_parameter('baseline', 0);
        $show_percentil = get_parameter('show_percentil', 0);
        $fullscale = get_parameter('fullscale');

if (!isset($_GET['fullscale_sent'])) {
    if (!isset($config['full_scale_option'])
        || $config['full_scale_option'] == 0
        || $config['full_scale_option'] == 2
    ) {
        $fullscale = 0;
    } else {
        $fullscale = 1;
    }
}

        $date = strtotime("$start_date $start_time");
        $now = time();

if ($date > $now) {
    $date = $now;
}

        $urlImage = ui_get_full_url(false);

        // Graph.
        echo '<div style="padding-top:80px;">';

        $height = 400;
        $width  = '90%';

        $params = [
            'period'    => $period,
            'width'     => $width,
            'height'    => $height,
            'unit_name' => array_fill(0, count($interface_traffic_modules), $config['interface_unit']),
            'date'      => $date,
            'homeurl'   => $config['homeurl'],
            'percentil' => (($show_percentil) ? $config['percentil'] : null),
            'fullscale' => $fullscale,
            'zoom'      => $zoom,
        ];

        if ($config['type_interface_charts'] == 'line') {
            $stacked = CUSTOM_GRAPH_LINE;
        } else {
            $stacked = CUSTOM_GRAPH_AREA;
        }

        $params_combined = [
            'weight_list'    => [],
            'projection'     => false,
            'labels'         => array_keys($interface_traffic_modules),
            'from_interface' => true,
            'modules_series' => array_values($interface_traffic_modules),
            'return'         => 0,
            'stacked'        => $stacked,
        ];

        graphic_combined_module(
            array_values($interface_traffic_modules),
            $params,
            $params_combined
        );

        echo '</div>';

        // FORM TABLE
        $table = html_get_predefined_table('transparent', 2);
        $table->width = '100%';
        $table->id = 'stat_win_form_div';
        $table->style[0] = 'text-align:left;';
        $table->style[1] = 'text-align:left;';
        $table->styleTable = 'margin-bottom: 20px;';
        $table->class = 'table_modal_alternate';

        $data = [];
        $data[0] = __('Refresh time');
        $data[1] = html_print_extended_select_for_time('refresh', $refresh, '', '', 0, 7, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Begin date');
        $data[1] = html_print_input_text('start_date', substr($start_date, 0, 10), '', 15, 255, true);
        $data[1] .= html_print_image('/images/calendar_view_day.png', true, ['onclick' => "scwShow(scwID('text-start_date'),this);", 'style' => 'vertical-align: bottom;'], false, false, false, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Begin time');
        $data[1] = html_print_input_text('start_time', $start_time, '', 10, 10, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Time range');
        $data[1] = html_print_extended_select_for_time('period', $period, '', '', 0, 7, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Show percentil');
        $data[1] = html_print_checkbox_switch('show_percentil', 1, (bool) $show_percentil, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Show full scale graph (TIP)').ui_print_help_tip(
            __('This option may cause performance issues'),
            true,
            'images/tip.png',
            true
        );
        $data[1] = html_print_checkbox_switch('fullscale', 1, (bool) $fullscale, true);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $data = [];
        $data[0] = __('Zoom factor');
        $options = [];
        $options[$zoom] = 'x'.$zoom;
        $options[1] = 'x1';
        $options[2] = 'x2';
        $options[3] = 'x3';
        $options[4] = 'x4';
        $options[5] = __('Full');
        $data[1] = html_print_select($options, 'zoom', $zoom, '', '', 0, true, false, false);
        $table->data[] = $data;
        $table->rowclass[] = '';

        $form_table = html_print_table($table, true);
        $form_table .= '<div style="width:100%; text-align:right;">'.html_print_submit_button(
            __('Reload'),
            'submit',
            false,
            'class="sub upd"',
            true
        ).'</div>';


        // Menu.
        $menu_form = "<form method='get' action='interface_traffic_graph_win.php'>".html_print_input_hidden('params', base64_encode($params_json), true);

        if (!empty($server_id)) {
            $menu_form .= html_print_input_hidden('server', $server_id, true);
        }

        echo $menu_form;
        echo '<div class="module_graph_menu_dropdown">
                <div id="module_graph_menu_header" class="module_graph_menu_header">
                    '.html_print_image('images/arrow_down_green.png', true, ['class' => 'module_graph_menu_arrow', 'float' => 'left'], false, false, true).'
                    <span>'.__('Graph configuration menu').'</span>
                    '.html_print_image('images/config.png', true, ['float' => 'right'], false, false, true).'
                </div>
                <div class="module_graph_menu_content module_graph_menu_content_closed" style="display:none;">'.$form_table.'</div>
            </div>';
        echo '</form>';

        // Hidden div to forced title
        html_print_div(['id' => 'forced_title_layer', 'class' => 'forced_title_layer', 'hidden' => true]);
        ?>

    </body>
</html>
<?php
// Echo the script tags of the datepicker and the timepicker
// Modify the user language cause the ui.datepicker language files use - instead _
$custom_user_language = str_replace('_', '-', $user_language);
ui_require_jquery_file('ui.datepicker-'.$custom_user_language, 'include/javascript/i18n/', true);
ui_include_time_picker(true);
?>
<script>
    var show_overview = false;
    var height_window;
    var width_window;
    $(document).ready(function() {
        height_window = $(window).height();
        width_window = $(window).width();
    });

    $("*").filter(function() {
        if (typeof(this.id) == "string")
            return this.id.match(/menu_overview_graph.*/);
        else
            return false;
        }).click(function() {
            show_overview = !show_overview;
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

    $.datepicker.setDefaults($.datepicker.regional["<?php echo $custom_user_language; ?>"]);

    forced_title_callback();

    // Menu.
    $('#module_graph_menu_header').on('click', function(){
        var arrow = $('#module_graph_menu_header .module_graph_menu_arrow');
        var arrow_up = 'arrow_up_green';
        var arrow_down = 'arrow_down_green';
        if( $('.module_graph_menu_content').hasClass('module_graph_menu_content_closed')){
            $('.module_graph_menu_content').show();
            $('.module_graph_menu_content').removeClass('module_graph_menu_content_closed');          
            arrow.attr('src',arrow.attr('src').replace(arrow_down, arrow_up));
        }
        else{
            $('.module_graph_menu_content').hide();
            $('.module_graph_menu_content').addClass('module_graph_menu_content_closed'); 
            arrow.attr('src',arrow.attr('src').replace(arrow_up, arrow_down));
        }
    });

</script>
