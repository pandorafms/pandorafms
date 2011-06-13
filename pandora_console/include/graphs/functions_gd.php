<?PHP

// ===========================================================
// Copyright (c) 2011-2011 Artica, info@artica.es

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.

// If is called from index
if(file_exists('include/functions.php')) {
	include_once('include/functions.php');
	include_once('include/functions_html.php');
	include_once('include/graphs/functions_utils.php');
} // If is called through url
else if(file_exists('../functions.php')) {
	include_once('../functions.php');
	include_once('../functions_html.php');
	include_once('functions_utils.php');
}

$types = array('histogram', 'progressbar');
	
$id_graph = get_parameter('id_graph', false);
$graph_type = get_parameter('graph_type', '');

if($id_graph && in_array($graph_type, $types)) {

	if (!$id_graph) {
		exit;
	}

	$graph = unserialize_in_temp($id_graph);

	if (!isset($graph)) {
		exit;
	}
	
	if (!isset($graph['fontsize'])) {
		$graph['fontsize'] = 6;
	}
	
	switch($graph_type) {
		case 'histogram': 
					gd_histogram ($graph['width'], 
						$graph['height'], 
						$graph['mode'], 
						json_decode($graph['data'], true), 
						$graph['max'], 
						$graph['font'], 
						$graph['title'],
						$graph['fontsize']);				
					break;
		case 'progressbar':	
					gd_progress_bar ($graph['width'], 
						$graph['height'], 
						$graph['progress'], 
						$graph['title'], 
						$graph['font'], 
						$graph['out_of_lim_str'],
						$graph['out_of_lim_image'], 
						$graph['mode'],
						$graph['fontsize']);	
					break;
	}
}


function gd_histogram ($width, $height, $mode, $data, $max_value, $font, $title, $fontsize = 8) {	
	// $title is for future use
	$nvalues = count($data);
	
   	Header("Content-type: image/png");
	$image = imagecreate($width,$height);
	$white = ImageColorAllocate($image,255,255,255);
	imagecolortransparent ($image, $white);

	$black = ImageColorAllocate($image,0,0,0);
	
	$red = ImageColorAllocate($image,255,60,75);
	$blue = ImageColorAllocate($image,75,60,255);
	$green = ImageColorAllocate($image,0,120,0);
	$magent = ImageColorAllocate($image,179,0,255);
	$yellow = ImageColorAllocate($image,204,255,0);

	$colors = array($blue, $red, $green, $magent, $yellow);
	
	$margin_up = 2;

	if ($mode != 2) {
		$size_per = ($max_value / ($width-40));
	} else {
		$size_per = ($max_value / ($width));
	}
	
	if ($mode == 0) // with strips 
		$rectangle_height = ($height - 10 - 2 - $margin_up ) / $nvalues;
	else
		$rectangle_height = ($height - 2 - $margin_up ) / $nvalues;

	if ($size_per == 0)
		$size_per = 1;
		
	if ($mode != 2) {
		$leftmargin = 40;
	}
	else {
		$leftmargin = 1;
	}
	
	$c = 0;
	foreach($data as $label => $value) {	
		ImageFilledRectangle($image, $leftmargin, $margin_up, ($value/$size_per)+$leftmargin, $margin_up+$rectangle_height -1 , $colors[$c]);
		if ($mode != 2) {
			ImageTTFText($image, $fontsize, 0, 0, $margin_up+8, $black, $font, $label);
		}
		
		$margin_up += $rectangle_height + 1;

		$c++;
		if(!isset($colors[$c])) {
			$c = 0;
		}
	}
	
	if ($mode == 0) { // With strips
		// Draw limits
		$risk_low =  ($config_risk_low / $size_per) + 40;
		$risk_med =  ($config_risk_med / $size_per) + 40;
		$risk_high =  ($config_risk_high / $size_per) + 40;
		imageline($image, $risk_low, 0, $risk_low , $height, $grey);
		imageline($image, $risk_med , 0, $risk_med  , $height, $grey);
		imageline($image, $risk_high, 0, $risk_high , $height, $grey);
		ImageTTFText($image, $fontsize, 0, $risk_low-20, $height, $grey, $font, "Low");
		ImageTTFText($image, $fontsize, 0, $risk_med-20, $height, $grey, $font, "Med.");
		ImageTTFText($image, $fontsize, 0, $risk_high-25, $height, $grey, $font, "High");
	}
	imagePNG($image);
	imagedestroy($image);
}

// ***************************************************************************
// Draw a dynamic progress bar using GDlib directly
// ***************************************************************************

function gd_progress_bar ($width, $height, $progress, $title, $font, $out_of_lim_str, $out_of_lim_image, $mode = 1, $fontsize=10) {
	if($out_of_lim_str === false) {
		$out_of_lim_str = "Out of limits";
	}
	
	if($out_of_lim_image === false) {
		$out_of_lim_image = "images_graphs/outlimits.png";
	}
	
	// Copied from the PHP manual:
	// http://us3.php.net/manual/en/function.imagefilledrectangle.php
	// With some adds from sdonie at lgc dot com
	// Get from official documentation PHP.net website. Thanks guys :-)
	function drawRating($rating, $width, $height, $font, $out_of_lim_str, $mode, $fontsize) {
		global $config;
		global $REMOTE_ADDR;
		
		if ($width == 0) {
			$width = 150;
		}
		if ($height == 0) {
			$height = 20;
		}

		//$rating = $_GET['rating'];
		$ratingbar = (($rating/100)*$width)-2;

		$image = imagecreate($width,$height);
		

		//colors
		$back = ImageColorAllocate($image,255,255,255);
		imagecolortransparent ($image, $back);
		$border = ImageColorAllocate($image,174,174,174);
		$text = ImageColorAllocate($image,74,74,74);
		$red = ImageColorAllocate($image,255,60,75);
		$green = ImageColorAllocate($image,50,205,50);
		$blue = ImageColorAllocate($image,44,81,120);
		
		$soft_green = ImageColorAllocate($image,176, 255, 84);
		$soft_yellow = ImageColorAllocate($image,255, 230, 84);
		$soft_red = ImageColorAllocate($image,255, 154, 84);
		$other_red = ImageColorAllocate($image,238, 0, 0);

		ImageFilledRectangle($image,0,0,$width-1,$height-1,$back);
		switch ($mode)
		{
			case 0:
				if ($rating > 70)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_green);
				elseif ($rating > 50)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_yellow);
				elseif ($rating > 30)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_red);
				else
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $other_red);
					
				ImageRectangle($image,0,0,$width-1,$height-1,$border);
				break;
			case 1:
				if ($rating > 100)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$red);
				elseif ($rating == 100)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$green);
				else
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1,$blue);
					
				ImageRectangle($image,0,0,$width-1,$height-1,$border);
				
				if ($rating > 50)
					if ($rating > 100)
						ImageTTFText($image, $fontsize, 0, ($width/4), ($height/2)+($height/5), $back, $font, $out_of_lim_str);
					else
						ImageTTFText($image, $fontsize, 0, ($width/2)-($width/10), ($height/2)+($height/5), $back, $font, $rating."%");
				else
					ImageTTFText($image, $fontsize, 0, ($width/2)-($width/10), ($height/2)+($height/5), $text, $font, $rating."%");
				break;
			case 2:
				if ($rating > 70)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $other_red);
				elseif ($rating > 50)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_red);
				elseif ($rating > 30)
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_yellow);
				else
					ImageFilledRectangle($image,1,1,$ratingbar,$height-1, $soft_green);
					
				ImageRectangle($image,0,0,$width-1,$height-1,$border);
				break;
		}
		imagePNG($image);
		imagedestroy($image);
   	}

   	Header("Content-type: image/png");
   	
   	switch ($mode)
   	{
   		case 0:
   			drawRating($progress, $width, $height, $font, $out_of_lim_str, $mode, $fontsize);
   			/*
if ($mode == 0) {
		$engine->background_color = '#E6E6D2';
		$engine->show_title = false;
		if ($progress > 70) 
			$color = '#B0FF54';
		elseif ($progress > 50)
			$color = '#FFE654';
		elseif ($progress > 30)
			$color = '#FF9A54';
		else
			$color = '#EE0000';
	} else {
		$engine->background_color = '#FFFFFF';
		$engine->show_title = true;
		$engine->title = format_numeric ($progress).' %';
		$color = '#2C5196';
	}
   		*/
   			break;
   		case 1:
			if ($progress > 100 || $progress < 0) {
				// HACK: This report a static image... will increase render in about 200% :-) useful for
				// high number of realtime statusbar images creation (in main all agents view, for example
				$imgPng = imageCreateFromPng($out_of_lim_image);
				imageAlphaBlending($imgPng, true);
				imageSaveAlpha($imgPng, true);
				imagePng($imgPng); 
		   	}
		   	else 
		   		drawRating($progress, $width, $height, $font, $out_of_lim_str, $mode, $fontsize);
   			break;
   		case 2:
			if ($progress > 100 || $progress < 0) {
				// HACK: This report a static image... will increase render in about 200% :-) useful for
				// high number of realtime statusbar images creation (in main all agents view, for example
				$imgPng = imageCreateFromPng($out_of_lim_image);
				imageAlphaBlending($imgPng, true);
				imageSaveAlpha($imgPng, true);
				imagePng($imgPng); 
		   	}
		   	else 
		   		drawRating($progress, $width, $height, $font, $out_of_lim_str, $mode, $fontsize);
   			break;   			
   	}
}

?>
