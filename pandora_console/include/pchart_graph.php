<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2009 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.



require_once ('pChart/pData.class');
require_once ('pChart/pChart.class');

class PchartGraph extends PandoraGraphAbstract {
	public $palette_path = false;
	private $graph = NULL;
	private $dataset = NULL;
	private $x1;
	private $x2;
	private $y1;
	private $y2;
	
	public function load_palette ($palette_path) {
		$this->palette_path = $palette_path;
	}
	
	public function pie_graph () {
		// dataset definition
		$this->dataset = new pData;
		$this->dataset->AddPoint ($this->data, "Serie1", $this->legend);
		$this->dataset->AddPoint ($this->legend, "Serie2");
		$this->dataset->AddAllSeries ();
		$this->dataset->SetAbsciseLabelSerie ("Serie2");
		
		// Initialise the graph
		$this->graph = new pChart ($this->width, $this->height);
		$this->graph->setFontProperties ($this->fontpath, 8);
		if ($this->palette_path) {
			$this->graph->loadColorPalette ($this->palette_path);
		}
		$this->add_background ();
		
		// Draw the pie chart
		if ($this->three_dimensions) {
			$this->graph->drawPieGraph ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				$this->width / 2,
				$this->height / 2 - 15, $this->zoom,
				PIE_PERCENTAGE_LABEL, 5, 70, 20, 5);
		} else {
			$this->graph->drawFlatPieGraph ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				$this->width / 2,
				$this->height / 2, $this->zoom,
				PIE_PERCENTAGE_LABEL, 5);
		}
		
		if ($this->show_legend) {
			$this->graph->drawPieLegend (10, 10, $this->dataset->GetData (),
				$this->dataset->GetDataDescription (), 255, 255, 255);
		}
		
		$this->graph->Stroke ();
	}
	
	public function horizontal_bar_graph () {
		// dataset definition 
		$this->dataset = new pData;
		foreach ($this->data as $x => $y) {
			$this->dataset->AddPoint ($y, "Serie1", $x);
		}
		$this->dataset->AddAllSeries ();
		$this->dataset->SetXAxisFormat ("label");
		$this->dataset->SetYAxisFormat ($this->yaxis_format);

		// Initialise the graph
		$this->graph = new pChart ($this->width, $this->height);
		if ($this->palette_path) {
			$this->graph->loadColorPalette ($this->palette_path);
		}
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->add_background ();
		$this->graph->drawGraphArea (255, 255, 255, true);
		if ($this->show_grid)
			$this->graph->drawGrid (4, true, 230, 230, 230, 50);
		
		// Draw the bar graph
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->graph->setFixedScale (0, max ($this->data));
		$this->graph->drawOverlayBarGraphH ($this->dataset->GetData (),
			$this->dataset->GetDataDescription (), 50);
		
		// Finish the graph
		$this->add_legend ();
		
		$this->graph->Stroke ();
	}
	
	public function single_graph () {
		// Dataset definition
		$this->dataset = new pData;
		$this->graph = new pChart ($this->width, $this->height);
		
		foreach ($this->data as $x => $y) {
			$this->dataset->AddPoint ($y, "Serie1", $x);
		}
		$color = $this->get_rgb_values ($this->graph_color[2]);
		$this->graph->setColorPalette (0, $color['r'], $color['g'], $color['b']);
		$this->dataset->AddAllSeries ();
		if ($this->legend !== false)
			$this->dataset->SetSerieName ($this->legend[0], "Serie1");
		
		if ($this->palette_path)
			$this->graph->loadColorPalette ($this->palette_path);
		
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->add_background ();
		$this->dataset->SetXAxisFormat ($this->xaxis_format);
		$this->dataset->SetYAxisFormat ($this->yaxis_format);
		$this->graph->drawGraphArea (255, 255, 255, true);
		
		if ($this->max_value == 0 || $this->max_value == 1)
			$this->graph->setFixedScale (0, 1, 1);
		
		$this->graph->drawScale ($this->dataset->GetData (),
			$this->dataset->GetDataDescription (),
			SCALE_START0, 80, 80, 80, $this->show_axis,
			0, 0, false,
			$this->xaxis_interval);
		
		if ($this->show_grid)
			$this->graph->drawGrid (4, false, 0, 0, 0);
		if ($this->max_value > 0) {
			// Draw the graph
			$this->graph->drawFilledLineGraph ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				50, true);
		}
		
		// Finish the graph
		$this->add_legend ();
		$this->add_events ();
		$this->add_alert_levels ();
		
		$this->graph->Stroke ();
	}
	
	public function sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name) {
		// Dataset definition
		$this->dataset = new pData;
		$this->graph = new pChart ($this->width, $this->height);
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->legend = array ();
		
		if ($avg_only) {
			foreach ($this->data as $data) {
				$this->dataset->AddPoint ($data['sum'], "AVG", $data['timestamp_bottom']);
			}
			$color = $this->get_rgb_values ($this->graph_color[2]);
			$this->graph->setColorPalette (0, $color['r'], $color['g'], $color['b']);
		} else {
			foreach ($this->data as $data) {
				$this->dataset->AddPoint ($data['sum'], "AVG", $data['timestamp_bottom']);
				$this->dataset->AddPoint ($data['min'], "MIN");
				$this->dataset->AddPoint ($data['max'], "MAX");
			}
			$this->legend[1] = __("Min. Value");
			$this->legend[0] = __("Avg. Value");
			$this->legend[2] = __("Max. Value");
			$this->dataset->SetSerieName (__("Min. Value"), "MIN");
			$this->dataset->SetSerieName (__("Avg. Value"), "AVG");
			$this->dataset->SetSerieName (__("Max. Value"), "MAX");
			$this->set_colors ();
		}
		$this->dataset->SetXAxisFormat ('date');
		$this->graph->setDateFormat ("Y");
		$this->dataset->SetYAxisFormat ('metric');
		$this->dataset->AddAllSeries ();
		$this->dataset->SetSerieName (__("Avg. Value"), "AVG");
		$this->legend[0] = __("Avg. Value");
		
		if ($this->palette_path) {
			$this->graph->loadColorPalette ($this->palette_path);
		}
		
		$this->add_background ();
		$this->graph->drawGraphArea (255, 255, 255, true);
		
		$this->xaxis_interval = ($this->xaxis_interval / 7 >= 1) ? ($this->xaxis_interval / 7) : 10;
		$this->graph->drawScale ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (), SCALE_START0,
				80, 80, 80, $this->show_axis, 0, 0, false,
				$this->xaxis_interval);
		/* NOTICE: The final "false" is a Pandora modificaton of pChart to avoid showing vertical lines. */
		if ($this->show_grid)
			$this->graph->drawGrid (1, true, 225, 225, 225, 100, false);
		// Draw the graph
		$this->graph->drawFilledLineGraph ($this->dataset->GetData(), $this->dataset->GetDataDescription(), 50, true);
		
		$this->add_legend ();
		$this->add_events ("AVG");
		$this->add_alert_levels ();
		
		$this->graph->Stroke ();
	}
	
	public function vertical_bar_graph () {
		// dataset definition 
		$this->dataset = new pData;
		foreach ($this->data as $x => $y) {
			$this->dataset->AddPoint ($y, "Serie1", $x);
		}
		$this->dataset->AddAllSeries ();
		$this->dataset->SetAbsciseLabelSerie ();
		$this->dataset->SetXAxisFormat ($this->xaxis_format);
		$this->dataset->SetYAxisFormat ($this->yaxis_format);
		
		// Initialise the graph
		$this->graph = new pChart ($this->width, $this->height);
		if ($this->palette_path) {
			$this->graph->loadColorPalette ($this->palette_path);
		}
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->add_background ();
		$this->graph->drawGraphArea (255, 255, 255, true);
		if ($this->show_grid)
			$this->graph->drawGrid (4, true, 230, 230, 230, 50);

		// Draw the bar graph
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->graph->drawScale ($this->dataset->GetData (),
					$this->dataset->GetDataDescription (),
					SCALE_START0, 80, 80, 80,
					$this->show_axis, 0, 0, false,
					$this->xaxis_interval);
		$this->graph->drawOverlayBarGraph ($this->dataset->GetData (),
						$this->dataset->GetDataDescription (),
						50);
		$this->add_events ("Serie1");
		$this->add_alert_levels ();
		
		// Finish the graph
		$this->graph->Stroke ();
	}
	
	public function combined_graph ($values, $events, $alerts, $unit_name, $max_value, $stacked) {
		set_time_limit (0);
		// Dataset definition
		$this->dataset = new pData;
		$this->graph = new pChart ($this->width, $this->height);
		
		foreach ($this->data as $i => $data) {
			foreach ($data as $j => $value) {
				$this->dataset->AddPoint ($value, $this->legend[$i],
					$values[$j]['timestamp_bottom']);
			}
		}
		
		foreach ($this->legend as $name) {
			$this->dataset->setSerieName ($name, $name);
			$this->dataset->AddSerie ($name);
		}
		
		$this->set_colors ();
		$this->graph->setFontProperties ($this->fontpath, 8);
		$this->dataset->SetXAxisFormat ('date');
		$this->dataset->SetYAxisFormat ('metric');
		$this->dataset->AddAllSeries ();
		$this->add_background ();
		$this->graph->drawGraphArea (255, 255, 255, true);
		
		// Draw the graph
		if ($stacked == 1) {
			$this->graph->drawScale ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				SCALE_ADDALL, 80, 80, 80, $this->show_axis,
				0, 0, false,
				$this->xaxis_interval);
			/* Stacked mode are only supported in bar charts */
			//$this->graph->DivisionWidth = ($this->x2 - $this->x1) / sizeof ($this->data[0]);
			$this->graph->drawStackedBarGraph ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				50);
		} else if ($stacked == 2) {
			$this->graph->drawScale ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				SCALE_START0, 80, 80, 80, $this->show_axis, 0, 0, false,
				$this->xaxis_interval);
			$this->graph->drawLineGraph ($this->dataset->GetData (),
				$this->dataset->GetDataDescription ());
		} else {
			$this->graph->drawScale ($this->dataset->GetData (),
				$this->dataset->GetDataDescription (),
				SCALE_START0, 80, 80, 80, $this->show_axis, 0, 0, false,
				$this->xaxis_interval);
			$this->graph->drawFilledCubicCurve ($this->dataset->GetData(),
				$this->dataset->GetDataDescription(), 0.1, 50, true);
		}
		$this->add_legend ();
		$this->add_events ($this->legend[0]);
		$this->add_alert_levels ();
		
		$this->graph->Stroke ();
	}
	
	public function progress_bar ($value, $color) {
		set_time_limit (0);
		
		// Dataset definition
		$this->graph = new pChart ($this->width, $this->height);
		$this->graph->setFontProperties ($this->fontpath, 8);

		// Round corners defined in global setup

		global $config;
		if ($config["round_corner"] != 0)
			$radius = ($this->height > 18) ? 8 : 0;
		else
			$radius = 0;

		$ratio = (int) $value / 100 * $this->width;
		
		/* Color stuff */
		$bgcolor = $this->get_rgb_values ($this->background_color);
		$r = hexdec (substr ($this->background_color, 1, 2));
		$g = hexdec (substr ($this->background_color, 3, 2));
		$b = hexdec (substr ($this->background_color, 5, 2));
		
		/* Actual percentage */
		if (! $this->show_title || $value > 0) {
			$color = $this->get_rgb_values ($color);
			$this->graph->drawFilledRoundedRectangle (0, 0, $ratio, 
				$this->height, $radius, $color['r'], $color['g'], $color['b']);
		}
		
		if ($config["round_corner"]) {
			/* Under this value, the rounded rectangle is painted great */
			if ($ratio <= 16) {
				/* Clean a bit of pixels */
				for ($i = 0; $i < 7; $i++) {
					$this->graph->drawLine (0, $i, 6 - $i, $i, 255, 255, 255);
				}
				$end = $this->height - 1;
				for ($i = 0; $i < 7; $i++) {
					$this->graph->drawLine (0, $end - $i, 5 - $i, $end - $i, 255, 255, 255);
				}
			}
		}
		
		if ($ratio <= 60) {
			if ($this->show_title) {
				$this->graph->drawTextBox (0, 0, $this->width, $this->height,
					$this->title, 0, 0, 0, 0, ALIGN_CENTER, false);
			}
		} else {
			if ($this->show_title) {
				$this->graph->drawTextBox (0, 0, $this->width, $this->height,
					$this->title, 0, 255, 255, 255, ALIGN_CENTER, false);
			}
		}
				
		if ($this->border) {
			$this->graph->drawRoundedRectangle (0, 0, $this->width - 1,
				$this->height - 1,
				$radius, 157, 157, 157);
		}
		
		$this->graph->Stroke ();
	}
	
	/* Gets an array with each the components of a RGB string */
	private static function get_rgb_values ($rgb_string) {
		$color = array ('r' => 0, 'g' => 0, 'b' => 0);
		$offset = 0;
		if ($rgb_string[0] == '#')
			$offset = 1;
		$color['r'] = hexdec (substr ($rgb_string, $offset, 2));
		$color['g'] = hexdec (substr ($rgb_string, $offset + 2, 2));
		$color['b'] = hexdec (substr ($rgb_string, $offset + 4, 2));
		return $color;
	}
	
	private function add_alert_levels () {
		if ($this->alert_top !== false) {
			$this->graph->drawTreshold ($this->alert_top, 57,
				96, 255, true, true, 4,
				"Alert top");
		}
		if ($this->alert_bottom !== false) {
			$this->graph->drawTreshold ($this->alert_bottom, 7,
				96, 255, true, true, 4,
				"Alert bottom");
		}
	}
	
	private function add_events ($serie = "Serie1") {
		if (! $this->events)
			return;
		
		/* Unfortunatelly, the events must be draw manually */
		
		$first = $this->dataset->Data[0]["Name"];
		$len = count ($this->dataset->Data) - 1;
		
		$last = $this->dataset->Data[$len]["Name"];
		$ylen = $this->y2 - $this->y1;
		
		foreach ($this->data as $i => $data) {
			/* Finally, check if there were events */
		
			if (! $data['events'])
				continue;
			
			$x1 = (int) ($this->x1 + $i * $this->graph->DivisionWidth);
			$y1 = (int) ($this->y2 - ($this->dataset->Data[$i][$serie] * $this->graph->DivisionRatio));
			$this->graph->drawFilledCircle ($x1, $y1, 1.5, 255, 0, 0);
			if ($y1 == $this->y2)
				/* Lines in the same dot fails */
				continue;
			
			$this->graph->drawDottedLine ($x1 - 1, $y1,
				$x1 - 1, $this->y2,
				5, 255, 150, 150);
		}
	}
	
	private function add_background () {
		if ($this->graph == NULL)
			return;
		
		$this->graph->setDateFormat ($this->date_format);
		
		$this->x1 = ($this->width > 300) ? 30 : 35;
		$this->y1 = ($this->height > 200) ? 25 : 10;
		$this->x2 = ($this->width > 300) ? $this->width - 30 : $this->width - 15;
		$this->y2 = ($this->height > 200) ? $this->height - 25 : $this->height - 25;
		
		if ($this->max_value > 10000 && $this->show_axis)
			$this->x1 += 20;
		
		if ($this->background_gradient)
			$this->graph->drawGraphAreaGradient (233, 243, 210, 50, TARGET_BACKGROUND);
		else
			$this->graph->drawGraphArea (255, 255, 255, true);
		
		if ($this->show_title) {
			$this->y1 += 30;
		}
		
		$this->graph->setFontProperties ($this->fontpath, 6);
		$size = $this->graph->getLegendBoxSize ($this->dataset->GetDataDescription ());
		if (is_array ($size)) {
			while ($size[1] > $this->y1)
				$this->y1 += (int) $size[1] / 2;
			if ($this->y1 > $this->y2)
				$this->y1 = $this->y2;
		}
		
		$this->graph->setGraphArea ($this->x1, $this->y1, $this->x2, $this->y2);
		
		if ($this->show_title) {
			$this->graph->setFontProperties ($this->fontpath, 10);
			$this->graph->drawTextBox (0, 0, $this->width, 20, $this->title, 0,
				255, 255, 255, ALIGN_LEFT, true, 0, 0, 0, 30);
			
			$this->graph->setFontProperties ($this->fontpath, 9);
			$this->graph->drawTextBox (0, 20, $this->width, 40, $this->subtitle,
				0, 255, 255, 255, ALIGN_LEFT, true, 0, 0, 0, 0);
			$this->graph->setFontProperties ($this->fontpath, 8);
		}
		
		/* This is a tiny watermark. Remove safely */
		if ($this->watermark) {
			$this->graph->setFontProperties ($this->fontpath, 7);
			$this->graph->drawTextBox ($this->width - 5, $this->height - 0,
				$this->width - 240, $this->height - 0, 'Pandora FMS', 90,
				154, 154, 154, ALIGN_BOTTOM_LEFT, false);
		}
	}
	
	private function add_legend () {
		if (! $this->show_title || $this->legend === false) {
			return;
		}
		
		/* Add legend */
		$this->graph->setFontProperties ($this->fontpath, 6);
		$size = $this->graph->getLegendBoxSize ($this->dataset->GetDataDescription ());
		$this->graph->drawLegend ($this->width - $size[0] - 32, 5,
			$this->dataset->GetDataDescription (),
			240, 240, 240);
	}
	
	private function set_colors () {
		if ($this->graph == NULL)
			return;
		
		$color = $this->get_rgb_values ($this->graph_color[1]);
		$this->graph->setColorPalette (0, $color['r'], $color['g'], $color['b']);
		$color = $this->get_rgb_values ($this->graph_color[2]);
		$this->graph->setColorPalette (1, $color['r'], $color['g'], $color['b']);
		$color = $this->get_rgb_values ($this->graph_color[3]);
		$this->graph->setColorPalette (2, $color['r'], $color['g'], $color['b']);
	}
}
?>
