<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Host&Devices
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 PandoraFMS S.L.
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
global $config;

$status_um = false;
$status_su = false;
$status_cm = false;
$status_lv = false;

// Header.
ui_print_standard_header(
    __('Mini-Diagnosis'),
    'images/op_snmp.png',
    false,
    false,
    false,
    [],
    [
        [
            'link'  => '',
            'label' => __('Configuration'),
        ],
        [
            'link'  => '',
            'label' => __('Configuration wizard'),
        ],
    ]
);

if (empty($config['pandora_uid']) === 'ONLINE') {
    $status_um = true;
}

require_once 'include/functions_servers.php';
if (check_all_servers_up() === true) {
    $status_su = true;
}

if (empty($config['welcome_mail_configured']) === false) {
    $status_cm = true;
}

if (enterprise_installed()) {
    $license_valid = true;
    enterprise_include_once('include/functions_license.php');
    $license = enterprise_hook('license_get_info');
    $days_to_expiry = ((strtotime($license['expiry_date']) - time()) / (60 * 60 * 24));
    if ($license === ENTERPRISE_NOT_HOOK || $days_to_expiry <= 30) {
        $license_valid = false;
    }

    if ($license_valid === true) {
        $status_lv = true;
    }
}


ui_require_css_file('mini_diagnosis');
$table = new stdClass();
$table->width = '60%';
$table->class = 'filter-table-adv databox';
$table->size = [];
$table->data = [];
$table->size[0] = '30%';
$table->size[1] = '30%';
$table->data[0][0] = html_print_wizard_diagnosis(__('Verification update manager register'), 'update_manager', __('Verification update manager register'), $status_um, true);
$table->data[0][1] = html_print_wizard_diagnosis(__('Please ensure mail configuration matches your needs'), 'configure_email', __('Please ensure mail configuration matches your needs'), $status_cm, true);
$table->data[1][0] = html_print_wizard_diagnosis(__('All servers up'), 'servers_up', __('All servers up'), $status_su, true);
$table->data[1][1] = html_print_wizard_diagnosis(__('Valid license verification and expiration greater than 30 days'), 'license_valid', __('Valid license verification and expiration greater than 30 days'), $status_lv, true);
html_print_table($table);
?>
<script type="text/javascript">
    document.getElementById("update_manager").setAttribute(
        'onclick',
        'configureUpdateManager()'
    );
    document.getElementById("configure_email").setAttribute(
        'onclick',
        'configureEmail()'
    );
    document.getElementById("servers_up").setAttribute(
        'onclick',
        'serversUp()'
    );
    document.getElementById("license_valid").setAttribute(
        'onclick',
        'messageLicense()'
    );

    function configureUpdateManager() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=messages&sec2=godmode/update_manager/update_manager&tab=online'); ?>';
    }

    function configureEmail() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=general&sec2=godmode/setup/setup&section=general#table3'); ?>';
    }

    function serversUp() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=gservers&sec2=godmode/servers/modificar_server&refr=60'); ?>';
    }

    function messageLicense() {
        window.location = '<?php echo ui_get_full_url('index.php?sec=message_list&sec2=operation/messages/message_list'); ?>';
    }
</script>