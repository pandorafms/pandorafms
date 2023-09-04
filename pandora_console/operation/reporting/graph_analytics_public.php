<?php
/**
 * Graph viewer.
 *
 * @category   Reporting - Graph analytics
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

// Requires.
require_once '../../include/config.php';
require_once $config['homedir'].'/vendor/autoload.php';
require_once '../../include/functions_custom_graphs.php';

use PandoraFMS\User;

$hash = (string) get_parameter('hash');

// Check input hash.
// DO NOT move it after of get parameter user id.
if (User::validatePublicHash($hash) !== true) {
    db_pandora_audit(
        AUDIT_LOG_GRAPH_ANALYTICS_PUBLIC,
        'Trying to access public graph analytics'
    );
    include 'general/noaccess.php';
    exit;
}

$text_subtitle = isset($config['rb_product_name_alt']) ? '' : ' - '.__('the Flexible Monitoring System');
echo '
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="../../images/pandora.ico" type="image/ico">
    <title>'.get_product_name().$text_subtitle.'</title>
';

// CSS.
ui_require_css_file('common', 'include/styles/', true);
ui_require_css_file('pandora', 'include/styles/', true);
ui_require_css_file('discovery', 'include/styles/', true);
ui_require_css_file('register', 'include/styles/', true);
ui_require_css_file('order_interpreter', 'include/styles/', true);
ui_require_css_file('graph_analytics', 'include/styles/', true);
ui_require_css_file('jquery-ui.min', 'include/styles/js/', true);
ui_require_css_file('jquery-ui_custom', 'include/styles/js/', true);
ui_require_css_file('introjs', 'include/styles/js/', true);
ui_require_css_file('events', 'include/styles/', true);

// JS.
ui_require_javascript_file('jquery.current', 'include/javascript/', true);
ui_require_javascript_file('jquery.pandora', 'include/javascript/', true);
ui_require_javascript_file('jquery-ui.min', 'include/javascript/', true);
ui_require_javascript_file('jquery.countdown', 'include/javascript/', true);
ui_require_javascript_file('pandora', 'include/javascript/', true);
ui_require_javascript_file('pandora_ui', 'include/javascript/', true);
ui_require_javascript_file('pandora_events', 'include/javascript/', true);
ui_require_javascript_file('select2.min', 'include/javascript/', true);
// ui_require_javascript_file('connection_check', 'include/javascript/', true);
ui_require_javascript_file('encode_decode_base64', 'include/javascript/', true);
ui_require_javascript_file('qrcode', 'include/javascript/', true);
ui_require_javascript_file('intro', 'include/javascript/', true);
ui_require_javascript_file('clippy', 'include/javascript/', true);
ui_require_javascript_file('underscore-min', 'include/javascript/', true);

echo '
<script type="text/javascript">
    var phpTimezone = "'.date_default_timezone_get().'";
    var configHomeurl = "'.$config['homeurl'].'";
</script>
';



ui_require_javascript_file('date', 'include/javascript/timezone/src/', true);
ui_require_javascript_file('jquery.flot.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.time', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.pie', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.crosshair.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.stack.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.selection.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.resize.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.threshold', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.threshold.multiple', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.symbol.min', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.exportdata.pandora', 'include/graphs/flot/', true);
ui_require_javascript_file('jquery.flot.axislabels', 'include/graphs/flot/', true);
ui_require_javascript_file('pandora.flot', 'include/graphs/flot/', true);
ui_require_javascript_file('chart', 'include/graphs/chartjs/', true);
ui_require_javascript_file('chartjs-plugin-datalabels.min', 'include/graphs/chartjs/', true);



ui_require_javascript_file('graph_analytics', 'include/javascript/', true);


echo '
</head>
<body>
';

// Content.
$right_content = '';

$right_content .= '
    <div id="droppable-graphs">
        <div class="droppable droppable-default-zone" data-modules="[]"><span class="drop-here">'.__('Drop here').'<span></div>
    </div>
';

$graphs_div = html_print_div(
    [
        'class'   => 'padding-div graphs-div-main',
        'content' => $right_content,
    ],
    true
);

html_print_div(
    [
        'class'   => 'white_box main-div graph-analytics-public',
        'content' => $graphs_div,
    ]
);

?>

<script>
const dropHere = "<?php echo __('Drop here'); ?>";

const titleNew = "<?php echo __('New graph'); ?>";
const messageNew = "<?php echo __('If you create a new graph, the current settings will be deleted. Please save the graph if you want to keep it.'); ?>";

const titleSave = "<?php echo __('Saved successfully'); ?>";
const messageSave = "<?php echo __('The filter has been saved successfully'); ?>";

const messageSaveEmpty = "<?php echo __('Empty graph'); ?>";
const messageSaveEmptyName = "<?php echo __('Empty name'); ?>";

const titleError = "<?php echo __('Error'); ?>";

const titleUpdate = "<?php echo __('Override filter?'); ?>";
const messageUpdate = "<?php echo __('Do you want to overwrite the filter?'); ?>";

const titleUpdateConfirm = "<?php echo __('Updated successfully'); ?>";
const messageUpdateConfirm = "<?php echo __('The filter has been updated successfully'); ?>";

const titleUpdateError = "<?php echo __('Error'); ?>";
const messageUpdateError = "<?php echo __('Empty graph'); ?>";

const titleLoad = "<?php echo __('Overwrite current graph?'); ?>";
const messageLoad = "<?php echo __('If you load a filter, it will clear the current graph'); ?>";

const titleLoadConfirm = "<?php echo __('Error'); ?>";
const messageLoadConfirm = "<?php echo __('Error loading filter'); ?>";


document.addEventListener("DOMContentLoaded", (event) => {
    const hash = "<?php echo get_parameter('hash'); ?>";
    const idFilter = atob("<?php echo get_parameter('id'); ?>");
    const idUser = "<?php echo get_parameter('id_user'); ?>";

    load_filter_values(idFilter, configHomeurl);
});

</script>

</body>
</html>