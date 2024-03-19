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
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
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

global $config;
echo '<link rel="stylesheet" href="../../include/styles/pandora.css?v='.$config['current_package'].'" type="text/css"/>';

if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
    echo '<link rel="stylesheet" href="../../include/styles/pandora_black.css?v='.$config['current_package'].'" type="text/css"/>';
}


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
$conn_title = __('Connection with console has been lost');
$conn_text = __('Connection to the console has been lost. Please check your internet connection.');
ui_require_javascript_file('connection_check');
set_js_value('absolute_homeurl', ui_get_full_url(false, false, false, false));
ui_print_message_dialog(
    $conn_title,
    $conn_text,
    'connection',
    '/images/fail@svg.svg'
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
        <link rel="stylesheet" href="../../include/styles/pandora_minimal.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/js/jquery-ui.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/js/jquery-ui_custom.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="../../include/styles/select2.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <script type='text/javascript' src='../../include/javascript/pandora_ui.js?v=<?php echo $config['current_package']; ?>'></script>
        <script type='text/javascript' src='../../include/javascript/jquery.current.js?v=<?php echo $config['current_package']; ?>'></script>
        <script type='text/javascript' src='../../include/javascript/jquery.pandora.js?v=<?php echo $config['current_package']; ?>'></script>
        <script type='text/javascript' src='../../include/javascript/jquery-ui.min.js?v=<?php echo $config['current_package']; ?>'></script>
        <script type='text/javascript' src='../../include/javascript/select2.min.js?v=<?php echo $config['current_package']; ?>'></script>
        <script type='text/javascript' src='../../include/javascript/pandora.js?v=<?php echo $config['current_package']; ?>'></script>
        <?php
        require_once $config['homedir'].'/include/graphs/functions_flot.php';
            echo include_javascript_dependencies_flot_graph(true, '../');
            echo ui_require_css_file('events', 'include/styles/', true);
        ?>
        <script type='text/javascript'>
            window.onload = function() {
                // Hack to repeat the init process to period select
                var periodSelectId = $('[name="period"]').attr('class');

                period_select_init(periodSelectId);
            };
        </script>
    </head>
    <body class='bg_general'>
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

        // If in metaconsole, resotre DB to check meta user acl.
        if (is_metaconsole()) {
            metaconsole_restore_db();
        }

        if (check_acl_one_of_groups($config['id_user'], $all_groups, 'AR') !== true) {
            include $config['homedir'].'/general/noaccess.php';
            exit;
        }

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

        $draw_alerts = get_parameter_checkbox('draw_alerts', 0);

        $period = get_parameter('period');
        $id     = get_parameter('id', 0);
        $start_date = get_parameter('start_date', date('Y/m/d'));
        $start_time = get_parameter('start_time', date('H:i:s'));
        $draw_events = get_parameter_checkbox('draw_events', 0);
        $graph_type = get_parameter('type', 'sparse');
        $zoom = get_parameter('zoom', $config['zoom_graph']);
        $baseline = get_parameter('baseline', 0);
        $show_events_graph = get_parameter('show_events_graph', 0);
        $show_percentil = get_parameter_checkbox('show_percentil', 0);
        $time_compare_separated = get_parameter_checkbox('time_compare_separated', 0);
        $time_compare_overlapped = get_parameter_checkbox('time_compare_overlapped', 0);
        $unknown_graph = get_parameter_checkbox('unknown_graph', 1);
        $histogram = (bool) get_parameter('histogram', 0);
        $period_graph = (int) get_parameter('period_graph', 0);
        $enable_projected_period = get_parameter_checkbox('enable_projected_period', 0);
        $period_projected = get_parameter('period_projected', SECONDS_5MINUTES);

        $period_maximum = get_parameter_checkbox('period_maximum', 1);
        $period_minimum = get_parameter_checkbox('period_minimum', 1);
        $period_average = get_parameter_checkbox('period_average', 1);
        $period_summatory = get_parameter_checkbox('period_summatory', 0);
        $period_slice_chart = get_parameter('period_slice_chart', SECONDS_1HOUR);
        $period_mode = get_parameter('period_mode', CUSTOM_GRAPH_VBARS);

        $graph_tab = get_parameter('graph_tab', 'tabs-chart-module-graph');

        $time_compare = false;

        if ($time_compare_separated) {
            $time_compare = 'separated';
        } else if ($time_compare_overlapped) {
            $time_compare = 'overlapped';
        }

        if ($histogram === false) {
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
                $fullscale = get_parameter_checkbox('fullscale', 0);
            }

            $type_mode_graph = get_parameter_checkbox(
                'type_mode_graph',
                ($fullscale === 1) ? 0 : $config['type_mode_graph']
            );

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

            if (isset($_GET['type']) === true) {
                $type = get_parameter_get('type');
            }
        }

        $form_data = [
            'id'                      => $id,
            'refresh'                 => $refresh,
            'draw_events'             => $draw_events,
            'draw_alerts'             => $draw_alerts,
            'start_date'              => $start_date,
            'start_time'              => $start_time,
            'unknown_graph'           => $unknown_graph,
            'period'                  => $period,
            'zoom'                    => $zoom,
            'show_percentil'          => $show_percentil,
            'time_compare_overlapped' => $time_compare_overlapped,
            'time_compare_separated'  => $time_compare_separated,
            'type_mode_graph'         => $type_mode_graph,
            'fullscale'               => $fullscale,
            'enable_projected_period' => $enable_projected_period,
            'period_projected'        => $period_projected,
            'type'                    => $type,
            'label'                   => $label,
            'server_id'               => $server_id,
            'histogram'               => $histogram,
            'period_graph'            => $period_graph,
            'period_maximum'          => $period_maximum,
            'period_minimum'          => $period_minimum,
            'period_average'          => $period_average,
            'period_summatory'        => $period_summatory,
            'period_slice_chart'      => $period_slice_chart,
            'period_mode'             => $period_mode,
            'graph_tab'               => $graph_tab,
        ];

        $params = [
            'agent_module_id'         => $id,
            'period'                  => $period,
            'show_events'             => $draw_events,
            'title'                   => $label,
            'unit_name'               => $unit,
            'show_alerts'             => $draw_alerts,
            'date'                    => $date,
            'unit'                    => $unit,
            'baseline'                => $baseline,
            'homeurl'                 => $urlImage,
            'adapt_key'               => 'adapter_'.$graph_type,
            'compare'                 => $time_compare,
            'show_unknown'            => $unknown_graph,
            'percentil'               => (($show_percentil) ? $config['percentil'] : null),
            'type_graph'              => $config['type_module_charts'],
            'fullscale'               => $fullscale,
            'zoom'                    => $zoom,
            'height'                  => 300,
            'type_mode_graph'         => $type_mode_graph,
            'histogram'               => $histogram,
            'begin_date'              => strtotime($start_date.' '.$start_time),
            'enable_projected_period' => $enable_projected_period,
            'period_projected'        => $period_projected,
            'period_maximum'          => $period_maximum,
            'period_minimum'          => $period_minimum,
            'period_average'          => $period_average,
            'period_summatory'        => $period_summatory,
            'period_slice_chart'      => $period_slice_chart,
            'period_mode'             => $period_mode,
            'graph_tab'               => $graph_tab,
        ];

        if ($histogram === false) {
            $output = '<div id="chart-modal">';
            $output .= '<div id="chart-module-graph" class="margin-top-10">';
            $output .= draw_container_chart_stat_win($graph_tab);
            $output .= '</div>';
            $output .= '</div>';
        } else {
            // Graph.
            $output .= draw_container_chart_stat_win();
        }

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
        var graph_data = "<?php echo base64_encode(json_encode($params)); ?>";
        var form_data = "<?php echo base64_encode(json_encode($form_data)); ?>";
        var url = "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>";
        var serverId = "<?php echo $server_id; ?>";
        var histogram = "<?php echo (int) $histogram; ?>";
        var period_graph = "<?php echo ($period_graph == 1) ? 1 : 0; ?>";
        if(histogram == 0) {
            var tab_active = '<?php echo $graph_tab; ?>';
            get_ajax_module(url, graph_data, form_data, serverId, tab_active);
        } else {
            get_ajax_module(url, graph_data, form_data, serverId, null);
        }
    });

    function change_tabs_periodicity(tab_active) {;
        let pg = 0;
        if (tab_active === 'ui-id-2') {
            pg = 1;
        }

        var regex = /(period_graph=)[^&]+(&?)/gi;
        var replacement = "$1" + pg + "$2";
        // Change the URL (if the browser has support).
        if ("history" in window) {
            var href = window.location.href.replace(regex, replacement);
            window.history.replaceState({}, document.title, href);
        }
    }

    function get_ajax_module(url, graph_data, form_data, serverId, id) {
        let active = 'stat-win-module-graph';
        if(id != null) {
            active = id;
        }
        $("#tabs-chart-module-graph-content").empty();
        $("#tabs-chart-period-graph-content").empty();
        $("#"+active+"-spinner").show();
        $.ajax({
            type: "POST",
            url: url,
            dataType: "html",
            data: {
                page: "include/ajax/module",
                get_graph_module: true,
                graph_data: graph_data,
                form_data: form_data,
                server_id: serverId,
                active: active
            },
            success: function (data) {
                $("#"+active+"-spinner").hide();
                $("#"+active+"-content").append(data);
                if (active === 'tabs-chart-module-graph' || active === 'tabs-chart-period-graph') {
                    let margin = 100;
                    if (navigator.userAgent.indexOf("Chrome") != -1) {
                        margin = 100;
                    } else if (navigator.userAgent.indexOf("Firefox") != -1) {
                        margin = 50;
                    }

                    var browserZoomLevel = window.outerWidth / window.innerWidth;
                    let height = ($('#chart-modal').height() + margin) * browserZoomLevel;
                    let width = 800 * browserZoomLevel;
                    window.resizeTo(width, height);
                }

                let pg = 0;
                if (active === 'tabs-chart-period-graph') {
                    pg = 1;
                }
                $('#hidden-period_graph').val(pg);
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
            },
            error: function (error) {
                console.error(error);
            }
        });
    }
</script>
