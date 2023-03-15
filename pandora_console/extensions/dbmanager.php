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

    // Header.
    ui_print_standard_header(
        __('Database interface'),
        'images/gm_db.png',
        false,
        '',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Extensions'),
            ],
        ]
    );

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

    ui_print_warning_message(
        __(
            "This is an advanced extension to interface with %s database directly from WEB console
            using native SQL sentences. Please note that <b>you can damage</b> your %s installation
            if you don't know </b>exactly</b> what are you are doing,
            this means that you can severily damage your setup using this extension.
            This extension is intended to be used <b>only by experienced users</b>
            with a depth knowledge of %s internals.",
            get_product_name(),
            get_product_name(),
            get_product_name()
        )
    );

    echo "<form method='post' action=''>";

    $table = new stdClass();
    $table->id = 'db_interface';
    $table->class = 'databox no_border filter-table-adv';
    $table->width = '100%';
    $table->data = [];
    $table->colspan = [];
    $table->style[0] = 'width: 30%;';
    $table->style[1] = 'width: 70%;';

    $table->colspan[1][0] = 2;

    $data[0][0] = "<b>Some samples of usage:</b> <blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>";
    $data[0][0] = html_print_label_input_block(
        __('Some samples of usage:'),
        "<blockquote><em>SHOW STATUS;<br />DESCRIBE tagente<br />SELECT * FROM tserver<br />UPDATE tagente SET id_grupo = 15 WHERE nombre LIKE '%194.179%'</em></blockquote>"
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

        $data[0][1] = html_print_label_input_block(
            __('Select query target'),
            html_print_select(
                $servers,
                'node_id',
                $node_id,
                '',
                __('This metaconsole'),
                -1,
                true,
                false,
                false,
                'w40p',
                false,
                'width: 40%;'
            )
        );
    }

    $data[1][0] = html_print_textarea(
        'sql',
        3,
        50,
        html_entity_decode($sql, ENT_QUOTES),
        'placeholder="'.__('Type your query here...').'"',
        true,
        'w100p'
    );

    $execute_button = html_print_submit_button(
        __('Execute SQL'),
        '',
        false,
        [ 'icon' => 'cog' ],
        true
    );

    $table->data = $data;
    // html_print_table($table);
    html_print_action_buttons($execute_button);
    ui_toggle(
        html_print_table($table, true),
        '<span class="subsection_header_title">'.__('SQL query').'</span>',
        __('SQL query'),
        'query',
        false,
        false,
        '',
        'white-box-content no_border',
        'box-flat white_table_graph fixed_filter_bar'
    );
    echo '</form>';

    // Processing SQL Code.
    if ($sql == '') {
        return;
    }

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

}


if (is_metaconsole() === true) {
    // This adds a option in the operation menu.
    extensions_add_meta_menu_option(
        __('DB interface'),
        'PM',
        'gextensions',
        'database.png',
        'v1r1'
    );

    extensions_add_meta_function('dbmgr_extension_main');
}

// This adds a option in the operation menu.
extensions_add_godmode_menu_option(__('DB interface'), 'PM', 'gextensions', 'dbmanager/icon.png', 'v1r1', 'gdbman');

// This sets the function to be called when the extension is selected in the operation menu.
extensions_add_godmode_function('dbmgr_extension_main');
