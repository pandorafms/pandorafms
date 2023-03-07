<?php
/**
 * License form.
 *
 * @category   Form
 * @package    Pandora FMS
 * @subpackage Enterprise
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

// File begin.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to change License settings'
    );
    include 'general/noaccess.php';
    return;
}

$update_settings = (bool) get_parameter_post('update_settings');

ui_require_javascript_file_enterprise('load_enterprise', is_metaconsole() === true);
enterprise_include_once('include/functions_license.php');

// Header.
ui_print_standard_header(
    __('License management'),
    'images/extensions.png',
    false,
    '',
    true,
    [],
    [
        [
            'link'  => '',
            'label' => __('License'),
        ],
    ]
);

enterprise_include_once('include/functions_crypto.php');

if ($renew_license_result !== null) {
    echo $renew_license_result;
}

if ($update_settings) {
    if (!is_metaconsole()) {
        // Node.
        foreach ($_POST['keys'] as $key => $value) {
            db_process_sql_update(
                'tupdate_settings',
                [db_escape_key_identifier('value') => $value],
                [db_escape_key_identifier('key') => $key]
            );
        }

        $customer_key = $_POST['keys']['customer_key'];

        $license_encryption_key = get_parameter('license_encryption_key', '');
        $check = db_get_value_sql('SELECT `key` FROM tupdate_settings WHERE `key` LIKE "license_encryption_key"');
        if ($check === false) {
            db_process_sql_insert(
                'tupdate_settings',
                [
                    db_escape_key_identifier('value') => $license_encryption_key,
                    db_escape_key_identifier('key')   => 'license_encryption_key',
                ]
            );
        } else {
            db_process_sql_update(
                'tupdate_settings',
                [db_escape_key_identifier('value') => $license_encryption_key],
                [db_escape_key_identifier('key') => 'license_encryption_key']
            );
        }

        if (empty($license_encryption_key) === false) {
            $customer_key = openssl_blowfish_encrypt_hex($customer_key, io_safe_output($license_encryption_key));
        }

        // Update the license file.
        $result = file_put_contents($config['remote_config'].'/'.LICENSE_FILE, $customer_key);
        if ($result === false) {
            ui_print_error_message(__('Failed to Update license file'));
        }

        ui_print_success_message(__('License updated'));
    }
}

$license = enterprise_hook('license_get_info');

$rows = db_get_all_rows_in_table('tupdate_settings');

$settings = new StdClass;
foreach ($rows as $row) {
    $settings->{$row['key']} = $row['value'];
}

?>
<script type="text/javascript">
var texts = {
    error_connecting: '<?php echo __('Error while connecting to licence server.'); ?>',
    error_license: '<?php echo __('Invalid response while validating license.'); ?>',
    error_unknown: '<?php echo __('Unknown error'); ?>',
}

<?php
if (enterprise_installed()) {
    print_js_var_enteprise();
}
?>

</script>
<?php
echo '<form method="post" id="form-license" class="max_floating_element_size">';
// Retrieve UM url configured (or default).
$url = get_um_url();

$table = new stdClass();
$table->width = '100%';
$table->class = 'databox filter-table-adv';
$table->size = [];
$table->size[0] = '50%';
$table->size[1] = '50%';
$table->data = [];
$table->colspan = [];

$table->colspan[-1][0] = 2;
$table->data[-1][0] = '<div class="section_table_title">'.__('Licence').'</div>';

$table->colspan[0][0] = 2;
$table->data[0][0] = html_print_label_input_block(
    __('Customer key'),
    html_print_textarea(
        'keys[customer_key]',
        10,
        255,
        $settings->customer_key,
        'style="width: 100%; height:80px;"',
        true
    )
);

$table->data[1][0] = html_print_label_input_block(
    __($license['expiry_caption']),
    html_print_input_text(
        'expires',
        $license['expiry_date'],
        '',
        10,
        255,
        true,
        true
    )
);

$table->data[1][1] = html_print_label_input_block(
    __('Platform Limit'),
    html_print_input_text(
        'expires',
        $license['limit'],
        '',
        10,
        255,
        true,
        true
    ).' '.($license['limit_mode'] == 0 ? __('agents') : __('modules'))
);

$table->data[2][0] = html_print_label_input_block(
    __('Current Platform Count'),
    html_print_input_text(
        'expires',
        $license['count'],
        '',
        10,
        255,
        true,
        true
    ).' '.($license['limit_mode'] == 0 ? __('agents') : __('modules'))
);

$table->data[2][1] = html_print_label_input_block(
    __('Current Platform Count (enabled: items)'),
    html_print_input_text(
        'expires',
        $license['count_enabled'],
        '',
        10,
        255,
        true,
        true
    ).' '.($license['limit_mode'] == 0 ? __('agents') : __('modules'))
);

$table->data[3][0] = html_print_label_input_block(
    __('Current Platform Count (disabled: items)'),
    html_print_input_text(
        'expires',
        $license['count_disabled'],
        '',
        10,
        255,
        true,
        true
    ).' '.($license['limit_mode'] == 0 ? __('agents') : __('modules'))
);

$table->data[3][1] = html_print_label_input_block(
    __('License Mode'),
    html_print_input_text(
        'expires',
        $license['license_mode'],
        '',
        10,
        255,
        true,
        true
    )
);

$table->data[4][0] = html_print_label_input_block(
    __('NMS'),
    html_print_input_text(
        'expires',
        ($license['nms'] == 1 ? __('enabled') : __('disabled')),
        '',
        10,
        255,
        true,
        true
    )
);

$table->data[4][1] = html_print_label_input_block(
    __('Satellite'),
    html_print_input_text(
        'expires',
        ($license['dhpm'] == 1 ? __('enabled') : __('disabled')),
        '',
        10,
        255,
        true,
        true
    )
);

$table->data[5][0] = html_print_label_input_block(
    __('Licensed to'),
    html_print_input_text(
        'licensed_to',
        $license['licensed_to'],
        '',
        64,
        255,
        true,
        true
    )
);

if ($license['dhpm'] == 1) {
    $table->data[5][1] = html_print_label_input_block(
        __('License encryption key').'</strong>'.ui_print_help_tip(
            __('This key is used to encrypt your Pandora FMS license when it is shared with other Pandora FMS components'),
            true
        ),
        html_print_input_password(
            'license_encryption_key',
            io_safe_output($settings->license_encryption_key),
            '',
            10,
            255,
            true,
            false
        )
    );
}

html_print_table($table);

// If DESTDIR is defined the enterprise license is expired.
if (enterprise_installed() || defined('DESTDIR')) {
    $buttons = html_print_input_hidden('update_settings', 1, true);
    $buttons .= html_print_submit_button(
        __('Validate'),
        'update_button',
        false,
        ['icon' => 'next'],
        true
    );
    $buttons .= html_print_button(
        __('Request new license'),
        'license',
        false,
        'generate_request_code()',
        [
            'fixed_id' => 'button-',
            'icon'     => 'next',
            'mode'     => 'secondary',
        ],
        true
    );

    html_print_action_buttons(
        $buttons
    );
}

echo '</form>';
if (is_metaconsole()) {
    ui_require_css_file('pandora_enterprise', ENTERPRISE_DIR.'/include/styles/');
    ui_require_css_file('register', 'include/styles/');
} else {
    ui_require_css_file('pandora');
    ui_require_css_file('pandora_enterprise', ENTERPRISE_DIR.'/include/styles/');
    ui_require_css_file('register');
}

if (enterprise_hook('print_activate_licence_dialog') == ENTERPRISE_NOT_HOOK) {
    echo '<div id="code_license_dialog" class="invisible left" title="'.__('Request new license').'">';
    echo '<div id="logo">';
    html_print_image(ui_get_custom_header_logo(true));
    echo '</div>';
    echo ''.__('To get your <b>%s Enterprise License</b>:', get_product_name()).'<br />';
    echo '<ul>';
    echo '<li>';
    echo ''.sprintf(__('Go to %s'), '<a target="_blank" href="'.$url.'/index.php?section=generate_key_client">'.$url.'index.php?section=generate_key_client</a>');
    echo '</li>';
    echo '<li>';
    echo ''.__('Enter the <b>auth key</b> and the following <b>request key</b>:');
    echo '</li>';
    echo '</ul>';
    echo '<div id="code"></div>';
    echo '<ul>';
    echo '<li>';
    echo ''.__('Enter your name (or a company name) and a contact email address.');
    echo '</li>';
    echo '<li>';
    echo ''.__('Click on <b>Generate</b>.');
    echo '</li>';
    echo '<li>';
    echo ''.__('Click <a href="javascript: close_code_license_dialog();">here</a>, enter the generated license key and click on <b>Validate</b>.');
    echo '</li>';
    echo '</ul>';
    echo '</div>';
}
