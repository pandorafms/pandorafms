<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
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

if (is_ajax()) {
    $skip_login_help = get_parameter('skip_login_help', 0);

    // Updates config['skip_login_help_dialog'] in order to don't show login help message
    if ($skip_login_help) {
        if (isset($config['skip_login_help_dialog'])) {
            $result_config = db_process_sql_update('tconfig', ['value' => 1], ['token' => 'skip_login_help_dialog']);
        } else {
            $result_config = db_process_sql_insert('tconfig', ['value' => 1, 'token' => 'skip_login_help_dialog']);
        }
    }

    return;
}

// Prints help dialog information
echo '<div id="login_help_dialog" title="'.__('Welcome to %s', get_product_name()).'" style="">';

    echo '<div style="font-size: 10pt; margin: 20px;">';
    echo __(
        "If this is your first time using %s, we suggest a few links that'll help you learn more about the software. Monitoring can be overwhelming, but take your time to learn how to harness the power of %s!",
        get_product_name(),
        get_product_name()
    );
    echo '</div>';

    echo '<div style="">';
        echo '<table cellspacing=0 cellpadding=0 style="border:1px solid #FFF; width:100%; height: 100%">';
        echo '<tr>';
            echo '<td style="border:1px solid #FFF; text-align:center;">';
                echo '<a href="'.ui_get_full_url(false).'general/pandora_help.php?id=main_help" target="_blank" style="text-decoration:none;">'.html_print_image(
                    'images/online_help.png',
                    true,
                    [
                        'alt'    => __('Online help'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br style="margin-bottom: 40px;" />';
                echo '<a style="font-size: 9pt;" href="'.ui_get_full_url(false).'general/pandora_help.php?id=main_help" target="_blank">'.__('Online help').'</a>';
                echo '</td>';

                echo '<td style="border:1px solid #FFF; text-align:center;">';
                echo '<a href="http://pandorafms.com/" target="_blank" style="text-decoration:none;">'.html_print_image(
                    'images/enterprise_version.png',
                    true,
                    [
                        'alt'    => __('Enterprise version'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br style="margin-bottom: 40px;" />';
                echo '<a style="font-size: 9pt;" href="http://pandorafms.com/" target="_blank">'.__('Enterprise version').'</a>';
                echo '</td>';

                echo '<td style="border:1px solid #FFF; text-align:center;">';
                echo '<a href="https://pandorafms.com/forums" target="_blank" style="text-decoration:none;">'.html_print_image(
                    'images/support.png',
                    true,
                    [
                        'alt'    => __('Support'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br style="margin-bottom: 40px;" />';
                echo '<a style="font-size: 9pt;" href="https://pandorafms.com/forums" target="_blank">'.__('Support').' / '.__('Forums').'</a>';
                echo '</td>';

                echo '<td style="border:1px solid #FFF; text-align:center;">';
                echo '<a href="'.$config['custom_docs_url'].'" target="_blank" style="text-decoration:none;">'.html_print_image(
                    'images/documentation.png',
                    true,
                    [
                        'alt'    => __('Documentation'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br style="margin-bottom: 40px;" />';
                echo '<a style="font-size: 9pt;" href="'.$config['custom_docs_url'].'" target="_blank">'.__('Documentation').'</span></a>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
                echo '</div>';

                echo '<div style="position:absolute; margin: 0 auto; top: 240px; right: 10px; border: 1px solid #FFF; width: 570px">';
                echo '<div style="float: left; margin-top: 3px; margin-left: 0px; width: 80%; text-align: left;">';
                html_print_checkbox('skip_login_help', 1, false, false, false, 'cursor: \'pointer\'');
                echo '&nbsp;<span style="font-size: 12px;">'.__("Click here to don't show again this message").'</span>';
                echo '</div>';
                echo '<div style="float: right; width: 20%;">';
                html_print_submit_button('Ok', 'hide-login-help', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok" style="width:100px;"');
                echo '</div>';
                echo '</div>';

                echo '</div>';
                ?>

<script type="text/javascript" language="javascript">
/* <![CDATA[ */

$(document).ready (function () {
    
    $("#login_help_dialog").dialog({
        resizable: true,
        draggable: true,
        modal: true,
        height: 350,
        width: 630,
        overlay: {
                opacity: 0.5,
                background: "black"
            }
    });
    
    
    $("#submit-hide-login-help").click (function () {
        
        $("#login_help_dialog" ).dialog('close');
        
        var skip_login_help = $("#checkbox-skip_login_help").is(':checked');
        
        // Update config['skip_login_help_dialog'] to don't display more this message
        if (skip_login_help) {
            jQuery.post ("ajax.php",
            {"page": "general/login_help_dialog",
             "skip_login_help": 1},
            function (data) {}
            );
        }
        
    });
});

/* ]]> */
</script>
