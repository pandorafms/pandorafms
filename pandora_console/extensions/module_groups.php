<?php
/**
 * Pandora FMS- http://pandorafms.com
 * ==================================================
 * Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */
 
/**
 * Translate the array texts using gettext
 */
 function translate(&$item, $key) {
 	$item = __($item);
 }
 
/**
 * The main function of module groups and the enter point to
 * execute the code.
 */
function mainModuleGroups() {
	global $config; //the useful global var of Pandora Console, it has many data can you use
	
	//The big query
	$sql = 'SELECT SUM(t1.id_agente) AS count, t5.estado
		FROM tagente AS t1
			INNER JOIN tgrupo AS t2
				ON t1.id_grupo = t2.id_grupo
			INNER JOIN tagente_modulo AS t3
				ON t1.id_agente = t3.id_agente
			INNER JOIN tmodule_group AS t4
				ON t3.id_module_group = t4.id_mg
			INNER JOIN tagente_estado AS t5
				ON t1.id_agente = t5.id_agente
		WHERE t3.delete_pending IS FALSE AND  t3.disabled IS FALSE AND
			t2.id_grupo = %d AND t3.id_module_group = %d
		GROUP BY t5.estado';
	
	echo "<h1>" . __("Combine table of agent group and module group") . "</h1>";
	
	echo "<p>" . __("This table show in columns the modules group and for rows agents group. The cell show all modules") . "</p>";
	
	
	$agentGroups = get_user_groups ($config['id_user']);
	$modelGroups = get_all_model_groups();
	array_walk($modelGroups, 'translate'); //Translate all head titles to language is set
	
	$head = $modelGroups;
	array_unshift($head, '&nbsp;');
	
	//Metaobject use in print_table
	$table = null;
	$table->align[0] = 'right'; //Align to right the first column.
	$table->style[0] = 'color: #ffffff; background-color: #778866; font-weight: bolder;';
	$table->head = $head;
	
	//The content of table
	$tableData = array();
	//Create rows and celds
	foreach ($agentGroups as $idAgentGroup => $name) {
		
		$row = array();
		
		array_push($row, $name);
		
		foreach ($modelGroups as $idModelGroup => $modelGroup) {
			$query = sprintf($sql,$idAgentGroup, $idModelGroup);
			$rowsDB = get_db_all_rows_sql ($query);
			
			$states = array();
			if ($rowsDB !== false) {
				foreach ($rowsDB as $rowDB) {
					$states[$rowDB['estado']] = $rowDB['count'];	
				}
			}
			
			$count = 0;
			foreach ($states as $idState => $state) {
				$count = $state;
			}
			
			$color = 'transparent'; //Defaut color for cell
			if ($count == 0) {
				$color = '#babdb6'; //Grey when the cell for this model group and agent group hasn't modules.
			}
			else {
				if (array_key_exists(0,$states) && (count($states) == 1))
					$color = '#8ae234'; //Green when the cell for this model group and agent has OK state all modules.
				else {
					if (array_key_exists(1,$states))
						$color = '#cc0000'; //Red when the cell for this model group and agent has at least one module in critical state and the rest in any state.
					else
						$color = '#fce94f'; //Yellow when the cell for this model group and agent has at least one in warning state and the rest in green state.
				}
			}
			
			array_push($row,
				'<div
					style="background: ' . $color . ' ;
						height: 15px;
						margin-left: auto; margin-right: auto;
						text-align: center; padding-top: 5px;">
					' . $count . ' modules</div>');
		}
		array_push($tableData,$row);
	}
	$table->data = $tableData;
	
	print_table($table);
	
	echo "<p>" . __("The colours meaning:") .
		"<ul>" .
		'<li style="clear: both;">
			<div style="float: left; background: #babdb6; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' .
			__("Grey when the cell for this model group and agent group hasn't modules.") . "</li>" .
		'<li style="clear: both;">
			<div style="float: left; background: #8ae234; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' . 
			__("Green when the cell for this model group and agent has OK state all modules.") . "</li>" .
		'<li style="clear: both;"><div style="float: left; background: #cc0000; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' . 
			__("Red when the cell for this model group and agent has at least one module in critical state and the rest in any state.") . "</li>" .
		'<li style="clear: both;"><div style="float: left; background: #fce94f; height: 20px; width: 80px;margin-right: 5px; margin-bottom: 5px;">&nbsp;</div>' . 
			__("Yellow when the cell for this model group and agent has at least one in warning state and the rest in green state.") . "</li>" .
		"</ul>" .
		"</p>";
}
 
add_operation_menu_option("Modules groups", 'estado', 'module_groups/icon_menu.png');
add_extension_main_function('mainModuleGroups');
?>
