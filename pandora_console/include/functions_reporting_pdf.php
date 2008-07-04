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

$reporting_debug = false;
if ($reporting_debug) {
	error_reporting (E_ALL);
} else {
	error_reporting (0);
}

function doTitle ($pdf, $title="") {
	$pdf->transaction('start');
	$ok=0;
	while (!$ok) {
		$thisPageNum = $pdf->ezPageCount;
		$pdf->saveState();
		$pdf->setColor(0.9,0.9,0.9);
		$pdf->filledRectangle($pdf->ez['leftMargin'],$pdf->y-$pdf->getFontHeight(16)+$pdf->getFontDecender(16),$pdf->ez['pageWidth']-$pdf->ez['leftMargin']-$pdf->ez['rightMargin'],$pdf->getFontHeight(16));
		$pdf->restoreState();
		$pdf->ezText (utf8_decode($title),14,array('justification'=>'left'));
		$pdf->ezText ("\n",6);
		if ($pdf->ezPageCount==$thisPageNum){
			$pdf->transaction('commit');
			$ok = 1;
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

function get_pdf_report ($report) {
	global $config;
	$session_id = session_id ();

	$report_name = html_entity_decode ($report['name'], ENT_COMPAT, "iso-8859-15");
	$report_description = html_entity_decode ($report['description'], ENT_COMPAT, "iso-8859-15");
	$report_private= $report['private'];
	$report_user = $report['id_user'];

	$date_today = date("Y/m/d H:i:s");
	$report_title = utf8_decode ("$report_name");

	// Start PDF 
	$pdf = new Cezpdf ();
	$pdf->selectFont ($config["homedir"].'/include/pdf/fonts/Times-Roman.afm', array('encoding'=>'utf-8'));
	doPageHeader ($pdf, $report_title);
	$pdf->ezSetCmMargins (2, 2, 2, 2);
	$pdf->ezText ("<b>$report_title </b>", 18);
	$pdf->ezText ("Generated at $date_today", 8);
	$pdf->ezText ("\n".$report_description, 10);
	$pdf->ezText ("\n\n", 8);
	$table_options = array ('width' => 450,
				'fontSize' => 9,
				'rowGap' => 2,
				'outerLineThickness' => 0.8,
				'innerLineThickness' => 0.2,
				'shaded' => 1);
	$group_name = dame_grupo ($report['id_group']);
	
	$agents = get_agents_in_group ($report['id_group']);
	
	$sql = sprintf ('SELECT * FROM treport_content WHERE id_report = %d ORDER BY `order`', $report['id_report']);
	$contents = get_db_all_rows_sql ($sql);
	foreach ($contents as $content) {		
		$module_name = utf8_decode (get_db_value ('nombre', 'tagente_modulo', 'id_agente_modulo', $content['id_agent_module']));
		$agent_name = utf8_decode (dame_nombre_agente_agentemodulo ($content['id_agent_module']));
		$period = human_time_description ($content['period']);
		
		switch ($content["type"]) {
		case 1:
		case 'simple_graph':
			doTitle ($pdf, lang_string ("Module graph").': '.$agent_name.
				' - '.$module_name.' - '.$period);
			$image = 'http://'.$_SERVER['HTTP_HOST'].$config["homeurl"].
				'/reporting/fgraph.php?PHPSESSID='.$session_id.
				'&tipo=sparse&id='.$content["id_agent_module"].
				'&height=180&width=780&period='.$content['period'].
				'&avg_only=1&pure=1';
			//ezImage(image,[padding],[width],[resize],[justification],[array border]
			$pdf->ezImage ($image,0,470,'none','left');
			
			break;
		case 2:
		case 'custom_graph':
			$graph = get_db_row ("tgraph", "id_graph", $content["id_gs"]);
			$modules = array ();
			$weights = array ();
			$sql = sprintf ('SELECT * FROM tgraph_source 
					WHERE id_graph = %d',
					$content["id_gs"]);
			$result = mysql_query ($sql);
			while ($content2 = mysql_fetch_array($result)) {
				array_push ($modules, $content2['id_agent_module']);
				array_push ($weights, $content2["weight"]);
			}
			doTitle ($pdf, lang_string ("Custom graph").': '.$graph["name"].
				' - '.$period);
			$image = 'http://'.$_SERVER['HTTP_HOST'].$config["homeurl"].
				'/reporting/fgraph.php?PHPSESSID='.$session_id.'&tipo=combined&id='.
				implode (',', $modules).'&weight_l='.implode (',', $weights).
				'&height=230&width=720&period='.$content['period'].'&stacked='.
				$graph["stacked"].'&pure=1';
			$pdf->ezImage ($image, 0, 470, 'none', 'left');
			
			break;
		case 3:
		case 'SLA':
			doTitle ($pdf, lang_string ('SLA').': '.$period);
			
			$slas = get_db_all_rows_field_filter ('treport_content_sla_combined',
							'id_report_content', $content['id_rc']);
			if (sizeof ($slas) == 0) {
				$pdf->ezText ("<b>".lang_string ('no_defined_slas') . " %</b>", 18);
			}
			$table->data = array ();
			$table->head = array (lang_string ('Info'),
						lang_string ('sla_result'));
			$sla_failed = false;
			foreach ($slas as $sla) {
				$data = array ();
				
				$data[0] = lang_string ('agent')." : ".dame_nombre_agente_agentemodulo ($sla['id_agent_module'])."\n";
				$data[0] .= lang_string ('module')." : ".dame_nombre_modulo_agentemodulo ($sla['id_agent_module'])."\n";
				$data[0] .= lang_string ('sla_max')." : ".$sla['sla_max']."\n";
				$data[0] .= lang_string ('sla_min')." : ".$sla['sla_min'];
				
				$sla_value = get_agent_module_sla ($sla['id_agent_module'], $content['period'],
								$sla['sla_min'], $sla['sla_max']);
				if ($sla_value === false) {
					$data[1] = lang_string ('unknown');
				} else {
					if ($sla_value < $sla['sla_limit']) {
						$pdf->setColor (0, 1, 0, 0); // Red
						$sla_failed = true;
					}
					$data[1] = format_numeric ($sla_value). " %";
					$pdf->setColor (0, 0, 0, 1); // Black
				}
				
				array_push ($table->data, $data);
			}
			$pdf->ezTable ($table->data, $table->head, "", $table_options);
			
			if (! $sla_failed) {
				$pdf->ezText ('<b>'.lang_string ('ok').'</b>', 8);
			} else {
				$pdf->ezText ('<b>'.lang_string ('fail').'</b>', 8);
			}
			unset ($slas);
			
			break;
		case 4:
		case 'event_report':
			doTitle ($pdf, lang_string ("event_report").' - '.$period);
			$table_events = event_reporting ($report['id_group'], $content['period'], 0, true);
			$pdf->ezTable ($table_events->data, $table_events->head,
					"", $table_options);
			
			break;
		case 5:
		case 'alert_report':
			$alerts = get_alerts_in_group ($report['id_group']);
			$alerts_fired = get_alerts_fired ($alerts, $content['period']);
			
			doTitle ($pdf, lang_string ("alert_report").': '.$group_name.
				' - '.$period);
			$fired_percentage = round (sizeof ($alerts_fired) / sizeof ($alerts) * 100, 2);
			$not_fired_percentage = 100 - $fired_percentage;
			$image = 'http://'.$_SERVER['HTTP_HOST'].$config["homeurl"].
				'/reporting/fgraph.php?PHPSESSID='.$session_id.
				'&tipo=alerts_fired_pipe&height=150&width=280&fired='.
				$fired_percentage.'&not_fired='.$not_fired_percentage;
			$pdf->ezImage ($image, 0, 150, 'none', 'left');
			$pdf->ezText ('<b>'.lang_string ('fired_alerts').': '.sizeof ($alerts_fired).'</b>', 8);
			$pdf->ezText ('<b>'.lang_string ('total_alerts_monitored').': '.sizeof ($alerts).'</b>', 8);
			$pdf->ezText ("\n", 8);
			
			$table_alerts = get_fired_alerts_reporting_table ($alerts_fired, true);
			$pdf->ezTable ($table_alerts->data, $table_alerts->head, 
					"", $table_options);
			unset ($alerts);
			unset ($alerts_fired);
			
			break;
		case 6:
		case 'monitor_report':
			$value = get_agent_module_sla ($content["id_agent_module"], $content['period'], 1, 1);
			doTitle ($pdf, lang_string ("monitor_report").': '.$agent_name.' - '.$module_name.
				' - '.$period);
			$pdf->setColor (0, 0.9, 0, 0); // Red
			$pdf->ezText ('<b>'.lang_string ('up').': '.format_for_graph ($value, 2) . " %</b>", 18);
			$pdf->setColor (0.9, 0, 0, 1); // Grey
			$pdf->ezText ('<b>'.lang_string ('down').': '.format_numeric (100 - $value, 2) . " %</b>", 18);
			$pdf->setColor (0, 0, 0, 1); // Black
			
			break;
		case 7:
		case 'avg_value':
			$value = get_agent_module_value_average ($content["id_agent_module"], $content['period']);
			doTitle ($pdf, lang_string("avg_value").': '.$agent_name.' - '.
				$module_name.' - '.$period);
			$pdf->ezText ("<b>".format_for_graph ($value, 2)."</b>", 18);
			
			break;
		case 8:
		case 'max_value':
			$value = get_agent_module_value_max ($content["id_agent_module"], $content['period']);
			doTitle ($pdf, lang_string ("max_value").': '.$agent_name.
				' - '.$module_name.' - '.$period);
			$pdf->ezText ("<b>".format_for_graph ($value, 2)."</b>", 18);
			
			break;
		case 9:
		case 'min_value':
			$value = get_agent_module_value_min ($content["id_agent_module"], $content['period']);
			doTitle ($pdf, lang_string ("min_value").': '.$agent_name.
				' - '.$module_name.' - '.$period);
			$pdf->ezText ("<b>".format_for_graph ($value, 2)."</b>", 18);
			
			break;
		case 10:
		case 'sumatory':
			$value = get_agent_module_value_sumatory ($content["id_agent_module"], $content['period']);
			doTitle ($pdf, lang_string ("sumatory").': '.$agent_name.
				' - '.$module_name.' - '.$period);
			$pdf->ezText ("<b>".format_for_graph ($value, 2)."</b>", 18);
			
			break;
		case 11:
		case 'general_group_report':
			doTitle ($pdf, lang_string ("group").': '.$group_name);
			$pdf->ezText ("<b>".lang_string ('agents_in_group').': '.sizeof ($agents)."</b>", 12);
			
			break;
		case 12:
		case 'monitor_health':
			$monitors = get_monitors_in_group ($report['id_group']);
			$monitors_down = get_monitors_down ($monitors, $content['period']);
			
			doTitle ($pdf, lang_string ("monitor_health").': '.
				$group_name. ' - '.$period);
			$down_percentage = round (sizeof ($monitors_down) / sizeof ($monitors) * 100, 2);
			$not_down_percentage = 100 - $down_percentage;
			$image = 'http://'.$_SERVER['HTTP_HOST'].$config["homeurl"].
				'/reporting/fgraph.php?PHPSESSID='.$session_id.
				'&tipo=monitors_health_pipe&height=150&width=280&down='.
				$down_percentage.'&not_down='.$not_down_percentage;
			$pdf->ezImage ($image, 0, 150, 'none', 'left');
			$pdf->ezText ("\n", 4);
			$pdf->ezText ('<b>'.lang_string ('total_monitors').': '.sizeof ($monitors).'</b>', 8);
			$pdf->ezText ('<b>'.lang_string ('monitors_down_on_period').': '.sizeof ($monitors_down).'</b>', 8);
			$pdf->ezText ("\n", 8);
			
			$table_monitors = get_monitors_down_reporting_table ($monitors_down, true);
			$pdf->ezTable ($table_monitors->data, $table_monitors->head, 
					"", $table_options);
			unset ($monitors);
			unset ($monitors_down);
			
			break;
		case 13:
		case 'agents_detailed':
			doTitle ($pdf, lang_string ("agents_detailed").': '.
				$group_name.' '.lang_string ('group'));
			foreach ($agents as $agent) {
				$pdf->ezText ("<b>".$agent['nombre']."</b>", 18);
				$table = get_agent_modules_reporting_table ($agent['id_agente'], $content['period'], 0, true);
				$pdf->ezText ("<b>".lang_string ('modules')."</b>", 12);
				$pdf->ezText ("\n", 3);
				$pdf->ezTable ($table->data, array (lang_string ('name')), "", $table_options);
				
				$table = get_agent_alerts_reporting_table ($agent['id_agente'], $content['period'], 0, true);
				if (sizeof ($table->data)) {
					$pdf->ezText ("<b>".lang_string ('alerts')."</b>", 12);
					$pdf->ezText ("\n", 3);
					$pdf->ezTable ($table->data, $table->head, "", $table_options);
				}
				
				$table = get_agent_monitors_reporting_table ($agent['id_agente'], $content['period'], 0, true);
				if (sizeof ($table->data)) {
					$pdf->ezText ("<b>".lang_string ('monitors')."</b>", 12);
					$pdf->ezText ("\n", 3);
					$pdf->ezTable ($table->data, $table->head, "", $table_options);
				}
			}
			
			break;
		}
		$pdf->ezText ("\n", 8);
	}
	
	// End report
	doPageNumbering ($pdf);
	$pdf->ezStream ();
}
?>
