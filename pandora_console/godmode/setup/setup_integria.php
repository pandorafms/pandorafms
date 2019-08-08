<?php
/**
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

check_login();

if (! check_acl($config['id_user'], 0, 'PM') && ! is_user_admin($config['id_user'])) {
    db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
    include 'general/noaccess.php';
    return;
}

hd(get_parameter('update_config', 0));

if (get_parameter('update_config', 0) == 1) {
    // Try to retrieve event response 'Create incident in IntegriaIMS from event' to check if it exists.
    $row = db_get_row_filter('tevent_response', ['name' => 'Create incident in IntegriaIMS from event']);

    if ($config['integria_enabled'] == 1) {
        if ($row === false) {
            // Create 'Create incident in IntegriaIMS from event' event response only when user enables IntegriaIMS integration and it does not exist in database.
            db_process_sql_insert('tevent_response', ['name' => 'Create incident in IntegriaIMS from event', 'description' => 'Create an incident in IntegriaIMS from an event', 'target' => 'index.php?sec=incident&sec2=operation/incidents/configure_integriaims_incident&from_event=_event_id_', 'type' => 'url', 'id_group' => '0', 'modal_width' => '0', 'modal_height' => '0', 'new_window' => '1', 'params' => '', 'server_to_exec' => '0']);
        }
    } else {
        if ($row != false) {
            // Delete 'Create incident in IntegriaIMS from event' event response if it does exist and IntegriaIMS integration is disabled
            db_process_sql_delete('tevent_response', ['name' => 'Create incident in IntegriaIMS from event']);
        }
    }
}

// Enable table.
$table_enable = new StdClass();
$table_enable->data = [];
$table_enable->width = '100%';
$table_enable->id = 'integria-enable-setup';
$table_enable->class = 'databox filters';
$table_enable->size['name'] = '30%';
$table_enable->style['name'] = 'font-weight: bold';

// Enable Integria.
$row = [];
$row['name'] = __('Enable Integria');
$row['control'] = html_print_checkbox_switch('integria_enabled', 1, $config['integria_enabled'], true);
$table_enable->data['integria_enabled'] = $row;

// Remote config table.
$table_remote = new StdClass();
$table_remote->data = [];
$table_remote->width = '100%';
$table_remote->styleTable = 'margin-bottom: 10px;';
$table_remote->id = 'integria-remote-setup';
$table_remote->class = 'databox filters';
$table_remote->size['name'] = '30%';
$table_remote->style['name'] = 'font-weight: bold';

// User.
$row = [];
$row['name'] = __('User');
$row['control'] = html_print_input_text('integria_user', $config['integria_user'], '', 30, 100, true);
$table_remote->data['integria_user'] = $row;

// Pass.
$row = [];
$row['name'] = __('Password');
$row['control'] = html_print_input_password('integria_pass', io_output_password($config['integria_pass']), '', 30, 100, true);
$table_remote->data['integria_pass'] = $row;

// Directory hostname.
$row = [];
$row['name'] = __('API Hostname');
$row['control'] = html_print_input_text('integria_hostname', $config['integria_hostname'], '', 30, 100, true);
$row['control'] .= ui_print_help_tip(__('Hostname of the Integria API'));
$table_remote->data['integria_hostname'] = $row;

// Request timeout.
$row = [];
$row['name'] = __('Request timeout');
$row['control'] = html_print_input_text('integria_req_timeout', $config['integria_req_timeout'], '', 3, 10, true);
$row['control'] .= ui_print_help_tip(__('Time in seconds to set the maximum time of the requests to the Integria API').'. '.__('0 to disable'), true);
$table_remote->data['integria_req_timeout'] = $row;

// Test.
$row = [];
$row['name'] = __('Test');
$row['control'] = html_print_button(__('Start'), 'test-integria', false, '', 'class="sub next"', true);
$row['control'] .= '<span id="test-integria-spinner" style="display:none;">&nbsp;'.html_print_image('images/spinner.gif', true).'</span>';
$row['control'] .= '<span id="test-integria-success" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_normal.png', true).'</span>';
$row['control'] .= '<span id="test-integria-failure" style="display:none;">&nbsp;'.html_print_image('images/status_sets/default/severity_critical.png', true).'</span>';
$row['control'] .= '&nbsp;<span id="test-integria-message" style="display:none;"></span>';
$table_remote->data['integria_test'] = $row;

hd($config['integria_enabled']);
hd($config['integria_user']);
hd($config['integria_user']);
hd($config['integria_pass']);
hd($config['integria_hostname']);
hd($config['integria_req_timeout']);

// Print.
echo '<div style="text-align: center; padding-bottom: 20px;">';
echo '<a target="_blank" rel="noopener noreferrer" href="http://ehorus.com">';
html_print_image('include/ehorus/images/ehorus-logo-grey.png');
echo '</a>';
echo '<br />';
echo '<div style="font-family: lato, "Helvetica Neue", Helvetica, Arial, sans-serif; color: #515151;">';
echo __('Remote Management System');
echo '</div>';
echo '<a target="_blank" rel="noopener noreferrer" href="https://ehorus.com">';
echo 'https://ehorus.com';
echo '</a>';
echo '</div>';

echo "<form method='post'>";
// Form enable.
echo '<div id="form_enable">';
html_print_input_hidden('update_config', 1);
html_print_table($table_enable);
echo '</div>';

// Form remote.
    echo '<div id="form_remote">';
    echo '<fieldset>';
    echo '<legend>'.__('Integria API').'</legend>';
    html_print_input_hidden('update_config', 1);
    html_print_table($table_remote);

    echo '</fieldset>';
    echo '</div>';
     echo '<div class="action-buttons" style="width: '.$table_remote->width.'">';
    html_print_submit_button(__('Update'), 'update_button', false, 'class="sub upd"');
    echo '</div>';
    echo '</form>';

?>

<script type="text/javascript">

if(!$('input:checkbox[name="integria_enabled"]').is(':checked'))
{
    $('#form_remote').hide();
}

 $('#form_enable').css('margin-bottom','20px');
    var showFields = function () {
        $('#form_remote').show();
    }
    var hideFields = function () {
        $('#form_remote').hide();
    }

    var hideUserPass = function () {
        $('#integria-remote-setup-integria_user').hide();
        $('#integria-remote-setup-integria_pass').hide();showFields
    }

    var showUserPass = function () {
        $('#integria-remote-setup-integria_user').show();
        $('#integria-remote-setup-integria_pass').show();
    }

    var handleEnable = function (event) {
        var is_checked = $('input:checkbox[name="integria_enabled"]').is(':checked');

        if (event.target.value == '1' && is_checked) {
            showFields();
            $('input:checkbox[name="integria_enabled"]').attr('checked', true);
        }
        else {
            hideFields();
            $('input:checkbox[name="integria_enabled"]').attr('checked', false);
        };
    }

    $('input:checkbox[name="integria_enabled"]').change(handleEnable);

    var handleTest = function (event) {
        var user = $('input#text-integria_user').val();
        var pass = $('input#password-integria_pass').val();
        var host = $('input#text-integria_hostname').val();
        var timeout = Number.parseInt($('input#text-integria_req_timeout').val(), 10);
    
        var timeoutMessage = '<?php echo __('Connection timeout'); ?>';
        var badRequestMessage = '<?php echo __('Empty user or password'); ?>';
        var notFoundMessage = '<?php echo __('User not found'); ?>';
        var invalidPassMessage = '<?php echo __('Invalid password'); ?>';
        
        var hideLoadingImage = function () {
            $('span#test-integria-spinner').hide();
        }
        var showLoadingImage = function () {
            $('span#test-integria-spinner').show();
        }
        var hideSuccessImage = function () {
            $('span#test-integria-success').hide();
        }
        var showSuccessImage = function () {
            $('span#test-integria-success').show();
        }
        var hideFailureImage = function () {
            $('span#test-integria-failure').hide();
        }
        var showFailureImage = function () {
            $('span#test-integria-failure').show();
        }
        var hideMessage = function () {
            $('span#test-integria-message').hide();
        }
        var showMessage = function () {
            $('span#test-integria-message').show();
        }
        var changeTestMessage = function (message) {
            $('span#test-integria-message').text(message);
        }
        
        hideSuccessImage();
        hideFailureImage();
        hideMessage();
        showLoadingImage();
       console.log(host);
        $.ajax({
            url: 'http://' + host,
            type: 'POST',
            dataType: 'json',
            timeout: timeout ? timeout * 1000 : 0
        })
        .done(function(data, textStatus, xhr) {
            showSuccessImage();
        })
        .fail(function(xhr, textStatus, errorThrown) {
            if (xhr.status === 400) {
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
    $('input#button-test-integria').click(handleTest);
    


</script>
