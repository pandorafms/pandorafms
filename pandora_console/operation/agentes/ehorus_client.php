<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Don't start a session before this import.
// The session is configured and started inside the config process.
require_once '../../include/config.php';
require_once $config['homedir'].'/include/functions.php';

check_login();

$agent_id = (string) get_parameter_get('agent_id');
$hostname = (string) get_parameter_get('hostname');
$port = (int) get_parameter_get('port');
$token = (string) get_parameter_get('token');
$expiration = (int) get_parameter_get('expiration');
$is_busy = (bool) get_parameter_get('is_busy');
$last_connection = (int) get_parameter_get('last_connection');
$section = (string) get_parameter_get('section');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>eHorus client</title>
        <link rel="stylesheet" href="../../include/ehorus/css/bootstrap.min.css" type="text/css" />
        <link rel="stylesheet" href="../../include/ehorus/css/style.css" type="text/css" />
        <link rel="stylesheet" href="../../include/ehorus/css/xterm.css" type="text/css" />
        <script type="text/javascript" src="../../include/ehorus/bundle.min.js"></script>
    </head>
    <body>
        <div id="ehorus-client-container"></div>
    </body>
</html>

<script type="text/javascript">
    (function () {
        var runClient = function (node, props) {
            props = props || {};
            var agentID = props.agentID;
            var protocol = 'wss';
            var hostname = props.hostname;
            var port = props.port;
            var token = props.token;
            var isBusy = props.isBusy;
            var lastConnection = props.lastConnection;
            var section = props.section || 'system';
            
            var eHorusProps = {
                url: {
                    protocol: protocol,
                    hostname: hostname,
                    port: port,
                    slashes: true,
                    pathname: '',
                    search: 'auth=' + token
                },
                agentID: agentID,
                agentLastContact: lastConnection,
                agentIsBusy: isBusy,
                header: false,
                section: section
            }
            var eHorus = new EHorus(eHorusProps);
            eHorus.renderIn(node);
            
            return eHorus;
        }
        
        var messageToParent = function (message) {
            window.parent.postMessage(message, window.location.origin);
        }
        
        var handleMessage = function (actionHandlers) {
            return function (event) {
                // The message source should be the created in the parent
                if (event.origin === window.location.origin &&
                    event.source !== window.parent) {
                    return;
                }
                if (typeof actionHandlers === 'undefined') return;
                
                if (event.data.action in actionHandlers) {
                    actionHandlers[event.data.action](event.data.payload);
                }
            }
        }
        
        window.onload = function () {
            var expiration = <?php echo $expiration; ?>;
            // Start client
            var ehorusContainer = document.getElementById('ehorus-client-container');
            var eHorus = runClient(ehorusContainer, {
                agentID: '<?php echo $agent_id; ?>',
                protocol: 'wss',
                hostname: '<?php echo $hostname; ?>',
                port: <?php echo $port; ?>,
                token: '<?php echo $token; ?>',
                isBusy: <?php echo json_encode($is_busy); ?>,
                lastConnection: <?php echo $last_connection; ?>,
                section: '<?php echo $section; ?>'
            });
            
            eHorus.remote.onClose(function () {
                if (expiration && expiration < Date.now() / 1000) {
                    eHorus.remote.close();
                    // Send expired message
                    messageToParent({
                        action: 'expired',
                        payload: {}
                    });
                }
            });
            
            // Listen for messages
            var actionHandlers = {
                change_section: function (payload) {
                    eHorus.changeSection(payload.section);
                }
            }
            window.addEventListener('message', handleMessage(actionHandlers));
            
            // Send ready message
            messageToParent({
                action: 'ready',
                payload: {}
            });
        }
    })()
</script>
