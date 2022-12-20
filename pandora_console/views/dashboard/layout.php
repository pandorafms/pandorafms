<?php
/**
 * Dashboards View List Table Pandora FMS Console
 *
 * @category   Console Class
 * @package    Pandora FMS
 * @subpackage Dashboards
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

// Css Files.
if ((bool) \is_metaconsole() !== true) {
    \ui_require_css_file('bootstrap.min');
}

\ui_require_css_file('gridstack.min');
\ui_require_css_file('gridstack-extra.min');
if ((bool) \is_metaconsole() === true) {
    \ui_require_css_file('meta_pandora');
} else {
    \ui_require_css_file('pandora');
}

\ui_require_css_file('dashboards');

// Js Files.
\ui_require_javascript_file('underscore-min');
\ui_require_javascript_file('gridstack');
\ui_require_javascript_file('gridstack.jQueryUI');
\ui_require_javascript_file('pandora_dashboards');
\ui_require_jquery_file('countdown');

$output = '';

// Div for modal update dashboard.
$output .= '<div id="modal-update-dashboard" style="display:none;"></div>';
$output .= '<div id="modal-add-widget" style="display:none;"></div>';
$output .= '<div id="modal-config-widget" style="display:none;"></div>';
$output .= '<div id="modal-slides-dialog" style="display:none;"></div>';

// Layout.
$output .= '<div class="container-fluid">';
$output .= '<div id="container-layout">';
$output .= '<div class="grid-stack"></div>';
$output .= '</div>';
$output .= '</div>';

echo $output;

?>
<script type="text/javascript">
    $(document).ready (function () {
        // Iniatilice Layout.
        initialiceLayout({
            page: '<?php echo $ajaxController; ?>',
            url: '<?php echo $url; ?>',
            dashboardId: '<?php echo $dashboardId; ?>',
            auth: {
                class: '<?php echo $class; ?>',
                hash: '<?php echo $hash; ?>',
                user: '<?php echo $config['id_user']; ?>'
            },
            title: '<?php echo __('New widget'); ?>',
        });

        // Mode for create new dashboard.
        var active = '<?php echo (int) $createDashboard; ?>';
        var cellId = '<?php echo (int) $cellIdCreate; ?>';
        if(active != 0){
            // Trigger simulate edit mode.
            setTimeout(() => {
                $("#checkbox-edit-mode").trigger("click");
            }, 300);
            // Trigger simulate new cell add widget.
            setTimeout(() => {
                $("#button-add-widget-"+cellId).trigger("click");
            }, 500);
        }
    });
</script>
