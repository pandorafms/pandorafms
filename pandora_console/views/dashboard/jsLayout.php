<?php
/**
 * Dashboards View js layout Pandora FMS Console
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

?>
<script type="text/javascript">
    $(document).ready (function () {
        // Slides and mode pure.
        // Show slides dialog.
        var startCountDown = function (duration, cb) {
            $('div.dashboard-countdown').countdown('destroy');
            if (!duration) return;
            var t = new Date();
            t.setTime(t.getTime() + duration * 1000);
            $('div.dashboard-countdown').countdown({
                until: t,
                format: 'MS',
                layout: '(%M%nn%M:%S%nn%S <?php echo __('Until next'); ?>) ',
                alwaysExpire: true,
                onExpiry: function () {
                    $('div.dashboard-countdown').countdown('destroy');
                    cb();
                }
            });
        }
        // Auto refresh select.
        $('form#refr-form').submit(function (event) {
            event.preventDefault();
        });
        var handleRefrChange = function (event) {
            event.preventDefault();
            var url = $('form#refr-form').prop('action');
            var refr = Number.parseInt(event.target.value, 10);
            startCountDown(refr, function () {
                window.location = url + '&refr=' + refr;
            });
        }
        $('form#refr-form select').change(handleRefrChange).change();
        // The pause button will disable the autorefresh.
        $('a#pause-btn').click(function (event) {
            $('form#refr-form select').val(0).change();
        });
        // Auto hide controls.
        var controls = document.getElementById('dashboard-controls');
        autoHideElement(controls, 1000);
    });
</script>
