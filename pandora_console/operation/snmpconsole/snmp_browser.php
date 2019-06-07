<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
require_once $config['homedir'].'/include/functions_snmp_browser.php';
ui_require_javascript_file('pandora_snmp_browser');

// AJAX call.
if (is_ajax()) {
    // Read the action to perform.
    $action = (string) get_parameter('action', '');
    $target_ip = (string) get_parameter('target_ip', '');
    $community = (string) get_parameter('community', '');
    $snmp_version = (string) get_parameter('snmp_browser_version', '');
    $server_to_exec = (int) get_parameter('server_to_exec', 0);
    $snmp3_auth_user = io_safe_output(get_parameter('snmp3_browser_auth_user'));
    $snmp3_security_level = get_parameter('snmp3_browser_security_level');
    $snmp3_auth_method = get_parameter('snmp3_browser_auth_method');
    $snmp3_auth_pass = io_safe_output(get_parameter('snmp3_browser_auth_pass'));
    $snmp3_privacy_method = get_parameter('snmp3_browser_privacy_method');
    $snmp3_privacy_pass = io_safe_output(
        get_parameter('snmp3_browser_privacy_pass')
    );

    // SNMP browser.
    if ($action == 'snmptree') {
        $starting_oid = (string) get_parameter('starting_oid', '.');

        $snmp_tree = snmp_browser_get_tree(
            $target_ip,
            $community,
            $starting_oid,
            $snmp_version,
            $snmp3_auth_user,
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            $server_to_exec
        );
        if (! is_array($snmp_tree)) {
            echo $snmp_tree;
        } else {
            snmp_browser_print_tree(
                $snmp_tree,
                // Id.
                0,
                // Depth.
                0,
                // Last.
                0,
                // Last_array.
                [],
                // Sufix.
                false,
                // Checked.
                [],
                // Return.
                false,
                // Descriptive_ids.
                false,
                // Previous_id.
                ''
            );
            echo html_print_submit_button(
                __('Create network components'),
                'create_network_component',
                false,
                [
                    'style' => 'display: none; position: absolute; bottom: 0px; right: 35px;',
                    'class' => 'sub add',
                ],
                true
            );

            echo '<div id="dialog_error" style="display: none" title="Network components">';
            echo '<div>';
            echo "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_error_mr.png'></div>";
            echo "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>ERROR</strong></h3>";
            echo "<p style='font-family:Verdana; font-size:12pt;margin-bottom: 0px'>".__('Error creating the following modules:').'</p>';
            echo "<p id='error_text' style='font-family:Verdana; font-size:12pt;'></p>";
            echo '</div>';
            echo '</div>';


            echo '<div id="dialog_success" style="display: none" title="Network components">';
            echo '<div>';
            echo "<div style='width:25%; float:left'><img style='padding-left:20px; padding-top:20px;' src='images/icono_exito_mr.png'></div>";
            echo "<div style='width:75%; float:left;'><h3><strong style='font-family:Verdana; font-size:13pt;'>SUCCESS</strong></h3>";
            echo "<p style='font-family:Verdana; font-size:12pt;'>".__('Modules successfully created').'</p>';
            echo '</div>';
            echo '</div>';
        }

        return;
    } else if ($action == 'snmpget') {
        // SNMP get.
        $target_oid = htmlspecialchars_decode(get_parameter('oid', ''));
        $custom_action = get_parameter('custom_action', '');
        if ($custom_action != '') {
            $custom_action = urldecode(base64_decode($custom_action));
        }

        $oid = snmp_browser_get_oid(
            $target_ip,
            $community,
            $target_oid,
            $snmp_version,
            $snmp3_auth_user,
            $snmp3_security_level,
            $snmp3_auth_method,
            $snmp3_auth_pass,
            $snmp3_privacy_method,
            $snmp3_privacy_pass,
            $server_to_exec
        );

        snmp_browser_print_oid(
            $oid,
            $custom_action,
            false,
            $community,
            $snmp_version
        );
        return;
    }

    return;
}

// Check login and ACLs.
check_login();
if (! check_acl($config['id_user'], 0, 'AR')) {
    db_pandora_audit(
        'ACL Violation',
        'Trying to access SNMP Console'
    );
    include 'general/noaccess.php';
    exit;
}

// Header.
$url = 'index.php?sec=snmpconsole&sec2=operation/snmpconsole/snmp_browser&pure='.$config['pure'];
if ($config['pure']) {
    // Windowed.
    $link['text'] = '<a target="_top" href="'.$url.'&pure=0&refr=30">';
    $link['text'] .= html_print_image(
        'images/normal_screen.png',
        true,
        ['title' => __('Normal screen')]
    );
    $link['text'] .= '</a>';
} else {
    // Fullscreen.
    $link['text'] = '<a target="_top" href="'.$url.'&pure=1&refr=0">';
    $link['text'] .= html_print_image(
        'images/full_screen.png',
        true,
        ['title' => __('Full screen')]
    );
    $link['text'] .= '</a>';
}

ui_print_page_header(
    __('SNMP Browser'),
    'images/op_snmp.png',
    false,
    'snmp_browser_view',
    false,
    [$link]
);

// SNMP tree container.
snmp_browser_print_container();
