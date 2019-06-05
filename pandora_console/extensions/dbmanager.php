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
function dbmanager_query($sql, &$error, $dbconnection)
{
    global $config;

    $retval = [];

    if ($sql == '') {
        return false;
    }

    $sql = html_entity_decode($sql, ENT_QUOTES);
    if ($config['mysqli']) {
        $result = mysqli_query($dbconnection, $sql);
        if ($result === false) {
            $backtrace = debug_backtrace();
            $error = mysqli_error($dbconnection);
            return false;
        }
    } else {
        $result = mysql_query($sql, $dbconnection);
        if ($result === false) {
            $backtrace = debug_backtrace();
            $error = mysql_error();
            return false;
        }
    }

    if ($result === true) {
        if ($config['mysqli']) {
            return mysqli_affected_rows($dbconnection);
        } else {
            return mysql_affected_rows();
        }
    }

    if ($config['mysqli']) {
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            array_push($retval, $row);
        }
    } else {
        while ($row = mysql_fetch_array($result, MYSQL_ASSOC)) {
            array_push($retval, $row);
        }
    }

    if ($config['mysqli']) {
        mysqli_free_result($result);
    } else {
        mysql_free_result($result);
    }

    if (! empty($retval)) {
        return $retval;
    }

    // Return false, check with === or !==
    return 'Empty';
}


function dbmgr_extension_main()
{
    ui_require_css_file('dbmanager', 'extensions/dbmanager/');

    global $config;

    if (!is_user_admin($config['id_user'])) {
        db_pandora_audit('ACL Violation', 'Trying to access Setup Management');
        include 'general/noaccess.php';
        return;
    }

    $sql = (string) get_parameter('sql');

    ui_print_page_header(__('Database interface'), 'images/gm_db.png', false, false, true);

    echo '<div class="notify">';
    echo __(
        "This is an advanced extension to interface with %s database directly from WEB console
		using native SQL sentences. Please note that <b>you can damage</b> your %s installation
		if you don't know </b>exactly</b> what are you are doing,
		this means that you can severily damage your setup using this extension.
		This extension is intended to be used <b>only by experienced users</b>
		with a depth knowledge of %s internals.",
        get_product_name(),
        get_product_name(),
        get_product_name()
    );
    echo '</div>';

    echo '<br />';
    echo "Some samples of usage: <blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>";

    echo '<br /><br />';
    echo "<form method='post' action=''>";
    html_print_textarea('sql', 5, 50, html_entity_decode($sql, ENT_QUOTES));
    echo '<br />';
    echo '<div class="action-buttons" style="width: 100%">';
    echo '<br />';
    html_print_submit_button(__('Execute SQL'), '', false, 'class="sub next"');
    echo '</div>';
    echo '</form>';

    // Processing SQL Code
    if ($sql == '') {
        return;
    }

    echo '<br />';
    echo '<hr />';
    echo '<br />';

    $dbconnection = $config['dbconnection'];
    $error = '';

    $result = dbmanager_query($sql, $error, $dbconnection);

    if ($result === false) {
        echo '<strong>An error has occured when querying the database.</strong><br />';
        echo $error;

        db_pandora_audit('DB Interface Extension', 'Error in SQL', false, false, $sql);

        return;
    }

    if (! is_array($result)) {
        echo '<strong>Output: <strong>'.$result;

        db_pandora_audit('DB Interface Extension', 'SQL', false, false, $sql);

        return;
    }

    echo "<div style='overflow: auto;'>";
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->head = array_keys($result[0]);

    $table->data = $result;

    html_print_table($table);
    echo '</div>';
}


// This adds a option in the operation menu
extensions_add_godmode_menu_option(__('DB interface'), 'PM', 'gextensions', 'dbmanager/icon.png', 'v1r1', 'gdbman');

// This sets the function to be called when the extension is selected in the operation menu
extensions_add_godmode_function('dbmgr_extension_main');
