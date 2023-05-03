<?php
/**
 * View interfaces charts.
 *
 * @category   View interfaces charts
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
enterprise_include_once('include/functions_agents.php');

check_login();

$params_json = base64_decode((string) get_parameter('params'));
$params = json_decode($params_json, true);

// Metaconsole connection to the node.
$server_id = (int) (isset($params['server']) ? $params['server'] : 0);
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

echo '<link rel="stylesheet" href="../../include/styles/pandora.css?v='.$config['current_package'].'" type="text/css"/>';
if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    ui_require_css_file('pandora_black', 'include/styles/', true);
}

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
// Parsing the refresh before sending any header.
$refresh = (int) get_parameter('refresh', SECONDS_5MINUTES);
if ($refresh > 0) {
    $query = ui_get_url_refresh(false);

    echo '<meta http-equiv="refresh" content="'.$refresh.'; URL='.$query.'" />';
}
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title><?php echo __('%s Interface Graph', get_product_name()).' ('.agents_get_alias($agent_id).' - '.$interface_name; ?>)</title>
<link rel="stylesheet" href="../../include/styles/pandora_minimal.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
<link rel="stylesheet" href="../../include/styles/js/jquery-ui.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
<link rel="stylesheet" href="../../include/styles/select2.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
<script type='text/javascript' src='../../include/javascript/pandora.js?v=<?php echo $config['current_package']; ?>'></script>
<script type='text/javascript' src='../../include/javascript/jquery.current.js?v=<?php echo $config['current_package']; ?>'></script>
<script type='text/javascript' src='../../include/javascript/jquery.pandora.js?v=<?php echo $config['current_package']; ?>'></script>
<script type='text/javascript' src='../../include/javascript/jquery-ui.min.js?v=<?php echo $config['current_package']; ?>'></script>
<script type='text/javascript' src='../../include/javascript/select2.min.js?v=<?php echo $config['current_package']; ?>'></script>
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
<body class='bg_white'>
<?php
// ACL.
$all_groups = agents_get_all_groups_agent($agent_id);
if (!check_acl_one_of_groups($config['id_user'], $all_groups, 'AR')) {
    include $config['homedir'].'/general/noaccess.php';
    exit;
}

// Get input parameters.
$period = get_parameter('period', SECONDS_1DAY);
$width = (int) get_parameter('width', 555);
$height = (int) get_parameter('height', 245);
$start_date = (string) get_parameter('start_date', date('Y-m-d'));
$start_time = get_parameter('start_time', date('H:i:s'));
$zoom = (int) get_parameter('zoom', $config['zoom_graph']);
$baseline = get_parameter('baseline', 0);
$show_percentil = get_parameter('show_percentil', 0);
$fullscale = get_parameter('fullscale');

if (isset($_GET['fullscale_sent']) === false) {
    if (isset($config['full_scale_option']) === false
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

// FORM TABLE.
$table = html_get_predefined_table('transparent', 2);
$table->width = '100%';
$table->id = 'stat_win_form_div';
$table->style[0] = 'text-align:left;';
$table->style[1] = 'text-align:left;';
$table->styleTable = 'margin-bottom: 20px;';
$table->class = 'table_modal_alternate';

$data = [];
$data[0] = __('Refresh time');
$data[1] = '<div class="small-input-select2">'.html_print_extended_select_for_time(
    'refresh',
    $refresh,
    '',
    '',
    0,
    7,
    true
).'</div>';
$table->data[] = $data;
$table->rowclass[] = '';

$data = [];
$data[0] = __('Begin date');
$data[1] = html_print_input_text(
    'start_date',
    substr($start_date, 0, 10),
    '',
    15,
    255,
    true,
    false,
    false,
    '',
    'small-input'
);
$data[1] .= html_print_image(
    '/images/calendar_view_day.png',
    true,
    [
        'onclick' => "scwShow(scwID('text-start_date'),this);",
        'style'   => 'vertical-align: bottom;',
        'class'   => 'invert_filter',
        'style'   => 'vertical-align: middle;',
    ],
    false,
    false,
    false,
    true
);
$table->data[] = $data;
$table->rowclass[] = '';

$data = [];
$data[0] = __('Begin time');
$data[1] = html_print_input_text(
    'start_time',
    $start_time,
    '',
    10,
    10,
    true,
    false,
    false,
    '',
    'small-input'
);
$table->data[] = $data;
$table->rowclass[] = '';

$data = [];
$data[0] = __('Time range');
$data[1] = '<div class="small-input-select2">'.html_print_extended_select_for_time(
    'period',
    $period,
    '',
    '',
    0,
    7,
    true
).'</div>';
$table->data[] = $data;
$table->rowclass[] = '';

$data = [];
$data[0] = __('Show percentil');
$data[1] = html_print_checkbox_switch(
    'show_percentil',
    1,
    (bool) $show_percentil,
    true
);
$table->data[] = $data;
$table->rowclass[] = '';

$data = [];
$data[0] = __('Show full scale graph (TIP)');
$data[0] .= ui_print_help_tip(
    __('TIP mode charts do not support average - maximum - minimum series, you can only enable TIP or average, maximum or minimum series'),
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
/*
    $data[1] = html_print_select(
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
    $table->data[] = $data;
$table->rowclass[] = '';*/

$form_table = html_print_table($table, true);
$form_table .= '<div class="w100p right right_align">';
$form_table .= html_print_submit_button(
    __('Reload'),
    'submit',
    false,
    [
        'class' => 'float-right mini',
        'icon'  => 'upd',
    ],
    true
);
$form_table .= '</div>';


// Menu.
$menu_form = "<form method='get' action='interface_traffic_graph_win.php' class='mrgn_top-10px'>".html_print_input_hidden('params', base64_encode($params_json), true);

if (empty($server_id) === false) {
    $menu_form .= html_print_input_hidden('server', $server_id, true);
}

$menu_form .= $form_table;
$menu_form .= '</form>';

ui_toggle(
    $menu_form,
    '<span class="subsection_header_title">'.__('Graph configuration menu').ui_print_help_tip(
        __('In Pandora FMS, data is stored compressed. The data visualization in database, charts or CSV exported data won\'t match, because is interpreted at runtime. Please check \'Pandora FMS Engineering\' chapter from documentation.'),
        true
    ).'</span>',
    __('Graph configuration menu'),
    'update',
    true,
    false,
    '',
    'white-box-content no_border',
    'filter-datatable-main box-flat white_table_graph fixed_filter_bar top_0px_important toggle-traffic-graph'
);


// Hidden div to forced title.
html_print_div(
    [
        'id'     => 'forced_title_layer',
        'class'  => 'forced_title_layer',
        'hidden' => true,
    ]
);

// Graph.
$output = '<div id="stat-win-interface-graph">';

$height = 280;
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

if (is_metaconsole() === true) {
    $params['server_id'] = $server_id;
}

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

$modules = array_values($interface_traffic_modules);
$output .= '<div id="stat-win-spinner" class="stat-win-spinner">';
$output .= html_print_image('images/spinner_charts.gif', true);
$output .= '</div>';

$output .= '</div>';
echo $output;
?>

    </body>
</html>
<?php
// Echo the script tags of the datepicker and the timepicker
// Modify the user language cause the ui.datepicker
// language files use - instead.
$custom_user_language = str_replace('_', '-', $user_language);
ui_require_jquery_file(
    'ui.datepicker-'.$custom_user_language,
    'include/javascript/i18n/',
    true
);
ui_include_time_picker(true);

if (is_metaconsole() === true && empty($server_id) === false) {
    metaconsole_restore_db();
}
?>
<script>
    var show_overview = false;
    var height_window;
    var width_window;
    $(document).ready(function() {
        height_window = $(window).height();
        width_window = $(window).width();

        var graph_data = "<?php echo base64_encode(json_encode($params)); ?>";
        var modules = "<?php echo base64_encode(json_encode($modules)); ?>";
        var graph_data_combined = "<?php echo base64_encode(json_encode($params_combined)); ?>";
        var url = "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>";
        var serverId = "<?php echo $server_id; ?>";
        get_ajax_modules_interfaces(
            url,
            graph_data,
            serverId,
            graph_data_combined,
            modules
        );
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

    $.datepicker.setDefaults(
        $.datepicker.regional["<?php echo $custom_user_language; ?>"]
    );

    forced_title_callback();

    // Menu.
    $('#module_graph_menu_header').on('click', function(){
        var arrow = $('#module_graph_menu_header .module_graph_menu_arrow');
        var arrow_up = 'arrow_up_green';
        var arrow_down = 'arrow_down_green';
        if( $('.module_graph_menu_content').hasClass('module_graph_menu_content_closed')){
            $('.module_graph_menu_content').show();
            $('.module_graph_menu_content')
                .removeClass('module_graph_menu_content_closed');
            arrow.attr('src',arrow.attr('src').replace(arrow_down, arrow_up));
        }
        else{
            $('.module_graph_menu_content').hide();
            $('.module_graph_menu_content')
                .addClass('module_graph_menu_content_closed');
            arrow.attr('src',arrow.attr('src').replace(arrow_up, arrow_down));
        }
    });

    function get_ajax_modules_interfaces(url, graph_data, serverId, graph_data_combined, modules) {
        $.ajax({
            type: "POST",
            url: url,
            dataType: "html",
            data: {
                page: "include/ajax/module",
                get_graph_module_interfaces: true,
                graph_data: graph_data,
                server_id: serverId,
                graph_data_combined: graph_data_combined,
                modules: modules
            },
            success: function (data) {
                $("#stat-win-spinner").hide();
                $("#stat-win-interface-graph").append(data);
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
</script>
