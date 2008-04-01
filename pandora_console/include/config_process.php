<?PHP

// Pandora FMS - the Free Monitoring System
// ========================================
// Copyright (c) 2008 Artica Soluciones Tecnológicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

//Pandora Version
if (!isset($build_version))
    $build_version="PC080227";
if (!isset($pandora_version))
    $pandora_version="v2.0-dev";

// Read remaining config tokens from DB
if (! mysql_connect($config["dbhost"],$config["dbuser"],$config["dbpass"])){ 

//Non-persistent connection. If you want persistent conn change it to mysql_pconnect()
    exit ('<html><head><title>Pandora FMS Error</title>
    <link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
    </head><body><div align="center">
    <div id="db_f">
        <div>
        <a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
        </div>
    <div id="db_ftxt">
        <h1 id="log_f" class="error">Pandora Console Error DB-001</h1>
        Cannot connect with Database, please check your database setup in the 
        <b>./include/config.php</b> file and read documentation.<i><br><br>
        Probably any of your user/database/hostname values are incorrect or 
        database is not running.</i><br><br><font class="error">
        <b>MySQL ERROR:</b> '. mysql_error().'</font>
        <br>&nbsp;
    </div>
    </div></body></html>');
}
mysql_select_db($config["dbname"]);
if($result2=mysql_query("SELECT * FROM tconfig")){
    while ($row2=mysql_fetch_array($result2)){
        switch ($row2["token"]) {
        case "language_code": $config["language"]=$row2["value"];
                        break;
        case "block_size": $config["block_size"]=$row2["value"];
                        break;
        case "days_purge": $config["days_purge"]=$row2["value"];
                        break;
        case "days_compact": $config["days_compact"]=$row2["value"];
                        break;
        case "graph_res": $config["graph_res"]=$row2["value"];
                        break;
        case "step_compact": $config["step_compact"]=$row2["value"];
                        break;  
        case "style": $config["style"]=$row2["value"];
                        break;
        case "show_unknown": $config["show_unknown"] = $row2["value"];
                            break;
        case "show_lastalerts": $config["show_lastalerts"] = $row2["value"];
                            break;
        }
    }
} else {
     exit ('<html><head><title>Pandora FMS Error</title>
             <link rel="stylesheet" href="./include/styles/pandora.css" type="text/css">
             </head><body><div align="center">
             <div id="db_f">
                 <div>
                 <a href="index.php"><img src="images/pandora_logo.png" border="0"></a>
                 </div>
             <div id="db_ftxt">
                 <h1 id="log_f" class="error">Pandora Console Error DB-002</h1>
                 Cannot load configuration variables. Please check your database setup in the
                 <b>./include/config.php</b> file and read documentation.<i><br><br>
                  Probably database schema is created but there are no data inside it or you have a problem with DB access credentials.
                 </i><br>
             </div>
             </div></body></html>');
}   


if ($config["language"] == 'ast_es') {
    $help_code='ast';
    }
else $help_code = substr($config["language"],0,2);

?>
