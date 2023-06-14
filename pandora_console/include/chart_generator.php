<?php
/**
 * Generate charts with given parameters.
 *
 * @category   ChartGenerator.
 * @package    Pandora FMS
 * @subpackage Opensource.
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
require_once __DIR__.'/config.php';
require_once __DIR__.'/functions.php';
require_once __DIR__.'/functions_db.php';
require_once __DIR__.'/auth/mysql.php';
require_once $config['homedir'].'/include/lib/User.php';
require_once $config['homedir'].'/include/functions_reporting.php';
require_once $config['homedir'].'/include/functions_graph.php';
require_once $config['homedir'].'/include/functions_custom_graphs.php';
require_once $config['homedir'].'/include/functions_modules.php';
require_once $config['homedir'].'/include/functions_agents.php';
require_once $config['homedir'].'/include/functions_tags.php';

$data_raw = get_parameter('data');
$data_decoded = json_decode(io_safe_output($data_raw), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $data = $data_decoded['data'];
    $session_id = $data_decoded['session_id'];
    $type_graph_pdf = $data_decoded['type_graph_pdf'];
    $id_user = $data_decoded['id_user'];
    $slicebar = $data_decoded['slicebar'];
    $slicebar_value = $data_decoded['slicebar_value'];

    $data_combined = [];
    if (isset($data_decoded['data_combined']) === true) {
        $data_combined = $data_decoded['data_combined'];
    }

    $data_module_list = [];
    if (isset($data_decoded['data_module_list']) === true) {
        $data_module_list = $data_decoded['data_module_list'];
    }
}

// Initialize session.
global $config;

// Care whit this!!! check_login not working if you remove this.
$config['id_user'] = $id_user;
$_SESSION['id_usuario'] = $id_user;
if (!isset($config[$slicebar])) {
    $config[$slicebar] = $slicebar_value;
}

// Try to initialize session using existing php session id.
$user = new PandoraFMS\User(['phpsessionid' => $session_id]);
if (check_login(false) === false) {
    // Error handler.
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Access denied</title>
    <link rel="stylesheet" href="styles/pandora.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
    <link rel="stylesheet" href="styles/pandora_minimal.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
    <link rel="stylesheet" href="styles/js/jquery-ui.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
    <link rel="stylesheet" href="styles/js/jquery-ui_custom.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
    <script language="javascript" type='text/javascript' src='javascript/pandora.js?v=<?php echo $config['current_package']; ?>'></script>
    <script language="javascript" type='text/javascript' src='javascript/pandora_ui.js?v=<?php echo $config['current_package']; ?>'></script>
    <script language="javascript" type='text/javascript' src='javascript/jquery.current.js?v=<?php echo $config['current_package']; ?>'></script>
</head>
<body>
    <h1>Access is not granted</h1>
    <div id="container-chart-generator-item" style="display:none; margin:0px;width:100px;height:100px;">
</body>
</html>

    <?php
    exit;
}

// Access granted.
$params = $data;
if (isset($params['backgroundColor']) === false) {
    $params['backgroundColor'] = 'inherit';
}

// Metaconsole connection to the node.
$server_id = 0;
if (isset($params['server_id']) === true) {
    $server_id = $params['server_id'];
}

if (is_metaconsole() === true && empty($server_id) === false) {
    $server = metaconsole_get_connection_by_id($server_id);
    // Error connecting.
    if (metaconsole_connect($server) !== NOERR) {
        ?>
        <html>
        <body>
        <?php
        ui_print_error_message(
            __('There was a problem connecting with the node')
        );
        ?>
        </body>
        </html>
        <?php
        exit;
    }
}

$user_language = get_user_language($config['id_user']);
if (file_exists('languages/'.$user_language.'.mo') === true) {
    $cfr = new CachedFileReader('languages/'.$user_language.'.mo');
    $l10n = new gettext_reader($cfr);
    $l10n->load_tables();
}

?>
<!DOCTYPE>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>Pandora FMS Graph</title>
        <link rel="stylesheet" href="styles/pandora.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="styles/pandora_minimal.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui.min.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui_custom.css?v=<?php echo $config['current_package']; ?>" type="text/css" />
        <script language="javascript" type='text/javascript' src='javascript/pandora_ui.js?v=<?php echo $config['current_package']; ?>'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery.current.js?v=<?php echo $config['current_package']; ?>'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery.pandora.js?v=<?php echo $config['current_package']; ?>'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery-ui.min.js?v=<?php echo $config['current_package']; ?>'></script>
        <script language="javascript" type='text/javascript' src='javascript/pandora.js?v=<?php echo $config['current_package']; ?>'></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.time.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.pie.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.crosshair.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.stack.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.selection.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.resize.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.threshold.multiple.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.symbol.min.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.exportdata.pandora.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/jquery.flot.axislabels.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/flot/pandora.flot.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/chartjs/chart.js?v=<?php echo $config['current_package']; ?>"></script>
        <script language="javascript" type="text/javascript" src="graphs/chartjs/chartjs-plugin-datalabels.min.js?v=<?php echo $config['current_package']; ?>"></script>
    </head>
    <body style='width:794px; margin: 0px; background-color: <?php echo $params['backgroundColor']; ?>;'>
    <?php
    $params['only_image'] = false;
    $params['menu'] = false;

    $params['disable_black'] = true;
    $params_combined = $data_combined;
    $module_list = $data_module_list;

    $viewport = [
        'width'  => 0,
        'height' => 0,
    ];

    $style = 'width:100%;';
    if (isset($params['options']['viewport']) === true) {
        $viewport = $params['options']['viewport'];
        if (empty($viewport['width']) === false) {
            $style .= 'width:'.$viewport['width'].'px;';
        }

        if (empty($viewport['height']) === false) {
            $style .= 'height:'.$viewport['height'].'px;';
        }
    }

    echo '<div id="container-chart-generator-item" style="'.$style.' margin:0px;">';
    switch ($type_graph_pdf) {
        case 'combined':
            $params['pdf'] = true;
            $result = graphic_combined_module(
                $module_list,
                $params,
                $params_combined
            );

            echo $result;
        break;

        case 'sparse':
            $params['pdf'] = true;
            echo grafico_modulo_sparse($params);
        break;

        case 'pie_graph':
            $params['pdf'] = true;
            $chart = get_build_setup_charts(
                'PIE',
                $params['options'],
                $params['chart_data']
            );

            echo $chart->render(true);
        break;

        case 'vbar_graph':
            $params['pdf'] = true;
            $chart = get_build_setup_charts(
                'BAR',
                $params['options'],
                $params['chart_data']
            );

            echo $chart->render(true);
        break;

        case 'ring_graph':
            $params['pdf'] = true;
            $params['options']['width'] = 500;
            $params['options']['height'] = 500;

            $chart = get_build_setup_charts(
                'DOUGHNUT',
                $params['options'],
                $params['chart_data']
            );

            echo $chart->render(true);
        break;

        case 'slicebar':
            // TO-DO Cambiar esto para que se pase por POST, NO SE PUEDE PASAR POR GET.
            $params['graph_data'] = json_decode(io_safe_output($config[$params['tokem_config']]), true);
            delete_config_token($params['tokem_config']);
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
                $params['sizeForTicks'],
                $params['show'],
                $params['date_to'],
                $params['server_id']
            );
        break;

        default:
            // Code...
        break;
    }

        echo '</div>';
    ?>
    </body>
</html>
