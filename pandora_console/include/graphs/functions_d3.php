<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
function include_javascript_d3($return=false)
{
    global $config;

    static $is_include_javascript = false;

    $output = '';
    if (!$is_include_javascript) {
        $is_include_javascript = true;

        $output .= '<script type="text/javascript" src="';
        $output .= ui_get_full_url(
            'include/javascript/d3.3.5.14.js',
            false,
            false,
            false
        );
        $output .= '" charset="utf-8"></script>';

        $output .= '<script type="text/javascript" src="';
        $output .= ui_get_full_url(
            'include/graphs/bullet.js',
            false,
            false,
            false
        );
        $output .= '" charset="utf-8"></script>';

        $output .= '<script type="text/javascript" src="';
        $output .= ui_get_full_url(
            'include/graphs/pandora.d3.js',
            false,
            false,
            false
        );
        $output .= '" charset="utf-8"></script>';
    }

    if (!$return) {
        echo $output;
    }

    return $output;
}


function d3_relationship_graph($elements, $matrix, $width=700, $return=false)
{
    global $config;

    if (is_array($elements)) {
        $elements = json_encode($elements);
    }

    if (is_array($matrix)) {
        $matrix = json_encode($matrix);
    }

    $output = '<div id="chord_diagram"></div>';
    $output .= include_javascript_d3(true);
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					chordDiagram('#chord_diagram', $elements, $matrix, $width);
				</script>";

    if (!$return) {
        echo $output;
    }

    return $output;
}


function d3_tree_map_graph($data, $width=700, $height=700, $return=false)
{
    global $config;

    if (is_array($data)) {
        $data = json_encode($data);
    }

    $output = "<div id=\"tree_map\" style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= '<style type="text/css">
					.cell>rect {
						pointer-events: all;
						cursor: pointer;
						stroke: #EEEEEE;
					}
					
					.chart {
						display: block;
						margin: auto;
					}
					
					.parent .label {
						color: #FFFFFF;
						text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-webkit-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-moz-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
					}
					
					.labelbody {
						text-align: center;
						background: transparent;
					}
					
					.label {
						margin: 2px;
						white-space: pre;
						overflow: hidden;
						text-overflow: ellipsis;
						text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-webkit-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
						-moz-text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
					}
					
					.child .label {
						white-space: pre-wrap;
						text-align: center;
						text-overflow: ellipsis;
					}
					
					.cell {
						font-size: 11px;
						cursor: pointer
					}
				</style>';
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					treeMap('#tree_map', $data, '$width', '$height');
				</script>";

    if (!$return) {
        echo $output;
    }

    return $output;
}


function d3_sunburst_graph($data, $width=700, $height=700, $return=false, $tooltip=true)
{
    global $config;

    if (is_array($data)) {
        $data = json_encode($data);
    }

    $output = "<div id=\"sunburst\" style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= '<style type="text/css">
					path {
						stroke: #fff;
						fill-rule: evenodd;
					}
				</style>';
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					sunburst('#sunburst', $data, '$width', '$height', '$tooltip');
				</script>";

    if (!$return) {
        echo $output;
    }

    return $output;
}


function d3_bullet_chart(
    $chart_data,
    $width,
    $height,
    $color,
    $legend,
    $homeurl,
    $unit,
    $font,
    $font_size
) {
    global $config;

    $output = '';
    $output .= include_javascript_d3(true);

    $output .= '<script language="javascript" type="text/javascript">';
    $output .= file_get_contents($homeurl.'include/graphs/bullet.js');
    $output .= '</script>';

    $id_bullet = uniqid();
    $font = array_shift(explode('.', array_pop(explode('/', $font))));

    $invert_color = '';
    if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
        $invert_color = 'filter: invert(100%);';
    }

    $output .= '<div id="bullet_graph_'.$id_bullet.'" class="bullet" style="overflow: hidden; width: '.$width.'px; margin-left: auto; margin-right: auto;"></div>
		<style>
			.bullet_graph {
				margin: auto;
				padding-top: 40px;
				position: relative;
				width: 100%;
			}

			.bullet { font: 7px lato; }
			.bullet .marker.s0 { stroke: #e63c52; stroke-width: 2px; }
			.bullet .marker.s1 { stroke: #f3b200; stroke-width: 2px; }
			.bullet .marker.s2 { stroke: steelblue; stroke-width: 2px; }
			.bullet .tick line { stroke: #666; stroke-width: .5px; }
			.bullet .range.s0 { fill: #fff; }
			.bullet .range.s1 { fill: #ddd; }
			.bullet .range.s2 { fill: #ccc; }
			.bullet .measure.s0 { fill: steelblue; }
			.bullet .measure.s1 { fill: steelblue; }
			.bullet .title { font-size: 9pt; font-weight: bold; text-align:left; cursor: help;}
            .bullet .subtitle { fill: #999; font-size: 7pt;}
			.bullet g text { font-size:'.$font_size.'pt; '.$invert_color.' }

		</style>
		<script language="javascript" type="text/javascript">

		var margin = {top: 5, right: 40, bottom: 20, left: 130};

		var width = ('.$width.'+10);
		var height = '.$height.'- margin.top - margin.bottom;

		var chart = d3.bullet()
			.width(width)
			.height(height)
			.orient("left");
		';

    $temp = [];
    foreach ($chart_data as $data) {
        if (isset($data['label'])) {
            $name = io_safe_output($data['label']);
        } else {
            $name = io_safe_output($data['nombre']);
        }

        $long_name = $name;
        $name = ui_print_truncate_text($name, 20, false, true, false, '...', false);
        $marker = '';
        if ($data['value'] == 0) {
            $marker = ', 0';
        }

        $temp[] = '{"longTitle":"'.$long_name.'", "title":"'.$name.'","subtitle":"'.$data['unit'].'",
				"ranges":['.((float) $data['max']).'],"measures":['.$data['value'].'],
					"markers":['.$data['min_warning'].','.$data['min_critical'].$marker.']}';
    }

    $output .= 'var data = ['.implode(',', $temp).'];
	';
    $output .= '
		var svg = d3.select("#bullet_graph_'.$id_bullet.'").selectAll("svg")
			.data(data)
			.enter().append("svg")
				.attr("class", "bullet")
				.attr("width", "100%")
				.attr("height", height+ margin.top + margin.bottom)
			.append("g")
				.attr("transform", "translate(" + (margin.left) + "," + margin.top + ")")
				.call(chart);

		var title = svg.append("g")
            .attr("width", "120px")
			.style("text-anchor", "end")
			.attr("transform", "translate(-10, 15)");

		title.append("text")
			.attr("class", "title '.$font.' invert_filter")
            .attr("textLength","120")
            .attr("lengthAdjust", "spacingAndGlyphs")
			.text(function(d) { return d.title; })
            .append("title")
                .text(function(d) { return d.longTitle; });



		title.append("text")
			.attr("class", "subtitle")
			.attr("dy", "1em")
			.text(function(d) { return d.subtitle; });

		$(".tick>text").each(function() {
			var label = $(this).text().replace(/,/g,"");
			label = parseFloat(label);
			var text = label.toLocaleString();
			if ( label >= 1000000)
				text = text.substring(0,3) + "M";
			else if (label >= 100000)
				text = text.substring(0,3) + "K";
			else if (label >= 1000)
                text = text.substring(0,2) + "K";
			$(this).text(text);
        });
		</script>';

    return $output;

}


function d3_gauges(
    $chart_data,
    $width,
    $height,
    $color,
    $legend,
    $homeurl,
    $unit,
    $font,
    $font_size,
    $no_data_image,
    $transitionDuration
) {
    global $config;

    if (is_array($chart_data)) {
        $data = json_encode($chart_data);
    }

    $output = include_javascript_d3(true);

    foreach ($chart_data as $module) {
        $output .= "<div class='gauge_d3_class gauge_class' id='".$module['gauge']."'></div>";
    }

    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					var data = $data;
					createGauges(data, '$width', '$height','$font_size','$no_data_image','$font', '$transitionDuration');
				</script>";

    return $output;
}


function ux_console_phases_donut(
    $phases,
    $id,
    $width=800,
    $height=500,
    $return=false
) {
    global $config;

    foreach ($phases as $i => $phase) {
        $phases[$i]['phase_name'] = io_safe_output($phase['phase_name']);
    }

    if (is_array($phases)) {
        $phases = json_encode($phases);
    }

    $recipient_name = 'phases_donut_'.$id;
    $recipient_name_to_js = '#phases_donut_'.$id;

    $output = '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= '<style type="text/css">
					path {
						stroke: #fff;
						fill-rule: evenodd;
					}
				</style>';
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					print_phases_donut('".$recipient_name_to_js."', ".$phases.', '.$width.', '.$height.');
				</script>';

    if ($return === false) {
        echo $output;
    }

    return $output;
}


function d3_progress_bar(
    $id,
    $percentile,
    $width,
    $height,
    $color,
    $unit='%',
    $text='',
    $fill_color='#FFFFFF',
    $radiusx=10,
    $radiusy=10,
    $transition=1
) {
    global $config;

    $recipient_name = 'progress_bar_'.$id;
    $recipient_name_to_js = '#progress_bar_'.$id;

    $output = '';

    $output .= '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					progress_bar_d3(
						'".$recipient_name_to_js."',
						".(int) $percentile.',
						'.(int) $width.',
						'.(int) $height.",
						'".$color."',
						'".$unit."',
						'".$text."',
						'".$fill_color."',
						".(int) $radiusx.',
						'.(int) $radiusy.',
						'.(int) $transition.'
					);
				</script>';

    return $output;
}


function d3_progress_bubble($id, $percentile, $width, $height, $color, $unit='%', $text='', $fill_color='#FFFFFF')
{
    global $config;

    $recipient_name = 'progress_bubble_'.$id;
    $recipient_name_to_js = '#progress_bubble_'.$id;

    $output = '';

    $output .= '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					progress_bubble_d3('".$recipient_name_to_js."', ".(int) $percentile.', '.(int) $width.', '.(int) $height.", '".$color."', '".$unit."', '".$text."', '".$fill_color."');
				</script>";

    return $output;
}


function progress_circular_bar($id, $percentile, $width, $height, $color, $unit='%', $text='', $fill_color='#FFFFFF', $transition=1)
{
    global $config;

    $recipient_name = 'circular_progress_bar_'.$id;
    $recipient_name_to_js = '#circular_progress_bar_'.$id;

    $output = '';

    $output .= '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					print_circular_progress_bar('".$recipient_name_to_js."', ".(int) $percentile.', '.(int) $width.', '.(int) $height.", '".$color."', '".$unit."', '".$text."', '".$fill_color."', '".$transition."');
				</script>";

    return $output;
}


function progress_circular_bar_interior($id, $percentile, $width, $height, $color, $unit='%', $text='', $fill_color='#FFFFFF')
{
    global $config;

    $recipient_name = 'circular_progress_bar_interior_'.$id;
    $recipient_name_to_js = '#circular_progress_bar_interior_'.$id;

    $output = '';

    $output .= '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					print_interior_circular_progress_bar('".$recipient_name_to_js."', ".(int) $percentile.', '.(int) $width.', '.(int) $height.", '".$color."', '".$unit."', '".$text."', '".$fill_color."');
				</script>";

    return $output;
}


function d3_donut_graph($id, $width, $height, $module_data, $resume_color)
{
    global $config;

    $module_data = json_encode($module_data);

    $recipient_name = 'donut_graph_'.$id;
    $recipient_name_to_js = '#donut_graph_'.$id;

    $output = '';
    $output .= '<div id='.$recipient_name." style='overflow: hidden;'></div>";
    $output .= include_javascript_d3(true);
    $output .= '<style type="text/css">
					path {
						stroke: #fff;
						fill-rule: evenodd;
					}
				</style>';

    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					$('".$recipient_name_to_js."').empty();
					print_donut_graph('".$recipient_name_to_js."', ".$width.', '.$height.', '.$module_data.", '".$resume_color."');
				</script>";

    return $output;
}


function print_clock_analogic_1(
    $time_format,
    $timezone,
    $clock_animation,
    $width,
    $height,
    $id_element,
    $color,
    $title=true
) {
    $output = '<style type="text/css">
		#rim {
			fill: none;
			stroke: #999;
			stroke-width: 3px;
		}

		.second-hand{
			stroke-width:3;

		}

		.minute-hand{
			stroke-width:8;
			stroke-linecap:round;
		}

		.hour-hand{
			stroke-width:12;
			stroke-linecap:round;
		}

		.hands-cover{
			stroke-width:3;
			fill:#fff;
		}

		.second-tick{
			stroke-width:3;
			fill:#000;
		}

		.hour-tick{
			stroke-width:8; //same as the miute hand
		}

		.second-label{
			font-size: 12px;
		}

		.hour-label{
			font-size: 24px;
		}
	}
	</style>';

    $tz = $timezone;
    $timestamp = time();
    $dt = new DateTime('now', new DateTimeZone($tz));
    // first argument "must" be a string.
    $dt->setTimestamp($timestamp);
    // adjust the object to correct timestamp.
    $dateTimeZoneOption = new DateTimeZone(date_default_timezone_get());
    $dateTimeZonePandora = new DateTimeZone($timezone);

    $dateTimeOption = new DateTime('now', $dateTimeZoneOption);
    $dateTimePandora = new DateTime('now', $dateTimeZonePandora);

    $timeOffset = $dateTimeZonePandora->getOffset($dateTimeOption);

    $output .= include_javascript_d3(true);

    if ($width == 0) {
        $date_width = 200;
    } else {
        $date_width = $width;
    }

    if ($title === true) {
        $output .= '<div style="width:'.$date_width.'px;text-align:center;font-style:italic;font-size:12pt;color:'.$color.'">';

        if ($time_format == 'timedate') {
            $output .= $dt->format('d / m / Y').' - ';
        }

        $output .= $dt->format('a').'</div>';

        $timezone_short = explode('/', $timezone);
        $timezone_short_end = end($timezone_short);
        $output .= '<div style="width:'.$date_width.'px;text-align:center;font-style:italic;font-size:12pt;color:'.$color.'">'.$timezone_short_end.'</div>';
    }

    $output .= "<script language=\"javascript\" type=\"text/javascript\">
					printClockAnalogic1('".$time_format."', '".$timeOffset."', '".$clock_animation."','".$width."','".$height."','".$id_element."','".$color."');
				</script>";

    return $output;

}


function print_clock_digital_1($time_format, $timezone, $clock_animation, $width, $height, $id_element, $color)
{
    global $config;
    $output .= '<style type="text/css">
	
				#underlay_'.$id_element.' path,
				#underlay circle {
				fill: none;
				stroke: none;
				}

				#underlay_'.$id_element.' .lit {
				fill: '.$color.';
				stroke: none;
				}

				#overlay_'.$id_element.' path,
				#overlay_'.$id_element.' circle {
				fill: rgba(246, 246, 246, 0.15);
				stroke: none;
				}

				#overlay_'.$id_element.' .lit {
				fill: '.$color.';
				stroke: none;
				}

				</style>';

                $output .= include_javascript_d3(true);
                $tz = $timezone;
                $timestamp = time();
                $dt = new DateTime('now', new DateTimeZone($tz));
    // first argument "must" be a string
                $dt->setTimestamp($timestamp);
    // adjust the object to correct timestamp
                $dateTimeZoneOption = new DateTimeZone(date_default_timezone_get());
                $dateTimeZonePandora = new DateTimeZone($timezone);

                $dateTimeOption = new DateTime('now', $dateTimeZoneOption);
                $dateTimePandora = new DateTime('now', $dateTimeZonePandora);

                $timeOffset = $dateTimeZonePandora->getOffset($dateTimeOption);

                $output .= include_javascript_d3(true);

    if ($width == 0) {
        $date_width = 200;
    } else {
        $date_width = $width;
    }

    if ($time_format == 'timedate') {
        $output .= '<div style="width:'.$date_width.'px;text-align:center;font-style:italic;font-size:12pt;color:'.$color.'">';
        $output .= $dt->format('d / m / Y').'</div>';
    }

                $output .= '
				
				<svg width="'.$date_width.'" height="'.($date_width / 3.9).'" viewBox="0 0 375 96">
				  <g transform="translate(17,0)">
				    <g class="digit" transform="skewX(-12)">
				      <path d="M10,8L14,4L42,4L46,8L42,12L14,12L10,8z"/>
				      <path d="M8,10L12,14L12,42L8,46L4,42L4,14L8,10z"/>
				      <path d="M48,10L52,14L52,42L48,46L44,42L44,14L48,10z"/>
				      <path d="M10,48L14,44L42,44L46,48L42,52L14,52L10,48z"/>
				      <path d="M8,50L12,54L12,82L8,86L4,82L4,54L8,50z"/>
				      <path d="M48,50L52,54L52,82L48,86L44,82L44,54L48,50z"/>
				      <path d="M10,88L14,84L42,84L46,88L42,92L14,92L10,88z"/>
				    </g>
				    <g class="digit" transform="skewX(-12)">
				      <path d="M66,8L70,4L98,4L102,8L98,12L70,12L66,8z"/>
				      <path d="M64,10L68,14L68,42L64,46L60,42L60,14L64,10z"/>
				      <path d="M104,10L108,14L108,42L104,46L100,42L100,14L104,10z"/>
				      <path d="M66,48L70,44L98,44L102,48L98,52L70,52L66,48z"/>
				      <path d="M64,50L68,54L68,82L64,86L60,82L60,54L64,50z"/>
				      <path d="M104,50L108,54L108,82L104,86L100,82L100,54L104,50z"/>
				      <path d="M66,88L70,84L98,84L102,88L98,92L70,92L66,88z"/>
				    </g>
				    <g class="separator">
				      <circle r="4" cx="112" cy="28"/>
				      <circle r="4" cx="103.5" cy="68"/>
				    </g>
				    <g class="digit" transform="skewX(-12)">
				      <path d="M134,8L138,4L166,4L170,8L166,12L138,12L134,8z"/>
				      <path d="M132,10L136,14L136,42L132,46L128,42L128,14L132,10z"/>
				      <path d="M172,10L176,14L176,42L172,46L168,42L168,14L172,10z"/>
				      <path d="M134,48L138,44L166,44L170,48L166,52L138,52L134,48z"/>
				      <path d="M132,50L136,54L136,82L132,86L128,82L128,54L132,50z"/>
				      <path d="M172,50L176,54L176,82L172,86L168,82L168,54L172,50z"/>
				      <path d="M134,88L138,84L166,84L170,88L166,92L138,92L134,88z"/>
				    </g>
				    <g class="digit" transform="skewX(-12)">
				      <path d="M190,8L194,4L222,4L226,8L222,12L194,12L190,8z"/>
				      <path d="M188,10L192,14L192,42L188,46L184,42L184,14L188,10z"/>
				      <path d="M228,10L232,14L232,42L228,46L224,42L224,14L228,10z"/>
				      <path d="M190,48L194,44L222,44L226,48L222,52L194,52L190,48z"/>
				      <path d="M188,50L192,54L192,82L188,86L184,82L184,54L188,50z"/>
				      <path d="M228,50L232,54L232,82L228,86L224,82L224,54L228,50z"/>
				      <path d="M190,88L194,84L222,84L226,88L222,92L194,92L190,88z"/>
				    </g>
				    <g class="separator">
				      <circle r="4" cx="236" cy="28"/>
				      <circle r="4" cx="227.5" cy="68"/>
				    </g>
				    <g class="digit" transform="skewX(-12)">
				      <path d="M258,8L262,4L290,4L294,8L290,12L262,12L258,8z"/>
				      <path d="M256,10L260,14L260,42L256,46L252,42L252,14L256,10z"/>
				      <path d="M296,10L300,14L300,42L296,46L292,42L292,14L296,10z"/>
				      <path d="M258,48L262,44L290,44L294,48L290,52L262,52L258,48z"/>
				      <path d="M256,50L260,54L260,82L256,86L252,82L252,54L256,50z"/>
				      <path d="M296,50L300,54L300,82L296,86L292,82L292,54L296,50z"/>
				      <path d="M258,88L262,84L290,84L294,88L290,92L262,92L258,88z"/>
				    </g>
				    <g class="digit" transform="skewX(-12)">
				      <path d="M314,8L318,4L346,4L350,8L346,12L318,12L314,8z"/>
				      <path d="M312,10L316,14L316,42L312,46L308,42L308,14L312,10z"/>
				      <path d="M352,10L356,14L356,42L352,46L348,42L348,14L352,10z"/>
				      <path d="M314,48L318,44L346,44L350,48L346,52L318,52L314,48z"/>
				      <path d="M312,50L316,54L316,82L312,86L308,82L308,54L312,50z"/>
				      <path d="M352,50L356,54L356,82L352,86L348,82L348,54L352,50z"/>
				      <path d="M314,88L318,84L346,84L350,88L346,92L318,92L314,88z"/>
				    </g>
				  </g>
				</svg>
				';

                $output .= "<script language=\"javascript\" type=\"text/javascript\">
								printClockDigital1('".$time_format."', '".$timeOffset."', '".$clock_animation."','".$width."','".$height."','".$id_element."','".$color."');
							</script>";

                $timezone_short = explode('/', $timezone);
                $timezone_short_end = end($timezone_short);

                $output .= '<div style="width:'.$date_width.'px;text-align:center;font-style:italic;font-size:12pt;color:'.$color.'">'.$timezone_short_end.'</div>';

                return $output;

}
