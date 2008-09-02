<?php

// Pandora FMS - the Flexible Monitoring System
// ============================================
// Copyright (c) 2008 Artica Soluciones Tecnologicas, http://www.artica.es
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

require_once ('Image/Graph.php');

class PearImageGraph extends PandoraGraphAbstract {
	public function pie_graph () {
		/* Create the graph */
		$Graph =& Image_Graph::factory ('graph', array ($this->width,
								$this->height));
		
		/* add a TrueType font */
		$Font =& $Graph->addNew ('font', $this->fontpath);
		// set the font size to 7 pixels
		$Font->setSize (7);
		$Graph->setFont ($Font);
		
		if ($this->show_title) {
			$Graph->add (
			Image_Graph::vertical (
				Image_Graph::vertical (
							$Title = Image_Graph::factory('title', array ($this->title, 10)),
							$Subtitle = Image_Graph::factory('title', array ($this->subtitle, 7)),
							90
					),
				Image_Graph::horizontal(
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					$this->zoom
					),
				5)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else { // Pure, without title and legends
			$Graph->add($Plotarea = Image_Graph::factory('plotarea'));
		}
		
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset1 =& Image_Graph::factory ('dataset');
		$len = sizeof ($this->data);
		foreach ($this->data as $x => $y) {
			$Dataset1->addPoint ($x, $y);
		}
		$Plot =& $Plotarea->addNew ('pie', $Dataset1);
		$Plotarea->hideAxis ();
		// create a Y data value marker
		$Marker =& $Plot->addNew ('Image_Graph_Marker_Value', IMAGE_GRAPH_PCT_Y_TOTAL);
		// create a pin-point marker type
		$PointingMarker =& $Plot->addNew ('Image_Graph_Marker_Pointing_Angular', array (1, &$Marker));
		// and use the marker on the 1st plot
		$Plot->setMarker ($PointingMarker);
		// format value marker labels as percentage values
		$Marker->setDataPreprocessor (Image_Graph::factory ('Image_Graph_DataPreprocessor_Formatted', '%0.1f%%'));
		$Plot->Radius = 15;
		$FillArray =& Image_Graph::factory ('Image_Graph_Fill_Array');
		$Plot->setFillStyle ($FillArray);
	
		$FillArray->addColor ('green@0.7');
		$FillArray->addColor ('yellow@0.7');
		$FillArray->addColor ('red@0.7');
		$FillArray->addColor ('orange@0.7');
		$FillArray->addColor ('blue@0.7');
		$FillArray->addColor ('purple@0.7');
		$FillArray->addColor ('lightgreen@0.7');
		$FillArray->addColor ('lightblue@0.7');
		$FillArray->addColor ('lightred@0.7');
		$FillArray->addColor ('grey@0.6', 'rest');
		$Plot->explode (6);
		$Plot->setStartingAngle (0);
		// output the Graph
		$Graph->done ();
	}
	
	public function vertical_bar_graph () {
		$Graph =& Image_Graph::factory ('graph', array ($this->width,
								$this->height));
		
		if ($this->show_title) {
			// add a TrueType font
			$Font =& $Graph->addNew ('font', $this->fontpath);
			$Font->setSize (7);
			$Graph->setFont ($Font);
			
			$Graph->add (Image_Graph::vertical (
				Image_Graph::vertical (
							$Title = Image_Graph::factory ('title', array ($this->title, 10)),
							$Subtitle = Image_Graph::factory('title', array ($this->subtitle, 7)),
							90
					),
				Image_Graph::horizontal (
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					100
					),
				15)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else { // Pure, without title and legends
			$Graph->add ($Plotarea = Image_Graph::factory ('plotarea'));
		}
		
		// Create the dataset
		$Dataset1 =& Image_Graph::factory ('dataset');
		foreach ($this->data as $x => $y) {
			$Dataset1->addPoint ($x, $y);
		}
		$Plot =& $Plotarea->addNew ('bar', $Dataset1);
		$GridY2 =& $Plotarea->addNew ('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor ('gray');
		$GridY2->setFillColor ('lightgray@0.05');
		$Plot->setLineColor ('gray');
		$Plot->setFillColor ("#437722@0.70");
		$AxisX =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_X);
		$AxisY =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y);
		$AxisX->setLabelInterval ($this->xaxis_interval / 5);
		$Graph->done();
	}
	
	public function horizontal_bar_graph () {
		// create the graph
		$Graph =& Image_Graph::factory ('graph', array ($this->width,
								$this->height));
		// add a TrueType font
		$Font =& $Graph->addNew ('font', $this->fontpath);
		$Font->setSize (9);
		$Graph->setFont ($Font);
		
		if ($this->show_title) {
			$Graph->add (
				Image_Graph::vertical (
					Image_Graph::vertical (
								$Title = Image_Graph::factory ('title', array ($this->title, 10)),
								$Subtitle = Image_Graph::factory ('title', array ($this->subtitle, 7)),
								90
						),
					Image_Graph::vertical (
						$Plotarea = Image_Graph::factory ('plotarea'),
						$Legend = Image_Graph::factory ('legend'),
						80
						),
				20)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else {
			$Graph->add (
				Image_Graph::vertical (
					$Plotarea = Image_Graph::factory ('plotarea', 
								array ('category',
									'axis',
									'horizontal')),
					$Legend = Image_Graph::factory ('legend'),
					85
					)
			);
		}
		
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset1 =& Image_Graph::factory ('dataset');
		foreach ($this->data as $x => $y) {
			$Dataset1->addPoint ($x, $y);
		}
		$Plot =& $Plotarea->addNew ('bar', $Dataset1);
		$GridY2 =& $Plotarea->addNew ('bar_grid',
						IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor ('gray');
		$GridY2->setFillColor ('lightgray@0.05');
		$Plot->setLineColor ('gray');
		$Plot->setFillColor ('blue@0.85');
		$Graph->done (); 
	}
	
	public function sparse_graph ($period, $avg_only, $min_value, $max_value, $unit_name) {
		$Graph =& Image_Graph::factory ('graph', array ($this->width, $this->height));
		// add a TrueType font
		$Font =& $Graph->addNew ('font', $this->fontpath);
		$Font->setSize (6);
		$Graph->setFont ($Font);
		
		if ($this->show_title) {
			$Graph->add (
			Image_Graph::vertical (
				Image_Graph::vertical (
							$Title = Image_Graph::factory('title', array ($this->title, 10)),
							$Subtitle = Image_Graph::factory('title', array ($this->subtitle, 7)),
							90
					),
				Image_Graph::horizontal (
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					90
					),
				15)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else {
			$Graph->add($Plotarea = Image_Graph::factory('plotarea'));
		}
		
		// Create the dataset
		// Merge data into a dataset object (sancho)
		// $Dataset =& Image_Graph::factory('dataset');
		if ($avg_only == 1) {
			$dataset[0] = Image_Graph::factory('dataset');
			$dataset[0]->setName("Avg.");
		} else {
			$dataset[0] = Image_Graph::factory('dataset');
			$dataset[0]->setName("Max.");
			$dataset[1] = Image_Graph::factory('dataset');
			$dataset[1]->setName("Avg.");
			$dataset[2] = Image_Graph::factory('dataset');
			$dataset[2]->setName("Min.");
		}
		
		$show_events = false;
		if (is_array ($this->events)) {
			$show_events = true;
			$dataset_event = Image_Graph::factory('dataset');
			$dataset_event -> setName(__("Event Fired"));
		}
		
		// ... and populated with data ...
		for ($i = 0; $i <= $this->xaxis_interval; $i++) {
			$t1 = (int) $this->data[$i][2];
			$tdate = date ($this->dateformat, $t1);
			if ($avg_only == 0) {
				$dataset[0]->addPoint ($tdate, $this->data[$i][5]);
				$dataset[1]->addPoint ($tdate, $this->data[$i][0]);
				$dataset[2]->addPoint ($tdate, $this->data[$i][4]);
			} else {
				$dataset[0]->addPoint ($tdate, $this->data[$i][0]);
			}
			if ($show_events) {
				if (! isset ($this->data[$i + 1]))
					continue;
				$t2 = (int) $this->data[$i + 1][2];
				for ($j = $t1; $j < $t2; $j++) {
					if (isset ($this->events[$j])) {
						$dataset_event->addPoint ($tdate,
									$this->data[$i][0]);
						break;
					}
				}
			}
		}
		// Show alert limits
		if ($this->alert_top !== false) {
			$Plot =& $Plotarea->addNew ('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor ('blue@0.2');
			$Plot->setUpperBound ($this->alert_top);
		}
		if ($this->alert_bottom !== false) {
			$Plot =& $Plotarea->addNew ('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor ('blue@0.2');
			$Plot->setLowerBound ($this->alert_bottom);
		}

		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew ('area', array(&$dataset));
		if ($avg_only == 1) {
			$Plot->setLineColor ('black@0.1');
		} else {
			$Plot->setLineColor ('yellow@0.2');
		}

		$AxisX =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_X);
	
		$AxisY =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y);
		$AxisY->setDataPreprocessor (Image_Graph::factory ('Image_Graph_DataPreprocessor_Function', 'format_for_graph'));
		$AxisY->setLabelOption ("showtext", true);
		$yinterval = $this->height / 30;

		if (($min_value < 0) && ($max_value > 0))
			$AxisY->setLabelInterval( -1 * ceil (($min_value - $max_value) / $yinterval ));
		elseif ($min_value < 0)
			$AxisY->setLabelInterval( -1 * ceil ($min_value / $yinterval));
		else
			$AxisY->setLabelInterval(ceil($max_value / $yinterval));

		$AxisY->showLabel(IMAGE_GRAPH_LABEL_ZERO);
		if ($unit_name != "") {
			$AxisY->setTitle ($unit_name, 'vertical');
			if ($period < 10000)
				$xinterval = 8;
		} else {
			$xinterval = $this->xaxis_interval / 7 ;
		}
		
		$AxisX->setLabelInterval ($xinterval) ;
		
		//$AxisY->forceMinimum($minvalue);
		$AxisY->forceMaximum ($max_value + ($max_value / 12)) ;
		$GridY2 =& $Plotarea->addNew ('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor ('gray');
		$GridY2->setFillColor ('lightgray@0.05');
		
		// set line colors
		$FillArray =& Image_Graph::factory ('Image_Graph_Fill_Array');
		
		$Plot->setFillStyle ($FillArray);
		if ($avg_only == 1){
			$FillArray->addColor ($this->graph_color[2]);
		} else {
			$FillArray->addColor ($this->graph_color[1]); 
			$FillArray->addColor ($this->graph_color[3]); 
			$FillArray->addColor ($this->graph_color[2]);
		}
		$AxisY_Weather =& $Plotarea->getAxis(IMAGE_GRAPH_AXIS_Y);

		// Show events !
		if ($show_events) {
			$Plot =& $Plotarea->addNew ('Plot_Impulse', array ($dataset_event));
			$Plot->setLineColor ('red');
			$Marker_event =& Image_Graph::factory ('Image_Graph_Marker_Circle');
			$Plot->setMarker ($Marker_event);
			$Marker_event->setFillColor ('red@0.5');
			$Marker_event->setLineColor ('red@0.5');
			$Marker_event->setSize (2);
		}
		
		$Graph->done();
	}
	
	public function single_graph () {
		// Create graph
		$Graph =& Image_Graph::factory ('graph', array ($this->width, $this->height));
		// add a TrueType font
		$Font =& $Graph->addNew ('font', $this->fontpath);
		$Font->setSize (6);
		$Graph->setFont ($Font);
		
		if ($this->show_title) {
			$Graph->add (
			Image_Graph::vertical (
				Image_Graph::vertical (
							$Title = Image_Graph::factory('title', array ($this->title, 10)),
							$Subtitle = Image_Graph::factory('title', array ($this->subtitle, 7)),
							90
					),
				Image_Graph::horizontal(
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					85
					),
				25)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else { // Pure, without title and legends
			$Graph->add($Plotarea = Image_Graph::factory('plotarea'));
		}
		
		// Create the dataset
		// Merge data into a dataset object (sancho)
		$Dataset =& Image_Graph::factory ('dataset');
		
		$show_events = false;
		if (is_array ($this->events)) {
			$show_events = true;
			$dataset_event = Image_Graph::factory('dataset');
			$dataset_event -> setName(__("Event Fired"));
		}
		
		$prev_x = 0;
		foreach ($this->data as $x => $y) {
			if ($this->xaxis_format == 'date') {
				$xval = date ($this->dateformat, $x);
			} else {
				$xval = $x;
			}
			
			$Dataset->addPoint ($xval, $y);
			if ($show_events) {
				if (! $prev_x) {
					$prev_x = $x;
					continue;
				}
				for ($j = $prev_x; $j < $x; $j++) {
					if (isset ($this->events[$j])) {
						$dataset_event->addPoint ($xval,
									$y);
						break;
					}
				}
				$prev_x = $x;
			}
		}
		
		// Show alert limits 
		if ($this->alert_top !== false) {
			$Plot =& $Plotarea->addNew ('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor ('blue@0.2');
			$Plot->setUpperBound ($this->alert_top);
		}
		if ($this->alert_bottom !== false) {
			$Plot =& $Plotarea->addNew ('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor ('blue@0.2');
			$Plot->setLowerBound ($this->alert_bottom);
		}
		// create the 1st plot as smoothed area chart using the 1st dataset
		$Plot =& $Plotarea->addNew ('area', array (&$Dataset));
		// set a line color
		$Plot->setLineColor ('green');
		// set a standard fill style
		$Plot->setFillColor ('green@0.5');
		
		$AxisX =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_X);
		$AxisX->setLabelInterval ($this->xaxis_interval);
		$AxisY =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y);
		$AxisY->setLabelInterval ($this->yaxis_interval);
		$AxisY->setLabelOption ("showtext", true);

		$GridY2 =& $Plotarea->addNew ('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor ('gray');
		$GridY2->setFillColor ('lightgray@0.05');
		$AxisY2 =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y_SECONDARY);
		
		if ($show_events) {
			$Plot =& $Plotarea->addNew ('Plot_Impulse', array ($dataset_event));
			$Plot->setLineColor ('red');
			$Marker_event =& Image_Graph::factory ('Image_Graph_Marker_Circle');
			$Plot->setMarker ($Marker_event);
			$Marker_event->setFillColor ('red@0.5');
			$Marker_event->setLineColor ('red@0.5');
			$Marker_event->setSize (2);
		}
		
		$Graph->done ();
	}
	
	public function combined_graph ($values, $events, $alerts, $unit_name, $max_value, $stacked) {
		// Create graph
		$Graph =& Image_Graph::factory ('graph', array ($this->width, $this->height));
		$Font =& $Graph->addNew ('font', $this->fontpath);
		$Font->setSize (7);
		$Graph->setFont ($Font);
		if ($this->show_title) {
			$Graph->add (
			Image_Graph::vertical (
				Image_Graph::vertical (
							$Title = Image_Graph::factory ('title', array ($this->title, 10)),
							$Subtitle = Image_Graph::factory ('title', array ($this->subtitle, 7)),
							90
					),
				Image_Graph::vertical (
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					80
					),
				20)
			);
			$Legend->setPlotarea ($Plotarea);
			$Title->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
			$Subtitle->setAlignment (IMAGE_GRAPH_ALIGN_LEFT);
		} else {
			$Graph->add (
				Image_Graph::vertical (
					$Plotarea = Image_Graph::factory ('plotarea'),
					$Legend = Image_Graph::factory ('legend'),
					85
					)
			);
			$Legend->setPlotarea ($Plotarea);
		}
	
		// Create the dataset
		// Merge data into a dataset object (sancho)
		// $Dataset =& Image_Graph::factory('dataset');
		$len = sizeof ($this->data);
		for ($i = 0; $i < $len; $i++) {
			$dataset[$i] = Image_Graph::factory ('dataset');
			$dataset[$i] -> setName ($this->legend[$i]);
		}
		$show_event = false;
		if (is_array ($events)) {
			$show_event = true;
			$dataset_event = Image_Graph::factory ('dataset');
			$dataset_event->setName ("Event Fired");
		}
		
		// ... and populated with data ...
		for ($i = 0; $i < $this->xaxis_interval; $i++) {
			$date = date($this->dateformat, $values[$i][2]);
			for ($j = 0; $j < $len; $j++) {
				$dataset[$j]->addPoint ($date, $this->data[$j][$i]);
				if (($show_event) && (isset ($event[$i]))) {
					$dataset_event->addPoint ($date, $max_value);
				}
			}
		}
		
		// Show events !
		if ($show_event) {
			$Plot =& $Plotarea->addNew ('Plot_Impulse', array ($dataset_event));
			$Plot->setLineColor ('black');
			$Marker_event =& Image_Graph::factory ('Image_Graph_Marker_Cross');
			$Plot->setMarker ($Marker_event);
			$Marker_event->setFillColor ('red');
			$Marker_event->setLineColor ('red');
			$Marker_event->setSize (5);
		}
	
		// Show limits (for alert or whathever you want...
		if (is_array ($alerts)) {
			$Plot =& $Plotarea->addNew('Image_Graph_Axis_Marker_Area', IMAGE_GRAPH_AXIS_Y);
			$Plot->setFillColor ('blue@0.1');
			$Plot->setLowerBound ($alerts['low']);
			$Plot->setUpperBound ($alerts['high']);
		}
	

		// create the 1st plot as smoothed area chart using the 1st dataset
		if ($stacked == 0) {
			// Non-stacked
			$Plot =& $Plotarea->addNew ('area', array (&$dataset));
		} elseif ($stacked == 1) {
			// Stacked (> 2.0)
			$Plot =& $Plotarea->addNew ('Image_Graph_Plot_Area', array (&$dataset, 'stacked'));
		} else {
			$color_array[0] = "red";
			$color_array[1] = "blue";
			$color_array[2] = "green";
			$color_array[3] = 'yellow'; // yellow
			$color_array[4] = '#FF5FDF'; // pink
			$color_array[5] = 'orange'; // orange
			$color_array[6] = '#FE00DA'; // magenta
			$color_array[7]	= '#00E2FF'; // cyan
			$color_array[8]	= '#000000'; // Black

			// Single lines, new in 2.0 (Jul08)
			for ($i = 0; $i < $len; $i++){
				$Plot =& $Plotarea->addNew ('line', array (&$dataset[$i]));
				$Plot->setLineColor ($color_array[$i]); 
			}
		}

		// Color management
		if ($stacked != 2) {
			$Plot->setLineColor('gray@0.4');
		}
		
		$AxisX =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_X);
		// $AxisX->Hide();
		$AxisY =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y);
		$AxisY->setLabelOption ("showtext",true);
		$AxisY->setLabelInterval (ceil ($max_value / 5));
		$AxisY->showLabel (IMAGE_GRAPH_LABEL_ZERO);
		if ($unit_name != "")
			$AxisY->setTitle ($unit_name, 'vertical');
		$AxisX->setLabelInterval ($this->xaxis_interval / 10);
		$GridY2 =& $Plotarea->addNew ('bar_grid', IMAGE_GRAPH_AXIS_Y_SECONDARY);
		$GridY2->setLineColor ('gray');
		$GridY2->setFillColor ('lightgray@0.05');
		// set line colors
		$FillArray =& Image_Graph::factory ('Image_Graph_Fill_Array');
		$Plot->setFillStyle ($FillArray);
		$FillArray->addColor ('#BFFF51@0.6'); // Green
		$FillArray->addColor ('yellow@0.6'); // yellow
		$FillArray->addColor ('#FF5FDF@0.6'); // pink
		$FillArray->addColor ('orange@0.6'); // orange
		$FillArray->addColor ('#7D8AFF@0.6'); // blue
		$FillArray->addColor ('#FF302A@0.6'); // red
		$FillArray->addColor ('brown@0.6'); // brown
		$FillArray->addColor ('green@0.6');
		$AxisY_Weather =& $Plotarea->getAxis (IMAGE_GRAPH_AXIS_Y);
		$Graph->done ();
	}
}
?>
