<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * @package General
 */

global $config;

require_once 'include/functions_update_manager.php';

$message = [];

if (is_ajax()) {
    $message_id = get_parameter('message_id', false);
    if ($message_id === false) {
        return false;
    }

    $message = update_manger_get_single_message($message_id);
} else {
    $message = update_manger_get_last_message();

    if ($message === false) {
        return false;
    }

    update_manger_set_read_message($message['svn_version'], 1);
    update_manager_remote_read_messages($message['svn_version']);
}



// Prints first step pandora registration
echo '<div id="message_id_dialog" title="'.io_safe_output($message['db_field_value']).'">';

    echo '<div>';
        echo io_safe_output_html($message['data']);
    echo '</div>';
echo '</div>';

?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {
    
    $("#message_id_dialog").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        width: 850
    });
    
    $(".ui-widget-overlay").css("background", "#000");
    $(".ui-widget-overlay").css("opacity", 0.6);
    
});

/* ]]> */
</script>
