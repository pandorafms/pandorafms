<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Please see http://pandora.sourceforge.net for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License (LGPL)
// as published by the Free Software Foundation for version 2.
// 
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.

/**
 * @package Include
 */

/**#@+
 * Includes for access to DB
 */
require_once ('../include/config.php');
require_once ($config["homedir"].'/include/functions.php');
require_once ($config["homedir"].'/include/functions_db.php');
require 'ezc/Base/ezc_bootstrap.php';
/**#@-*/

/**
 * @package Include
 */
class customPalette extends ezcGraphPalette
{
        protected $dataSetColor = array(
	'#8866ff',
	'#a613ca',
	'#b923da',
	'#dd33cf',
	'#66aaff',
	'#54a6aa',
	'#d3474a',
	'#ff9900',
	'#49aa00',
	'#55bb34',
	'#119900',
	'#b3474a',
	'#ffbb99',
	'#cf9930',
	'#c5bb34',
	'#ecd9df',
	'#dd1f4a',
        );
	protected $dataSetSymbol = array(
	ezcGraph::BULLET,
	);
	protected $fontName = '';
	protected $fontColor = '#000000';
}

if ($_GET["tipo"]= "group_events") {
		grafico_eventos_grupo();
}

function grafico_eventos_grupo () {
	global $config;
	$data = array();
	$legend = array();
	//This will give the distinct id_agente, give the id_grupo that goes
	//with it and then the number of times it occured. GROUP BY statement
	//is required if both DISTINCT() and COUNT() are in the statement 
	$sql = "SELECT DISTINCT(id_agente) AS id_agente, id_grupo, COUNT(id_agente) AS count FROM tevento WHERE 1=1 GROUP BY id_agente ORDER BY count DESC"; 
	$result = get_db_all_rows_sql ($sql);

	foreach ($result as $row) {
		$data[] = $row["count"];
		if ($row["id_agente"] == 0) {
				//System event
			$legend[] = "SYSTEM";
		} else {
			//Other events
			$legend[] = substr (get_agent_name ($row["id_agente"], "lower"), 0, 15);
		}
		$comb = array_combine ($legend, $data);
	}

	$max_items = 6; //Maximum items on the piegraph
	while (count($data) > $max_items) {
		//Pops an element off the array until the array is small enough
		array_pop ($data);
	}
	$chart = new ezcGraphPieChart();
	$chart->renderer = new ezcGraphRenderer3d();
	$chart->driver = new ezcGraphGdDriver();
	$chart->palette = new customPalette();
	$chart->options->label = '%2$d (%3$.1f%%) ';
	$chart->legend->landscapeSize = .05;
	$chart->options->font = 'FreeSans.ttf';
	$chart->options->percentThreshold = 0.01;
	$chart->legend = true;
	$chart->legend->position = ezcGraph::RIGHT; 
	$chart->data['datos'] = new ezcGraphArrayDataSet($comb);
	$chart->data['datos']->highlight = true;
	$chart->data['datos']->highlight['SYSTEM'] = false;
	$chart->renderToOutput( 300, 200 );
}
?>
