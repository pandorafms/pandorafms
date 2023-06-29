<?PHP

// Copyright (c) 2007-2008 Sancho Lerena, slerena@gmail.com
// Copyright (c) 2008 Esteban Sanchez, estebans@artica.es
// Copyright (c) 2007-2021 Artica, info@artica.es
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public License
// (LGPL) as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Lesser General Public License for more details.
// JQuery 1.6.1 library addition
global $config;


function include_javascript_dependencies_flot_graph($return=false, $mobile=false)
{
    global $config;

    static $is_include_javascript = false;

    if (!$is_include_javascript) {
        $is_include_javascript = true;

        $metaconsole_hack = '';
        if (is_metaconsole()) {
            $metaconsole_hack = '../../';
        }

        $output = '';

        if ($mobile === true) {
            $output .= '
                <script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/mobile/include/javascript/jquery.js').'"></script>
                <script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/mobile/include/javascript/jquery.mobile-1.5.0-rc1.js').'"></script>';
        }

        // NOTE: jquery.flot.threshold is not te original file. Is patched to allow multiple thresholds and filled area
        $output .= '
			<!--[if lte IE 8]><script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/excanvas.js').'"></script><![endif]-->
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.time.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.pie.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.crosshair.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.stack.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.selection.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.resize.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.threshold.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.threshold.multiple.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.symbol.min.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.exportdata.pandora.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/jquery.flot.axislabels.js').'"></script>
			<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/flot/pandora.flot.js').'?v='.$config['current_package'].'"></script>';

        // Chartjs.
        $output .= '<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/chartjs/chart.js').'"></script>';
        $output .= '<script language="javascript" type="text/javascript" src="'.ui_get_full_url($metaconsole_hack.'/include/graphs/chartjs/chartjs-plugin-datalabels.min.js').'"></script>';

        $output .= "
			<script type='text/javascript'>
			var precision_graph = ".$config['graph_precision'].";
			function pieHover(event, pos, obj)
			{
				if (!obj)
					return;
				percent = parseFloat(obj.series.percent).toFixed(2);
				$('#hover').html('<span style=\'font-weight: bold; color: '+obj.series.color+'\'>'+obj.series.label+' ('+percent+'%)</span>');
				$('.legendLabel').each(function() {
					if ($(this).html() == obj.series.label) {
						$(this).css('font-weight','bold');
					}
					else {
						$(this).css('font-weight','');
					}
				});
			}

			function pieClick(event, pos, obj)
			{
				if (!obj)
					return;
				percent = parseFloat(obj.series.percent).toFixed(2);
				alert(''+obj.series.label+': '+obj.series.data[0][1]+' ('+percent+'%)');
			}
            </script>";

        if (!$return) {
            echo $output;
        }

        return $output;
    }
}


/**
 * Function create container for print charts.
 *
 * @param integer $agent_module_id     Id module.
 * @param array   $array_data          Data.
 * @param array   $legend              Legend.
 * @param array   $series_type         Series.
 * @param array   $color               Color.
 * @param array   $date_array          Date.
 * @param array   $data_module_graph   Data module.
 * @param array   $params              Params.
 * @param string  $water_mark          Water.
 * @param array   $array_events_alerts Events array.
 *
 * @return string Return graphs.
 */
function flot_area_graph(
    $agent_module_id,
    $array_data,
    $legend,
    $series_type,
    $color,
    $date_array,
    $data_module_graph,
    $params,
    $water_mark,
    $array_events_alerts
) {
    global $config;
    global $is_mobile;

    // Get a unique identifier to graph.
    $graph_id = uniqid('graph_');

    $background_style = '';
    switch ($params['backgroundColor']) {
        case 'white':
            $background_style = ' background: #fff; ';
            $params['grid_color'] = '#C1C1C1';
        break;

        case 'black':
            $background_style = ' background: #000; ';
            $params['grid_color'] = '#BDBDBD';
        break;

        case 'transparent':
            $background_style = '';
            $params['grid_color'] = '#A4A4A4';
        break;

        default:
            $background_style = 'background-color: '.$params['backgroundColor'];
            $params['grid_color'] = '#C1C1C1';
        break;
    }

    if (($config['style'] === 'pandora_black' && !is_metaconsole()) && ($params['pdf'] === false || $params['pdf'] === null) && (isset($is_mobile) === false || $is_mobile === false)
    ) {
        $background_style = '#222';
        $params['grid_color'] = '#fff';
        $params['backgroundColor'] = '#222';
        $params['legend_color'] = '#fff';
    } else if ($params['pdf']) {
        $params['legend_color'] = '#000';
    }

    // Parent layer.
    if (strpos($params['width'], '%') === false) {
        $width = 'width: '.$params['width'].'px;';
    } else {
        $width = 'width: '.$params['width'].';';
    }

    $return = "<div class='parent_graph' style='".$width.$background_style."'>";

    if ($params['title'] === true && empty($params['title']) === false) {
        $return .= '<p class="center">'.$params['title'].'</p>';
    }

    // Set some containers to legend, graph, timestamp tooltip, etc.
    if ($params['show_legend']) {
        $return .= '<p id="legend_'.$graph_id.'" class="legend_background" style="text-align:left; width: '.$params['width'].'px;"></p>';
    }

    if (isset($params['graph_combined']) === true && $params['graph_combined']
        && (isset($params['from_interface']) === false || !$params['from_interface'])
    ) {
        if (isset($params['threshold_data']) === true
            && is_array($params['threshold_data']) === true
        ) {
            $yellow_threshold = $params['threshold_data']['yellow_threshold'];
            $red_threshold    = $params['threshold_data']['red_threshold'];
            $yellow_up        = $params['threshold_data']['yellow_up'];
            $red_up           = $params['threshold_data']['red_up'];
            $yellow_inverse   = $params['threshold_data']['yellow_inverse'];
            $red_inverse      = $params['threshold_data']['red_inverse'];
        } else {
            $yellow_up      = 0;
            $red_up         = 0;
            $yellow_inverse = false;
            $red_inverse    = false;
        }
    } else if (isset($params['combined']) === false || !$params['combined']) {
        $yellow_threshold = $data_module_graph['w_min'];
        $red_threshold    = $data_module_graph['c_min'];
        // Get other required module datas to draw warning and critical.
        if ($agent_module_id == 0) {
            $yellow_up      = 0;
            $red_up         = 0;
            $yellow_inverse = false;
            $red_inverse    = false;
        } else {
            $yellow_up      = $data_module_graph['w_max'];
            $red_up         = $data_module_graph['c_max'];
            $yellow_inverse = !($data_module_graph['w_inv'] == 0);
            $red_inverse    = !($data_module_graph['c_inv'] == 0);
        }
    } else if (isset($params['from_interface']) === true
        && $params['from_interface']
    ) {
        if (isset($params['threshold_data']) === true
            && is_array($params['threshold_data'])
        ) {
            $yellow_threshold = $params['threshold_data']['yellow_threshold'];
            $red_threshold    = $params['threshold_data']['red_threshold'];
            $yellow_up        = $params['threshold_data']['yellow_up'];
            $red_up           = $params['threshold_data']['red_up'];
            $yellow_inverse   = $params['threshold_data']['yellow_inverse'];
            $red_inverse      = $params['threshold_data']['red_inverse'];
        } else {
            $yellow_up      = 0;
            $red_up         = 0;
            $yellow_inverse = false;
            $red_inverse    = false;
        }
    } else {
        $yellow_up      = 0;
        $red_up         = 0;
        $yellow_inverse = false;
        $red_inverse    = false;
    }

    if ($params['menu']) {
        $return .= menu_graph(
            $yellow_threshold,
            $red_threshold,
            $yellow_up,
            $red_up,
            $yellow_inverse,
            $red_inverse,
            $graph_id,
            $params
        );
    }

    $return .= html_print_input_hidden(
        'line_width_graph',
        $config['custom_graph_width'],
        true
    );
    $return .= "<div id='timestamp_$graph_id'
                     class='timestamp_graph'
                     style='font-size:".$params['font_size']."pt;
                        display:none; position:absolute;
                        background:#fff; border: solid 1px #aaa;
                        padding: 2px; z-index:1000; color: #000;
                '></div>";
    $return .= "<div id='$graph_id' class='";

    if ($params['type'] == 'area_simple') {
        $return .= 'noresizevc ';
    }

    $return .= 'graph'.$params['adapt_key']."'
				style='	width: ".$params['width'].'px;
				height: '.$params['height']."px;'></div>";

    $legend_top = 10;
    if (empty($params['show_legend']) === false) {
        $legend_top = (int) $params['height'];
    }

    if ($params['menu']) {
        $params['height'] = 100;
    } else {
        $params['height'] = 1;
    }

    if ((bool) $params['vconsole'] === false) {
        $return .= '<div id="overview_'.$graph_id.'" class="overview_graph" style="margin:0px; margin-top:30px; margin-bottom:50px; width:'.$params['width'].'px; height: 200px;"></div>';

        if ($water_mark != '' && (bool) $params['dashboard'] === false) {
            $return .= '<div id="watermark_'.$graph_id.'" style="position:absolute; bottom: '.$legend_top.'px; left: calc(100% - 100px);">';
            $return .= '<img id="watermark_image_'.$graph_id.'" src="'.$water_mark['url'].'">';
            $return .= '</div>';
        }
    }

    foreach ($series_type as $k => $v) {
        $series_type_unique['data_'.$graph_id.'_'.$k] = $v;
    }

    // Store data series in javascript format.
    $extra_width = (int) ($params['width'] / 3);
    $return .= "<div id='extra_$graph_id'
    style='font-size: ".$params['font_size'].'pt;
    display:none; position:absolute; overflow: auto;
    max-height: '.($params['height'] + 50).'px;
    width: '.$extra_width."px;
    background:#fff; padding: 2px 2px 2px 2px;
    border: solid #000 1px;'></div>";

    // Trick to get translated string from javascript.
    $return .= html_print_input_hidden('unknown_text', __('Unknown'), true);

    $values = json_encode($array_data);

    $legend              = json_encode($legend);
    $series_type         = json_encode($series_type);
    $color               = json_encode($color);
    $date_array          = json_encode($date_array);
    $data_module_graph   = json_encode($data_module_graph);
    $params              = json_encode($params);
    $array_events_alerts = json_encode($array_events_alerts);

    // Javascript code.
    if ($font_size == '') {
        $font_size = '\'\'';
    }

    $return .= "<script type='text/javascript'>";

    $return .= "pandoraFlotArea(\n";
    $return .= "'".$graph_id."', \n";
    $return .= $values.", \n";
    $return .= $legend.", \n";
    $return .= $series_type.", \n";
    $return .= $color.", \n";
    $return .= $date_array.", \n";
    $return .= $data_module_graph.", \n";
    $return .= $params.", \n";
    $return .= $array_events_alerts."\n";
    $return .= ');';

    $return .= '</script>';

    // Parent layer.
    $return .= '</div>';

    return $return;
}


function menu_graph(
    $yellow_threshold,
    $red_threshold,
    $yellow_up,
    $red_up,
    $yellow_inverse,
    $red_inverse,
    $graph_id,
    $params
) {
    global $config;

    $return = '';
    $threshold = false;
    if ($yellow_threshold != $yellow_up || $red_threshold != $red_up) {
        $threshold = true;
    }

    $return .= "<div id='general_menu_$graph_id' class='menu_graph'>";
    $return .= "<div id='menu_$graph_id' "."style='display: none; ".'text-align: center;'.'position: relative;'."border-bottom: 0px;'>
        <a href='javascript:'><img id='menu_cancelzoom_$graph_id' class='main_menu_icon invert_filter' src='".$params['homeurl'].'images/disable.svg'."' alt='".__('Cancel zoom')."' title='".__('Cancel zoom')."'></a>";
    if ($threshold) {
        $return .= " <a href='javascript:'><img id='menu_threshold_$graph_id' src='".$params['homeurl'].'images/chart_curve_threshold.png'."' alt='".__('Warning and Critical thresholds')."' title='".__('Warning and Critical thresholds')."'></a>";
    }

    if ($params['show_overview']) {
        $return .= " <a href='javascript:'>
            <img id='menu_overview_$graph_id' class='menu_overview' src='".$params['homeurl'].'images/chart_curve_overview.png'."' alt='".__('Overview graph')."' title='".__('Overview graph')."'></a>";
    }

    // Export buttons.
    if ($params['show_export_csv'] && enterprise_installed() === true) {
        $return .= "<a href='javascript:'><img id='menu_export_csv_$graph_id' class='main_menu_icon invert_filter' src='".$params['homeurl'].'images/file-csv.svg'."' alt='".__('Export to CSV')."' title='".__('Export to CSV')."'></a>";
    }

    $return .= '</div>';
    $return .= '</div>';

    return $return;
}


/**
 * Pie chart.
 *
 * @param array   $values          Values.
 * @param array   $labels          Labels.
 * @param integer $width           Width.
 * @param integer $height          Height.
 * @param boolean $water_mark      Water mark.
 * @param string  $font            Font.
 * @param integer $font_size       Font Size.
 * @param string  $legend_position Psition Legend.
 * @param string  $colors          Array Colors.
 * @param boolean $hide_labels     Hide labels.
 *
 * @return void
 */
function flot_pie_chart(
    $values,
    $labels,
    $width,
    $height,
    $water_mark,
    $font='',
    $font_size=8,
    $legend_position='',
    $colors='',
    $hide_labels=false
) {
    $series = count($values);

    if (($series !== count($labels)) || ($series === 0)) {
        return;
    }

    $graph_id = uniqid('graph_');

    switch ($legend_position) {
        case 'bottom':
            $height = ($height + (count($values) * 24));
        break;

        case 'right':
        default:
            // TODO FOR TOP OR LEFT OR RIGHT.
        break;
    }

    $return = "<div id='".$graph_id."' class='graph' style='width: ".$width.'px; height: '.$height."px;'></div>";

    if ($water_mark != '') {
        $return .= "<div id='watermark_$graph_id' class='invisible absolute'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
        $water_mark = 'true';
    } else {
        $water_mark = 'false';
    }

    $separator = ';;::;;';

    $labels = implode($separator, $labels);
    $values = implode($separator, $values);
    if (empty($colors) === false) {
        $colors = implode($separator, $colors);
    }

    $return .= "<script type='text/javascript'>";
    $return .= "pandoraFlotPie(
        '$graph_id',
        '$values',
        '$labels',
        '$series',
        '$width',
        $font_size,
        $water_mark,
        '$separator',
        '$legend_position',
        '$height',
        '$colors',
        ".json_encode($hide_labels).')';
    $return .= '</script>';

    return $return;
}


// Prints a FLOT pie chart
function flot_custom_pie_chart(
    $graph_values,
    $width,
    $height,
    $colors,
    $module_name_list,
    $long_index,
    $no_data,
    $xaxisname,
    $yaxisname,
    $water_mark,
    $fontpath,
    $font_size,
    $unit,
    $ttl,
    $homeurl,
    $background_color,
    $legend_position,
    $pdf
) {
    global $config;

    // TODO
    // include_javascript_dependencies_flot_graph();.
    $total_modules = $graph_values['total_modules'];
    unset($graph_values['total_modules']);

    foreach ($graph_values as $label => $value) {
        if ($value['value']) {
            if ($value['value'] > 1000000) {
                $legendvalue = sprintf('%sM', remove_right_zeros(number_format(($value['value'] / 1000000), $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])));
            } else if ($value['value'] > 1000) {
                $legendvalue = sprintf('%sK', remove_right_zeros(number_format(($value['value'] / 1000), $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator'])));
            } else {
                $legendvalue = remove_right_zeros(number_format($value['value'], $config['graph_precision'], $config['decimal_separator'], $config['thousand_separator']));
            }
        } else {
            $legendvalue = __('No data');
        }

        $values[] = $value['value'];
        $legend[] = $label.': '.$legendvalue.' '.$value['unit'];
        $labels[] = $label;
    }

    $graph_id = uniqid('graph_');

    $return = "<div id='$graph_id' class='graph noresizevc' style='width: ".$width.'px; height: '.$height."px;'></div>";

    if ($water_mark != '') {
        $return .= "<div id='watermark_$graph_id' class='invisible absolute'><img id='watermark_image_$graph_id' src='".$water_mark['url']."'></div>";
        $water_mark = 'true';
    } else {
        $water_mark = 'false';
    }

    $separator = ';;::;;';

    $labels = implode($separator, $labels);
    $legend = implode($separator, $legend);
    $values = implode($separator, $values);
    if (!empty($colors)) {
        foreach ($colors as $color) {
            $temp_colors[] = $color['color'];
        }
    }

    $colors = implode($separator, $temp_colors);

    $return .= "<script type='text/javascript'>";
    $return .= "pandoraFlotPieCustom('$graph_id', '$values', '$labels',
			'$width', $font_size, '$fontpath', $water_mark,
			'$separator', '$legend_position', '$height', '$colors','$legend','$background_color')";
    $return .= '</script>';

    return $return;
}


// Returns a 3D column chart.
function flot_hcolumn_chart($graph_data, $width, $height, $water_mark, $font='', $font_size=7, $background_color='white', $tick_color='white', $val_min=null, $val_max=null, $pdf=false)
{
    global $config;

    // Include_javascript_dependencies_flot_graph().
    $return = '';

    $stacked_str = '';
    $multicolor = true;

    // Get a unique identifier to graph.
    $graph_id = uniqid('graph_');
    $graph_id2 = uniqid('graph_');

    // Set some containers to legend, graph, timestamp tooltip, etc.
    $return .= "<div id='$graph_id' class='graph' style='width: ".$width.'px; height: '.$height."px; padding-left: 20px;'></div>";
    $return .= "<div id='value_$graph_id' class='flot_container'></div>";

    if ($water_mark != '') {
        $return .= "<div id='watermark_$graph_id' class='invisible absolute'><img id='watermark_image_$graph_id' src='$water_mark'></div>";
        $watermark = 'true';
    } else {
        $watermark = 'false';
    }

    // Set a weird separator to serialize and unserialize passing data
    // from php to javascript.
    $separator = ';;::;;';
    $separator2 = ':,:,,,:,:';

    // Transform data from our format to library format.
    $labels = [];
    $a = [];
    $vars = [];

    $max = (PHP_INT_MIN + 1);
    $min = (PHP_INT_MAX - 1);
    $i = count($graph_data);
    $data = [];

    foreach ($graph_data as $label => $values) {
        $labels[] = io_safe_output($label);
        $i--;

        foreach ($values as $key => $value) {
            $jsvar = 'data_'.$graph_id.'_'.$key;

            $data[$jsvar][] = $value;

            if ($value > $max) {
                $max = $value;
            }

            if ($value < $min) {
                $min = $value;
            }
        }
    }

    if (!is_numeric($val_min)) {
        $val_min = $min;
    }

    if (!is_numeric($val_max)) {
        $val_max = $max;
    }

    // Store serialized data to use it from javascript.
    $labels = implode($separator, $labels);

    // Store data series in javascript format.
    $jsvars = '';
    $jsseries = [];

    $i = 0;

    $values2 = [];

    foreach ($data as $jsvar => $values) {
        $values2[] = implode($separator, $values);
    }

    $values = implode($separator2, $values2);

    $jsseries = implode(',', $jsseries);

    // Javascript code.
    $return .= "<script type='text/javascript'>";
    $return .= "pandoraFlotHBars('$graph_id', '$values', '$labels', $max, '$water_mark', '$separator', '$separator2', '$font', $font_size, '$background_color', '$tick_color', $val_min, $val_max)";
    $return .= '</script>';

    return $return;
}


/**
 * Draw html graph vertical bars.
 *
 * @param array $options Settings draw chart.
 *
 * @return mixed
 */
function flot_vcolumn_chart(array $options)
{
    global $config;

    $stacked_str = '';
    $multicolor = false;

    // Get a unique identifier to graph.
    $graphId = uniqid('graph_');

    // Div draw chart.
    $style = 'width: 100%; height: 100%;';
    if (isset($options['generals']['pdf']['width']) === true
        && isset($options['generals']['pdf']['height']) === true
    ) {
        $style = 'width:'.$options['generals']['pdf']['width'].'px;';
        $style .= 'height:'.$options['generals']['pdf']['height'].'px;';
    }

    if ($options['agent_view'] === true) {
        $style = 'width: 95%; height: 85px';
    }

    $class = '';
    if ($options['generals']['rotate'] === true) {
        $class = 'bars-graph-rotate';
    }

    $return .= '<div id="'.$graphId.'" class="'.$class.'" style="'.$style.'">';
    $return .= '</div>';

    // Set some containers to legend, graph, timestamp tooltip, etc.
    $return .= '<div id="value_'.$graphId.'" class="invisible"></div>';

    // Add id to options.
    $options['graphId'] = $graphId;

    // If pandora_black theme its enabled then change grid colors.
    $settings = base64_encode(json_encode($options));

    // Javascript code.
    $return .= '<script type="text/javascript">';
    $return .= 'pandoraFlotVBars(\''.$settings.'\')';
    $return .= '</script>';

    return $return;
}


function flot_slicesbar_graph(
    $graph_data,
    $period,
    $width,
    $height,
    $legend,
    $colors,
    $fontpath,
    $round_corner,
    $homeurl,
    $watermark='',
    $adapt_key='',
    $stat_win=false,
    $id_agent=0,
    $full_legend_date=[],
    $not_interactive=0,
    $ttl=1,
    $sizeForTicks=false,
    $show=true,
    $date_to=false,
    $server_id=''
) {
    global $config;

    if ($ttl == 2) {
        $tokem_config = uniqid('slicebar');
        $params = [
            'tokem_config'       => $tokem_config,
            'period'             => $period,
            'width'              => $width,
            'height'             => $height,
            'legend'             => $legend,
            'colors'             => $colors,
            'fontpath'           => $fontpath,
            'round_corner'       => $round_corner,
            'homeurl'            => $homeurl,
            'watermark'          => $watermark,
            'adapt_key'          => $adapt_key,
            'stat_win'           => $stat_win,
            'id_agent'           => $id_agent,
            'full_legend_date'   => $full_legend_date,
            'not_interactive'    => $not_interactive,
            'ttl'                => 1,
            'sizeForTicks'       => $sizeForTicks,
            'show'               => $show,
            'return_img_base_64' => true,
            'date_to'            => $date_to,
            'server_id'          => $server_id,
        ];
        // TO-DO Cambiar esto para que se pase por POST, NO SE PUEDE PASAR POR GET.
        update_check_config_token($tokem_config, json_encode($graph_data));
        $_SESSION['slicebar'] = $tokem_config;
        $_SESSION['slicebar_value'] = json_encode($graph_data);
        $graph = '<img src="data:image/png;base64,';
        $graph .= generator_chart_to_pdf('slicebar', $params);
        $graph .= '" />';

        return $graph;
    }

    // Get a unique identifier to graph.
    $graph_id = uniqid('graph_');

    // Set some containers to legend, graph, timestamp tooltip, etc.
    if (is_numeric($height) === true) {
        $height = ((int) $height + 15);
    }

    $style = 'width:'.$width.'%;';

    // Fixed height size.
    if (empty($height) === true) {
        $style .= 'height: 100%;';
    } else {
        if (is_numeric($height) === true) {
            $style .= 'height: '.$height.'px;';
        } else {
            $style .= 'height: '.$height.';';
        }
    }

    $return = "<div id='".$graph_id."' class='noresizevc graph ".$adapt_key."' style='".$style."'></div>";

    $return .= "<div id='value_".$graph_id."' class='flot_container'></div>";

    // Set a weird separator to serialize and unserialize
    // passing data from php to javascript.
    $separator = ';;::;;';
    $separator2 = ':,:,,,:,:';

    // Transform data from our format to library format.
    $vars = [];

    $datacolor = [];

    $fontsize = ((int) $config['font_size'] + 2);
    $fontpath = $config['fontpath'];

    $return .= '<div id="extra_'.$graph_id.'" class="slicebar-box-hover-styles invisible" style="font-size:'.$fontsize.'"></div>';

    $maxticks = (int) 20;
    if ($sizeForTicks === false) {
        $maxticks = (int) 12;
    } else if ($sizeForTicks < 300) {
        $maxticks = (int) 3;
    } else if ($sizeForTicks < 600) {
        $maxticks = (int) 6;
    } else if ($sizeForTicks < 900) {
        $maxticks = (int) 9;
    }

    $intervaltick = ($period / $maxticks);

    $maxticks_aux = $maxticks;

    while (1) {
        if ($maxticks_aux <= $maxticks) {
            break;
        }

        $intervaltick *= 2;

        $maxticks_aux /= 2;
    }

    $intervaltick = (int) $intervaltick;

    $i = count($graph_data);
    foreach ($graph_data as $label => $values) {
        $i--;

        foreach ($values as $key => $value) {
            $jsvar = 'd_'.$graph_id.'_'.$i;
            if ($key == 'data') {
                $datacolor[$jsvar] = $colors[$value];
                continue;
            }

            $data[$jsvar][] = $value;
        }
    }

    // Store serialized data to use it from javascript.
    $datacolor = implode($separator, $datacolor);
    if (is_array($legend)) {
        $legend = io_safe_output(implode($separator, $legend));
    }

    if (!empty($full_legend_date) && count($full_legend_date) > 0) {
        $full_legend_date = io_safe_output(implode($separator, $full_legend_date));
    } else {
        $full_legend_date = false;
    }

    if (!$date_to || $date_to === '1') {
        $date_to = get_system_time();
    }

    $datelimit = (($date_to - $period));

    $i = 0;
    $values2 = [];
    foreach ($data as $jsvar => $values) {
        $values2[] = implode($separator, $values);
        $i++;
    }

    $values = implode($separator2, $values2);

    // Javascript code.
    $return .= "<script type='text/javascript'>";
    $return .= "//<![CDATA[\n";
    $return .= "pandoraFlotSlicebar(
        '$graph_id',
        '$values',
        '$datacolor',
        '$legend',
        $intervaltick,
        '$fontpath',
        $fontsize,
        '$separator',
        '$separator2',
        $id_agent,
        '$full_legend_date',
        $not_interactive,
        '$show',
        $datelimit,
        '$server_id'
    )";
    $return .= "\n//]]>";
    $return .= '</script>';

    return $return;
}
