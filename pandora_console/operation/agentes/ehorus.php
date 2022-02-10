<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

// Load global vars
global $config;

if (!$config['ehorus_enabled']) {
    return;
}

check_login();

// Get the parameters
$agent_id = (int) get_parameter('id_agente');
$client_tab = (string) get_parameter('client_tab');

if (empty($agent_id)) {
    ui_print_error_message(__('Missing agent id'));
    return;
}

$group_id = db_get_value('id_grupo', 'tagente', 'id_agente', $agent_id);

if ($group_id === false || (!check_acl($config['id_user'], $group_id, 'AW') && !is_user_admin($config['id_user']))) {
    db_pandora_audit(
        AUDIT_LOG_ACL_VIOLATION,
        'Trying to access eHorus'
    );
    include 'general/noaccess.php';
    return;
}

require_once $config['homedir'].'/include/functions_ui.php';
require_once $config['homedir'].'/include/functions_agents.php';

$ehorus_agent_id = agents_get_agent_custom_field($agent_id, $config['ehorus_custom_field']);

if (empty($ehorus_agent_id)) {
    ui_print_error_message(__('Missing ehorus agent id'));
    return;
}

// Directory data
$hostname = $config['ehorus_hostname'];
$port = $config['ehorus_port'];
$user = $config['ehorus_user'];
$password = io_safe_output(io_output_password($config['ehorus_pass']));
$curl_timeout = $config['ehorus_req_timeout'];

$base_url = 'https://'.$hostname.':'.$port;

// If user login is enabled, fetch user and pass from user info
if ($config['ehorus_user_level_conf']) {
    $user = $user_info['ehorus_user_level_user'];
    $password = io_safe_output(io_output_password($user_info['ehorus_user_level_pass']));
}

// Get the login auth token
$login_path = '/api/login';
$body = [
    'user' => $user,
    'pass' => $password,
    'exp'  => 300,
// 5 minutes
];
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url.$login_path);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_timeout);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($body));

$result_login = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = false;
if ($result_login === false) {
    $error = curl_error($ch);
}

curl_close($ch);

if ($error !== false || $http_status !== 200) {
    if ($error !== false) {
        // echo $error;
        ui_print_error_message(__('There was an error retrieving an authorization token'));
    } else {
        ui_print_error_message($http_status.' '.$result_login);
    }

    return;
}

$response_auth = [];
try {
    $response_auth = json_decode($result_login, true);
} catch (Exception $e) {
    ui_print_error_message(__('There was an error processing the response'));
}

// Get the agent auth token
$token_path = '/api/agents/'.$ehorus_agent_id.'/token';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url.$token_path);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_timeout);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: JWT '.$response_auth['token']]);

$result_token = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = false;
if ($result_token === false) {
    $error = curl_error($ch);
}

curl_close($ch);

if ($error !== false || $http_status !== 200) {
    if ($error !== false) {
        // echo $error;
        ui_print_error_message(__('There was an error retrieving an authorization token'));
    } else {
        ui_print_error_message($http_status.' '.$result_token);
    }

    return;
}

$response_auth = [];
try {
    $response_auth = json_decode($result_token, true);
} catch (Exception $e) {
    ui_print_error_message(__('There was an error processing the response'));
}

// Get agent info
$agent_path = '/api/agents/'.$ehorus_agent_id;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $base_url.$agent_path);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $curl_timeout);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: JWT '.$response_auth['token']]);

$result_agent = curl_exec($ch);
$http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = false;
if ($result_agent === false) {
    $error = curl_error($ch);
}

curl_close($ch);

if ($error !== false || $http_status !== 200) {
    if ($error !== false) {
        // echo $error;
        ui_print_error_message(__('There was an error retrieving the agent data'));
    } else {
        ui_print_error_message($http_status.' '.$result_agent);
    }

    return;
}

$agent_data = [];
try {
    $agent_data = json_decode($result_agent, true);
} catch (Exception $e) {
    ui_print_error_message(__('There was an error processing the response'));
}

echo '<table id="ehorus-client-run-info" class="databox w100p"><tr>';
echo '<td>';
echo __('Remote management of this agent with eHorus');
echo '</td><td>';
echo '<input type="button" id="run-ehorus-client" class="sub next" value="'.__('Launch').'" />';
echo '</td>';
echo '</tr></table>';

echo '<div id="expired_message" class="invisible">';
ui_print_error_message(
    __('The connection was lost and the authorization token was expired').'. '.__('Reload the page to request a new authorization token').'. '
);
echo '</div>';

echo '<div id="ehorus-client-iframe"></div>';

$query_data = [
    'agent_id'        => $ehorus_agent_id,
    'hostname'        => (string) $agent_data['serverAddress'],
    'port'            => (int) $agent_data['serverPort'],
    'token'           => (string) $response_auth['token'],
    'expiration'      => (int) $response_auth['exp'],
    'is_busy'         => (bool) $agent_data['isBusy'],
    'last_connection' => (int) $agent_data['lastConnection'],
    'section'         => $client_tab,
];
$query = http_build_query($query_data);
$client_url = $config['homeurl'].'operation/agentes/ehorus_client.php?'.$query;

?>

<script type="text/javascript">
    $(document).ready(function () {
        var handleTabClick = function (section, messager) {
            return function (event) {
                event.preventDefault();
                messager({
                    action: 'change_section',
                    payload: {
                        section: section
                    }
                })
            }
        }
        
        var heightCorrection = 20;
        
        var createIframe = function (node, src) {
            var iframe = document.createElement('iframe');
            iframe.src = src;
            iframe.style.border = 'none';
            iframe.style.position = 'relative';
            iframe.style.top = '-' + heightCorrection + 'px';
            iframe.style.border = 'none';
            resizeIframe(iframe);
            node.appendChild(iframe);
            
            return iframe;
        }
        
        var getOptimalIframeSize = function () {
            var $elem = $('div#ehorus-client-iframe');
            return {
                width: $elem.width(),
                height: $(window).height() - $elem.offset().top + heightCorrection
            }
        }
        var resizeIframe = function (iframe) {
            var size = getOptimalIframeSize();
            iframe.style.width = size.width + 'px';
            iframe.style.height = size.height + 'px';
        }
        var handleResize = function (iframe) {
            return function (event) {
                resizeIframe(iframe);
            }
        }
        
        var handleMessage = function (iframe, actionHandlers) {
            return function (event) {
                // The message source should be the created iframe
                if (event.origin === window.location.origin &&
                    event.source !== iframe.contentWindow) {
                    return;
                }
                if (typeof actionHandlers === 'undefined') return;
                
                if (event.data.action in actionHandlers) {
                    actionHandlers[event.data.action](event.data.payload);
                }
            }
        }
        var messageToElement = function (elem, message) {
            elem.postMessage(message, window.location.origin);
        }
        
        var handleButtonClick = function (event) {
            $('table#ehorus-client-run-info').remove();
            
            // Init iframe
            var clientURL = '<?php echo $client_url; ?>';
            var iframe = createIframe(document.getElementById('ehorus-client-iframe'), clientURL);
            
            var messageToIframe = function (message) {
                return messageToElement(iframe.contentWindow, message)
            }
            
            var actionHandlers = {
                ready: function () {
                    $('a.ehorus_tab').click(handleTabClick('system', messageToIframe));
                    $('a.tab_terminal').click(handleTabClick('terminal', messageToIframe));
                    $('a.tab_display').click(handleTabClick('display', messageToIframe));
                    $('a.tab_processes').click(handleTabClick('processes', messageToIframe));
                    $('a.tab_services').click(handleTabClick('services', messageToIframe));
                    $('a.tab_files').click(handleTabClick('files', messageToIframe));
                },
                expired: function () {
                    $(iframe).remove();
                    $('a.ehorus_tab').unbind('click');
                    $('a.tab_terminal').unbind('click');
                    $('a.tab_display').unbind('click');
                    $('a.tab_processes').unbind('click');
                    $('a.tab_services').unbind('click');
                    $('a.tab_files').unbind('click');
                    iframe = null;
                    $('div#expired_message').show();
                }
            }
            
            // Listen for messages
            window.addEventListener('message', handleMessage(iframe, actionHandlers));
            // Listen for resize
            window.addEventListener('resize', handleResize(iframe));
        }
            
        $('input#run-ehorus-client').click(handleButtonClick);
    });
</script>
