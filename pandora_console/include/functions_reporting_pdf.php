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

global $config;

$reporting_debug = 0;
if ($reporting_debug != 0){
        error_reporting(E_ALL);
} else {
        error_reporting(0);
}

function doTitle ($pdf, $title=""){
        $pdf->transaction('start');
        $ok=0;
        while (!$ok){
                $thisPageNum = $pdf->ezPageCount;
                $pdf->saveState();
                $pdf->setColor(0.9,0.9,0.9);
                $pdf->filledRectangle($pdf->ez['leftMargin'],$pdf->y-$pdf->getFontHeight(16)+$pdf->getFontDecender(16),$pdf->ez['pageWidth']-$pdf->ez['leftMargin']-$pdf->ez['rightMargin'],$pdf->getFontHeight(16));
                $pdf->restoreState();
                $pdf->ezText (utf8_decode($title),14,array('justification'=>'left'));
                $pdf->ezText ("\n",6);
                if ($pdf->ezPageCount==$thisPageNum){
                        $pdf->transaction('commit');
                        $ok=1;
                } else {
                        // then we have moved onto a new page, bad bad, as the background colour will be on the old one
                        $pdf->transaction('rewind');
                        $pdf->ezNewPage();
                }
        }
}

function doPageNumbering($pdf, $x=500, $y=25, $size=9) { 
// Original code by Johny Mnemonic (mnemonic23 in SF site)
// God bless Sourceforge forums !

        //count pages 
        $pages = count($pdf->ezPages);  
        //iterate through pages 
        for($pageno = 1; $pageno <= $pages; $pageno++) { 
                //build footer string 
                $foot = 'Page '.$pageno.' of '.$pages; 
                //open the page again 
                $pdf->reopenObject($pdf->ezPages[$pageno]); 
                //print the footer 
                $pdf->addText($x, $y, $size, $foot); 
                //close the page 
                $pdf->closeObject(); 
        }
} 

function doPageHeader ($pdf, $title){
        global $config;

        $pdf->addInfo("Title", $title);
        $pdf->addInfo("Author","Pandora FMS 2.0");
        $pdf->addInfo("Creator","Pandora FMS with ezPDF engine");
        $pdf->addInfo("Subject","Automated Pandora FMS report for user defined report");
        
        // Add header
        $all = $pdf->openObject();
        $pdf->saveState();
        $pdf->addJpegFromFile($config["homedir"]."/images/pandora_logo.jpg",20,812,25);
        $pdf->setStrokeColor(0,0,0,1);
        $pdf->line(20,40,578,40);
        $pdf->line(20,810,578,810);
        $pdf->addText(25,25,8,'Pandora FMS 2.0 - http://pandora.sourceforge.net');
        $pdf->addText(50,815,10,"Pandora FMS - Report $title");
        $pdf->restoreState();
        $pdf->closeObject();
        $pdf->addObject($all,'all');
}

// ===========================================================================================
// ===========================================================================================

function event_reporting_pdf ($id_agent, $period){
    global $config;
    require ($config["homedir"]."/include/languages/language_".$config["language"].".php");

    $id_user=$_SESSION["id_usuario"];
    global $REMOTE_ADDR;
    $ahora = date("U");
    $mytimestamp = $ahora - $period;
    $total_count = 0;
    $table_data = array();
    $sql2="SELECT * FROM tevento WHERE id_agente = $id_agent AND utimestamp > '$mytimestamp'";    
    // Make query for data (all data, not only distinct).
    $result2=mysql_query($sql2);
    while ($row2=mysql_fetch_array($result2)){
        $id_grupo = $row2["id_grupo"];
        if (give_acl($id_user, $id_grupo, "IR") == 1){ // Only incident read access to view data !
            $id_group = $row2["id_grupo"];
            if ($row2["estado"] == 0)
                $table_data[$total_count][0] = "--";
            else
                $table_data[$total_count][0] = "OK";
            $table_data[$total_count][1] = $row2["evento"];
            
            if ($row2["estado"] <> 0)
                $table_data[$total_count][2] = substr($row2["id_usuario"],0,8);
            else
                $table_data[$total_count][2] = "";
            $table_data[$total_count][3] = $row2["timestamp"];
            $total_count++;
        }
    }
    return $table_data;
}

function alert_reporting_pdf ($id_agent_module){
    global $config;
    require ($config["homedir"]."/include/languages/language_".$config["language"].".php");

    $query_gen='SELECT talerta_agente_modulo.alert_text, talerta_agente_modulo.id_alerta, talerta_agente_modulo.descripcion, talerta_agente_modulo.last_fired, talerta_agente_modulo.times_fired, tagente_modulo.nombre, talerta_agente_modulo.dis_max, talerta_agente_modulo.dis_min, talerta_agente_modulo.max_alerts, talerta_agente_modulo.time_threshold, talerta_agente_modulo.min_alerts, talerta_agente_modulo.id_agente_modulo, tagente_modulo.id_agente_modulo FROM tagente_modulo, talerta_agente_modulo WHERE tagente_modulo.id_agente_modulo = talerta_agente_modulo.id_agente_modulo and talerta_agente_modulo.id_agente_modulo  = '.$id_agent_module.' ORDER BY tagente_modulo.nombre';
    $result_gen=mysql_query($query_gen);
    $total_count = 0;
    $table_data = array();
    if (mysql_num_rows ($result_gen)) {
        while ($data=mysql_fetch_array($result_gen)){
            if ($data["times_fired"] <> 0)
                $table_data[$total_count][0] = "FIRED";
            else
                $table_data[$total_count][0] = "--";
            $table_data[$total_count][1] = $data["descripcion"];
            $table_data[$total_count][2] = human_time_description($data["time_threshold"]);
            
            if ($data["last_fired"] == "0000-00-00 00:00:00") {
                $table_data[$total_count][3] = $lang_label["never"];
            }
            else {
                $table_data[$total_count][3] = human_time_comparation ($data["last_fired"]);
            }
            $table_data[$total_count][4] = $data["times_fired"];
            $total_count++;
        }
    }
    return $table_data;
}

function general_report ($id_report){
    global $config;
    include $config["homedir"]."/include/languages/language_".$config["language"].".php";
    include ($config["homedir"].'/include/pdf/class.ezpdf.php');
    require ($config["homedir"]."/include/functions_reporting.php");
    $session_id = session_id();

    $report_name = html_entity_decode(give_db_value ("name", "treport", "id_report", $id_report), ENT_COMPAT, "iso-8859-15");
    $report_description = html_entity_decode (give_db_value ("description", "treport", "id_report", $id_report), ENT_COMPAT, "iso-8859-15");

    $report_private= html_entity_decode( give_db_value ("private", "treport", "id_report", $id_report), ENT_COMPAT, "iso-8859-15");
    $report_user = html_entity_decode( give_db_value ("id_user", "treport", "id_report", $id_report), ENT_COMPAT, "iso-8859-15");


	$date_today = date("Y/m/d H:i:s");
    $report_title = utf8_decode ("$report_name");

    // Start PDF 
    $pdf = new Cezpdf ();
    $pdf->selectFont ($config["homedir"].'/include/pdf/fonts/Times-Roman.afm', array('encoding'=>'utf-8'));
    doPageHeader ($pdf, $report_title);
    $pdf->ezSetCmMargins(2,2,2,2);
    $pdf->ezText ("<b>$report_title </b>", 18);
    $pdf->ezText ("Generated at $date_today", 8);
    $pdf->ezText ("\n".$report_description, 10);
    $pdf->ezText ("\n\n", 8);

    $sql = "SELECT * FROM treport_content WHERE id_report = $id_report ORDER by type, id_agent_module DESC";
    $res=mysql_query($sql);
    while ($row = mysql_fetch_array($res)){
        $type = $row["type"];
        $sla_max = $row["sla_max"];
        $sla_min = $row["sla_min"];
        $sla_limit = $row["sla_limit"];
        $id_agent_module = $row["id_agent_module"];
        $period = $row["period"];
        $id_gs = $row["id_gs"];
        unset ($modules);
        unset ($weights);
        // Agent name for type 3 (event )
         
        if ($type != 3) {
            $module_name = utf8_decode(get_db_sql ("SELECT nombre FROM tagente_modulo WHERE id_agente_modulo = ". $id_agent_module));
            $agent_name = dame_nombre_agente_agentemodulo ($id_agent_module);
        } else {
            $agent_name = utf8_decode(get_db_sql ("SELECT nombre FROM tagente WHERE id_agente =$id_agent_module"));
            $module_name = "";
        }
        
        switch($type){
            case 2: // SLA
                    $sla_result = format_numeric(return_module_SLA ($id_agent_module, $period, $sla_min, $sla_max), 2);
                    doTitle($pdf, lang_string("SLA").":   $agent_name - $module_name");
                    //if ($sla_result < $sla_limit)
                        $pdf->setColor(0.9,0,0,0); // Red ink
                    $pdf->ezText ("<b>".$sla_result . " %</b>", 18);
                    $pdf->setColor(0,0,0,1); // Black again
                    $pdf->ezText ($lang_label["sla_max"]. " : ".$sla_max,8);
                    $pdf->ezText ($lang_label["sla_min"]. " : ".$sla_min,8);
                    $pdf->ezText ($lang_label["sla_limit"]. " : ".$sla_limit,8);
                    $pdf->ezText ("\n",8);
                    break;

            case 0: // Simple graph
                    doTitle($pdf, lang_string("Module graph").":   $agent_name - $module_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $image = $config["homeurl"]."/reporting/fgraph.php?PHPSESSID=".$session_id."&tipo=sparse&id=$id_agent_module&height=180&width=780&period=$period&avg_only=1&pure=1";
                    //ezImage(image,[padding],[width],[resize],[justification],[array border])
                    $pdf->ezImage($image,0,470,'none','left');
                    $pdf->ezText ("\n",8);
                    break;

            case 1: // Custom/Combined graph
                    $graph_name = give_db_value ("name", "tgraph", "id_graph", $id_gs);
                    $sql2="SELECT * FROM tgraph_source WHERE id_graph = $id_gs";
                    $res2=mysql_query($sql2);
                    while ( $row2 = mysql_fetch_array($res2)){
                        $weight = $row2["weight"];
                        $id_agent_module = $row2["id_agent_module"];
                        if (!isset($modules)){
                            $modules = $id_agent_module;
                            $weights = $weight;
                        } else {
                            $modules = $modules.",".$id_agent_module;
                            $weights = $weights.",".$weight;
                        }
                    }
                    doTitle($pdf, lang_string("Custom graph").":  $graph_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $image = $config["homeurl"]."/reporting/fgraph.php?tipo=combined&id=$modules&weight_l=$weights&height=230&width=720&period=$period&pure=1";
                    //ezImage(image,[padding],[width],[resize],[justification],[array border])
                    $pdf->ezImage($image,0,470,'none','left');
                    $pdf->ezText ("\n",8);
                    break;
            case 6: // AVG value
                    $avg_value = format_for_graph(return_moduledata_avg_value ($id_agent_module, $period),2);
                    doTitle($pdf, lang_string("avg_value").": $agent_name - $module_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $pdf->ezText ("<b>".$avg_value . "</b>", 18);
                    $pdf->ezText ("\n",8);
                    break;

            case 7: // MAX value
                    $max_value = format_for_graph(return_moduledata_max_value ($id_agent_module, $period),2);
                    doTitle($pdf, lang_string("max_value").": $agent_name - $module_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $pdf->ezText ("<b>".$max_value . "</b>", 18);
                    $pdf->ezText ("\n",8);
                    break;
            case 8: // MIN value
                    $min_value = format_for_graph(return_moduledata_min_value ($id_agent_module, $period),2);
                    doTitle($pdf, lang_string("min_value").": $agent_name - $module_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $pdf->ezText ("<b>".$min_value . "</b>", 18);
                    $pdf->ezText ("\n",8);
                    break;
            case 5: // Monitor report
                    $monitor_value = $sla_result = format_numeric(return_module_SLA ($id_agent_module, $period, 1, 1), 2);
                    doTitle($pdf, lang_string("monitor_report").": $agent_name - $module_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $pdf->setColor(0,0.9,0,0); // Red ink
                    $pdf->ezText ("<b>UP: ".$monitor_value . " %</b>", 18);
                    $pdf->setColor(0.9,0,0,1); // Black again
                    $monitor_value2 = format_numeric(100 - $monitor_value,2) ;
                    $pdf->ezText ("<b>DOWN: ".$monitor_value2 . " %</b>", 18);
                    $pdf->setColor(0,0,0,1); // Black again
                    $pdf->ezText ("\n",8);
                    break;
            case 3: // Event report
                    doTitle($pdf, lang_string("event_report")." - $agent_name");
                    $pdf->ezText (human_time_description($period)."\n",8);
                    $table_data = array ();
                    $table_label[0] = lang_string ("status");
                    $table_label[1] = lang_string ("event");
                    $table_label[2] = lang_string ("user");
                    $table_label[3] = lang_string ("timestamp");
                    $table_data = event_reporting_pdf ($id_agent_module, $period);
                    $pdf->ezTable( $table_data, $table_label, "",
                            array('width'=>450, 'fontSize'=>9, 'rowGap'=>2, 
                            'outerLineThickness'=>0.8, 'innerLineThickness'=>0.2, 'shaded'=>1)
                    );
                    $pdf->ezText ("\n",8);
                    break;
            case 4: // Alert report
                    $module_name = give_db_value ("nombre", "tagente_modulo", "id_agente_modulo", $id_agent_module);
                    $agent_name = dame_nombre_agente_agentemodulo ($id_agent_module);
                    $table_data = array();
                    $table_label[0] = $lang_label["status"];
                    $table_label[1] = $lang_label["description"];
                    $table_label[2] = $lang_label["time_threshold"];
                    $table_label[3] = $lang_label["last_fired"];
                    $table_label[4] = $lang_label["times_fired"];

                    doTitle($pdf, lang_string("alert_report").":   $agent_name - $module_name");
                    $table_data = alert_reporting_pdf ($id_agent_module);
                    $pdf->ezTable( $table_data, $table_label, "",
                            array('width'=>450, 'fontSize'=>9, 'rowGap'=>2, 
                            'outerLineThickness'=>0.8, 'innerLineThickness'=>0.2, 'shaded'=>1)
                    );
                    $pdf->ezText ("\n",8);
                    break;
        } // switch
    } // while row



        // End report
        doPageNumbering($pdf);
        $pdf->ezStream ();
}

// End code
?>