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
echo '<div id="login_help_dialog" title="'.__('Welcome to %s', get_product_name()).'"  >';

    echo '<div id="help_dialog">';
    echo __(
        "If this is your first time using %s, we suggest a few links that'll help you learn more about the software. Monitoring can be overwhelming, but take your time to learn how to harness the power of %s!",
        get_product_name(),
        get_product_name()
    );
    echo '</div>';

    echo '<div>';
        echo '<table cellspacing=0 cellpadding=0 class="border_solid_white w100p h100p">';
        echo '<tr>';
            echo '<td class="border_solid_white center">';
                echo '<a href="'.ui_get_full_url(false).'general/pandora_help.php?id=main_help" target="_blank" class="no_decoration">'.html_print_image(
                    'images/online_help.png',
                    true,
                    [
                        'alt'    => __('Online help'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br id="br_mb_40" />';
                echo '<a class="font_9pt" href="'.ui_get_full_url(false).'general/pandora_help.php?id=main_help" target="_blank">'.__('Online help').'</a>';
                echo '</td>';

                echo '<td class="border_solid_white center">';
                echo '<a href="http://pandorafms.com/" target="_blank" class="no_decoration">'.html_print_image(
                    'images/enterprise_version.png',
                    true,
                    [
                        'alt'    => __('Enterprise version'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br id="br_mb_40" />';
                echo '<a class="font_9pt" href="http://pandorafms.com/" target="_blank">'.__('Enterprise version').'</a>';
                echo '</td>';

                echo '<td class="border_solid_white center">';
                echo '<a href="https://pandorafms.com/forums" target="_blank" class="no_decoration">'.html_print_image(
                    'images/support.png',
                    true,
                    [
                        'alt'    => __('Support'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br id="br_mb_40" />';
                echo '<a class="font_9pt" href="https://pandorafms.com/forums" target="_blank">'.__('Support').' / '.__('Forums').'</a>';
                echo '</td>';

                echo '<td class="border_solid_white center">';
                echo '<a href="'.ui_get_full_external_url($config['custom_docs_url']).'" target="_blank" class="no_decoration">'.html_print_image(
                    'images/documentation.png',
                    true,
                    [
                        'alt'    => __('Documentation'),
                        'border' => 0,
                    ]
                ).'</a>';
                echo '<br id="br_mb_40" />';
                echo '<a clas="font_9pt" href="'.ui_get_full_external_url($config['custom_docs_url']).'" target="_blank">'.__('Documentation').'</span></a>';
                echo '</td>';
                echo '</tr>';
                echo '</table>';
                echo '</div>';

                echo '<div class="absolute help_dialog_login" ">';
                echo '<div class="skip_help_login">';
                html_print_checkbox('skip_login_help', 1, false, false, false, 'cursor: \'pointer\'');
                echo '&nbsp;<span class="font_12pt">'.__("Click here to don't show again this message").'</span>';
                echo '</div>';
                echo '<div class="float-right w20p">';
                html_print_submit_button('Ok', 'hide-login-help', false, 'class="ui-button-dialog ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok w100p"');
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
