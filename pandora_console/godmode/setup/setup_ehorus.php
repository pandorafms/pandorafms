<?php
/**
 * View eHorus setup.
 *
 * @category   Setup
 * @package    Pandora FMS
 * @subpackage Opensource
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

// Load globals.
global $config;

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access Setup Management'
    );
    include 'general/noaccess.php';
    return;
}

// Check custom field.
$custom_field = db_get_value('name', 'tagent_custom_fields', 'name', $config['ehorus_custom_field']);
$custom_field_exists = !empty($custom_field);
$custom_field_created = null;
if ($config['ehorus_enabled'] && !$custom_field_exists) {
    $values = [
        'name'             => $config['ehorus_custom_field'],
        'display_on_front' => 1,
    ];
    $result = (bool) db_process_sql_insert('tagent_custom_fields', $values);
    $custom_field_exists = $custom_field_created = $result;
}

// Enable table.
$table_enable = new StdClass();
$table_enable->data = [];
$table_enable->width = '100%';
$table_enable->id = 'ehorus-enable-setup';
$table_enable->class = 'databox filters';
$table_enable->size['name'] = '30%';
$table_enable->style['name'] = 'font-weight: bold';

// Enable eHorus.
$row = [];
$row['name'] = __('Enable eHorus');
$row['control'] = html_print_checkbox_switch('ehorus_enabled', 1, $config['ehorus_enabled'], true);
$table_enable->data['ehorus_enabled'] = $row;

// Remote config table.
$table_remote = new StdClass();
$table_remote->data = [];
$table_remote->width = '100%';
$table_remote->styleTable = 'margin-bottom: 10px;';
$table_remote->id = 'ehorus-remote-setup';
$table_remote->class = 'databox filters filter-table-adv';
$table_remote->size['ehorus_hostname'] = '50%';
$table_remote->size['ehorus_port'] = '50%';

// Enable eHorus user configuration.
$row = [];
$row['ehorus_user_level_conf'] = html_print_label_input_block(
    __('eHorus configuration at user level'),
    html_print_checkbox_switch(
        'ehorus_user_level_conf',
        1,
        $config['ehorus_user_level_conf'],
        true
    )
);
$table_remote->data['ehorus_user_level_conf'] = $row;

// User.
$row = [];
$row['ehorus_user'] = html_print_label_input_block(
    __('User'),
    html_print_input_text('ehorus_user', $config['ehorus_user'], '', 30, 100, true),
    ['div_class' => 'ehorus-remote-setup-ehorus_user']
);

// Pass.
$row['ehorus_pass'] = html_print_label_input_block(
    __('Password'),
    html_print_input_password('ehorus_pass', io_output_password($config['ehorus_pass']), '', 30, 100, true),
    ['div_class' => 'ehorus-remote-setup-ehorus_user']
);
$table_remote->data['ehorus_pass'] = $row;

// Directory hostname.
$row = [];
$row['ehorus_hostname'] = html_print_label_input_block(
    __('API Hostname'),
    html_print_input_text('ehorus_hostname', $config['ehorus_hostname'], '', 30, 100, true)
);

// Directory port.
$row['ehorus_port'] = html_print_label_input_block(
    __('API Port'),
    html_print_input_text('ehorus_port', $config['ehorus_port'], '', 6, 100, true)
);
$table_remote->data['ehorus_port'] = $row;

// Request timeout.
$row = [];
$row['ehorus_req_timeout'] = html_print_label_input_block(
    __('Request timeout'),
    html_print_input_text('ehorus_req_timeout', $config['ehorus_req_timeout'], '', 3, 10, true)
);
$table_remote->data['ehorus_req_timeout'] = $row;

// Test.
$row = [];
$test_start = '<span id="test-ehorus-spinner" class="invisible">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
$test_start .= '<span id="test-ehorus-success" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'&nbsp;'.__('Connection its OK').'</span>';
$test_start .= '<span id="test-ehorus-failure" class="invisible">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'&nbsp;'.__('Connection failed').'</span>';
$test_start .= '&nbsp;<span id="test-ehorus-message" class="invisible"></span>';
$row['ehorus_test'] = html_print_label_input_block(
    __('Test connection'),
    html_print_button(
        __('Test'),
        'test-ehorus',
        false,
        '',
        [
            'icon'  => 'cog',
            'mode'  => 'secondary mini',
            'style' => 'width: 115px;',
        ],
        true
    ).$test_start
);
$table_remote->data['ehorus_test'] = $row;

// Print.
echo '<div class="center pdd_b_10px mrgn_btn_20px white_box max_floating_element_size">';
echo '<a target="_blank" rel="noopener noreferrer" href="http://ehorus.com">';
if ($config['style'] === 'pandora_black' && is_metaconsole() === true) {
    html_print_image(
        'include/ehorus/images/ehorus-logo.png',
        false,
        ['class' => 'w400px mrgn_top_15px']
    );
} else {
    html_print_image(
        'include/ehorus/images/ehorus-logo-grey.png',
        false,
        ['class' => 'w400px mrgn_top_15px']
    );
}

echo '</a>';
echo '<br />';
echo '<div class="ehorus_title">';
echo __('Remote Management System');
echo '</div>';
echo '<a target="_blank" rel="noopener noreferrer" href="https://ehorus.com">';
echo 'https://ehorus.com';
echo '</a>';
echo '</div>';

if ($custom_field_created !== null) {
    ui_print_result_message($custom_field_created, __('Custom field eHorusID created'), __('Error creating custom field'));
}

if ($custom_field_created) {
    $info_messsage = __('eHorus has his own agent identifiers');
    $info_messsage .= '. '.__('To store them, it will be necessary to use an agent custom field');
    $info_messsage .= '.<br />'.__('Possibly the eHorus id will have to be filled in by hand for every agent').'.';
    ui_print_info_message($info_messsage);
}

if ($config['ehorus_enabled'] && !$custom_field_exists) {
    $error_message = __('The custom field does not exists already');
    ui_print_error_message($error_message);
}

echo "<form method='post' class='max_floating_element_size'>";
// Form enable.
echo '<div id="form_enable">';
html_print_input_hidden('update_config', 1);
html_print_table($table_enable);
echo '</div>';

// Form remote.
    echo '<div id="form_remote">';
    echo '<fieldset>';
    echo '<legend>'.__('eHorus API').'</legend>';
    html_print_input_hidden('update_config', 1);
    html_print_table($table_remote);

    echo '</fieldset>';
    echo '</div>';
    html_print_action_buttons(
        html_print_submit_button(
            __('Update'),
            'update_button',
            false,
            ['icon' => 'update'],
            true
        )
    );
    echo '</form>';

    ?>

<script type="text/javascript">

if(!$('input:checkbox[name="ehorus_enabled"]').is(':checked'))
{
    $('#form_remote').hide();
}

if($('input:checkbox[name="ehorus_user_level_conf"]').is(':checked'))
{
    $('.ehorus-remote-setup-ehorus_user').hide();
    $('.ehorus-remote-setup-ehorus_pass').hide()
}


 $('#form_enable').css('margin-bottom','20px');
    var showFields = function () {
        $('#form_remote').show();
    }
    var hideFields = function () {
        $('#form_remote').hide();
    }

    var hideUserPass = function () {
        $('.ehorus-remote-setup-ehorus_user').hide();
        $('.ehorus-remote-setup-ehorus_pass').hide();
    }

    var showUserPass = function () {
        $('.ehorus-remote-setup-ehorus_user').show();
        $('.ehorus-remote-setup-ehorus_pass').show();
    }

    var handleEnable = function (event) {
        var is_checked = $('input:checkbox[name="ehorus_enabled"]').is(':checked');
        if (event.target.value == '1' && is_checked) {
            showFields();
            $('input:checkbox[name="ehorus_enabled"]').attr('checked', true);
        }
        else {
            hideFields();
            $('input:checkbox[name="ehorus_enabled"]').attr('checked', false);
        };
    }

    var handleUserLevel = function(event) {
        var is_checked = $('input:checkbox[name="ehorus_enabled"]').is(':checked');
        var is_checked_userlevel = $('input:checkbox[name="ehorus_user_level_conf"]').is(':checked');

        if (event.target.value == '1' && is_checked && !is_checked_userlevel) {
            showUserPass();
            $('input:checkbox[name="ehorus_user_level_conf"]').attr('checked', true);
        }
        else {
            hideUserPass();
            $('input:checkbox[name="ehorus_user_level_conf"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="ehorus_enabled"]').change(handleEnable);
    $('input:checkbox[name="ehorus_user_level_conf"]').change(handleUserLevel);

    var handleTest = function (event) {
        var user = $('input#text-ehorus_user').val();
        var pass = $('input#password-ehorus_pass').val();
        var host = $('input#text-ehorus_hostname').val();
        var port = $('input#text-ehorus_port').val();
        var timeout = Number.parseInt($('input#text-ehorus_req_timeout').val(), 10);
        var is_checked_user_level = $('input:checkbox[name="ehorus_user_level_conf"]').is(':checked');
    
        var timeoutMessage = '<?php echo __('Connection timeout'); ?>';
        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
        
        var hideLoadingImage = function () {
            $('span#test-ehorus-spinner').hide();
        }
        var showLoadingImage = function () {
            $('span#test-ehorus-spinner').show();
        }
        var hideSuccessImage = function () {
            $('span#test-ehorus-success').hide();
        }
        var showSuccessImage = function () {
            $('span#test-ehorus-success').show();
        }
        var hideFailureImage = function () {
            $('span#test-ehorus-failure').hide();
        }
        var showFailureImage = function () {
            $('span#test-ehorus-failure').show();
        }
        var hideMessage = function () {
            $('span#test-ehorus-message').hide();
        }
        var showMessage = function () {
            $('span#test-ehorus-message').show();
        }
        var changeTestMessage = function (message) {
            $('span#test-ehorus-message').text(message);
        }

        hideSuccessImage();
        hideFailureImage();
        hideMessage();
        showLoadingImage();

        $.ajax({
            url: 'https://' + host + ':' + port + '/login',
            type: 'POST',
            dataType: 'json',
            timeout: timeout ? timeout * 1000 : 0,
            data: {
                user: user,
                pass: pass
            }
        })
        .done(function(data, textStatus, xhr) {
            showSuccessImage();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            if((xhr.status === 400 || xhr.status === 403) && is_checked_user_level)
            {
                showSuccessImage();
                return;
            }else if (xhr.status === 400) {
                changeTestMessage(badRequestMessage);
            }
            else if (xhr.status === 401 || xhr.status === 403) {
                changeTestMessage(invalidPassMessage);
            }
            else if (xhr.status === 404) {
                changeTestMessage(notFoundMessage);
            }
            else if (errorThrown === 'timeout') {
                changeTestMessage(timeoutMessage);
            }
            else {
                changeTestMessage(errorThrown);
            }

            showFailureImage();
            showMessage();
        })
        .always(function(xhr, textStatus) {
            hideLoadingImage();
        });
    }
    $('#button-test-ehorus').click(handleTest);

</script>
