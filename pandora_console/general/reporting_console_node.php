<?php
/**
 * Static page to lock access to console but console reporting
 *
 * @category   Reporting
 * @package    Pandora FMS
 * @subpackage Applications
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2022 Artica Soluciones Tecnologicas
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

global $config;

// Begin.
echo ui_require_css_file('maintenance', 'include/styles/', true);

$data = [];
$data['id_node'] = $config['metaconsole_node_id'];
$data['check_ver'] = $config['current_package'];
$data['check_mr'] = $config['MR'];
$data['collection_max_size'] = $config['collection_max_size'];
$data['check_post_max_size'] = ini_get('post_max_size');
$data['check_upload_max_filesize'] = ini_get('upload_max_filesize');
$data['check_memory_limit'] = ini_get('memory_limit');
$data['check_php_version'] = phpversion();

?>
<html>
<body class="responsive-height">
    <div class="responsive center padding-6">
        <p><?php echo __('Console only reporting node'); ?></p>
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
        <p><?php echo __('Info'); ?></p>
        <ul class="container-list">
            <li>
                <span class=title>
                    <?php echo __('Version'); ?>:
                </span>
                <span>
                    <?php echo $config['current_package']; ?>
                </span>
            </li>
            <li>
                <span class=title>
                    <?php echo __('Mr'); ?>:
                </span>
                <span>
                    <?php echo $config['MR']; ?>
                </span>
            </li>
            <li>
                <span class=title>
                    <?php echo __('Memory limit'); ?>:
                </span>
                <span>
                    <?php echo ini_get('memory_limit'); ?>
                </span>
            </li>
            <li>
                <span class=title>
                    <?php echo __('Php version'); ?>:
                </span>
                <span>
                    <?php echo phpversion(); ?>
                </span>
            </li>
        </ul>
    </div>
</body>

</html>