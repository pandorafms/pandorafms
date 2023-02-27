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
use PandoraFMS\Enterprise\Metaconsole\Node;


function dbmanager_query($sql, &$error, $dbconnection)
{
    global $config;

    $retval = [];

    if ($sql == '') {
        return false;
    }

    $sql = html_entity_decode($sql, ENT_QUOTES);

    // Extract the text in quotes to add html entities before query db.
    $patttern = '/(?:"|\')+([^"\']*)(?:"|\')+/m';
    $sql = preg_replace_callback(
        $patttern,
        function ($matches) {
            return '"'.io_safe_input($matches[1]).'"';
        },
        $sql
    );

    if ($config['mysqli']) {
        $result = mysqli_query($dbconnection, $sql);
        if ($result === false) {
            $backtrace = debug_backtrace();
            $error = mysqli_error($dbconnection);
            return false;
        }
    }

    if ($result === true) {
        if ($config['mysqli']) {
            return mysqli_affected_rows($dbconnection);
        }
    }

    if ($config['mysqli']) {
        while ($row = mysqli_fetch_array($result, MYSQLI_ASSOC)) {
            array_push($retval, $row);
        }
    }

    if ($config['mysqli']) {
        mysqli_free_result($result);
    }

    if (! empty($retval)) {
        return $retval;
    }

    // Return false, check with === or !== .
    return 'Empty';
}


function dbmgr_extension_main()
{
    ui_require_css_file('dbmanager', 'extensions/dbmanager/');

    global $config;

    if (is_metaconsole() === true) {
        open_meta_frame();
    }

    if (!is_user_admin($config['id_user'])) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access Setup Management'
        );
        include 'general/noaccess.php';
        return;
    }

    $sql = (string) get_parameter('sql');
    $node_id = (int) get_parameter('node_id', -1);

    ui_print_page_header(__('Database interface'), 'images/gm_db.png', false, false, true);

    if (is_metaconsole() === true) {
        $img = '../../images/warning_modern.png';
    } else {
        $img = 'images/warning_modern.png';
    }

    $msg = '<div id="err_msg_centralised">'.html_print_image(
        $img,
        true
    );
    $msg .= '<div>'.__(
        'Warning, you are accessing the database directly. You can leave the system inoperative if you run an inappropriate SQL statement'
    ).'</div></div>';

    $warning_message = '<script type="text/javascript">
        $(document).ready(function () {
            infoMessage({
                title: \''.__('Warning').'\',
                text: \''.$msg.'\'    ,
                simple: true,
            })
        })
    </script>';

    if (empty($sql) === true) {
        echo $warning_message;
    }

    echo "<form method='post' action=''>";

    $table = new stdClass();
    $table->id = 'db_interface';
    $table->class = 'databox';
    $table->width = '100%';
    $table->data = [];
    $table->head = [];
    $table->colspan = [];
    $table->rowstyle = [];

    $table->colspan[0][0] = 2;
    $table->colspan[1][0] = 2;
    $table->rowspan[2][0] = 3;

    $table->rowclass[0] = 'notify';
    $table->rowclass[3] = 'pdd_5px';
    $table->rowclass[3] = 'flex-content-right';
    $table->rowclass[4] = 'flex-content-right';

    $data[0][0] = __(
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

    $data[1][0] = "Some samples of usage: <blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>";

    $data[2][0] = html_print_textarea(
        'sql',
        5,
        50,
        html_entity_decode($sql, ENT_QUOTES),
        '',
        true
    );

    if (is_metaconsole() === true) {
        // Get the servers.
        \enterprise_include_once('include/functions_metaconsole.php');
        $servers = \metaconsole_get_servers();
        if (is_array($servers) === true) {
            $servers = array_reduce(
                $servers,
                function ($carry, $item) {
                    $carry[$item['id']] = $item['server_name'];
                    return $carry;
                }
            );
        } else {
            $servers = [];
        }

        $data[3][2] = html_print_input(
            [
                'name'          => 'node_id',
                'type'          => 'select',
                'fields'        => $servers,
                'selected'      => $node_id,
                'nothing'       => __('This metaconsole'),
                'nothing_value' => -1,
                'return'        => true,
                'label'         => _('Select query target'),
            ]
        );
    }

    $data[4][2] = '<div class="action-buttons w100p">';
    $data[4][2] .= html_print_submit_button(
        __('Execute SQL'),
        '',
        false,
        'class="sub next"',
        true
    );
    $data[4][2] .= '</div>';

    $table->data = $data;
    html_print_table($table);
    echo '</form>';

    // Processing SQL Code.
    if ($sql == '') {
        return;
    }

    echo '<br />';
    echo '<hr />';
    echo '<br />';

    try {
        if (\is_metaconsole() === true && $node_id !== -1) {
            $node = new Node($node_id);
                $dbconnection = @get_dbconnection(
                    [
                        'dbhost' => $node->dbhost(),
                        'dbport' => $node->dbport(),
                        'dbname' => $node->dbname(),
                        'dbuser' => $node->dbuser(),
                        'dbpass' => io_output_password($node->dbpass()),
                    ]
                );
                $error = '';
                $result = dbmanager_query($sql, $error, $dbconnection);
        } else {
            $dbconnection = $config['dbconnection'];
            $error = '';
            $result = dbmanager_query($sql, $error, $dbconnection);
        }
    } catch (\Exception $e) {
        $error = __('Error querying database node');
        $result = false;
    }

    if ($result === false) {
        echo '<strong>An error has occured when querying the database.</strong><br />';
        echo $error;

        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            'DB Interface Extension. Error in SQL',
            false,
            false,
            $sql
        );

        return;
    }

    if (is_array($result) === false) {
        echo '<strong>Output: <strong>'.$result;

        db_pandora_audit(
            AUDIT_LOG_SYSTEM,
            'DB Interface Extension. SQL',
            false,
            false,
            $sql
        );

        return;
    }

    echo "<div class='overflow'>";
    $table = new stdClass();
    $table->width = '100%';
    $table->class = 'info_table';
    $table->head = array_keys($result[0]);

    $table->data = $result;

    html_print_table($table);
    echo '</div>';

    if (is_metaconsole()) {
        close_meta_frame();
    }

}


if (is_metaconsole() === true) {
    // This adds a option in the operation menu.
    extensions_add_meta_menu_option(
        'DB interface',
        'PM',
        'gextensions',
        'database.png',
        'v1r1',
        'gdbman'
    );

    extensions_add_meta_function('dbmgr_extension_main');
}

// This adds a option in the operation menu.
extensions_add_godmode_menu_option(__('DB interface'), 'PM', 'gextensions', 'dbmanager/icon.png', 'v1r1', 'gdbman');

// This sets the function to be called when the extension is selected in the operation menu.
extensions_add_godmode_function('dbmgr_extension_main');
