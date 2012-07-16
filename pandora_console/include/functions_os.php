<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

// Get critical agents by using the status code in modules.

function os_agents_critical ($id_os) {
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT( DISTINCT tagente_estado.id_agente) FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND estado = 1 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_os = $id_os");
}

// Get ok agents by using the status code in modules.

function os_agents_ok($id_os) {
		
	// Agent of OS X and critical status
	$agents_critical = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 1 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";		

	// Agent of OS X and warning status	
	$agents_warning = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 2 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";

	// Agent of OS X and unknown status		
	$agents_unknown = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 3
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";
						
	// Agent of OS X and ok status		
	$agents_ok = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 0
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os
						group by tagente.id_agente";
	
	// Agents without modules has a normal status					
	$void_agents = "SELECT id_agente FROM tagente 
						WHERE disabled = 0
						AND id_os = $id_os
						AND id_agente NOT IN (SELECT id_agente FROM tagente_estado)";		
						
	return db_get_sql ("SELECT COUNT(*) FROM ( SELECT DISTINCT tagente.id_agente
						FROM tagente, tagente_modulo, tagente_estado 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo

						AND tagente.id_os = $id_os 
						AND tagente.id_agente NOT IN ($agents_critical)
						AND tagente.id_agente NOT IN ($agents_warning)
						AND tagente.id_agente NOT IN ($agents_unknown)
						AND tagente.id_agente IN ($agents_ok)
						UNION $void_agents) AS t");	

}

// Get warning agents by using the status code in modules.

function os_agents_warning ($id_os) {
	
	// Agent of OS X and critical status
	$agents_critical = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 1 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";

	// Agent of OS X and warning status	
	$agents_warning = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 2 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";
	
	return db_get_sql ("SELECT COUNT(*) FROM ( SELECT DISTINCT tagente.id_agente
						FROM tagente, tagente_modulo, tagente_estado 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo

						AND tagente.id_os = $id_os
						AND tagente.id_agente NOT IN ($agents_critical)
						AND tagente.id_agente IN ($agents_warning) ) AS t");	
	
}

// Get unknown agents by using the status code in modules.

function os_agents_unknown ($id_os) {
	
	// Agent of module group X and critical status
	$agents_critical = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 1 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";		

	// Agent of module group X and warning status	
	$agents_warning = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 2 
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";

	// Agent of module group X and unknown status		
	$agents_unknown = "SELECT tagente.id_agente 
						FROM tagente_estado, tagente, tagente_modulo
						WHERE tagente_estado.id_agente = tagente.id_agente
						AND tagente_estado.id_agente_modulo = tagente_modulo.id_agente_modulo
						AND tagente.disabled = 0
						AND tagente_modulo.disabled = 0
						AND estado = 3
						AND tagente_estado.utimestamp != 0
						AND tagente.id_os = $id_os 
						group by tagente.id_agente";	

	return db_get_sql ("SELECT COUNT(*) FROM ( SELECT DISTINCT tagente.id_agente
						FROM tagente, tagente_modulo, tagente_estado 
						WHERE tagente.id_agente = tagente_modulo.id_agente
						AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo

						AND tagente.id_os = $id_os 
						AND tagente.id_agente NOT IN ($agents_critical)
						AND tagente.id_agente NOT IN ($agents_warning)
						AND tagente.id_agente IN ($agents_unknown) ) AS t");
	
}

?>
