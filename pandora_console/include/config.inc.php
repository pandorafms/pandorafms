<?php

/**
 * Configuraton sample file.
 *
 * @category   Config
 * @package    Pandora FMS
 * @subpackage Community
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

/*
 * Default values
 *   $config["dbname"]="pandora";
 *   $config["dbuser"]="pandora";
 *   $config["dbpass"]="pandora";
 *   $config["dbhost"]="localhost";
 *
 *
 * This is used for reporting, please add "/" character at the end
 *   $config["homedir"]="/var/www/pandora_console/";
 *   $config["homeurl"]="/pandora_console/";
 *   $config["auth"]["scheme"] = "mysql";
 *
 * This is used to configure MySQL SSL console connection
 *   $config["dbssl"]=0;
 *   $config["dbsslcafile"]="/path/ca-cert.pem";
 *   $config["sslverifyservercert"]=1;
 */

// By default report any error but notices.
error_reporting(E_ALL ^ E_NOTICE);

/*
 * Uncomment to display only critical errors.
 *   error_reporting(E_ERROR);
 * Uncomment to display none errors.
 *   error_reporting(0);
 */

$ownDir = dirname(__FILE__).DIRECTORY_SEPARATOR;
require $ownDir.'config_process.php';
