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
function extension_db_status()
{
    global $config;

    $db_user = get_parameter('db_user', '');
    $db_password = get_parameter('db_password', '');
    $db_host = get_parameter('db_host', '');
    $db_name = get_parameter('db_name', '');
    $db_status_execute = (bool) get_parameter('db_status_execute', false);

    ui_print_standard_header(
        __('DB Schema check'),
        'images/extensions.png',
        false,
        'db_status_tab',
        true,
        [],
        [
            [
                'link'  => '',
                'label' => __('Admin tools'),
            ],
            [
                'link'  => '',
                'label' => __('Run test'),
            ],
        ]
    );

    if (!is_user_admin($config['id_user'])) {
        db_pandora_audit(
            AUDIT_LOG_ACL_VIOLATION,
            'Trying to access db status'
        );
        include 'general/noaccess.php';
        return;
    }

    ui_print_info_message(
        __('This extension checks the DB is correct. Because sometimes the old DB from a migration has not some fields in the tables or the data is changed.')
    );
    ui_print_info_message(
        __('At the moment the checks is for MySQL/MariaDB.')
    );

    echo "<form method='post' class='max_floating_element_size'>";

    echo '<fieldset>';
    echo '<legend>'.__('DB settings').'</legend>';
    $table = new stdClass();
    $table->data = [];
    $row = [];
    $row[] = html_print_label_input_block(
        __('DB User with privileges'),
        html_print_input_text(
            'db_user',
            $db_user,
            '',
            50,
            255,
            true,
            false,
            false,
            '',
            'w100p mrgn_top_10px'
        )
    );
    $row[] = html_print_label_input_block(
        __('DB Password for this user'),
        html_print_input_password(
            'db_password',
            $db_password,
            '',
            50,
            255,
            true,
            false,
            false,
            'w100p mrgn_top_10px'
        )
    );
    $table->data[] = $row;
    $row = [];
    $row[] = html_print_label_input_block(
        __('DB Hostname'),
        html_print_input_text(
            'db_host',
            $db_host,
            '',
            50,
            255,
            true,
            false,
            false,
            '',
            'w100p mrgn_top_10px'
        )
    );
    $row[] = html_print_label_input_block(
        __('DB Name (temporal for testing)'),
        html_print_input_text(
            'db_name',
            $db_name,
            '',
            50,
            255,
            true,
            false,
            false,
            '',
            'w100p mrgn_top_10px'
        )
    );
    $table->data[] = $row;
    html_print_table($table);
    echo '</fieldset>';

    html_print_action_buttons(
        html_print_submit_button(
            __('Execute Test'),
            'submit',
            false,
            [ 'icon' => 'cog' ],
            true
        )
    );

    html_print_input_hidden('db_status_execute', 1);
    echo '</form>';

    if ($db_status_execute) {
        extension_db_status_execute_checks(
            $db_user,
            $db_password,
            $db_host,
            $db_name
        );
    }
}


function extension_db_status_execute_checks($db_user, $db_password, $db_host, $db_name)
{
    global $config;

    $connection_system = $config['dbconnection'];

    // Avoid SQL injection
    $db_name = io_safe_output($db_name);
    $db_name = str_replace(';', ' ', $db_name);
    $db_name = explode(' ', $db_name);
    $db_name = $db_name[0];

    if ($config['mysqli'] === true) {
        $connection_test = mysqli_connect($db_host, $db_user, $db_password);
    } else {
        $connection_test = mysql_connect($db_host, $db_user, $db_password);
    }

    if (!$connection_test) {
        ui_print_error_message(
            __('Unsuccessful connected to the DB')
        );
    } else {
        if ($config['mysqli'] === true) {
            $create_db = mysqli_query($connection_test, "CREATE DATABASE `$db_name`");
        } else {
            $create_db = mysql_query("CREATE DATABASE `$db_name`");
        }

        if (!$create_db) {
            ui_print_error_message(
                __('Unsuccessful created the testing DB')
            );
        } else {
            if ($config['mysqli'] === true) {
                mysqli_select_db($connection_test, $db_name);
            } else {
                mysql_select_db($db_name, $connection_test);
            }

            $install_tables = extension_db_status_execute_sql_file(
                $config['homedir'].'/pandoradb.sql',
                $connection_test
            );

            if (!$install_tables) {
                ui_print_error_message(
                    __('Unsuccessful installed tables into the testing DB')
                );
            } else {
                extension_db_check_tables_differences(
                    $connection_test,
                    $connection_system,
                    $db_name,
                    $config['dbname']
                );
            }

            if ($config['mysqli'] === true) {
                mysqli_select_db($connection_test, $db_name);
                mysqli_query($connection_test, "DROP DATABASE IF EXISTS `$db_name`");
            } else {
                mysql_select_db($db_name, $connection_test);
                mysql_query("DROP DATABASE IF EXISTS `$db_name`", $connection_test);
            }
        }
    }
}


function extension_db_check_tables_differences(
    $connection_test,
    $connection_system,
    $db_name_test,
    $db_name_system
) {
    global $config;

    // --------- Check the tables --------------------------------------
    if ($config['mysqli'] === true) {
        mysqli_select_db($connection_test, $db_name_test);
        $result = mysqli_query($connection_test, 'SHOW TABLES');
    } else {
        mysql_select_db($db_name_test, $connection_test);
        $result = mysql_query('SHOW TABLES', $connection_test);
    }

    $tables_test = [];

    if ($config['mysqli'] === true) {
        while ($row = mysqli_fetch_array($result)) {
            $tables_test[] = $row[0];
        }

        mysqli_free_result($result);

        mysqli_select_db($connection_test, $db_name_system);

        $result = mysqli_query($connection_test, 'SHOW TABLES');
    } else {
        while ($row = mysql_fetch_array($result)) {
            $tables_test[] = $row[0];
        }

        mysql_free_result($result);

        mysql_select_db($db_name_system, $connection_test);

        $result = mysql_query('SHOW TABLES', $connection_test);
    }

    $tables_system = [];

    if ($config['mysqli'] === true) {
        while ($row = mysqli_fetch_array($result)) {
            $tables_system[] = $row[0];
        }

        mysqli_free_result($result);
    } else {
        while ($row = mysql_fetch_array($result)) {
            $tables_system[] = $row[0];
        }

        mysql_free_result($result);
    }

    $diff_tables = array_diff($tables_test, $tables_system);

    ui_print_result_message(
        empty($diff_tables),
        __('Success! %s DB contains all tables', get_product_name()),
        __(
            '%s DB could not retrieve all tables. The missing tables are (%s)',
            get_product_name(),
            implode(', ', $diff_tables)
        )
    );

    if (!empty($diff_tables)) {
        foreach ($diff_tables as $table) {
            if ($config['mysqli'] === true) {
                mysqli_select_db($connection_test, $db_name_test);
                $result = mysqli_query($connection_test, 'SHOW CREATE TABLE '.$table);
                $tables_test = [];
                while ($row = mysql_fetch_array($result)) {
                    ui_print_info_message(
                        __('You can execute this SQL query for to fix.').'<br />'.'<pre>'.$row[1].'</pre>'
                    );
                }

                mysqli_free_result($result);
            } else {
                mysql_select_db($db_name_test, $connection_test);
                $result = mysql_query('SHOW CREATE TABLE '.$table, $connection_test);
                $tables_test = [];
                while ($row = mysql_fetch_array($result)) {
                    ui_print_info_message(
                        __('You can execute this SQL query for to fix.').'<br />'.'<pre>'.$row[1].'</pre>'
                    );
                }

                mysql_free_result($result);
            }
        }
    }

    // --------------- Check the fields -------------------------------
    $correct_fields = true;

    foreach ($tables_system as $table) {
        if ($config['mysqli'] === true) {
            mysqli_select_db($connection_test, $db_name_test);
            $result = mysqli_query($connection_test, 'EXPLAIN '.$table);
        } else {
            mysql_select_db($db_name_test, $connection_test);
            $result = mysql_query('EXPLAIN '.$table, $connection_test);
        }

        $fields_test = [];
        if (!empty($result)) {
            if ($config['mysqli'] === true) {
                while ($row = mysqli_fetch_array($result)) {
                    $fields_test[$row[0]] = [
                        'field '  => $row[0],
                        'type'    => $row[1],
                        'null'    => $row[2],
                        'key'     => $row[3],
                        'default' => $row[4],
                        'extra'   => $row[5],
                    ];
                }

                mysqli_free_result($result);
                mysqli_select_db($connection_test, $db_name_system);
            } else {
                while ($row = mysql_fetch_array($result)) {
                    $fields_test[$row[0]] = [
                        'field '  => $row[0],
                        'type'    => $row[1],
                        'null'    => $row[2],
                        'key'     => $row[3],
                        'default' => $row[4],
                        'extra'   => $row[5],
                    ];
                }

                mysql_free_result($result);
                mysql_select_db($db_name_system, $connection_test);
            }
        }

        if ($config['mysqli'] === true) {
            $result = mysqli_query($connection_test, 'EXPLAIN '.$table);
        } else {
            $result = mysql_query('EXPLAIN '.$table, $connection_test);
        }

        $fields_system = [];
        if (!empty($result)) {
            if ($config['mysqli'] === true) {
                while ($row = mysqli_fetch_array($result)) {
                    $fields_system[$row[0]] = [
                        'field '  => $row[0],
                        'type'    => $row[1],
                        'null'    => $row[2],
                        'key'     => $row[3],
                        'default' => $row[4],
                        'extra'   => $row[5],
                    ];
                }

                mysqli_free_result($result);
            } else {
                while ($row = mysql_fetch_array($result)) {
                    $fields_system[$row[0]] = [
                        'field '  => $row[0],
                        'type'    => $row[1],
                        'null'    => $row[2],
                        'key'     => $row[3],
                        'default' => $row[4],
                        'extra'   => $row[5],
                    ];
                }

                mysql_free_result($result);
            }
        }

        foreach ($fields_test as $name_field => $field_test) {
            if (!isset($fields_system[$name_field])) {
                $correct_fields = false;

                ui_print_error_message(
                    __(
                        'Unsuccessful the table %s has not the field %s',
                        $table,
                        $name_field
                    )
                );
                ui_print_info_message(
                    __('You can execute this SQL query for to fix.').'<br />'.'<pre>'.'ALTER TABLE '.$table.' ADD COLUMN '.$name_field.' text;'.'</pre>'
                );
            } else {
                $correct_fields = false;
                $field_system = $fields_system[$name_field];

                $diff = array_diff($field_test, $field_system);

                if (!empty($diff)) {
                        $info_message = '';
                        $error_message = '';
                    if ($diff['type']) {
                        $error_message .= 'Unsuccessful the field '.$name_field.' in the table '.$table.' must be set the type with '.$diff['type'].'<br>';
                    }

                    if ($diff['null']) {
                        $error_message .= "Unsuccessful the field $name_field in the table $table must be null: (".$diff['null'].').<br>';
                    }

                    if ($diff['default']) {
                        $error_message .= "Unsuccessful the field $name_field in the table $table must be set ".$diff['default'].' as default value.<br>';
                    }

                    if ($field_test['null'] == 'YES' || !isset($field_test['null']) || $field_test['null'] == '') {
                        $null_defect = ' NULL';
                    } else {
                        $null_defect = ' NOT NULL';
                    }

                    if (!isset($field_test['default']) || $field_test['default'] == '') {
                        $default_value = '';
                    } else {
                        $default_value = ' DEFAULT '.$field_test['default'];
                    }

                    if ($diff['type'] || $diff['null'] || $diff['default']) {
                        $info_message .= 'ALTER TABLE '.$table.' MODIFY COLUMN '.$name_field.' '.$field_test['type'].$null_defect.$default_value.';';
                    }

                    if ($diff['key']) {
                        $error_message .= "Unsuccessful the field $name_field in the table $table must be set the key as defined in the SQL file.<br>";
                        $info_message .= '<br><br>Please check the SQL file for to know the kind of key needed.';
                    }

                    if ($diff['extra']) {
                        $error_message .= "Unsuccessful the field $name_field in the table $table must be set as defined in the SQL file.<br>";
                        $info_message .= '<br><br>Please check the SQL file for to know the kind of extra config needed.';
                    }

                            ui_print_error_message(__($error_message));

                            ui_print_info_message(__($info_message));
                }
            }
        }
    }

    if ($correct_fields) {
        ui_print_success_message(
            __('Successful all the tables have the correct fields')
        );
    }
}


function extension_db_status_execute_sql_file($url, $connection)
{
    global $config;
    if (file_exists($url)) {
        $file_content = file($url);
        $query = '';
        foreach ($file_content as $sql_line) {
            if (trim($sql_line) != '' && strpos($sql_line, '--') === false) {
                $query .= $sql_line;
                if (preg_match("/;[\040]*\$/", $sql_line)) {
                    if ($config['mysqli'] === true) {
                        if (!$result = mysqli_query($connection, $query)) {
                            echo mysqli_error();
                            // Uncomment for debug
                            echo "<i><br>$query<br></i>";
                            return 0;
                        }
                    } else {
                        if (!$result = mysql_query($query, $connection)) {
                            echo mysql_error();
                            // Uncomment for debug
                            echo "<i><br>$query<br></i>";
                            return 0;
                        }
                    }

                    $query = '';
                }
            }
        }

        return 1;
    } else {
        return 0;
    }
}


extensions_add_godmode_function('extension_db_status');
extensions_add_godmode_menu_option(__('DB Schema check'), 'DM', 'gextensions', null, 'v1r1', 'gdbman');
