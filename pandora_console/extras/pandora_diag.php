<script type="text/javascript">

function effectFadeOut() {
    $('.content').fadeOut(800).fadeIn(800)
}
$(document).ready(function(){
    setInterval(effectFadeOut, 1600);
});

</script>

<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2010 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
global $config;
check_login();

if (! check_acl($config['id_user'], 0, 'PM')) {
    db_pandora_audit('ACL Violation', 'Trying to change License settings');
    include 'general/noaccess.php';
    return;
}

$update_settings = (bool) get_parameter_post('update_settings');

if ($update_settings) {
    foreach ($_POST['keys'] as $key => $value) {
        db_process_sql_update(
            'tupdate_settings',
            [db_escape_key_identifier('value') => $value],
            [db_escape_key_identifier('key') => $key]
        );
    }

    ui_print_success_message(__('License updated'));
}

ui_require_javascript_file_enterprise('load_enterprise');
enterprise_include_once('include/functions_license.php');
$license = enterprise_hook('license_get_info');

$rows = db_get_all_rows_in_table('tupdate_settings');

$settings = new StdClass;
foreach ($rows as $row) {
    $settings->{$row['key']} = $row['value'];
}

echo '<script type="text/javascript">';
if (enterprise_installed()) {
    print_js_var_enteprise();
}

echo '</script>';


function render_info($table)
{
    global $console_mode;

    $info = db_get_sql("SELECT COUNT(*) FROM $table");
    render_row($info, "DB Table $table");
}


function render_info_data($query, $label)
{
    global $console_mode;

    $info = db_get_sql($query);
    render_row($info, $label);
}


function render_row($data, $label)
{
    global $console_mode;

    if ($console_mode == 1) {
        echo $label;
        echo '|';
        echo $data;
        echo "\n";
    } else {
        echo '<tr>';
        echo "<td style='padding:2px;border:0px;' width='60%'><div style='padding:5px;background-color:#f2f2f2;border-radius:2px;text-align:left;border:0px;'>".$label;
        echo '</div></td>';
        echo "<td style='font-weight:bold;padding:2px;border:0px;' width='40%'><div style='padding:5px;background-color:#f2f2f2;border-radius:2px;text-align:left;border:0px;'>".$data;
        echo '</div></td>';
        echo '</tr>';
    }
}


function get_value_sum($arr)
{
    foreach ($arr as $clave) {
        foreach ($clave as $valor) {
            if (is_numeric($valor) === true) {
                $result += $valor;
            }
        }
    }

        return $result;
}


function execution_time()
{
    $times = db_get_all_rows_sql('SELECT datos FROM tagente_datos WHERE id_agente_modulo = 29 ORDER BY utimestamp DESC LIMIT 2');
    if ($times[0]['datos'] > ($times[1]['datos'] * 1.2)) {
        return "<a class= 'content' style= 'color: red;'>Warning Status</a><a>&nbsp&nbsp The execution time could be degrading. For a more extensive information of this data consult the  Execution Time graph</a>";
    } else {
        return "<a style ='color: green;'>Normal Status</a><a>&nbsp&nbsp The execution time is correct. For more information about this data, check the Execution Time graph</a>";
    }
}


function get_logs_size($file)
{
    $file_name = '/var'.$file.'';
    $size_server_log = filesize($file_name);
    return $size_server_log;

}


function get_status_logs($path)
{
    $status_server_log = '';
    $size_server_log = number_format(get_logs_size($path));
    $size_server_log = (0 + str_replace(',', '', $size_server_log));
    if ($size_server_log <= 10485760) {
        $status_server_log = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp You have less than 10 MB of logs</a>";
    } else {
        $status_server_log = "<a class= 'content' style= 'color: red;text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbsp You have more than 10 MB of logs</a>";
    }

    return $status_server_log;
}


function percentage_modules_per_agent()
{
    $status_average_modules = '';
    $total_agents = db_get_value_sql('SELECT count(*) FROM tagente');
    $total_modules = db_get_value_sql('SELECT count(*) FROM tagente_modulo');
    $average_modules_per_agent = ($total_modules / $total_agents);
    if ($average_modules_per_agent <= 40) {
        $status_average_modules = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The average of modules per agent is less than 40</a>";
    } else {
        $status_average_modules = "<a class= 'content' style= 'color: red;text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbspThe average of modules per agent is more than 40. You can have performance problems</a>";
    }

    return $status_average_modules;
}


function license_capacity()
{
    $license = enterprise_hook('license_get_info');
    $license_limit = $license['limit'];
    $status_license_capacity = '';
        $current_count = db_get_value_sql('SELECT count(*) FROM tagente');
    if ($current_count > ($license_limit * 90 / 100)) {
        $status_license_capacity = "<a class= 'content' style= 'color: red;text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbsp License capacity exceeds 90 percent</a>";
    } else {
        $status_license_capacity = "<a  style= 'color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp License capacity is less than 90 percent</a>";
    }

    return $status_license_capacity;
}


function status_license_params($license_param)
{
    $status_license_par = '';
    if ($license_param <= 0) {
        $status_license_par = 'OFF';
    } else {
        $status_license_par = 'ON';
    }

    return $status_license_par;
}


function interval_average_of_network_modules()
{
    $total_network_modules = db_get_value_sql('SELECT count(*) FROM tagente_modulo WHERE id_tipo_modulo BETWEEN 6 AND 18');
    $total_module_interval_time = db_get_value_sql('SELECT SUM(module_interval) FROM tagente_modulo WHERE id_tipo_modulo BETWEEN 6 AND 18');
    $average_time = ((int) $total_module_interval_time / $total_network_modules);

    if ($average_time < 180) {
        $status_average_modules = "<a class= 'content' style= 'color: red;text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The system is overloaded (average time $average_time) and a very fine configuration is required</a>";
    } else {
        $status_average_modules = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The system is not overloaded (average time $average_time) </a>";
    }

    if ($average_time == 0) {
        $status_average_modules = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The system has no load</a>";
    }

    return $status_average_modules;
}


$attachment_total_files = count(glob($config['homedir'].'/attachment/{*.*}', GLOB_BRACE));


function files_attachment_folder($total_files)
{
    if ($total_files <= 700) {
        $status_total_files = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The attached folder contains less than 700 files.</a>";
    } else {
        $status_total_files = "<a class= 'content' style= 'color: red;text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The attached folder contains more than 700 files.</a>";
    }

    return $status_total_files;
}


$tagente_datos_size = db_get_value_sql('SELECT COUNT(*) FROM tagente_datos');


function status_tagente_datos($tagente_datos_size)
{
    if ($tagente_datos_size <= 3000000) {
        $tagente_datos_size = "<a style ='color: green;text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp The tagente_datos table contains an acceptable amount of data.</a>";
    } else {
        $tagente_datos_size = "<a class= 'content' style ='color: red;text-decoration: none;'>Warning Status</a><a>&nbsp&nbsp The tagente_datos table contains too much data. A historical database is recommended.</a>";
    }

    return $tagente_datos_size;
}


function status_values($val_rec, $val)
{
    if ($val_rec <= $val) {
        return $val."<a style='text-decoration: none;'> (Min. Recommended Value </a>".$val_rec.'<a>)</a>';
    } else {
        return $val."<a style='text-decoration: none;'> (Min. Recommended Value </a>".$val_rec."<a>)</a><a class= 'content' style ='color: red;text-decoration: none;'> Warning Status</a>";
    }
}


$tables_fragmentation = db_get_sql(
    "SELECT (data_free/(index_length+data_length)) 
as frag_ratio from information_schema.tables  where  DATA_FREE > 0 and table_name='tagente_datos' and table_schema='pandora'"
);
$db_size = db_get_all_rows_sql(
    'SELECT table_schema,
ROUND(SUM(data_length+index_length)/1024/1024,3)
FROM information_schema.TABLES
GROUP BY table_schema;'
);

if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
    $total_server_threads = shell_exec('ps -T aux | grep pandora_server | grep -v grep | wc -l');
    $percentage_threads_ram = shell_exec("ps axo pmem,cmd | grep pandora_server | awk '{sum+=$1} END {print sum}'");
    $percentage_threads_cpu = shell_exec("ps axo pcpu,cmd | grep pandora_server | awk '{sum+=$1} END {print sum}'");
    $innodb_buffer_pool_size_min_rec_value = shell_exec("cat /proc/meminfo | grep -i total | head -1 | awk '{print $(NF-1)*0.4/1024}'");
}

$path_server_logs = '/log/pandora/pandora_server.log';
$path_err_logs = '/log/pandora/pandora_server.error';
$path_console_logs = '/www/html/pandora_console/pandora_console.log';
$innodb_log_file_size_min_rec_value = '64M';
$innodb_log_buffer_size_min_rec_value = '16M';
$innodb_flush_log_at_trx_commit_min_rec_value = 0;
$query_cache_limit_min_rec_value = 2;
$max_allowed_packet_min_rec_value = 32;
$innodb_buffer_pool_size_min_rec_value = shell_exec("cat /proc/meminfo | grep -i total | head -1 | awk '{print $(NF-1)*0.4/1024}'");
$sort_buffer_size_min_rec_value = 32;
$join_buffer_size_min_rec_value = 265;
$query_cache_type_min_rec_value = 'ON';
$query_cache_size_min_rec_value = 24;
$innodb_lock_wait_timeout_max_rec_value = 120;
$tables_fragmentation_max_rec_value = 10;
$thread_cache_size_max_rec_value = 8;
$thread_stack_min_rec_value = 256;
$max_connections_max_rec_value = 150;
$key_buffer_size_min_rec_value = 256;
$read_buffer_size_min_rec_value = 32;
$read_rnd_buffer_size_min_rec_value = 32;
$query_cache_min_res_unit_min_rec_value = 2;
$innodb_file_per_table_min_rec_value = 1;


function status_fragmentation_tables($tables_fragmentation_max_rec_value, $tables_fragmentation)
{
    $status_tables_frag = '';
    if ($tables_fragmentation > $tables_fragmentation_max_rec_value) {
        $status_tables_frag = "<a class= 'content' style ='color: red; text-decoration: none;'>Warning Status</a><a style ='text-decoration: none;'>&nbsp&nbsp Table fragmentation is higher than recommended. They should be defragmented.</a>";
    } else {
        $status_tables_frag = "<a style ='color: green; text-decoration: none;'>Normal Status</a><a style ='text-decoration: none;'>&nbsp&nbsp Table fragmentation is correct.</a>";
    }

            return $status_tables_frag;
}


$console_mode = 1;
if (!isset($argc)) {
    $console_mode = 0;
}

if ($console_mode == 1) {
    echo "\nPandora FMS PHP diagnostic tool v3.2 (c) Artica ST 2009-2010 \n";

    if ($argc == 1 || in_array($argv[1], ['--help', '-help', '-h', '-?'])) {
        echo "\nThis command line script contains information about Pandora FMS database. 
        This program can only be executed from the console, and it needs a parameter, the
        full path to Pandora FMS 'config.php' file.

  Usage:
  php pandora_diag.php path_to_pandora_console
  
  Example:
  php pandora_diag.php /var/www/pandora_console
  
";
        exit;
    }

    if (preg_match('/[^a-zA-Z0-9_\/\.]|(\/\/)|(\.\.)/', $argv[1])) {
        echo "Invalid path: $argv[1]. Always use absolute paths.";
        exit;
    }

    include $argv[1].'/include/config.php';
} else {
    if (file_exists('../include/config.php')) {
        include '../include/config.php';
    }

    // Not from console, this is a web session.
    if ((!isset($config['id_user'])) || (!check_acl($config['id_user'], 0, 'PM'))) {
        echo "<h2>You don't have privileges to use diagnostic tool</h2>";
        echo '<p>Please login with an administrator account before try to use this tool</p>';
        exit;
    }

    // Header.
    ui_print_page_header(
        __('Pandora FMS Diagnostic tool'),
        '',
        false,
        'diagnostic_tool_tab',
        true
    );

    echo "<table id='diagnostic_info' width='1000px' border='0' style='border:0px;' class='databox data' cellpadding='4' cellspacing='4'>";
    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('Pandora status info').'</th></tr>';
}

render_row($build_version, 'Pandora FMS Build');
render_row($pandora_version, 'Pandora FMS Version');
render_info_data("SELECT value FROM tconfig where token ='MR'", 'Minor Release');
render_row($config['homedir'], 'Homedir');
render_row($config['homeurl'], 'HomeUrl');
render_info_data(
    "SELECT `value`
	FROM tconfig
	WHERE `token` = 'enterprise_installed'",
    'Enterprise installed'
);

    $full_key = db_get_sql(
        "SELECT value
		FROM tupdate_settings
		WHERE `key` = 'customer_key'"
    );

    $compressed_key = substr($full_key, 0, 5).'...'.substr($full_key, -5);

    render_row($compressed_key, 'Update Key');

    render_info_data(
        "SELECT value
		FROM tupdate_settings
		WHERE `key` = 'updating_code_path'",
        'Updating code path'
    );

    render_info_data(
        "SELECT value
		FROM tupdate_settings
		WHERE `key` = 'current_update'",
        'Current Update #'
    );


    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('PHP setup').'</th></tr>';


    render_row(phpversion(), 'PHP Version');

    render_row(ini_get('max_execution_time').'&nbspseconds', 'PHP Max execution time');

    render_row(ini_get('max_input_time').'&nbspseconds', 'PHP Max input time');

    render_row(ini_get('memory_limit'), 'PHP Memory limit');

    render_row(ini_get('session.cookie_lifetime'), 'Session cookie lifetime');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('Database size stats').'</th></tr>';

    render_info_data('SELECT COUNT(*) FROM tagente', 'Total agents');
    render_info_data('SELECT COUNT(*) FROM tagente_modulo', 'Total modules');
    render_info_data('SELECT COUNT(*) FROM tgrupo', 'Total groups');
    render_info_data('SELECT COUNT(*) FROM tagente_datos', 'Total module data records');
    render_info_data('SELECT COUNT(*) FROM tagent_access', 'Total agent access record');
    render_info_data('SELECT COUNT(*) FROM tevento', 'Total events');

    if ($config['enterprise_installed']) {
        render_info_data('SELECT COUNT(*) FROM ttrap', 'Total traps');
    }

    render_info_data('SELECT COUNT(*) FROM tusuario', 'Total users');
    render_info_data('SELECT COUNT(*) FROM tsesion', 'Total sessions');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('Database sanity').'</th></tr>';

    render_info_data(
        'SELECT COUNT( DISTINCT tagente.id_agente)
	FROM tagente_estado, tagente, tagente_modulo
	WHERE tagente.disabled = 0
		AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo
		AND tagente_modulo.disabled = 0
		AND tagente_estado.id_agente = tagente.id_agente
		AND tagente_estado.estado = 3',
        'Total unknown agents'
    );

    render_info_data(
        'SELECT COUNT(tagente_estado.estado)
	FROM tagente_estado
	WHERE tagente_estado.estado = 4',
        'Total not-init modules'
    );


    $last_run_difference = '';

    $diferencia = (time() - date(
        db_get_sql(
            "SELECT `value`
		FROM tconfig
		WHERE `token` = 'db_maintance'"
        )
    ));

    $last_run_difference_months = 0;
    $last_run_difference_weeks = 0;
    $last_run_difference_days = 0;
    $last_run_difference_minutos = 0;
    $last_run_difference_seconds = 0;

    while ($diferencia >= 2419200) {
        $diferencia -= 2419200;
        $last_run_difference_months++;
    }

    while ($diferencia >= 604800) {
        $diferencia -= 604800;
        $last_run_difference_weeks++;
    }

    while ($diferencia >= 86400) {
        $diferencia -= 86400;
        $last_run_difference_days++;
    }

    while ($diferencia >= 3600) {
        $diferencia -= 3600;
        $last_run_difference_hours++;
    }

    while ($diferencia >= 60) {
        $diferencia -= 60;
        $last_run_difference_minutes++;
    }

    $last_run_difference_seconds = $diferencia;

    if ($last_run_difference_months > 0) {
        $last_run_difference .= $last_run_difference_months.'month/s ';
    }

    if ($last_run_difference_weeks > 0) {
        $last_run_difference .= $last_run_difference_weeks.' week/s ';
    }

    if ($last_run_difference_days > 0) {
        $last_run_difference .= $last_run_difference_days.' day/s ';
    }

    if ($last_run_difference_hours > 0) {
        $last_run_difference .= $last_run_difference_hours.' hour/s ';
    }

    if ($last_run_difference_minutes > 0) {
        $last_run_difference .= $last_run_difference_minutes.' minute/s ';
    }

    $last_run_difference .= $last_run_difference_seconds.' second/s ago';

    render_row(
        date(
            'Y/m/d H:i:s',
            db_get_sql(
                "SELECT `value`
	FROM tconfig
	WHERE `token` = 'db_maintance'"
            )
        ).' ('.$last_run_difference.')'.' *',
        'PandoraDB Last run'
    );

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('Database status info').'</th></tr>';

    switch ($config['dbtype']) {
        case 'mysql':
            render_info_data(
                "SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_first_version'",
                'DB Schema Version (first installed)'
            );
            render_info_data(
                "SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_version'",
                'DB Schema Version (actual)'
            );
            render_info_data(
                "SELECT `value`
			FROM tconfig
			WHERE `token` = 'db_scheme_build'",
                'DB Schema Build'
            );

            render_row(get_value_sum($db_size).'M', 'DB Size');


            if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
                echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('System info').'</th></tr>';

                $output = 'cat /proc/cpuinfo  | grep "model name" | tail -1 | cut -f 2 -d ":"';
                $output2 = 'cat /proc/cpuinfo  | grep "processor" | wc -l';

                render_row(exec($output).' x '.exec($output2), 'CPU');

                $output = 'cat /proc/meminfo  | grep "MemTotal"';

                render_row(exec($output), 'RAM');
            }
        break;

        case 'postgresql':
            render_info_data(
                "SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'db_scheme_version'",
                'DB Schema Version'
            );
            render_info_data(
                "SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'db_scheme_build'",
                'DB Schema Build'
            );
            render_info_data(
                "SELECT \"value\"
			FROM tconfig
			WHERE \"token\" = 'enterprise_installed'",
                'Enterprise installed'
            );
            render_row(
                date(
                    'Y/m/d H:i:s',
                    db_get_sql(
                        "SELECT \"value\"
				FROM tconfig WHERE \"token\" = 'db_maintance'"
                    )
                ),
                'PandoraDB Last run'
            );

            render_info_data(
                "SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'customer_key';",
                'Update Key'
            );
            render_info_data(
                "SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'updating_code_path'",
                'Updating code path'
            );
            render_info_data(
                "SELECT value
			FROM tupdate_settings
			WHERE \"key\" = 'current_update'",
                'Current Update #'
            );
        break;

        case 'oracle':
            render_info_data(
                "SELECT value
			FROM tconfig
			WHERE token = 'db_scheme_version'",
                'DB Schema Version'
            );
            render_info_data(
                "SELECT value
			FROM tconfig
			WHERE token = 'db_scheme_build'",
                'DB Schema Build'
            );
            render_info_data(
                "SELECT value
			FROM tconfig
			WHERE token = 'enterprise_installed'",
                'Enterprise installed'
            );
            render_row(
                db_get_sql(
                    "SELECT value
			FROM tconfig
			WHERE token = 'db_maintance'"
                ),
                'PandoraDB Last run'
            );

            render_info_data(
                'SELECT '.db_escape_key_identifier('value')." FROM tupdate_settings
			WHERE \"key\" = 'customer_key'",
                'Update Key'
            );
            render_info_data(
                'SELECT '.db_escape_key_identifier('value')." FROM tupdate_settings
			WHERE \"key\" = 'updating_code_path'",
                'Updating code path'
            );
            render_info_data(
                'SELECT '.db_escape_key_identifier('value')." FROM tupdate_settings
			WHERE \"key\" = 'current_update'",
                'Current Update #'
            );
        break;
    }

    $innodb_log_file_size = (db_get_value_sql('SELECT @@innodb_log_file_size') / 1048576);
    $innodb_log_buffer_size = (db_get_value_sql('SELECT @@innodb_log_buffer_size') / 1048576);
    $innodb_flush_log_at_trx_commit = db_get_value_sql('SELECT @@innodb_flush_log_at_trx_commit');
    $max_allowed_packet = (db_get_value_sql('SELECT @@max_allowed_packet') / 1048576);
    $innodb_buffer_pool_size = (db_get_value_sql('SELECT @@innodb_buffer_pool_size') / 1024);
    $sort_buffer_size = number_format((db_get_value_sql('SELECT @@sort_buffer_size') / 1024), 2);
    $join_buffer_size = (db_get_value_sql('SELECT @@join_buffer_size') / 1024);
    $query_cache_type = db_get_value_sql('SELECT @@query_cache_type');
    $query_cache_size = (db_get_value_sql('SELECT @@query_cache_size') / 1048576);
    $query_cache_limit = (db_get_value_sql('SELECT @@query_cache_limit') / 1048576);
    $innodb_lock_wait_timeout = db_get_value_sql('SELECT @@innodb_lock_wait_timeout');
    $thread_cache_size = db_get_value_sql('SELECT @@thread_cache_size');
    $thread_stack = (db_get_value_sql('SELECT @@thread_stack') / 1024);
    $max_connections = db_get_value_sql('SELECT @@max_connections');
    $key_buffer_size = (db_get_value_sql('SELECT @@key_buffer_size') / 1024);
    $read_buffer_size = (db_get_value_sql('SELECT @@read_buffer_size') / 1024);
    $read_rnd_buffer_size = (db_get_value_sql('SELECT @@read_rnd_buffer_size') / 1024);
    $query_cache_min_res_unit = (db_get_value_sql('SELECT @@query_cache_min_res_unit') / 1024);
    $innodb_file_per_table = db_get_value_sql('SELECT @@innodb_file_per_table');
    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('MySQL Performance metrics').' '.ui_print_help_icon('performance_metrics_tab', true).'</th></tr>';

    render_row(status_values($innodb_log_file_size_min_rec_value, $innodb_log_file_size), 'InnoDB log file size ', 'InnoDB log file size ');
    render_row(status_values($innodb_log_buffer_size_min_rec_value, $innodb_log_buffer_size), 'InnoDB log buffer size ', 'InnoDB log buffer size ');
    render_row(status_values($innodb_flush_log_at_trx_commit_min_rec_value, $innodb_flush_log_at_trx_commit), 'InnoDB flush log at trx-commit ', 'InnoDB flush log at trx-commit ');
    render_row(status_values($max_allowed_packet_min_rec_value, $max_allowed_packet), 'Maximun allowed packet ', 'Maximun allowed packet ');
    render_row(status_values($innodb_buffer_pool_size_min_rec_value, $innodb_buffer_pool_size), 'InnoDB buffer pool size ', 'InnoDB buffer pool size ');
    render_row(status_values($sort_buffer_size_min_rec_value, $sort_buffer_size), 'Sort buffer size ', 'Sort buffer size ');
    render_row(status_values($join_buffer_size_min_rec_value, $join_buffer_size), 'Join buffer size ', 'Join buffer size ');
    render_row(status_values($query_cache_type_min_rec_value, $query_cache_type), 'Query cache type ', 'Query cache type ');
    render_row(status_values($query_cache_size_min_rec_value, $query_cache_size), 'Query cache size ', 'Query cache size ');
    render_row(status_values($query_cache_limit_min_rec_value, $query_cache_limit), 'Query cache limit ', 'Query cache limit ');
    render_row(status_values($innodb_lock_wait_timeout_max_rec_value, $innodb_lock_wait_timeout), 'InnoDB lock wait timeout ', 'InnoDB lock wait timeout ');
    render_row(status_values($thread_cache_size_max_rec_value, $thread_cache_size), 'Thread cache size ', 'Thread cache size ');
    render_row(status_values($thread_stack_min_rec_value, $thread_stack), 'Thread stack ', 'Thread stack ');
    render_row(status_values($max_connections_max_rec_value, $max_connections), 'Maximum connections ', 'Maximun connections ');
    render_row(status_values($key_buffer_size_min_rec_value, $key_buffer_size), 'Key buffer size ', 'Key buffer size ');
    render_row(status_values($read_buffer_size_min_rec_value, $read_buffer_size), 'Read buffer size ', 'Read buffer size ');
    render_row(status_values($read_rnd_buffer_size_min_rec_value, $read_rnd_buffer_size), 'Read rnd-buffer size ', 'Read rnd-buffer size ');
    render_row(status_values($query_cache_min_res_unit_min_rec_value, $query_cache_min_res_unit), 'Query cache min-res-unit ', 'Query cache min-res-unit ');
    render_row(status_values($innodb_file_per_table_min_rec_value, $innodb_file_per_table), 'InnoDB file per table ', 'InnoDB file per table ');
    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__('Tables fragmentation in the Pandora FMS database').'</th></tr>';



    render_row($tables_fragmentation_max_rec_value.'%', 'Tables fragmentation (maximum recommended value)');
    render_row(number_format($tables_fragmentation, 2).'%', 'Tables fragmentation (current value)');
    render_row(status_fragmentation_tables($tables_fragmentation_max_rec_value, $tables_fragmentation), 'Table fragmentation status');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Pandora FMS logs dates').'</th></tr>';

    render_row(number_format((get_logs_size($path_server_logs) / 1048576), 3).'M', 'Size server logs (current value)');
    render_row(get_status_logs($path_server_logs), 'Status server logs');
    render_row(number_format((get_logs_size($path_err_logs) / 1048576), 3).'M', 'Size error logs (current value)');
    render_row(get_status_logs($path_err_logs), 'Status error logs');
    render_row(number_format((get_logs_size($path_console_logs) / 1048576), 3).'M', 'Size console logs (current value)');
    render_row(get_status_logs($path_console_logs), 'Status console logs');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Pandora FMS Licence Information').'</th></tr>';

    render_row(html_print_textarea('keys[customer_key]', 10, 255, $settings->customer_key, 'style="height:40px; width:450px;"', true), 'Customer key');
    render_row($license['expiry_date'], $license['expiry_caption']);
    render_row($license['limit'].' agents', 'Platform Limit');
    render_row($license['count'].' agents', 'Current Platform Count');
    render_row($license['count_enabled'].' agents', 'Current Platform Count (enabled: items)');
    render_row($license['count_disabled'].' agents', 'Current Platform Count (disabled: items)');
    render_row($license['license_mode'], 'License Mode');
    render_row(status_license_params($license['nms']), 'Network Management System');
    render_row(status_license_params($license['dhpm']), 'Satellite');
    render_row($license['licensed_to'], 'Licensed to');
    render_row(license_capacity(), 'Status of agents capacity');
    render_row(percentage_modules_per_agent(), 'Status of average modules per agent');
    render_row(interval_average_of_network_modules(), 'Interval average of the network modules');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Status of the attachment folder').'</th></tr>';

    render_row($attachment_total_files, 'Total files in the attached folder');
    render_row(files_attachment_folder($attachment_total_files), 'Status of the attachment folder');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Information from the tagente_datos table').'</th></tr>';

    render_row($tagente_datos_size, 'Total data in tagente_datos table');
    render_row(status_tagente_datos($tagente_datos_size), 'Tangente_datos table status');
    render_row(execution_time(), 'Execution time degradation when executing a count');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Pandora FMS server threads').'</th></tr>';

    render_row($total_server_threads, 'Total server threads');
    render_row($percentage_threads_ram.'%', 'Percentage of threads used by the RAM');
    render_row($percentage_threads_cpu.'%', 'Percentage of threads used by the CPU');

    echo "<tr><th style='background-color:#b1b1b1;font-weight:bold;font-style:italic;border-radius:2px;' align=center colspan='2'>".__(' Graphs modules that represent the self-monitoring system').'</th></tr>';

    $server_name = db_get_value_sql('SELECT name FROM tserver WHERE master = 1');
    $agent_id = db_get_value_sql("SELECT id_agente FROM tagente WHERE nombre = '$server_name'");

    $id_modules = agents_get_modules($agent_id);

    $id_modules = [
        modules_get_agentmodule_id('Agents_Unknown', $agent_id),
        modules_get_agentmodule_id('Database&#x20;Maintenance', $agent_id),
        modules_get_agentmodule_id('FreeDisk_SpoolDir', $agent_id),
        modules_get_agentmodule_id('Free_RAM', $agent_id),
        modules_get_agentmodule_id('Queued_Modules', $agent_id),
        modules_get_agentmodule_id('Status', $agent_id),
        modules_get_agentmodule_id('System_Load_AVG', $agent_id),
        modules_get_agentmodule_id('Execution_time', $agent_id),
    ];

    foreach ($id_modules as $id_module) {
        $params = [
            'agent_module_id' => $id_module['id_agente_modulo'],
            'period'          => SECONDS_1MONTH,
            'date'            => time(),
            'height'          => '150',
        ];
        render_row(grafico_modulo_sparse($params), 'Graph of the '.$id_module['nombre'].' module.');
    }

    if ($console_mode == 0) {
        echo '</table>';
    }

    echo "<hr color='#b1b1b1' size=1 width=1000 align=left>";

    echo '<span>'.__(
        '(*) Please check your Pandora Server setup and make sure that the database maintenance daemon is running. It\' is very important to 
        keep the database up-to-date to get the best performance and results in Pandora'
    ).'</span><br><br><br>';
