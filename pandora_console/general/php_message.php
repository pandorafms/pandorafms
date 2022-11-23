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

if ($config['language'] == 'es') {
    $url_help = 'https://pandorafms.com/manual/es/documentation/07_technical_annexes/18_php_8';
} else {
    $url_help = 'https://pandorafms.com/manual/en/documentation/07_technical_annexes/18_php_8';
}

// Prints help dialog information
echo '<div id="login_help_dialog" title="PHP UPDATE REQUIRED" class="invisible">';
    echo '<div class="login_help_dialog">';
        echo "<p><b class='font_10'>".__('For a correct operation of PandoraFMS, PHP must be updated to version 8.0 or higher.').'</b></p>';
        echo "<p class='font_10'><b>".__('Otherwise, functionalities will be lost.').'</b></p>';
        echo '<ul>';
            echo "<li class='pdd_5px'>".__('Report download in PDF format').'</li>';
            echo "<li class='pdd_5px'>".__('Emails Sending').'</li>';
            echo "<li class='pdd_5px'>".__('Metaconsole Collections').'</li>';
            echo "<li class='pdd_5px'>".'...'.'</li>';
        echo '</ul>';
        echo '<p><a target="blank" href="'.$url_help.'"><b>'.__('Access Help').'</b></a></p>';
    echo '</div>';
    echo "<div class='mrg_top_80'>";
        echo html_print_image('images/icono_warning_mr.png', true, ['alt' => __('Warning php version'), 'border' => 0]);
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
        height: 320,
        width: 550,
        overlay: {
            opacity: 0.5,
            background: "black"
        }
    });
});

/* ]]> */
</script>
