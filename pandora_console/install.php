<?php
/**
 * Extension to manage a list of gateways and the node address where they should
 * point to.
 *
 * @category   Extensions
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <title>Pandora FMS - Installation Wizard</title>
        <meta http-equiv="expires" content="0">
        <meta http-equiv="content-type" content="text/html; charset=utf-8">
        <meta name="resource-type" content="document">
        <meta name="distribution" content="global">
        <meta name="author" content="Pandora FMS Development Team">
        <meta name="copyright" content="This is GPL software. Created by Sancho Lerena and many others">
        <meta name="keywords" content="pandora, fms, monitoring, network, system, GPL, software">
        <meta name="robots" content="index, follow">
        <link rel="icon" href="images/pandora.ico" type="image/ico">
        <script type="text/javascript" src="include/javascript/jquery-3.3.1.min.js"></script>
        <script type="text/javascript" src="include/javascript/select2.min.js"></script>
        <link rel="stylesheet" href="include/styles/select2.min.css" type="text/css">
        <link rel="stylesheet" href="include/styles/install.css" type="text/css">
    </head>
    <script type="text/javascript">
        options_text = new Array('An existing Database','A new Database');
        options_values = new Array('db_exist','db_new');

        var userHasConfirmed = false;

        function ChangeDBDrop(causer) {
            if (causer.value != 'db_exist') {
                window.document.step2_form.drop.checked = 0;
                window.document.step2_form.drop.disabled = 1;
            }
            else {
                window.document.step2_form.drop.disabled = 0;
            }
        }
        function ChangeDBAction(causer) {
            var i = 0;
            if (causer.value == 'oracle') {
                window.document.step2_form.db_action.length = 1;
            }
            else {
                window.document.step2_form.db_action.length = 2;
            }
            while (i < window.document.step2_form.db_action.length) {
                window.document.step2_form.db_action.options[i].value = options_values[i];
                window.document.step2_form.db_action.options[i].text = options_text[i];
                i++;
            }
            window.document.step2_form.db_action.options[window.document.step2_form.db_action.length-1].selected=1;
            ChangeDBDrop(window.document.step2_form.db_action);
        }
        function CheckDBhost(value){
            if (( value != "localhost") && ( value != "127.0.0.1")) {
                document.getElementById('tr_dbgrant').style["display"] = "table-row";
            }
            else {
                document.getElementById('tr_dbgrant').style["display"] = "none";
            }
        }
        function popupShow(){
            document.getElementsByTagName('body')[0].style["margin"] = "0";
            document.getElementById('add-lightbox').style["visibility"] = "visible";
            document.getElementById('open_popup').style["display"] = "block";
            document.getElementById('open_popup').style["visibility"] = "visible";
        }
        function popupClose(){
            document.getElementById('add-lightbox').style["visibility"] = "hidden";
            document.getElementById('open_popup').style["display"] = "none";
            document.getElementById('open_popup').style["visibility"] = "hidden";
        }
        function handleConfirmClick (event) {
            userHasConfirmed = true;
            var step3_form = document.getElementsByName('step2_form')[0];
            step3_form.submit();
        }
        function handleStep3FormSubmit (event) {
            var dbOverride = document.getElementById("drop").checked;
            if (dbOverride && !userHasConfirmed) {
                event.preventDefault();
                popupShow();
                return false;
            }
        }
    </script>
    <body>
        <div id='add-lightbox' onclick='popupClose();' class='popup-lightbox'></div>
        <div id='open_popup' class='popup' style='visibility:hidden;display: block;'>
            <div class='popup-title'>
                <span id='title_popup'>Warning</span>
                <a href='#' onclick='popupClose();'><img src='./images/icono_cerrar.png' alt='close' title='Close' style='float:right;'/></a>
            </div>
            <div class='popup-inner' style='padding: 20px 40px;'>
            <?php
            echo '<p><strong>Attention</strong>, you are going to <strong>overwrite the data</strong> of your current installation.</p>
                  <p>This means that if you do not have a backup <strong>you will irremissibly LOSE ALL THE STORED DATA</strong>, the configuration and everything relevant to your installation.</p><p><strong>Are you sure of what you are going to do?</strong></p>';
                echo "<div style='text-align:right;';>";
                echo "<button type='button' class='btn_primary outline' onclick='javascript:handleConfirmClick();'><span class='btn_install_next_text'>Yes, I'm sure I want to delete everything</span></button>";
                echo "<button type='button' class='btn_primary' onclick='javascript:popupClose();'><span class='btn_install_next_text'>Cancel</span></button>";
                echo '</div>';
            ?>
            </div>
        </div>
        <div style='padding-bottom: 50px'>
            <?php
            $version = '7.0NG.776';
            $build = '240314';
            $banner = "v$version Build $build";
            error_reporting(0);

            // ---------------
            // Main page code
            // ---------------
            if (! isset($_GET['step'])) {
                install_step1();
            } else {
                $step = $_GET['step'];
                switch ($step) {
                    case 11: install_step1_licence();
                    break;

                    case 2: install_step2();
                    break;

                    case 3: install_step3();
                    break;

                    case 4: install_step4();
                    break;

                    case 5: install_step5();
                    break;
                }
            }
            ?>
        </div>
    </body>
</html>

<?php
/**
 * Check the php extension and print a
 * new row in the table with the result
 *
 * @param string $ext   Extension.
 * @param string $label Label extension.
 *
 * @return integer status
 */
function check_extension($ext, $label)
{
    echo '<tr><td>';
    echo "<span class='arr'> $label </span>";
    echo '</td><td>';
    if (!extension_loaded($ext)) {
        echo "<span class='incomplete'>incomplete</span>";
        return 1;
    } else {
        echo "<span class='checked'>checked</span>";
        return 0;
    }

    echo '</td></tr>';
}

/**
 * Check if file exist and print a
 * new row in the table with the result
 *
 * @param string $file  File.
 * @param string $label Label file.
 *
 * @return integer status
 */
function check_exists($file, $label)
{
    echo '<tr><td>';
    echo "<span class='arr'> $label </span>";
    echo '</td><td>';
    if (!file_exists($file)) {
        echo " <span class='incomplete'>incomplete</span>";
        return 1;
    } else {
        echo " <span class='checked'>checked</span>";
        return 0;
    }

    echo '</td></tr>';
}


/**
 * Check variable ok and return row
 * with 'checked' if is 1 or 'incomplete' if is 0
 *
 * @param integer $ok    Status.
 * @param string  $label Label to show.
 *
 * @return integer status
 */
function check_generic($ok, $label)
{
    echo '<tr><td>';
    if ($ok == 0) {
        echo "<span class='arr'> $label </span>";
        echo '<td>';
        echo " <span class='incomplete'>incomplete</span>";
        echo '</td>';
        echo '</td></tr>';
        return 1;
    } else {
        echo "<span class='arr'> $label </span>";
        echo '<td>';
        echo " <span class='checked'>checked</span>";
        echo '</td>';
        echo '</td></tr>';
        return 0;
    }
}


/**
 * Check if path is writable and print a
 * new row in the table with the result.
 *
 * @param string $fullpath Path folder or file.
 * @param string $label    Label to show.
 *
 * @return integer status
 */
function check_writable($fullpath, $label)
{
    echo '<tr><td>';
    if (file_exists($fullpath)) {
        if (is_writable($fullpath)) {
            echo "<span class='arr'> $label </span>";
            echo '</td>';
            echo '<td>';
            echo "<span class='checked'>checked</span>";
            echo '</td>';
            echo '</tr>';
            return 0;
        } else {
            echo "<span class='arr'> $label </span>";
            echo '</td>';
            echo '<td>';
            echo "<span class='incomplete'>incomplete</span>";
            echo '</td>';
            echo '</tr>';
            return 1;
        }
    } else {
        echo "<span class='arr'> $label </span>";
        echo '<td>';
        echo "<span class='incomplete'>incomplete</span>";
        echo '</td>';
        echo '</td></tr>';
        return 1;
    }
}


/**
 * Check if $var is equal to $value and
 * print result in a row.
 *
 * @param string  $var   Variable.
 * @param string  $value Value to check.
 * @param string  $label Label to show.
 * @param integer $mode  Mode.
 *
 * @return integer status
 */
function check_variable($var, $value, $label, $mode)
{
    echo '<tr><td>';
    echo "<span class='arr'> $label </span>";
    echo '</td><td>';
    if ($mode == 1) {
        if ($var >= $value) {
            echo "<span class='checked'>checked</span>";
            return 0;
        } else {
            echo "<span class='incomplete'>incomplete</span>";
            return 1;
        }
    } else if ($var == $value) {
        echo "<span class='checked'>checked</span>";
        return 0;
    } else {
        echo "<span class='incomplete'>incomplete</span>";
        return 1;
    }

    echo '</td></tr>';
}


function parse_mysql_dump($url)
{
    if (file_exists($url)) {
        $file_content = file($url);
        $query = '';
        foreach ($file_content as $sql_line) {
            if (trim($sql_line) != '' && strpos($sql_line, '-- ') === false) {
                $query .= $sql_line;
                if (preg_match("/;[\040]*\$/", $sql_line)) {
                    if (!$result = mysql_query($query)) {
                        echo mysql_error();
                        // Uncomment for debug
                        echo "<i><br>$query<br></i>";
                        return 0;
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


/**
 * Parse sql to script dump, execute it
 * and return if exist error.
 *
 * @param object $connection Connection sql.
 * @param string $url        Path file sql script.
 *
 * @return integer status
 */
function parse_mysqli_dump($connection, $url)
{
    if (file_exists($url)) {
        $file_content = file($url);
        $query = '';
        foreach ($file_content as $sql_line) {
            if (trim($sql_line) != '' && strpos($sql_line, '-- ') === false) {
                $query .= $sql_line;
                if (preg_match("/;[\040]*\$/", $sql_line)) {
                    if (!$result = mysqli_query($connection, $query)) {
                        if (mysqli_error($connection)) {
                            return mysqli_error($connection).'<i><br>'.$query.'<br></i>';
                        }

                        return 0;
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


/**
 * Generate a random password
 *
 * Admits a huge mount of ASCII chars.
 *
 * @param integer $size Size of the password returned.
 *
 * @return string $output
 */
function random_name(int $size)
{
    $output = '';

    // Range pair of ASCII position for allow A-Z, a-z, 0-9 and special chars.
    $rangeSeed = [
        '48:57',
        '65:90',
        '97:122',
        '40:47',
    ];

    // Size of the password must be over range seed count.
    $size = ($size >= count($rangeSeed)) ? $size : count($rangeSeed);

    $auxIndex = 0;
    for ($i = 0; $i < $size; $i++) {
        $tmpSeedValues = explode(':', $rangeSeed[$auxIndex]);
        $output = $output.chr(rand($tmpSeedValues[1], $tmpSeedValues[0]));
        $auxIndex++;
        if ($auxIndex >= 4) {
            $auxIndex = 0;
        }
    }

    // Remix the string for strong the password.
    return str_shuffle($output);
}


/**
 * Print the header installation
 *
 * @param integer $step Number of step.
 *
 * @return string Html output.
 */
function print_logo_status($step)
{
    global $banner;

    $header = '
        <div class="header">
            <h3 class="title-pandora">Pandora FMS OpenSource Installer 
                <span class="build-banner">'.$banner.'</span>
            </h3>
            <div class="steps">
                <span class="step '.(($step === 11 || $step === 1) ? 'active' : '').'">1</span>
                <hr class="step-separator"/>
                <span class="step '.(($step === 2) ? 'active' : '').'">2</span>
                <hr class="step-separator"/>
                <span class="step '.(($step === 3) ? 'active' : '').'">3</span>
                <hr class="step-separator"/>
                <span class="step '.(($step === 4) ? 'active' : '').'">4</span>
                <hr class="step-separator"/>
                <span class="step '.(($step === 5) ? 'active' : '').'">5</span>
                <hr class="step-separator"/>
                <span class="step '.(($step === 6) ? 'active' : '').'">6</span>

            </div>
        </div>
    ';

    return $header;
}


/**
 * This function adjusts path settings in pandora db for FreeBSD.
 * All packages and configuration files except operating system's base files
 * are installed under /usr/local in FreeBSD. So, path settings in pandora db
 * for some programs should be changed from the Linux default.
 *
 * @param string $engine     Type of engine.
 * @param object $connection Connection database.
 *
 * @return integer Status.
 */
function adjust_paths_for_freebsd($engine, $connection=false)
{
    $adjust_sql = [
        "update trecon_script set script = REPLACE(script,'/usr/share','/usr/local/share');",
        "update tconfig set value = REPLACE(value,'/usr/bin','/usr/local/bin') where token='netflow_daemon' OR token='netflow_nfdump' OR token='netflow_nfexpire';",
        "update talert_commands set command = REPLACE(command,'/usr/bin','/usr/local/bin');",
        "update talert_commands set command = REPLACE(command,'/usr/share', '/usr/local/share');",
        "update tplugin set execute = REPLACE(execute,'/usr/share','/usr/local/share');",
        "update tevent_response set target = REPLACE(target,'/usr/share','/usr/local/share');",
        "insert into tconfig (token, value) VALUES ('graphviz_bin_dir', '/usr/local/bin');",
    ];

    for ($i = 0; $i < count($adjust_sql); $i++) {
        switch ($engine) {
            case 'mysql':
                $result = mysql_query($adjust_sql[$i]);
            break;

            case 'mysqli':
                $result = mysqli_query($connection, $adjust_sql[$i]);
            break;

            case 'oracle':
                // Delete the last semicolon from current query
                $query = substr($adjust_sql[$i], 0, (strlen($adjust_sql[$i]) - 1));
                $sql = oci_parse($connection, $query);
                $result = oci_execute($sql);
            break;

            case 'pgsql':
                pg_send_query($connection, $adjust_sql[$i]);
                $result = pg_get_result($connection);
            break;
        }

        if (!$result) {
            return 0;
        }
    }

    return 1;
}


/**
 * Print all step 1
 *
 * @return void
 */
function install_step1()
{
    echo "
	<div id='install_container'>
	<div id='wizard'>
    ".print_logo_status(1, 6)."
        <div class='row'>
        <div class='col-md-6'>
		<div id='install_box'>
			<h2 class='title'>Welcome to Pandora FMS installation Wizard</h2>
			<p class='text'>This wizard helps you to quick install Pandora FMS console and main database in your system.
			In four steps, this installer will check all dependencies and will create your configuration, ready to use.</p>
			<p class='text'>For more information, please refer to <a class='link' href='https://pandorafms.com/en/documentation/' target='_blank'>documentation →</a></p>
		";
    if (file_exists('include/config.php')) {
        echo "<div class='warn'> You already have a config.php file. 
			Configuration and database would be overwritten if you continued.</div>";
    }

        echo '<br>';
        echo '<table class="check-table">';
        $writable = check_writable('include', './include is writable');
    if (file_exists('include/config.php')) {
        $writable += check_writable('include/config.php', 'include/config.php is writable');
    }

        echo '</table>';

        echo "<div class='warn'>This installer will <b>overwrite and destroy</b> 
		your existing Pandora FMS configuration and <b>Database</b>. Before continue, 
		please <b>be sure that you have no valuable Pandora FMS data in your Database</b>.<br>
		</div>";

    if ($writable !== 0) {
        echo "<div class='err'>You need to setup permissions to be able to write in ./include directory</div>";
    }

        echo '</div>';

        echo "<div style='clear:both;'></div>";
        echo "
        </div>
        <div class='col-md-6 hide-phone'>
        <div class='content-animation'>
        <div class='popuphero'>
            <div class='popupgear1'><img src='images/Pandora-FMS-installer-gear.png'></div>
            <div class='popupgear2'><img src='images/Pandora-FMS-installer-gear.png'></div>
            <div class='popuplaptop'><img src='images/Pandora-FMS-installer.png'></div>
        </div>
        </div>
        </div>
        </div>
        </div>
        <div id='foot_install'>
            <div class='content-footer'>
            <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
            </span>";
    if ($writable === 0) {
        echo "<a id='step11' href='install.php?step=11'><button type='submit' class='btn_primary'>Start installation</button></a>";
    }

        echo '</div></div></div>';
}


/**
 * Print license content
 *
 * @return void
 */
function install_step1_licence()
{
    echo "
	<div id='install_container'>
	<div id='wizard'>
	".print_logo_status(2, 6)."
		<div id='install_box'>
			<h2 class='subtitle'>GPL2 Licence terms agreement</h2>
			<p class='text'>Pandora FMS is an OpenSource software project licensed under the GPL2 licence. Pandora FMS includes, as well, another software also licensed under LGPL and BSD licenses. Before continue, <i>you must accept the licence terms.</i>.
			<p class='text'>For more information, please refer to our website at https://pandorafms.com/community/ and contact us if you have any kind of question about the usage of Pandora FMS</p>
            <p>If you dont accept the licence terms, please, close your browser and delete Pandora FMS files.</p>
		";

    if (!file_exists('COPYING')) {
        echo "<div class='warn'><b>Licence file 'COPYING' is not present in your distribution. This means you have some 'partial' Pandora FMS distribution. We cannot continue without accepting the licence file.</b>";
        echo '</div>';
    } else {
        echo "<textarea name='gpl2' cols=52 rows=15 style='width: 100%;'>";
        echo file_get_contents('COPYING');
        echo '</textarea>';
        echo '<p>';
    }

    echo '</div>';

    echo "</div>
	    <div id='foot_install'>
            <div class='content-footer'>
            <a href='install.php'><button class='btn_primary outline'>Previous step</button></a>
            <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
            </span>";
    if (file_exists('COPYING')) {
        echo "<a href='install.php?step=2'><button id='btn_accept' class='btn_primary'>Yes, I accept licence terms</button></a>";
    }

    echo '</div></div></div>';
}


/**
 * Print all step 2
 *
 * @return void
 */
function install_step2()
{
    echo "
	<div id='install_container'>
	<div id='wizard'>
	".print_logo_status(3, 6)."
		<div id='install_box'>";
        echo '<h2 class="subtitle">Checking software dependencies</h2>';
            echo '
            <div class="row reverse">
            <div class="col-md-6">
            <table class="check-table">';
            $res = 0;
            $res += check_variable(phpversion(), '7.0', 'PHP version >= 7.0', 1);
            $res += check_extension('gd', 'PHP GD extension');
            $res += check_extension('ldap', 'PHP LDAP extension');
            $res += check_extension('snmp', 'PHP SNMP extension');
            $res += check_extension('session', 'PHP session extension');
            $res += check_extension('gettext', 'PHP gettext extension');
            $res += check_extension('mbstring', 'PHP Multibyte String');
            $res += check_extension('zip', 'PHP Zip');
            $res += check_extension('zlib', 'PHP Zlib extension');
            $res += check_extension('json', 'PHP json extension');
            $res += check_extension('curl', 'CURL (Client URL Library)');
            $res += check_extension('filter', 'PHP filter extension');
            $res += check_extension('calendar', 'PHP calendar extension');
    if (PHP_OS == 'FreeBSD') {
        $res += check_exists('/usr/local/bin/twopi', 'Graphviz Binary');
    } else if (PHP_OS == 'NetBSD') {
        $res += check_exists('/usr/pkg/bin/twopi', 'Graphviz Binary');
    } else if (substr(PHP_OS, 0, 3) == 'WIN') {
        $res += check_exists("..\\..\\..\\Graphviz\\bin\\twopi.exe", 'Graphviz Binary');
    } else {
        $res += check_exists('/usr/bin/twopi', 'Graphviz Binary');
    }

            echo '<tr><td>';
            echo "<span style='display: block; margin-top: 2px; font-weight: bolder; color: white; font-size: 22px;'>DB Engines</span>";
            echo '</td><td>';
            echo '</td></tr>';
            check_extension('mysqli', 'PHP MySQL(mysqli) extension');
            echo '</table></div>';
    if ($res > 0) {
        echo "<div class='col-md-6'>
			  <div class='err'>You have some incomplete
                    dependencies. Please correct them or this installer
                    will not be able to finish your installation.
			   </div>
			   <div class='err'>
                    Remember, if you install any PHP module to comply
                    with these dependences, you <b>need to restart</b>
                    your HTTP/Apache server after it to use the new
                    modules.
			    </div>
                </div>";
    }

        echo '</div>';
        echo "</div></div>
            <div id='foot_install'>
                <div class='content-footer'>
                <a href='install.php?step=11'><button class='btn_primary outline'>Previous step</button></a>
                <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
                </span>";
    if ($res > 0) {
        echo "<span class='text' style='margin-right: 10px'>Ignore it.</span><a id='step3' href='install.php?step=3'><button class='btn_primary'>Force install</button></a>";
    } else {
        echo "<a id='step3' href='install.php?step=3'><button class='btn_primary'>Next Step</button></a>";
    }

        echo '</div></div>';
}


/**
 * Print all step 3
 *
 * @return void
 */
function install_step3()
{
    $options = [];
    if (extension_loaded('mysql')) {
        $options['mysql'] = 'MySQL';
    }

    if (extension_loaded('mysqli')) {
        $options['mysqli'] = 'MySQL(mysqli)';
    }

    $error = false;
    if (empty($options)) {
        $error = true;
    }

    echo "
	<div id='install_container'>
	<div id='wizard'>
	".print_logo_status(4, 6)."
		<div id='install_box'>
        <div class='row'>
        <div class='col-md-6'>
			<h2 class='subtitle'>Environment and database setup</h2>
			<p class='text'>
                This wizard will create your Pandora FMS database, 
                and populate it with all the data needed to run for the first time.
			</p>
			<p class='text'>
			    You need a privileged user to create database schema, this is usually <b>root</b> user.
			    Information about <b>root</b> user will not be used or stored anymore.	
			</p>
			<p class='text'>
			    You can also deploy the scheme into an existing Database. 
			    In this case you need a privileged Database user and password of that instance. 
			</p>
			<p class='text'>
			    Now, please, complete all details to configure your database and environment setup.
			</p>
			<div class='warn'>
			    This installer will <b>overwrite and destroy</b> your existing 
			    Pandora FMS configuration and <b>Database</b>. Before continue, 
			    please <b>be sure that you have no valuable Pandora FMS data in your Database.</b>
			<br><br>
			</div>";
    if ($error) {
        echo "<div class='warn'>
			    You haven't a any DB engine with PHP. Please check the previous step to DB engine dependencies.
			</div>";
    }

    if (extension_loaded('oci8')) {
        echo "<div class='warn'>For Oracle installation an existing Database with a privileged user is needed.</div>";
    }

    echo '</div>';
    echo '<div class="col-md-6">';
    if (!$error) {
        echo "<form method='post' name='step2_form' action='install.php?step=4'>";
    }

    echo "<table class='table-config-database' cellpadding=6 width=100% border=0 style='text-align: left;'>";

    if (!$error) {
        echo '<tr><td>';
        echo '<p class="input-label">DB Engine</p>';

        echo '<select id="engine" name="engine"
                style="width:
                100%"
                data-select2-id="engine"
                tabindex="-1"
                class="select2-hidden-accessible"
                aria-hidden="true">';

        foreach ($options as $key => $value) {
            echo '<option value="'.$key.'">'.$value.'</option>';
        }

        echo '</select>';

        echo '<script type="text/javascript">$("#engine").select2({closeOnSelect: true});</script>';

        echo '<td>';
        echo '<p class="input-label">Installation in </p>';

        echo '<select id="db_action"
                      name="db_action"
                      style="width:
                      100%"
                      data-select2-id="db_action"
                      tabindex="-1"
                      class="select2-hidden-accessible"
                      aria-hidden="true">
            <option value="db_new">A new Database</option>
            <option value="db_exist">An existing Database</option>
            </select>';

        echo '<script type="text/javascript">$("#db_action").select2({closeOnSelect: true});</script>';
    }

    echo "<tr>
            <td>
                <p class='input-label'>DB User with privileges</p>
                <input class='login' type='text' name='user' value='root' size=20>
            </td>
			<td>
                <p class='input-label'>DB Password for this user</p>
				<input class='login' type='password' name='pass' value='' size=20>
			</td>
         </tr>
	     <tr>
            <td>
                <p class='input-label'>DB Hostname</p>
				<input class='login' type='text' name='host' value='localhost' onkeyup='CheckDBhost(this.value);'size=20>
			</td>
            <td>
                <p class='input-label'>DB Name (pandora by default)</p>
				<input class='login' type='text' name='dbname' value='pandora' size=20>
			</td>
         </tr>";

    // the field dbgrant is only shown when the DB host is different from 127.0.0.1 or localhost
    echo "<tr id='tr_dbgrant' style='display: none;'>
            <td colspan=\"2\">
                <p class='input-label'>DB Host Access<img style='cursor:help;' src='/pandora_console/images/tip.png' title='Ignored if DB Hostname is localhost or 127.0.0.1'/></p>
                <input class='login' type='text' name='dbgrant' value='".$_SERVER['SERVER_ADDR']."'>
            </td>
           </tr>";

    echo "<tr>
            <td colspan='2'>
                <p class='input-label'>Full path to HTTP publication directory</p>
                <p class='example-message'>For example /var/www/pandora_console/</p>
                <input class='login' type='text' name='path'  value='".dirname(__FILE__)."'>
            </td>
          </tr>";
    echo "
        <tr>
            <td colspan='2'>
                <p class='input-label'>URL path to Pandora FMS Console</p>
                <p class='example-message'>For example '/pandora_console'</p>
				<input class='login' type='text' name='url' value='".dirname($_SERVER['SCRIPT_NAME'])."'>
            </td>
        </tr>";

    echo "<tr>
            <td colspan='2' class='inline'>
                <label class='switch'>
                    <input type='checkbox' name='drop' id='drop' value=1>
                    <span class='slider round'></span>
                </label>
                <p class='input-label'>Drop Database if exists</p>
            </td>
         </tr>";

    echo '</table>';

    echo '</div>';

    echo '</div></div>';
    echo '</div>';
    echo "<div id='foot_install'>
            <div class='content-footer'>
            <a href='install.php?step=2' class='btn_primary outline'>Previous step</a>
            <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
            </span>";
    if (!$error) {
        echo "<button class='btn_primary' type='submit' id='step4'>Next Step</button>";
        echo '</form>';
        ?>
        <script type="text/javascript">
            var step3_form = document.getElementsByName('step2_form')[0];
            step3_form.addEventListener("submit", handleStep3FormSubmit);
        </script>
        <?php
    }

    echo '</div></div>';
}


/**
 * Print all step 4
 *
 * @return void
 */
function install_step4()
{
    $pandora_config = 'include/config.php';

    if ((! isset($_POST['user'])) || (! isset($_POST['dbname'])) || (! isset($_POST['host']))
        || (! isset($_POST['pass'])) || (!isset($_POST['engine'])) || (! isset($_POST['db_action']))
    ) {
        $dbpassword = '';
        $dbuser = '';
        $dbhost = '';
        $dbname = '';
        $engine = '';
        $dbaction = '';
        $dbgrant = '';
    } else {
        $engine = $_POST['engine'];
        $dbpassword = $_POST['pass'];
        $dbuser = $_POST['user'];
        $dbhost = $_POST['host'];
        $dbaction = $_POST['db_action'];
        if (isset($_POST['dbgrant']) && $_POST['dbgrant'] != '') {
            $dbgrant = $_POST['dbgrant'];
        } else {
            $dbgrant = $_SERVER['SERVER_ADDR'];
        }

        if (isset($_POST['drop'])) {
            $dbdrop = $_POST['drop'];
        } else {
            $dbdrop = 0;
        }

        $dbname = $_POST['dbname'];
        if (isset($_POST['url'])) {
            $url = $_POST['url'];
        } else {
            $url = 'http://localhost';
        }

        if (isset($_POST['path'])) {
            $path = $_POST['path'];
            $path = str_replace('\\', '/', $path);
            // Windows compatibility
        } else {
            $path = '/var/www';
        }
    }

    $everything_ok = 0;
    $step1 = 0;
    $step2 = 0;
    $step3 = 0;
    $step4 = 0;
    $step5 = 0;
    $step6 = 0;
    $step7 = 0;
    $errors = [];
    echo "
	<div id='install_container'>
	<div id='wizard'>
	".print_logo_status(5, 6)."
		<div id='install_box'>
            <div class='row reverse'>
            <div class='col-md-6'>
			<table class='check-table'>";
    switch ($engine) {
        case 'mysql':
            if (! mysql_connect($dbhost, $dbuser, $dbpassword)) {
                check_generic(0, 'Connection with Database');
            } else {
                check_generic(1, 'Connection with Database');

                // Drop database if needed and don't want to install over an existing DB
                if ($dbdrop == 1) {
                    mysql_query("DROP DATABASE IF EXISTS `$dbname`");
                }

                // Create schema
                if ($dbaction == 'db_new' || $dbdrop == 1) {
                    $step1 = mysql_query("CREATE DATABASE `$dbname`");
                    check_generic($step1, "Creating database '$dbname'");
                } else {
                    $step1 = 1;
                }

                if ($step1 == 1) {
                    $step2 = mysql_select_db($dbname);
                    check_generic($step2, "Opening database '$dbname'");

                    $step3 = parse_mysql_dump('pandoradb.sql');

                    if ($step3 !== 0 && $step3 !== 1) {
                        $errors[] = $step3;
                        $step3 = 0;
                    }

                    check_generic($step3, 'Creating schema');
                    $step4 = parse_mysql_dump('pandoradb_data.sql');

                    if ($step4 !== 0 && $step4 !== 1) {
                        $errors[] = $step4;
                        $step4 = 0;
                    }

                    check_generic($step4, 'Populating database');
                    if (PHP_OS == 'FreeBSD') {
                        $step_freebsd = adjust_paths_for_freebsd($engine);
                        check_generic($step_freebsd, 'Adjusting paths in database for FreeBSD');
                    }

                    $random_password = random_name(8);
                    $host = $dbhost;
                    // set default granted origin to the origin of the queries
                    if (($dbhost != 'localhost') && ($dbhost != '127.0.0.1')) {
                        $host = $dbgrant;
                        // if the granted origin is different from local machine, set the valid origin
                    }

                    $step5 = mysql_query(
                        "GRANT ALL PRIVILEGES ON `$dbname`.* to pandora@$host 
								IDENTIFIED BY '".$random_password."'"
                    );
                    mysql_query('FLUSH PRIVILEGES');
                    check_generic($step5, "Established privileges for user pandora. A new random password has been generated: <b>$random_password</b><p>Please write it down, you will need to setup your Pandora FMS server, editing the </i>/etc/pandora/pandora_server.conf</i> file</p>");

                    $step6 = is_writable('include');
                    check_generic($step6, "Write permissions to save config file in './include'");

                    $cfgin = fopen('include/config.inc.php', 'r');
                    $cfgout = fopen($pandora_config, 'w');
                    $config_contents = fread($cfgin, filesize('include/config.inc.php'));
                    $dbtype = 'mysql';
                    $config_new = '<?php
							// Begin of automatic config file
							$config["dbtype"] = "'.$dbtype.'"; //DB type (mysql, postgresql...in future others)
							$config["dbname"]="'.$dbname.'";			// MySQL DataBase name
							$config["dbuser"]="pandora";			// DB User
							$config["dbpass"]="'.$random_password.'";	// DB Password
							$config["dbhost"]="'.$dbhost.'";			// DB Host
                            $config["homedir"]="'.$path.'";		// Config homedir
                            // ----------Rebranding--------------------
                            // Uncomment this lines and add your customs text and paths.
                            // $config["custom_logo_login_alt"] ="login_logo.png";
                            // $config["custom_splash_login_alt"] = "splash_image_default.png";
                            // $config["custom_title1_login_alt"] = "WELCOME TO Pandora FMS";
                            // $config["custom_title2_login_alt"] = "NEXT GENERATION";
                            // $config["rb_product_name_alt"] = "Pandora FMS";
                            // $config["custom_docs_url_alt"] = "http://wiki.pandorafms.com/";
                            // $config["custom_support_url_alt"] = "https://support.pandorafms.com";


                        
							/*
							----------Attention--------------------
							Please note that in certain installations:
								- reverse proxy.
								- web server in other ports.
								- https
							
							This variable might be dynamically altered.
							
							But it is save as backup in the
							$config["homeurl_static"]
							for expecial needs.
							----------Attention--------------------
							*/
							$config["homeurl"]="'.$url.'";			// Base URL
							$config["homeurl_static"]="'.$url.'";			// Don\'t  delete
							// End of automatic config file
							?>';
                    $step7 = fputs($cfgout, $config_new);
                    $step7 = ($step7 + fputs($cfgout, $config_contents));
                    if ($step7 > 0) {
                        $step7 = 1;
                    }

                    fclose($cfgin);
                    fclose($cfgout);
                    chmod($pandora_config, 0600);
                    check_generic($step7, "Created new config file at '".$pandora_config."'");
                }
            }

            if (($step7 + $step6 + $step5 + $step4 + $step3 + $step2 + $step1) == 7) {
                $everything_ok = 1;
            }
        break;

        case 'mysqli':
            $connection = mysqli_connect($dbhost, $dbuser, $dbpassword);
            if (mysqli_connect_error() > 0) {
                check_generic(0, 'Connection with Database');
            } else {
                check_generic(1, 'Connection with Database');

                // Drop database if needed and don't want to install over an existing DB
                if ($dbdrop == 1) {
                    mysqli_query($connection, "DROP DATABASE IF EXISTS `$dbname`");
                }

                // Create schema
                if ($dbaction == 'db_new' || $dbdrop == 1) {
                    $step1 = mysqli_query($connection, "CREATE DATABASE `$dbname`");
                    check_generic($step1, "Creating database '$dbname'");
                } else {
                    $step1 = 1;
                }

                if ($step1 == 1) {
                    $step2 = mysqli_select_db($connection, $dbname);
                    check_generic($step2, "Opening database '$dbname'");

                    $step3 = parse_mysqli_dump($connection, 'pandoradb.sql');
                    if ($step3 !== 0 && $step3 !== 1) {
                        $errors[] = $step3;
                        $step3 = 0;
                    }

                    check_generic($step3, 'Creating schema');

                    $step4 = parse_mysqli_dump($connection, 'pandoradb_data.sql');

                    if ($step4 !== 0 && $step4 !== 1) {
                        $errors[] = $step4;
                        $step4 = 0;
                    }

                    check_generic($step4, 'Populating database');
                    if (PHP_OS == 'FreeBSD') {
                        $step_freebsd = adjust_paths_for_freebsd($engine, $connection);
                        check_generic($step_freebsd, 'Adjusting paths in database for FreeBSD');
                    }

                    $random_password = random_name(8);
                    $host = $dbhost;
                    // set default granted origin to the origin of the queries
                    if (($dbhost != 'localhost') && ($dbhost != '127.0.0.1')) {
                        $host = $dbgrant;
                        // if the granted origin is different from local machine, set the valid origin
                    }

                    $step5 = mysqli_query(
                        $connection,
                        "CREATE USER IF NOT EXISTS pandora@$host"
                    );

                    mysqli_query(
                        $connection,
                        "SET PASSWORD FOR 'pandora'@'".$host."' = '".$random_password."'"
                    );

                    $step5 |= mysqli_query(
                        $connection,
                        "GRANT ALL PRIVILEGES ON `$dbname`.* to pandora@$host"
                    );
                    mysqli_query($connection, 'FLUSH PRIVILEGES');
                    check_generic($step5, "Established privileges for user pandora. A new random password has been generated: <b>$random_password</b><p>Please write it down, you will need to setup your Pandora FMS server, editing the </i>/etc/pandora/pandora_server.conf</i> file</p>");

                    $step6 = is_writable('include');
                    check_generic($step6, "Write permissions to save config file in './include'");

                    $cfgin = fopen('include/config.inc.php', 'r');
                    $cfgout = fopen($pandora_config, 'w');
                    $config_contents = fread($cfgin, filesize('include/config.inc.php'));
                    $dbtype = 'mysql';
                    $config_new = '<?php
							// Begin of automatic config file
							$config["dbtype"] = "'.$dbtype.'"; //DB type (mysql, postgresql...in future others)
							$config["mysqli"] = true;
							$config["dbname"]="'.$dbname.'";			// MySQL DataBase name
							$config["dbuser"]="pandora";			// DB User
							$config["dbpass"]="'.$random_password.'";	// DB Password
							$config["dbhost"]="'.$dbhost.'";			// DB Host
                            $config["homedir"]="'.$path.'";		// Config homedir
                            // ----------Rebranding--------------------
                            // Uncomment this lines and add your customs text and paths.
                            // $config["custom_logo_login_alt"] ="login_logo.png";
                            // $config["custom_splash_login_alt"] = "splash_image_default.png";
                            // $config["custom_title1_login_alt"] = "WELCOME TO Pandora FMS";
                            // $config["custom_title2_login_alt"] = "NEXT GENERATION";
                            // $config["rb_product_name_alt"] = "Pandora FMS";
                            // $config["custom_docs_url_alt"] = "http://wiki.pandorafms.com/";
                            // $config["custom_support_url_alt"] = "https://support.pandorafms.com";

							/*
							----------Attention--------------------
							Please note that in certain installations:
								- reverse proxy.
								- web server in other ports.
								- https
							
							This variable might be dynamically altered.
							
							But it is save as backup in the
							$config["homeurl_static"]
							for expecial needs.
							----------Attention--------------------
							*/
							$config["homeurl"]="'.$url.'";			// Base URL
							$config["homeurl_static"]="'.$url.'";			// Don\'t  delete
							// End of automatic config file
							?>';
                    $step7 = fputs($cfgout, $config_new);
                    $step7 = ($step7 + fputs($cfgout, $config_contents));
                    if ($step7 > 0) {
                        $step7 = 1;
                    }

                    fclose($cfgin);
                    fclose($cfgout);
                    chmod($pandora_config, 0600);
                    check_generic($step7, "Created new config file at '".$pandora_config."'");
                }
            }

            if (($step7 + $step6 + $step5 + $step4 + $step3 + $step2 + $step1) == 7) {
                $everything_ok = 1;
            }
        break;
    }

        echo '</table>';
        echo '</div>';
        echo '<div class="col-md-6" id="content-errors">';
        echo "<h2 class='subtitle'>Creating database and default configuration file</h2>";
    if ($everything_ok !== 1) {
        $info = '';

        if (!empty($errors)) {
            foreach ($errors as $key => $err) {
                $info .= '<div class="err-sql">'.$err.'</div>';
            }

            $info .= "<div class='err'>If you use MySQL 8 make sure to include the 
                        following parameter in your installation's my.cnf configuration file<br />
                        sql_mode=\"\"</div>";
        }

        $info .= "<div class='err'><b>There were some problems.
				Installation was not completed.</b> 
				<p>Please correct failures before trying again.
				All database ";

        if ($engine == 'oracle') {
            $info .= 'objects ';
        } else {
            $info .= 'schemes ';
        }

        $info .= 'created in this step have been dropped. </p>
				</div>';
        echo $info;

        switch ($engine) {
            case 'mysql':
                if (mysql_error() != '') {
                    echo "<div class='err'>".mysql_error().'.</div>';
                    echo "<div class='err'>If you use MySQL 8 make sure to include the 
                    following parameter in your installation's my.cnf configuration file<br />
                    sql_mode=\"\"</div>";
                }

                if ($step1 == 1) {
                    mysql_query("DROP DATABASE $dbname");
                }
            break;

            case 'mysqli':
                if ($connection && mysqli_error($connection) != '') {
                    echo "<div class='err'>".mysqli_error($connection).'.</div>';
                    echo "<div class='err'>If you use MySQL 8 make sure to include the 
                    following parameter in your installation's my.cnf configuration file<br />
                    sql_mode=\"\"</div>";
                }

                if ($step1 == 1) {
                    mysqli_query($connection, "DROP DATABASE $dbname");
                }
            break;
        }
    }

        echo '</div>';
        echo '</div>';
        echo '</div></div>';
        echo "
		<div id='foot_install'>
            <div class='content-footer'>
            <a href='install.php?step=3' class='btn_primary outline'>Previous step</a>
            <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
            </span>";
    if ($everything_ok === 1) {
        echo "<a id='step5' href='install.php?step=5'>
                <button class='btn_primary' type='submit'>Next Step</button>
              </a>";
    }

        echo '</div></div>';
}


/**
 * Print all step 5
 *
 * @return void
 */
function install_step5()
{
    echo "
	<div id='install_container'>
	<div id='wizard'>
	".print_logo_status(6, 6)."
		<div id='install_box'>
		    <h2 class='subtitle'>Installation complete</h2>
			<p class='text'>For security, you now must manually delete this installer 
			    ('<i>install.php</i>') file before trying to access to your Pandora FMS console.
			<p class='text'>You should also install Pandora FMS Servers before trying to monitor anything;
			    please read documentation on how to install it.</p>
			<p class='text'>Default user is <b>'admin'</b> with password <b>'pandora'</b>, 
			    please change it both as soon as possible.</p>
			<p class='text'>Don't forget to check <a href='https://pandorafms.com' class='link'>https://pandorafms.com</a> 
			    for updates.
			<p class='text'>Select if you want to rename '<i>install.php</i>'.</p>
			<form method='post' action='index.php'>
				<button class='btn_primary outline' type='submit' name='rn_file'><span class='btn_install_next_text'>Yes, rename the file</span></button>
				<input type='hidden' name='rename_file' value='1'>
			</form>
			</p>
		</div>
    </div>
	<div id='foot_install'>
        <div class='content-footer'>
                <span class='signature'>Pandora FMS is an OpenSource software project registered at <a target='_blank' href='http://pandora.sourceforge.net'>SourceForge →</a>
                </span>
                <a id='access_pandora' href='index.php'>
                    <button class='btn_primary'>Click here to access to your Pandora FMS console</button>
                </a>
            </div>
        </div>
    </div>";
}
