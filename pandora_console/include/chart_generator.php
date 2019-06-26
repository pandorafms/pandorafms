<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2018 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Global & session manageme
session_id($_REQUEST['session_id']);

require_once 'config.php';
require_once $config['homedir'].'/include/auth/mysql.php';
require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/functions_db.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_tags.php';

check_login();

global $config;
// get_parameter(array)('data', '');
$params = json_decode($_REQUEST['data'], true);

// Metaconsole connection to the node
$server_id = $params['server_id'];

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
if (file_exists('languages/'.$user_language.'.mo')) {
    $l10n = new gettext_reader(new CachedFileReader('languages/'.$user_language.'.mo'));
    $l10n->load_tables();
}

?>
<!DOCTYPE>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Pandora FMS Graph (<?php echo agents_get_alias($agent_id).' - '.$interface_name; ?>)</title>
        <link rel="stylesheet" href="styles/pandora.css" type="text/css" />
        <link rel="stylesheet" href="styles/pandora_minimal.css" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui.min.css" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui_custom.css" type="text/css" />
        <script language="javascript" type='text/javascript' src='javascript/pandora.js'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery-3.3.1.min.js'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery.pandora.js'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery-ui.min.js'></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.time.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.pie.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.crosshair.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.stack.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.selection.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.resize.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.multiple.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.symbol.min.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.exportdata.pandora.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.axislabels.js"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/pandora.flot.js"></script>
    </head>
    <body bgcolor="#ffffff" style='background:#ffffff;'>
    <?php
    $params['only_image'] = false;
        $params['width']      = (int) $_REQUEST['viewport_width'];
        $params['menu']       = false;

    if ((!isset($params['width']) || ($params['width'] <= 0))) {
        $params['width'] = 650;
    }

        $params_combined = json_decode($_REQUEST['data_combined'], true);
        $module_list     = json_decode($_REQUEST['data_module_list'], true);
        $type_graph_pdf  = $_REQUEST['type_graph_pdf'];

        echo '<div>';
    switch ($type_graph_pdf) {
        case 'combined':
            echo graphic_combined_module(
                $module_list,
                $params,
                $params_combined
            );
        break;

        case 'sparse':
            echo grafico_modulo_sparse($params);
        break;

        case 'pie_chart':
            echo flot_pie_chart(
                $params['values'],
                $params['keys'],
                $params['width'],
                $params['height'],
                $params['water_mark_url'],
                $params['font'],
                $config['font_size'],
                $params['legend_position'],
                $params['colors'],
                $params['hide_labels']
            );
        break;

        case 'vbar':
            echo flot_vcolumn_chart(
                $params['chart_data'],
                $params['width'],
                $params['height'],
                $params['color'],
                $params['legend'],
                $params['long_index'],
                $params['homeurl'],
                $params['unit'],
                $params['water_mark_url'],
                $params['homedir'],
                $params['font'],
                $config['font_size'],
                $params['from_ux'],
                $params['from_wux'],
                $params['backgroundColor'],
                $params['tick_color']
            );
        break;

        case 'hbar':
            echo flot_hcolumn_chart(
                $params['chart_data'],
                $params['width'],
                $params['height'],
                $params['water_mark_url'],
                $params['font'],
                $config['font_size'],
                $params['backgroundColor'],
                $params['tick_color'],
                $params['val_min'],
                $params['val_max']
            );
        break;

        case 'ring_graph':
            echo flot_custom_pie_chart(
                $params['chart_data'],
                $params['width'],
                $params['height'],
                $params['colors'],
                $params['module_name_list'],
                $params['long_index'],
                $params['no_data'],
                false,
                '',
                $params['water_mark'],
                $params['font'],
                $config['font_size'],
                $params['unit'],
                $params['ttl'],
                $params['homeurl'],
                $params['background_color'],
                $params['legend_position'],
                $params['background_color']
            );
        break;

        case 'slicebar':
            echo flot_slicesbar_graph(
                $params['graph_data'],
                $params['period'],
                $params['width'],
                $params['height'],
                $params['legend'],
                $params['colors'],
                $params['fontpath'],
                $params['round_corner'],
                $params['homeurl'],
                $params['watermark'],
                $params['adapt_key'],
                $params['stat_winalse'],
                $params['id_agent'],
                $params['full_legend_daterray'],
                $params['not_interactive'],
                $params['ttl'],
                $params['widgets'],
                $params['show']
            );
        break;

        default:
            // code...
        break;
    }

        echo '</div>';

    ?>

    <script type="text/javascript">
        $('document').ready(function () {
            setTimeout(function () {
                try {
                    var status = window.callPhantom({ status: "loaded" });
                } catch (error) {
                    console.log("CALLBACK ERROR", error.message)
                }
            }, 100);
        });
    </script>

    </body>
</html>
