<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/**
 * Image_Graph - PEAR PHP OO Graph Rendering Utility.
 *
 * PHP versions 4 and 5
 *
 * LICENSE: This library is free software; you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation; either version 2.1 of the License, or (at your
 * option) any later version. This library is distributed in the hope that it
 * will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty
 * of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser
 * General Public License for more details. You should have received a copy of
 * the GNU Lesser General Public License along with this library; if not, write
 * to the Free Software Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 * 02111-1307 USA
 *
 * @category   Images
 * @package    Image_Graph
 * @subpackage Plot
 * @author     Jesper Veggerby <pear.nosey@veggerby.dk>
 * @copyright  Copyright (C) 2003, 2004 Jesper Veggerby Hansen
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    CVS: $Id: Area.php,v 1.13 2005/11/27 22:21:17 nosey Exp $
 * @link       http://pear.php.net/package/Image_Graph
 */

/**
 * Include file Image/Graph/Plot.php
 */
require_once 'Image/Graph/Plot.php';

/**
 * Bubble Chart plot.
 *
 * An area chart plots all data points similar to a {@link
 * Image_Graph_Plot_Line}, but the area beneath the line is filled and the whole
 * area 'the-line', 'the right edge', 'the x-axis' and 'the left edge' is
 * bounded. Smoothed charts are only supported with non-stacked types
 *
 * @category   Images
 * @package    Image_Graph
 * @subpackage Plot
 * @author     Eric Ross <eric.ross.c@gmail.com>
 * @copyright  Copyright (C) 2007 Eric Ross
 * @license    http://www.gnu.org/copyleft/lesser.html  LGPL License 2.1
 * @version    Release: 0.7.2
 * @link       http://pear.php.net/package/Image_Graph
 */
class Image_Graph_Plot_Bubble extends Image_Graph_Plot
{
    var $_only_minmax = true;
    
    function Image_Graph_Plot_Bubble(&$dataset, $only_minmax = false)
    {
	$this->_only_minmax = $only_minmax;
	
	parent::Image_Graph_Plot($dataset);
    }
    
    /**
     * Perform the actual drawing on the legend.
     *
     * @param int $x0 The top-left x-coordinate
     * @param int $y0 The top-left y-coordinate
     * @param int $x1 The bottom-right x-coordinate
     * @param int $y1 The bottom-right y-coordinate
     * @access private
     */
    function _drawLegendSample($x0, $y0, $x1, $y1)
    {
	if ($this->_only_minmax)
		return;
		
	
	$x = ($x0 + $x1) / 2;
	$y = ($y0 + $y1) / 2;
	
	$rx = abs($x0 - $x1) / 2.0;
	$ry = abs($y0 - $y1) / 2.0;
	
	$fill = $this->_fillStyle->_getFillStyle(-1);
	$this->_canvas->ellipse(array(
					'x' => $x, 
					'y' => $y,
					'rx' => $rx,
					'ry' => $ry,
					'fill' => $fill,
					'line' => 'black'
				)
		);
    }


    /**
     * Output the plot
     *
     * @return bool Was the output 'good' (true) or 'bad' (false).
     * @access private
     */
function _done()
{
	if (parent::_done() === false) {
		return false;
	}

	if ($this->_only_minmax)
		return $this->_drawMinMax();
		
	$this->_canvas->startGroup(get_class($this) . '_' . $this->_title);

	$this->_clip(true);        

	$index = 0;
	
	$canvas_width = $this->_canvas->getWidth();
	$graph_width = $this->_right - $this->_left;
	$graph_height = $this->_bottom - $this->_top;
	
	$num_datasets = count($this->_dataset);
	$y_step = $graph_height / ($num_datasets + 1);
	
	$keys = array_keys($this->_dataset);

	foreach ($keys as $key) {
		$dataset =& $this->_dataset[$key];
	    
		$dataset->_reset();
	    
		$fill = $this->_fillStyle->_getFillStyle(-1);
		
		while ($point = $dataset->_next()) {
			//$pivot = $point['X'];
			$y     = $point['Y'];
			$size  = $point['data']['size'];
			$x     = $point['data']['x'];
			
			// x is the % from the left border (0% = full left; 100% = full right)
			$x = $this->_left + $x * $graph_width / 100.0;
			
			// y is the number of steps (the number of the dataset) zero based
			$y = $this->_bottom - ($y + 1) * $y_step;
			
			$this->_canvas->ellipse(array(
							'x' => $x, 
							'y' => $y,
							'rx' => $size,
							'ry' => $size,
							'fill' => $fill,
							'line' => 'black'
						)
				);
            /*
            if (isset($this->_callback)) {
                call_user_func($this->_callback, array( 'event' => 'bubble',
                                                        'x' => $x, 
                                                        'y' => $y,
                                                        'rx' => $size,
                                                        'ry' => $size,
                                                        'value' => $point['data']['value']
                        )
                 ); 
            }
            */
		}
		$index++;
	}
	unset($keys);
	
	
	$this->_drawMarker();
	$this->_clip(false);
                
	$this->_canvas->endGroup();

	return true;
}

function getTextWidth($str)
{
	return $this->_canvas->textWidth($str);
}

function _drawMinMax()
{
	$this->_canvas->startGroup(get_class($this) . '_' . $this->_title);

	$this->_clip(true);        
	
	$index = 0;
	
	$canvas_width = $this->_canvas->getWidth();
	$graph_width = $this->_right - $this->_left;
	$graph_height = $this->_bottom - $this->_top;
	
	$num_datasets = count($this->_dataset);
	$y_step = $graph_height / ($num_datasets + 1);
	
	$keys = array_keys($this->_dataset);

	$max_value = -1;
	$max_size = -1;
	$min_value = 999999;
	$min_size = 100;
	$avg_value = 0;
	$avg_size = 1;
	$count = 0;
	
	foreach ($keys as $key) {
		$dataset =& $this->_dataset[$key];
	    
		$dataset->_reset();

		while ($point = $dataset->_next()) {
			//$pivot = $point['X'];
			//$y = $point['Y'];
			$value = $point['data']['value'];
			$size = $point['data']['size'];
			//$x = $point['data']['x'];
			
			if ($value > $max_value) {
				$max_value = $value;
				$max_size = $size;
			}
			if ($value < $min_value) {
				$min_value = $value;
				$min_size = $size;
			}
			$avg_value += $value;
			$avg_size += $size;
			
			$count++;
		}
	}
	
	$avg_value /= $count == 0 ? 1 : $count;
	$avg_size /= $count == 0 ? 1 : $count;
	
	unset($keys);
	
	$xs = array(0, 0, 0);
	$y = 0;
	$fills = array("gray", "gray", "gray");
	$sizes = array($min_size, $avg_size, $max_size);
	$values = array($min_value, $avg_value, $max_value);
	$titles = array("Min", "Prom", "Max");
	
	for($i=0;$i<3;$i++) {
	
		$x = $this->_left + $xs[$i];
		
		$this->_canvas->setFont($this->_getFont());
		
		$this->_canvas->addText(array(
						"x" => $x,
						"y" => $this->_top + $y,
						"text" => $titles[$i],
						"color" => "black",
						)
					);
		
		$textHeight = $this->_canvas->textWidth($titles[$i]);
		$x += $textHeight + $sizes[$i] + 6;
	
		
		$ye = $y + $this->_canvas->textHeight($titles[$i]) / 2.0;
		
		$this->_canvas->ellipse(array(
							'x' => $x, 
							'y' => $this->_top + $ye,
							'rx' => $sizes[$i],
							'ry' => $sizes[$i],
							'fill' => $fills[$i],
							'line' => 'black'
						)
				);
		
		$x += $sizes[$i] + 6;
		
		$this->_canvas->setFont($this->_getFont());
		$txt = "" . round($values[$i]);
		$this->_canvas->addText(array(
						"x" => $x,
						"y" => $this->_top + $y,
						"text" => $txt,
						"color" => "black",
						)
					);
		
		$yOld = $y;
		
		$y += $textHeight / 2.0;
		$y += $sizes[$i];
		$y += ($i < 2 ? $sizes[$i + 1] : 0);
		$y -= ($i < 2 ? $this->_canvas->textHeight($titles[$i+1]) : 0);
		
		if ($y < $yOld + $textHeight)
			$y = $yOld + $textHeight;
	}
	
	$this->_drawMarker();
	$this->_clip(false);
                
	$this->_canvas->endGroup();
	return true;
}


}




?>
