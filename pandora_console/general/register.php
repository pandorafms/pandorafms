<?php
/**
 * Update Manager registration process client controller.
 *
 * @category   Client controller
 * @package    Pandora FMS
 * @subpackage Update manager
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

// Begin.
global $config;

require_once $config['homedir'].'/include/functions_update_manager.php';
require_once $config['homedir'].'/include/class/WelcomeWindow.class.php';


if (is_ajax()) {
    // Parse responses, flow control.
    $configuration_wizard = get_parameter('save_required_wizard', 0);
    $change_language = get_parameter('change_language', 0);
    $cancel_wizard = get_parameter('cancel_wizard', 0);

    // Console registration.
    $cancel_registration = get_parameter('cancel_registration', 0);
    $register_console = get_parameter('register_console', 0);

    // Newsletter.
    $cancel_newsletter = get_parameter('cancel_newsletter', 0);
    $register_newsletter = get_parameter('register_newsletter', 0);

    // Load wizards.
    $load_wizards = get_parameter('load_wizards', '');

    $feedback = [];

    // Load wizards.
    if ($load_wizards != '') {
        switch ($load_wizards) {
            case 'initial':
            return config_wiz_modal(false, false);

            case 'registration':
            return registration_wiz_modal(false, false);

            case 'newsletter':
            return newsletter_wiz_modal(false, false);

            case 'all':
                config_wiz_modal(false, false);
                registration_wiz_modal(false, false);
                newsletter_wiz_modal(false, false);
            return;

            default:
                // Ignore.
            break;
        }
    }

    // Configuration wizard process.
    if ($configuration_wizard) {
        $feedback = config_wiz_process();
    }

    if ($change_language) {
        // Change the language if is change in checkbox.
        config_update_value('language', $change_language);
    }

    if ($cancel_wizard) {
        config_update_value('initial_wizard', 1);
    }

    // Update Manager registration.
    if ($cancel_registration) {
        config_update_value('pandora_uid', 'OFFLINE');
    }

    if ($register_console) {
        $feedback = registration_wiz_process();
    }

    // Newsletter.
    if ($cancel_newsletter) {
        db_process_sql_update(
            'tusuario',
            ['middlename' => -1],
            ['id_user' => $config['id_user']]
        );

        // XXX: Also notify UpdateManager.
    }

    if ($register_newsletter) {
        $feedback = newsletter_wiz_process();
    }

    if (is_array($feedback)) {
        echo json_encode($feedback);
    }


    // Ajax calls finish here.
    exit();
}



ui_require_css_file('register');

$initial = isset($config['initial_wizard']) !== true
    || $config['initial_wizard'] != '1';

$newsletter = db_get_value(
    'middlename',
    'tusuario',
    'id_user',
    $config['id_user']
);
$show_newsletter = $newsletter == '0' || $newsletter == '';

$registration = isset($config['pandora_uid']) !== true
    || $config['pandora_uid'] == '';


if ($initial && users_is_admin()) {
    // Show all forms in order.
    // 1- Ask for email, timezone, etc. Fullfill alerts and user mail.
    config_wiz_modal(
        false,
        true,
        (($registration === true) ? 'show_registration_wizard()' : null)
    );
}

if (!$config['disabled_newsletter']) {
    if ($registration && users_is_admin()) {
        // Prepare registration wizard, not launch. leave control to flow.
        registration_wiz_modal(
            false,
            // Launch only if not being launch from 'initial'.
            !$initial,
            (($show_newsletter === true) ? 'force_run_newsletter()' : null)
        );
    } else {
        if ($show_newsletter) {
            // Show newsletter wizard for current user.
            newsletter_wiz_modal(
                false,
                // Launch only if not being call from 'registration'.
                !$registration && !$initial
            );
        }
    }
}

$welcome = !$registration && !$show_newsletter && !$initial;
try {
    $welcome_window = new WelcomeWindow($welcome);
    if ($welcome_window !== null) {
        $welcome_window->run();
    }
} catch (Exception $e) {
    $welcome = false;
}

$newsletter = null;

?>
<script type="text/javascript">

$(document).ready (function () {

});



</script>
