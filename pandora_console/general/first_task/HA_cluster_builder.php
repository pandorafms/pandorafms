<?php
/**
 * Manage database HA cluster.
 *
 * @category   Manager
 * @package    Pandora FMS
 * @subpackage Database HA cluster
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2021 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access HA cluster'
    );
    include 'general/noaccess.php';
    exit;
}

ui_require_css_file('first_task');
ui_print_info_message(['no_close' => true, 'message' => __('There are no HA clusters defined yet.') ]);
?>

<div class="new_task_cluster">
    <div class="image_task_cluster">
        <?php echo html_print_image('images/first_task/slave-mode.png', true, ['title' => __('Clusters')]); ?>
    </div>
    <div class="text_task_cluster">
        <h3> <?php echo __('%s DB CLUSTER', get_product_name()); ?></h3>
        <p id="description_task"> 
    <?php
    echo __('With %s you can add high availability to your %s installation by adding redundant MySQL servers', get_product_name(), get_product_name()).'<br><br>';

    echo __('Click on "add new node" to start transforming your %s DB Cluster into a %s DB Cluster.', get_product_name(), get_product_name()).'<br><br>';
    ?>
    </p>
        
        <?php
        if (check_acl($config['id_user'], 0, 'PM')) {
            echo "<div id='create_master_window' style='display: none'></div>";
            echo "<div id='msg' style='display: none'></div>";
            ?>
            <input  onclick="show_create_ha_cluster();" type="submit" class="button_task ui_toggle" value="<?php echo __('Add new node'); ?>" />
            <?php
        }
        ?>
    </div>
</div>
