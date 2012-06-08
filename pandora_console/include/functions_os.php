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
	
	//!!!Query explanation!!!
	//An agent is OK if all its modules are OK
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MAX value for status which has the value 0 
	//If MAX(estado) is 0 it means all modules has status 0 => OK
	//Then we count the agents of the group selected to know how many agents are in OK status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(max_estado) FROM (SELECT MAX(tagente_estado.estado) as max_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_os = $id_os GROUP BY tagente.id_agente HAVING max_estado = 0) AS S1");
}

// Get warning agents by using the status code in modules.

function os_agents_warning ($id_os) {
	
	//!!!Query explanation!!!
	//An agent is Warning when has at least one module in warning status and nothing more in critical status
	//The status values are: 0 OK; 1 Critical; 2 Warning; 3 Unkown
	//This query grouped all modules by agents and select the MIN value for status which has the value 0 
	//If MIN(estado) is 2 it means at least one module is warning and there is no critical modules
	//Then we count the agents of the group selected to know how many agents are in warning status
	
	//TODO REVIEW ORACLE AND POSTGRES
	
	return db_get_sql ("SELECT COUNT(min_estado) FROM (SELECT MIN(tagente_estado.estado) as min_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente.id_os = $id_os GROUP BY tagente.id_agente HAVING min_estado = 2) AS S1");
	
}

// Get unknown agents by using the status code in modules.

function os_agents_unknown ($id_os) {
	
	//TODO REVIEW ORACLE AND POSTGRES
		
	return db_get_sql ("SELECT COUNT(min_estado) FROM (SELECT MIN(tagente_estado.estado) as min_estado FROM tagente_estado, tagente, tagente_modulo WHERE tagente.disabled = 0 AND tagente_estado.utimestamp != 0 AND tagente_modulo.id_agente_modulo = tagente_estado.id_agente_modulo AND tagente_modulo.disabled = 0 AND tagente_estado.id_agente = tagente.id_agente AND tagente_estado.estado != 0 AND tagente.id_os = $id_os GROUP BY tagente.id_agente HAVING min_estado = 3) AS S1");
	
}

?>
