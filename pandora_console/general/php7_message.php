<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
    $url_help = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_es:Instalaci%C3%B3n_y_actualizaci%C3%B3n_PHP_7';
} else {
    $url_help = 'https://wiki.pandorafms.com/index.php?title=Pandora:Documentation_en:_PHP_7';
}

// Prints help dialog information
echo '<div id="login_help_dialog" title="PHP UPDATE REQUIRED" style="display: none;">';
    echo '<div style="width:70%; font-size: 10pt; margin: 20px; float:left">';
        echo "<p><b style='font-size: 10pt;'>".__('For a correct operation of PandoraFMS, PHP must be updated to version 7.0 or higher.').'</b></p>';
        echo "<p style='font-size: 10pt;'><b>".__('Otherwise, functionalities will be lost.').'</b></p>';
        echo '<ul>';
            echo "<li style='padding:5px;'>".__('Report download in PDF format').'</li>';
            echo "<li style='padding:5px;'>".__('Emails Sending').'</li>';
            echo "<li style='padding:5px;'>".__('Metaconsole Collections').'</li>';
            echo "<li style='padding:5px;'>".'...'.'</li>';
        echo '</ul>';
        echo '<p><a target="blank" href="'.$url_help.'"><b>'.__('Access Help').'</b></a></p>';
    echo '</div>';
    echo "<div style='margin-top: 80px;'>";
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
