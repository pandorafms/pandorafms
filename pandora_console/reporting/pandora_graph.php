<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
// Copyright (c) 2008 Esteban Sánchez, <estebans@artica.es>
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

abstract class PandoraGraphAbstract {
	public $width = 300;
	public $height = 200;
	public $data;
	public $legend = false;
	public $fontpath;
	public $three_dimensions = true;
	public $graph_color = array ();
	public $xaxis_interval = 1;
	public $yaxis_interval = 5;
	public $xaxis_format = 'numeric';
	public $yaxis_format = 'numeric';
	public $show_title = false;
	public $show_legend = false;
	public $background_gradient = false;
	public $title = "";
	public $subtitle = "";
	public $stacked = false;
	public $zoom = 85;
	public $events = false;
	public $alert_top = false;
	public $alert_bottom = false;
	public $date_format = "d/m";
	public $max_value = 0;
	public $min_value = 0;
	public $background_color = '#FFFFFF';
	public $border = true;
	public $watermark = true;
	public $show_axis = true;
	public $show_grid = true;
	
	abstract protected function pie_graph ();
	abstract protected function horizontal_bar_graph ();
	abstract protected function vertical_bar_graph ();
	abstract protected function sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name);
	abstract protected function single_graph ();
	abstract protected function combined_graph ($values, $events, $alerts, $unit_name, $max_value, $stacked);
	abstract protected function progress_bar ($value, $color);
}

function get_graph_engine ($period = 3600) {
	global $config;
	
	if (file_exists ('pchart_graph.php')) {
		require_once ('pchart_graph.php');
		$engine = new PchartGraph ();
		if (isset ($config['graphics_palette']))
			$engine->load_palette ($config['graphics_palette']);
	} else {
		exit;
	}
	$engine->graph_color[1] = $config['graph_color1'];
	$engine->graph_color[2] = $config['graph_color2'];
	$engine->graph_color[3] = $config['graph_color3'];
	
	if ($period <= 86400)
		$engine->date_format = 'g:iA';
	elseif ($period <= 604800)
		$engine->date_format = 'd/m';
	else
		$engine->date_format = 'd/m/y';
	
	return $engine;
}

?>
