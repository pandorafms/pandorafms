<?php

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

// Load global vars
global $config;

if ( (give_acl($id_user, 0, "LM")==0)){
    audit_db($id_user,$REMOTE_ADDR, "ACL Violation","Trying to access Plugin Management");
    require ("general/noaccess.php");
    exit;
}

// Plugin operations
/*
tplugin table DB struct
     id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
    `name` varchar(200) NOT NULL,
    `description` mediumtext default "",
    `max_timeout` int(4) UNSIGNED NOT NULL default 0,
    `execute`varchar(250) NOT NULL,
    `net_dst_opt` varchar(50) default '',
    `net_port_opt` varchar(50) default '',
    `user_opt` varchar(50) default '',
    `pass_opt` varchar(50) default '',
*/

// Update plugin
if (isset($_GET["update_plugin"])){ // if modified any parameter
    $plugin_id = get_parameter ("update_plugin", 0);
    $plugin_name = get_parameter ("form_name", "");
    $plugin_description = get_parameter ("form_description", "");
    $plugin_max_timeout = get_parameter ("form_max_timeout", "");
    $plugin_execute = get_parameter ("form_execute", "");
    $plugin_net_dst_opt = get_parameter ("form_net_dst_opt", "");
    $plugin_net_port_opt = get_parameter ("form_net_port_opt", "");
    $plugin_user_opt = get_parameter ("form_user_opt", "");
    $plugin_pass_opt = get_parameter ("form_pass_opt", "");
    $sql_update ="UPDATE tplugin SET 
    name = '$plugin_name',  
    description = '$plugin_description', 
    max_timeout = '$plugin_max_timeout', 
    execute = '$plugin_execute', 
    net_dst_opt = '$plugin_net_dst_opt', 
    net_port_opt = '$plugin_net_port_opt', 
    user_opt = '$plugin_user_opt', 
    pass_opt = '$plugin_pass_opt' 
    WHERE id = $plugin_id";
    $result=mysql_query($sql_update);   
    if (! $result) {
        echo "<h3 class='error'>".lang_string ("Problem updating plugin")."</h3>";
    } else {
        echo "<h3 class='suc'>".lang_string ("Plugin updated successfully")."</h3>";
    }
}

// Create plugin
if (isset($_GET["create_plugin"])){     
    $plugin_name = get_parameter ("form_name", "");
    $plugin_description = get_parameter ("form_description", "");
    $plugin_max_timeout = get_parameter ("form_max_timeout", "");
    $plugin_execute = get_parameter ("form_execute", "");
    $plugin_net_dst_opt = get_parameter ("form_net_dst_opt", "");
    $plugin_net_port_opt = get_parameter ("form_net_port_opt", "");
    $plugin_user_opt = get_parameter ("form_user_opt", "");
    $plugin_pass_opt = get_parameter ("form_pass_opt", "");
    $sql_insert ="INSERT tplugin (name, description, max_timeout, execute, net_dst_opt, net_port_opt, user_opt, pass_opt) VALUES ('$plugin_name', '$plugin_description', '$plugin_max_timeout', '$plugin_execute', '$plugin_net_dst_opt', '$plugin_net_port_opt', '$plugin_user_opt', '$plugin_pass_opt')";
    $result=mysql_query($sql_insert);
    if (! $result){
       echo "<h3 class='error'>".lang_string ("Problem creating plugin")."</h3>";
        echo $sql_insert;
    } else {
        echo "<h3 class='suc'>".lang_string ("Plugin created successfully")."</h3>";
    }
}

if (isset($_GET["kill_plugin"])){ // if delete alert
    $plugin_id = get_parameter ("kill_plugin", 0);
    $sql_delete= "DELETE FROM tplugin WHERE id= ".$plugin_id;
    $result=mysql_query($sql_delete);       
    if (! $result){
        echo "<h3 class='error'>".lang_string ("Problem deleting plugin")."</h3>";
    } else {
        echo "<h3 class='suc'>".lang_string ("Plugin deleted successfully")."</h3>";
    }
    if ($plugin_id != 0){
        $sql_delete2 ="DELETE FROM tagente_modulo WHERE id_plugin = ".$plugin_id; 
        $result=mysql_query($sql_delete2);
    }
}

$view = get_parameter ("view", "");
$create = get_parameter ("create", "");

if ($view != ""){
    $form_id = $view;
	$plugin = get_db_row ("tplugin", "id", $form_id);
    $form_name = $plugin["name"];
    $form_description = $plugin["description"];
    $form_max_timeout = $plugin ["max_timeout"];
    $form_execute = $plugin ["execute"];
    $form_net_dst_opt = $plugin ["net_dst_opt"];
    $form_net_port_opt = $plugin ["net_port_opt"];
    $form_user_opt = $plugin ["user_opt"];
    $form_pass_opt = $plugin ["pass_opt"];
} 
if ($create != ""){
    $form_name = "";
    $form_description = "";
    $form_max_timeout = "";
    $form_execute = "";
    $form_net_dst_opt = "";
    $form_net_port_opt = "";
    $form_user_opt = "";
    $form_pass_opt = "";
}

// SHOW THE FORM
// =================================================================

if (($create != "") OR ($view != "")){
	
	echo "<h2>";
    if ($create != "")
	    echo lang_string ("Plugin creation");
    else {
        echo lang_string ("Plugin update");
        $plugin_id = get_parameter ("view","");
    }
	pandora_help("plugin_definition");
    echo "</h2>";
    
    if ($create == "") 
        echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&update_plugin=$plugin_id'>";
    else
        echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create_plugin=1'>";

    echo '<table width="600" cellspacing="4" cellpadding="4" class="databox_color">';
    
    echo '<tr><td class="datos">'.lang_string ("Name");
    echo '<td class="datos">';
    echo '<input type="text" name="form_name" size=30 value="'.$form_name.'"></td>';
    
    echo '<tr><td class="datos2">'.lang_string ("Plugin command");
    echo '<td class="datos2">';
    echo '<input type="text" name="form_execute" size=45 value="'.$form_execute.'"></td>';

    echo '<tr><td class="datos">'.lang_string ("Max.Timeout");
    echo '<td class="datos">';
    echo '<input type="text" name="form_max_timeout" size=5 value="'.$form_max_timeout.'"></td>';

    echo '<tr><td class="datos2">'.lang_string ("IP address option");
    echo '<td class="datos2">';
    echo '<input type="text" name="form_net_dst_opt" size=15 value="'.$form_net_dst_opt.'"></td>';

    echo '<tr><td class="datos">'.lang_string ("Port option");
    echo '<td class="datos">';
    echo '<input type="text" name="form_net_port_opt" size=5 value="'.$form_net_port_opt.'"></td>';


    echo '<tr><td class="datos2">'.lang_string ("User option");
    echo '<td class="datos2">';
    echo '<input type="text" name="form_user_opt" size=15 value="'.$form_user_opt.'"></td>';

    echo '<tr><td class="datos">'.lang_string ("Password option");
    echo '<td class="datos">';
    echo '<input type="text" name="form_pass_opt" size=15 value="'.$form_pass_opt.'"></td>';

    echo '<tr><td class="datos2">'.$lang_label["description"].'</td>';
    echo '<td class="datos2"><textarea name="form_description" cols="50" rows="4">';
    echo $form_description;
    echo '</textarea></td></tr>';

    echo '</table>';
    echo '<table width=600>';
    echo '<tr><td align="right">';
    
    if ($create != ""){
	    echo "<input name='crtbutton' type='submit' class='sub wand' value='".$lang_label["create"]."'>";
    } else {
	    echo "<input name='uptbutton' type='submit' class='sub upd' value='".$lang_label["update"]."'>";
    }
    echo '</form></table>';
}

else {
	echo "<h2>". lang_string ("Plugins registered in Pandora FMS")."</h2>";
	// If not edition or insert, then list available plugins
    $sql1='SELECT * FROM tplugin ORDER BY name';
    $result=mysql_query($sql1);
    if (mysql_num_rows($result) > 0){
        echo '<table width="530" cellspacing="4" cellpadding="4" class="databox">';
        echo "<th>".lang_string("name");
        echo "<th>".lang_string("execute");
        echo "<th>".lang_string("delete");
        $color = 0;
        while ($row=mysql_fetch_array($result)){
            if ($color == 1){
                $tdcolor = "datos";
                $color = 0;
                }
            else {
                $tdcolor = "datos2";
                $color = 1;
            }
            echo "<tr>";
            echo "<td class=$tdcolor>";
            echo "<b><a href='index.php?sec=gservers&sec2=godmode/servers/plugin&view=".$row["id"]."'>";
            echo $row["name"];
            echo "</a></b>";
            echo "<td class=$tdcolor>";
            echo $row["execute"];
            echo "<td class=$tdcolor>";
            echo "<a href='index.php?sec=gservers&sec2=godmode/servers/plugin&kill_plugin=".$row["id"]."'><img src='images/cross.png' border=0></a>";
        }
        echo "</table>";
    } else {
        echo '<div class="nf">'. lang_string ("There is no plugins in the system");
        echo "<br>";
    }
    echo "<table width=530>";
    echo "<tr><td align=right>";
    echo "<form name=plugin method='post' action='index.php?sec=gservers&sec2=godmode/servers/plugin&create=1'>";
    echo "<input name='crtbutton' type='submit' class='sub wand' value='".$lang_label["create"]."'>";
    echo "</table>";
    
}

?>
