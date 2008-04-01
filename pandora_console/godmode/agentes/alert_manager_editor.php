<h3><?php echo $lang_label["alert_asociation_form"] ?></h3>


<?php
// ==================================================================================
// Add alerts
// ==================================================================================
echo '<form name="agente" method="post" action="index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&tab=alert&id_agente='.$id_agente.'">';
if (! isset($update_alert))
    $update_alert = -1;
    
if ($update_alert != 1) {
    echo '<input type="hidden" name="insert_alert" value=1>';
} else {
    echo '<input type="hidden" name="update_alert" value=1>';
    echo '<input type="hidden" name="id_aam" value="'.$alerta_id_aam.'">';
}
?>
<input type="hidden" name="id_agente" value="<?php echo $id_agente ?>">
<a name="alerts"> <!-- Don't Delete !! -->

<table width=600 cellpadding="4" cellspacing="4" class="databox_color" border=0>
<tr>
<td class="datos"><?php echo $lang_label["alert_type"]?>
<td class="datos">
<select name="tipo_alerta"> 
<?php

    if (isset($tipo_alerta)){
        echo "<option value='".$tipo_alerta."'>".dame_nombre_alerta($tipo_alerta)."</option>";
    }
if ($form_alerttype == "combined"){
    $sql1 = 'SELECT id_alerta, nombre FROM talerta WHERE id_alerta = 0';
} else {
    $sql1 = 'SELECT id_alerta, nombre FROM talerta ORDER BY nombre';
}
    $result = mysql_query ($sql1);
    while ($row = mysql_fetch_array ($result)){
        echo "<option value='".$row["id_alerta"]."'>".$row["nombre"]."</option>";
    }

    echo "</select>";
    
    echo "<td class='datos'>";
    echo $lang_label["alert_status"];
    echo "<td class='datos'>";
    echo '<select name="disable_alert">';
    if ((isset($alerta_disable)) AND ($alerta_disable == "1")) {
        echo "<option value='1'>".$lang_label["disabled"];
        echo "<option value='0'>".$lang_label["enabled"];
    } else {
        echo "<option value='0'>".$lang_label["enabled"];
        echo "<option value='1'>".$lang_label["disabled"];
    }
    echo "</select>";

// Trigger values for alert
if ($form_alerttype != "combined"){
    echo '<tr><td class="datos2">'.$lang_label["min_value"];
    echo "<a href='#' class='tip'>&nbsp;<span>";echo $lang_label["min_valid_value_help"]."</span></a>";
    echo '<td class="datos2"><input type="text" name="minimo" size="5" value="'.$alerta_dis_min.'" style="margin-right: 70px;">';

    echo "<td class='datos2'>";
    echo $lang_label["max_value"];
    echo "<a href='#' class='tip'>&nbsp;<span>";
    echo $lang_label["max_valid_value_help"];
    echo "</span></a>";
    echo "<td class='datos2'>";
    echo "<input type='text' name='maximo' size='5' value='$alerta_dis_max'>";

    // <!-- FREE TEXT ALERT -->

    echo '<tr><td class="datos">'.$lang_label["alert_text"]."<a href='#' class='tip'>&nbsp;<span>Regular Expression Supported </span></a>";
    echo '<td class="datos" colspan=4><input type="text" name="alert_text" size="60" value ="'.$alert_text.'">';
}

echo '<tr><td class="datos2">'.$lang_label["description"];
echo '<td class="datos2" colspan=4><input type="text" name="descripcion" size="60" value ="'.$alerta_descripcion.'">';

?>

<tr><td class="datos"><?php echo $lang_label["field1"] ?> 
<td class="datos" colspan=4><input type="text" name="campo_1" size="39" value="<?php echo $alerta_campo1 ?>">
<a href='#' class='tip'><span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>


<tr><td class="datos2"><?php echo $lang_label["field2"] ?> 
<td class="datos2"  colspan=4><input type="text" name="campo_2" size="39" value="<?php echo $alerta_campo2 ?>">
<a href='#' class='tip'><span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>

<tr><td class="datos"><?php echo $lang_label["field3"] ?> 
<td class="datos"  colspan=4><textarea name="campo_3" style='height:85px;' cols="36" rows="4"><?php echo $alerta_campo3 ?></textarea>
<a href='#' class='tip'><span>
<b>Macros:</b><br>
_agent_<br>
_timestamp_<br>
_data_<br>
</span></a>

<?PHP

if ($form_alerttype != "combined"){
    echo "<tr><td class='datos2'>".$lang_label["time_from"];    
    echo "<td class='datos2'><select name='time_from'>";
    if ($time_from != ""){
        echo "<option value='$time_from'>".substr($time_from,0,5);
    }
    
    for ($a=0; $a < 48; $a++){
        echo "<option value='";
        echo render_time ($a);
        echo "'>";
        echo render_time ($a);
    }
    echo "</select>";
    
    echo "<td class='datos2'>".$lang_label["time_to"];
    echo "<td class='datos2'><select name='time_to'>";
    if ($time_from != ""){
        echo "<option value='$time_to'>".substr($time_to,0,5);
    }
    
    for ($a=0; $a < 48; $a++){
        echo "<option value='";
        echo render_time ($a);
        echo "'>";
        echo render_time ($a);
    }
    echo "</select>";
    
    ?>
    
    <tr><td class="datos"><?php echo $lang_label["time_threshold"] ?>
    <a href='#' class='tip'>&nbsp;<span><?PHP echo $lang_label["alert_time_threshold_help"]; ?></span></a>
    
    <td class="datos">
    <select name="time_threshold" style="margin-right: 60px;">
    <?php
    if ($alerta_time_threshold != ""){ 
        echo "<option value='".$alerta_time_threshold."'>".human_time_description($alerta_time_threshold)."</option>";
    }
    echo '
    <option value=300>5 Min.</option>
    <option value=600>10 Min.</option>
    <option value=900>15 Min.</option>
    <option value=1800>30 Min.</option>
    <option value=3600>1 Hour</option>
    <option value=7200>2 Hour</option>
    <option value=18000>5 Hour</option>
    <option value=43200>12 Hour</option>
    <option value=86400>1 Day</option>
    <option value=604800>1 Week</option>
    <option value=-1>Other value</option>
    </select>';

    echo '<td class="datos">';
    echo $lang_label["other"];
    echo '<td class="datos">';
    echo '<input type="text" name="other" size="5">';

    // Max / Min alerts 
    echo "<tr><td class='datos2'>".$lang_label["min_alerts"];
    echo '<td class="datos2">';
    echo '<input type="text" name="min_alerts" size="5" value="';
    if (isset($alerta_min_alerts)) 
        echo $alerta_min_alerts;
    else
        echo 0;
    echo '" style="margin-right: 10px;">';


    echo '<td class="datos2">';
    echo $lang_label["max_alerts"];
    echo '<td class="datos2">';
    echo '<input type="text" name="max_alerts" size="5" value="';
    if (isset($alerta_max_alerts)) 
        echo $alerta_max_alerts;
    else
        echo 1;
    echo '" style="margin-right: 10px;">';
}


if ($form_alerttype != "combined"){
    echo '<tr><td class="datos">'.lang_string("assigned_module");
    echo '<td class="datos" colspan="4">';
    if ($update_alert != 1) {
        echo '<select name="agente_modulo"> ';
        $sql2 = "SELECT id_agente_modulo, id_tipo_modulo, nombre FROM tagente_modulo WHERE id_agente = $id_agente ORDER BY nombre";
        $result2=mysql_query($sql2);
        while ($row2=mysql_fetch_array($result2)){
            if ($row2["id_tipo_modulo"] != -1) {
                $sql1='SELECT nombre FROM ttipo_modulo WHERE id_tipo = '.$row2["id_tipo_modulo"];
                $result=mysql_query($sql1);
                while ($row=mysql_fetch_array($result)){
                    echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"]." ( ".$row["nombre"]." )</option>";
                }
            } else // for -1, is a special module, keep alive monitor !!
                echo "<option value='".$row2["id_agente_modulo"]."'>".$row2["nombre"]."</option>";
        }
        echo "</select>";
    } else {
        $agentmodule_name = give_db_value ("nombre", "tagente_modulo", "id_agente_modulo", $alerta_id_agentemodulo);
        echo $agentmodule_name;
    }
}

 // End block only if $creacion_agente != 1;

echo "</td></tr></table>";
echo '<table width=605>';
echo '<tr><td align="right">';
    if ($update_alert== "1"){
        echo '<input name="updbutton" type="submit" class="sub upd" value="'.$lang_label["update"].'">';
    } else {
        echo '<input name="crtbutton" type="submit" class="sub wand" value="'.$lang_label["add"].'">';
    }
    echo '</form>';
echo '</td></tr></table>';

if ($form_alerttype == "combined"){
    echo "<h3>".lang_string ("Combined alert components")."</h3>";
    echo '<table width=750 cellpadding="4" cellspacing="4" class="databox" border=0>';
    echo '<tr><th>'.lang_string ("operation");
    echo '<th>'.lang_string ("agent");
    echo '<th>'.lang_string ("module");
    echo '<th>'.lang_string ("max_value");
    echo '<th>'.lang_string ("min_value");
    echo '<th>'.lang_string ("tt");
    echo '<th>'.lang_string ("min_alerts");
    echo '<th>'.lang_string ("max_alerts");
    echo '<th>'.lang_string ("delete");
    echo "<tr><td class='datos'>";
    echo "</table>";
}



?>