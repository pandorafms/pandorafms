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
require_once 'config.php';

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
$data_decoded = json_decode(base64_decode($data_raw), true);
if (json_last_error() === JSON_ERROR_NONE) {
    $data = urldecode($data_decoded['data']);
    $session_id = urldecode($data_decoded['session_id']);
    $data_combined = urldecode($data_decoded['data_combined']);
    $data_module_list = urldecode($data_decoded['data_module_list']);
    $type_graph_pdf = urldecode($data_decoded['type_graph_pdf']);
    $viewport_width = urldecode($data_decoded['viewport_width']);
}


/**
 * Echo to stdout a PhantomJS callback call.
 *
 * @return void
 */
function echoPhantomCallback()
{
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
    <?php
}


// Initialize session.
global $config;

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
    <link rel="stylesheet" href="styles/pandora.css" type="text/css" />
    <link rel="stylesheet" href="styles/pandora_minimal.css" type="text/css" />
    <link rel="stylesheet" href="styles/js/jquery-ui.min.css" type="text/css" />
    <link rel="stylesheet" href="styles/js/jquery-ui_custom.css" type="text/css" />
    <script language="javascript" type='text/javascript' src='javascript/pandora.js'></script>
    <script language="javascript" type='text/javascript' src='javascript/pandora_ui.js'></script>
    <script language="javascript" type='text/javascript' src='javascript/jquery.current.js'></script>
</head>
<body>
    <h1>Access is not granted</h1>
    <?php echoPhantomCallback(); ?>
</body>
</html>

    <?php
    exit;
}

// Access granted.
$params = json_decode($data, true);

// Metaconsole connection to the node.
$server_id = $params['server_id'];

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
        echoPhantomCallback();
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
        <title>Pandora FMS Graph (<?php echo agents_get_alias($agent_id).' - '.$interface_name; ?>)</title>
        <link rel="stylesheet" href="styles/pandora.css" type="text/css" />
        <link rel="stylesheet" href="styles/pandora_minimal.css" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui.min.css" type="text/css" />
        <link rel="stylesheet" href="styles/js/jquery-ui_custom.css" type="text/css" />
        <script language="javascript" type='text/javascript' src='javascript/pandora.js'></script>
        <script language="javascript" type='text/javascript' src='javascript/pandora_ui.js'></script>
        <script language="javascript" type='text/javascript' src='javascript/jquery.current.js'></script>
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
    <body style='background-color: <?php echo $params['backgroundColor']; ?>;'>
    <?php
    $params['only_image'] = false;
    $params['menu'] = false;

    $params['disable_black'] = true;
    $params_combined = json_decode($data_combined, true);
    $module_list = json_decode($data_module_list, true);

    if (isset($params['vconsole']) === false || $params['vconsole'] === false) {
        if ((int) $viewport_width > 0) {
            $params['width'] = (int) $viewport_width;
        }

        if ((isset($params['width']) === false
            || ($params['width'] <= 0))
        ) {
            if ((int) $params['width'] <= 0) {
                $params['width'] = 650;
            }

            if ((int) $params['landscape'] === 1) {
                $params['width'] = 850;
            }

            if ($type_graph_pdf === 'slicebar') {
                $params['width'] = 100;
                $params['height'] = 70;
            }
        }
    }

        echo '<div>';
    switch ($type_graph_pdf) {
        case 'combined':
            $params['pdf'] = true;
            echo graphic_combined_module(
                $module_list,
                $params,
                $params_combined
            );
        break;

        case 'sparse':
            $params['pdf'] = true;
            echo grafico_modulo_sparse($params);
        break;

        case 'pie_chart':
            $params['pdf'] = true;
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
            $params['pdf'] = true;
            echo flot_vcolumn_chart($params);
        break;

        case 'hbar':
            $params['pdf'] = true;
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
                $params['val_max'],
                $params['pdf']
            );
        break;

        case 'ring_graph':
            $params['pdf'] = true;
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
                $params['background_color'],
                $params['pdf']
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
        echoPhantomCallback();
    ?>
    </body>
</html>
