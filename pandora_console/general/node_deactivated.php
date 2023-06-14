<?php
/**
 * Static page to lock access to console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Applications.VMware
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
ui_require_css_file('maintenance');
?>
<html>
<body>

    <div class="responsive center padding-6">
        <p><?php echo __('You cannot use this node until system is unified'); ?></p>
        <br>
        <br>

        <?php
        html_print_image(
            'images/pandora_tinylogo.png',
            false,
            ['class' => 'responsive flex margn']
        );
        html_print_image(
            'images/maintenance.png',
            false,
            [
                'class' => 'responsive',
                'width' => 800,
            ]
        );
        ?>

        <br>
        <br>
        <p>
        <?php
        echo __(
            'Please navigate to %s to unify system',
            '<a href="'.ui_get_meta_url(
                'index.php?sec=advanced&sec2=advanced/command_center'
            ).'" target="_new">'.__('command center').'</a>'
        );
        ?>
    </p>
        <br>
        <p><?php echo __('You will be automatically redirected when all tasks finish'); ?></p>
    </div>
</body>

<script type="text/javascript">
    $(document).ready(function() {
        setTimeout(
            function() {
                location.reload();
            },
            10000
        );
    })
</script>

</html>