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
\ui_require_css_file('dashboards');

// Js Files.
\ui_require_javascript_file('pandora_dashboards');
\ui_require_jquery_file('countdown');
$output = '';

// Layout.
$style = 'style="background-color:'.$options['background'].';"';
$output .= '<div id="view-slides-cell-mode" '.$style.'></div>';

echo $output;
?>
<script type="text/javascript">
    // Load Widget.
    loadSliceWidget('<?php echo json_encode($settings); ?>');
</script>
