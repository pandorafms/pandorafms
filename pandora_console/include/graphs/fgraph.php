<?php
// Copyright (c) 2011-2011 Pandora FMS
// http://www.pandorafms.com  <info@pandorafms.com>
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// Turn on output buffering.
// The entire buffer will be discarded later so that any accidental output
// does not corrupt images generated by fgraph.
use Artica\PHPChartJS\Factory;

ob_start();

global $config;

if (empty($config['homedir'])) {
    include_once '../../include/config.php';
    global $config;
}

require_once $config['homedir'].'/include/functions.php';
require_once $config['homedir'].'/include/graphs/functions_flot.php';

$ttl = get_parameter('ttl', 1);
$graph_type = get_parameter('graph_type', '');

if (!empty($graph_type)) {
    include_once $config['homedir'].'/include/functions_html.php';
    include_once $config['homedir'].'/include/graphs/functions_gd.php';
    include_once $config['homedir'].'/include/graphs/functions_utils.php';
    include_once $config['homedir'].'/include/graphs/functions_d3.php';
    include_once $config['homedir'].'/include/graphs/functions_flot.php';
}

// Clean the output buffer and turn off output buffering
ob_end_clean();

switch ($graph_type) {
    case 'histogram':
        $width = get_parameter('width');
        $height = get_parameter('height');
        $data = json_decode(io_safe_output(get_parameter('data')), true);

        $max = get_parameter('max');
        $title = get_parameter('title');
        $mode = get_parameter('mode', 1);
        gd_histogram($width, $height, $mode, $data, $max, $config['fontpath'], $title);
    break;

    case 'progressbar':
        $width = get_parameter('width');
        $height = get_parameter('height');
        $progress = get_parameter('progress');

        $out_of_lim_str = io_safe_output(get_parameter('out_of_lim_str', false));
        $out_of_lim_image = get_parameter('out_of_lim_image', false);

        // Add relative path to avoid phar object injection.
        $out_of_lim_image = '../graphs/'.$out_of_lim_image;

        $title = get_parameter('title');

        $mode = get_parameter('mode', 1);

        $fontsize = get_parameter('fontsize', 10);

        $value_text = get_parameter('value_text', '');
        $colorRGB = get_parameter('colorRGB', '');

        gd_progress_bar(
            $width,
            $height,
            $progress,
            $title,
            $config['fontpath'],
            $out_of_lim_str,
            $out_of_lim_image,
            $mode,
            $fontsize,
            $value_text,
            $colorRGB
        );
    break;

    case 'progressbubble':
        $width = get_parameter('width');
        $height = get_parameter('height');
        $progress = get_parameter('progress');

        $out_of_lim_str = io_safe_output(get_parameter('out_of_lim_str', false));
        $out_of_lim_image = get_parameter('out_of_lim_image', false);

        $title = get_parameter('title');

        $mode = get_parameter('mode', 1);

        $fontsize = get_parameter('fontsize', 7);

        $value_text = get_parameter('value_text', '');
        $colorRGB = get_parameter('colorRGB', '');

        gd_progress_bubble(
            $width,
            $height,
            $progress,
            $title,
            $config['fontpath'],
            $out_of_lim_str,
            $out_of_lim_image,
            $mode,
            $fontsize,
            $value_text,
            $colorRGB
        );
    break;
}


function progressbar(
    $progress,
    $width,
    $height,
    $title,
    $font,
    $mode=1,
    $out_of_lim_str=false,
    $out_of_lim_image=false,
    $ttl=1
) {
    $graph = [];

    $graph['progress'] = $progress;
    $graph['width'] = $width;
    $graph['height'] = $height;
    $graph['out_of_lim_str'] = $out_of_lim_str;
    $graph['out_of_lim_image'] = $out_of_lim_image;
    $graph['title'] = $title;
    $graph['font'] = $font;
    $graph['mode'] = $mode;

    $id_graph = serialize_in_temp($graph, null, $ttl);
    if (is_metaconsole()) {
        return "<img src='../../include/graphs/functions_gd.php?static_graph=1&graph_type=progressbar&ttl=".$ttl.'&id_graph='.$id_graph."'>";
    } else {
        return "<img src='include/graphs/functions_gd.php?static_graph=1&graph_type=progressbar&ttl=".$ttl.'&id_graph='.$id_graph."'>";
    }
}


/**
 * Draw vertical bars graph.
 *
 * @param array|null $chart_data Data chart.
 * @param array      $params     Params draw chart.
 * @param integer    $ttl        Pdf option.
 *
 * @return mixed
 */
function vbar_graph(
    array|null $chart_data,
    array $options
) {
    if (empty($chart_data) === true) {
        if (isset($options['ttl']) === true
            && (int) $options['ttl'] === 2
        ) {
            $options['base64'] = true;
        }

        return graph_nodata_image($options);
    }

    if (isset($options['ttl']) === true && (int) $options['ttl'] === 2) {
        $params = [
            'chart_data'         => $chart_data,
            'options'            => $options,
            'return_img_base_64' => true,
        ];

        return generator_chart_to_pdf('vbar_graph', $params);
    }

    $chart = get_build_setup_charts('BAR', $options, $chart_data);
    $output = $chart->render(true);
    return $output;
}


function area_graph(
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

    include_once 'functions_flot.php';

    if ($water_mark !== false) {
        setup_watermark($water_mark, $water_mark_file, $water_mark_url);
    }

    return flot_area_graph(
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
    );
}


function stacked_bullet_chart(
    $chart_data,
    $width,
    $height,
    $color,
    $legend,
    $long_index,
    $no_data_image,
    $xaxisname='',
    $yaxisname='',
    $water_mark='',
    $font='',
    $font_size='',
    $unit='',
    $ttl=1,
    $homeurl='',
    $backgroundColor='white'
) {
    include_once 'functions_d3.php';

    if ($water_mark !== false) {
        setup_watermark($water_mark, $water_mark_file, $water_mark_url);
    }

    if (empty($chart_data)) {
        return '<img src="'.$no_data_image.'" />';
    }

    return d3_bullet_chart(
        $chart_data,
        $width,
        $height,
        $color,
        $legend,
        $homeurl,
        $unit,
        $font,
        $font_size
    );

}


function stacked_gauge(
    $chart_data,
    $width,
    $height,
    $color,
    $legend,
    $no_data_image,
    $font='',
    $font_size='',
    $unit='',
    $homeurl='',
    $transitionDuration=500
) {
    include_once 'functions_d3.php';

    if (empty($chart_data)) {
        return '<img src="'.$no_data_image.'" />';
    }

    return d3_gauges(
        $chart_data,
        $width,
        $height,
        $color,
        $legend,
        $homeurl,
        $unit,
        $font,
        ($font_size + 2),
        $no_data_image,
        $transitionDuration
    );
}


function hbar_graph(
    $chart_data,
    $width,
    $height,
    $color,
    $legend,
    $long_index,
    $no_data_image,
    $xaxisname='',
    $yaxisname='',
    $water_mark='',
    $font='',
    $font_size='',
    $unit='',
    $ttl=1,
    $homeurl='',
    $backgroundColor='white',
    $tick_color='white',
    $val_min=null,
    $val_max=null,
    $base64=false,
    $pdf=false
) {
    global $config;
    if ($water_mark !== false) {
        setup_watermark($water_mark, $water_mark_file, $water_mark_url);
    }

    if ($chart_data === false || empty($chart_data) === true) {
        return graph_nodata_image($options);
    }

    if ($ttl == 2) {
        $params = [
            'chart_data'         => $chart_data,
            'width'              => $width,
            'height'             => $height,
            'water_mark_url'     => $water_mark_url,
            'font'               => $font,
            'font_size'          => $font_size,
            'backgroundColor'    => $backgroundColor,
            'tick_color'         => $tick_color,
            'val_min'            => $val_min,
            'val_max'            => $val_max,
            'return_img_base_64' => $base64,
        ];
        return generator_chart_to_pdf('hbar', $params);
    }

    if ($config['style'] === 'pandora_black' && !is_metaconsole() && $ttl === 1) {
        $backgroundColor = '#222';
    }

    if ($config['style'] === 'pandora_black' && !is_metaconsole() && $ttl === 1) {
        $tick_color = '#fff';
    }

    return flot_hcolumn_chart(
        $chart_data,
        $width,
        $height,
        $water_mark_url,
        $font,
        $font_size,
        $backgroundColor,
        $tick_color,
        $val_min,
        $val_max,
        $pdf
    );
}


/**
 * Pie graph PIE.
 *
 * @param array $chart_data Data.
 * @param array $options    Options.
 *
 * @return string  Output html charts
 */
function pie_graph(
    $chart_data,
    $options
) {
    if (empty($chart_data) === true) {
        if (isset($options['ttl']) === true
            && (int) $options['ttl'] === 2
        ) {
            $options['base64'] = true;
        }

        return graph_nodata_image($options);
    }

    // Number max elements.
    $max_values = (isset($options['maxValues']) === true) ? $options['maxValues'] : 15;
    if (count($chart_data) > $max_values) {
        $others_str = (isset($options['otherStr']) === true) ? $options['otherStr'] : __('Others');
        $chart_data_trunc = [];
        $n = 1;
        foreach ($chart_data as $key => $value) {
            if ($n <= $max_values) {
                $chart_data_trunc[$key] = $value;
            } else {
                if (isset($options['labels'][$key]) === true) {
                    unset($options['labels'][$key]);
                }

                if (isset($chart_data_trunc[$others_str]) === false) {
                    $chart_data_trunc[$others_str] = 0;
                }

                if (empty($value) === false) {
                    $chart_data_trunc[$others_str] += (float) $value;
                }
            }

            $n++;
        }

        $options['labels'][$max_values] = $others_str;
        $chart_data = $chart_data_trunc;
    }

    if (isset($options['ttl']) === true
        && (int) $options['ttl'] === 2
    ) {
        $params = [
            'chart_data'         => $chart_data,
            'options'            => $options,
            'return_img_base_64' => true,
        ];

        return generator_chart_to_pdf('pie_graph', $params);
    }

    $chart = get_build_setup_charts('PIE', $options, $chart_data);
    $output = $chart->render(true, true);
    return $output;
}


/**
 * Rin graph DOUGHNUT.
 *
 * @param array $chart_data Data.
 * @param array $options    Options.
 *
 * @return string  Output html charts
 */
function ring_graph(
    $chart_data,
    $options
) {
    global $config;

    if (empty($chart_data) === true) {
        if (isset($options['ttl']) === true
            && (int) $options['ttl'] === 2
        ) {
            $options['base64'] = true;
        }

        return graph_nodata_image($options);
    }

    if (isset($options['ttl']) === true && (int) $options['ttl'] === 2) {
        $params = [
            'chart_data'         => $chart_data,
            'options'            => $options,
            'return_img_base_64' => true,
        ];

        return generator_chart_to_pdf('ring_graph', $params);
    }

    $chart = get_build_setup_charts('DOUGHNUT', $options, $chart_data);
    $output = $chart->render(true, true);
    return $output;
}


function get_build_setup_charts($type, $options, $data)
{
    global $config;

    $factory = new Factory();

    switch ($type) {
        case 'DOUGHNUT':
            $chart = $factory->create($factory::DOUGHNUT);
        break;

        case 'PIE':
            $chart = $factory->create($factory::PIE);
        break;

        case 'BAR':
            $chart = $factory->create($factory::BAR);
        break;

        default:
            // code...
        break;
    }

    $example = [
        'id'                  => null,
        'width'               => null,
        'height'              => null,
        'maintainAspectRatio' => false,
        'responsive'          => true,
        'radius'              => null,
        'rotation'            => null,
        'circumference'       => null,
        'axis'                => 'y',
        'legend'              => [
            'display'  => true,
            'position' => 'top',
            'align'    => 'center',
            'font'     => [
                'family'     => '',
                'size'       => 12,
                'style'      => 'normal',
                'weight'     => null,
                'lineHeight' => 1.2,
            ],
        ],
        'title'               => [
            'display'  => true,
            'position' => 'top',
            'color'    => '',
            'align'    => 'center',
            'text'     => '',
            'font'     => [
                'family'     => '',
                'size'       => 12,
                'style'      => 'normal',
                'weight'     => null,
                'lineHeight' => 1.2,
            ],
        ],
        'dataLabel'           => [
            'display'   => true,
            'color'     => '',
            'clip'      => true,
            'clamp'     => true,
            'anchor'    => 'center',
            'formatter' => 'namefunction',
            'fonts'     => [
                'family'     => '',
                'size'       => 12,
                'style'      => 'normal',
                'weight'     => null,
                'lineHeight' => 1.2,
            ],
        ],
        'scales'              => [
            'x' => [
                'grid'  => [
                    'display' => false,
                    'color'   => 'orange',
                ],
                'ticks' => [
                    'fonts' => [
                        'family'     => '',
                        'size'       => 12,
                        'style'      => 'normal',
                        'weight'     => null,
                        'lineHeight' => 1.2,
                    ],
                ],
            ],
            'y' => [
                'grid'  => [
                    'display' => false,
                    'color'   => 'orange',
                ],
                'ticks' => [
                    'fonts' => [
                        'family'     => '',
                        'size'       => 12,
                        'style'      => 'normal',
                        'weight'     => null,
                        'lineHeight' => 1.2,
                    ],
                ],
            ],
        ],
    ];

    // Set Id.
    $id = uniqid('graph_');
    if (isset($options['id']) === true && empty($options['id']) === false) {
        $id = $options['id'];
    }

    $chart->setId($id);

    // Height is null maximum possible.
    if (isset($options['height']) === true
        && empty($options['height']) === false
    ) {
        $chart->setHeight($options['height']);
    }

    // Width is null maximum possible.
    if (isset($options['width']) === true
        && empty($options['width']) === false
    ) {
        $chart->setWidth($options['width']);
    }

    // Fonts defaults.
    $chart->defaults()->getFonts()->setFamily((empty($config['fontpath']) === true) ? 'Lato' : $config['fontpath']);
    $chart->defaults()->getFonts()->setStyle('normal');
    $chart->defaults()->getFonts()->setWeight(600);
    $chart->defaults()->getFonts()->setSize(((int) $config['font_size'] + 2));

    if (isset($options['waterMark']) === true
        && empty($options['waterMark']) === false
    ) {
        // WaterMark.
        $chart->defaults()->getWaterMark()->setWidth(88);
        $chart->defaults()->getWaterMark()->setHeight(16);
        $chart->defaults()->getWaterMark()->setSrc($options['waterMark']['url']);
        $chart->defaults()->getWaterMark()->setPosition('end');
        $chart->defaults()->getWaterMark()->setAlign('top');
    }

    if ((isset($options['pdf']) === true && $options['pdf'] === true)
        || (isset($options['ttl']) === true && (int) $options['ttl'] === 2)
    ) {
        $chart->options()->disableAnimation(false);
    }

    // Set Maintain Aspect Ratio for responsive charts.
    $maintainAspectRatio = false;
    if (isset($options['maintainAspectRatio']) === true
        && empty($options['maintainAspectRatio']) === false
    ) {
        $maintainAspectRatio = $options['maintainAspectRatio'];
    }

    $chart->options()->setMaintainAspectRatio($maintainAspectRatio);

    // Set Responsive for responsive charts.
    $responsive = true;
    if (isset($options['responsive']) === true
        && empty($options['responsive']) === false
    ) {
        $responsive = $options['responsive'];
    }

    $chart->options()->setResponsive($responsive);

    // LEGEND.
    if (isset($options['legend']) === true
        && empty($options['legend']) === false
        && is_array($options['legend']) === true
    ) {
        $legend = $chart->options()->getPlugins()->getLegend();

        // Set Display legends.
        $legendDisplay = true;
        if (isset($options['legend']['display']) === true) {
            $legendDisplay = $options['legend']['display'];
        }

        $legend->setDisplay($legendDisplay);

        // Set Position legends.
        $legendPosition = 'top';
        if (isset($options['legend']['position']) === true
            && empty($options['legend']['position']) === false
        ) {
            $legendPosition = $options['legend']['position'];
        }

        $legend->setPosition($legendPosition);

        // Set Align legends.
        $legendAlign = 'center';
        if (isset($options['legend']['align']) === true
            && empty($options['legend']['align']) === false
        ) {
            $legendAlign = $options['legend']['align'];
        }

        $legend->setAlign($legendAlign);

        // Defaults fonts legends.
        $legend->labels()->getFonts()->setFamily((empty($config['fontpath']) === true) ? 'lato' : $config['fontpath']);
        $legend->labels()->getFonts()->setStyle('normal');
        $legend->labels()->getFonts()->setWeight(600);
        $legend->labels()->getFonts()->setSize(((int) $config['font_size'] + 2));
        if (isset($options['legend']['fonts']) === true
            && empty($options['legend']['fonts']) === false
            && is_array($options['legend']['fonts']) === true
        ) {
            if (isset($options['legend']['fonts']['size']) === true) {
                $legend->labels()->getFonts()->setSize($options['legend']['fonts']['size']);
            }

            if (isset($options['legend']['fonts']['style']) === true) {
                $legend->labels()->getFonts()->setStyle($options['legend']['fonts']['style']);
            }

            if (isset($options['legend']['fonts']['weight']) === true) {
                $legend->labels()->getFonts()->setWeight($options['legend']['fonts']['weight']);
            }

            if (isset($options['legend']['fonts']['family']) === true) {
                $legend->labels()->getFonts()->setFamily($options['legend']['fonts']['family']);
            }
        }
    }

    if (isset($options['layout']) === true
        && empty($options['layout']) === false
        && is_array($options['layout']) === true
    ) {
        $layout = $chart->options()->getLayout();
        if (isset($options['layout']['padding']) === true
            && empty($options['layout']['padding']) === false
            && is_array($options['layout']['padding']) === true
        ) {
            if (isset($options['layout']['padding']['top']) === true) {
                $layout->padding()->setTop($options['layout']['padding']['top']);
            }

            if (isset($options['layout']['padding']['bottom']) === true) {
                $layout->padding()->setBottom($options['layout']['padding']['bottom']);
            }

            if (isset($options['layout']['padding']['left']) === true) {
                $layout->padding()->setLeft($options['layout']['padding']['left']);
            }

            if (isset($options['layout']['padding']['right']) === true) {
                $layout->padding()->setRight($options['layout']['padding']['right']);
            }
        }
    }

    // Display labels.
    if (isset($options['dataLabel']) === true
        && empty($options['dataLabel']) === false
        && is_array($options['dataLabel']) === true
    ) {
        $dataLabel = $chart->options()->getPlugins()->getDataLabel();

        $chart->addPlugin('ChartDataLabels');

        $dataLabelDisplay = 'auto';
        if (isset($options['dataLabel']['display']) === true) {
            $dataLabelDisplay = $options['dataLabel']['display'];
        }

        $dataLabel->setDisplay($dataLabelDisplay);

        $dataLabelColor = '#343434';
        if (isset($options['dataLabel']['color']) === true) {
            $dataLabelColor = $options['dataLabel']['color'];
        }

        $dataLabel->setColor($dataLabelColor);

        $dataLabelClip = false;
        if (isset($options['dataLabel']['clip']) === true) {
            $dataLabelClip = $options['dataLabel']['clip'];
        }

        $dataLabel->setClip($dataLabelClip);

        $dataLabelClamp = true;
        if (isset($options['dataLabel']['clamp']) === true) {
            $dataLabelClamp = $options['dataLabel']['clamp'];
        }

        $dataLabel->setClamp($dataLabelClamp);

        $dataLabelAnchor = 'end';
        if (isset($options['dataLabel']['anchor']) === true) {
            $dataLabelAnchor = $options['dataLabel']['anchor'];
        }

        $dataLabel->setAnchor($dataLabelAnchor);

        $dataLabelAlign = 'end';
        if (isset($options['dataLabel']['align']) === true) {
            $dataLabelAlign = $options['dataLabel']['align'];
        }

        $dataLabel->setAlign($dataLabelAlign);

        $dataLabelOffset = 0;
        if (isset($options['dataLabel']['offset']) === true) {
            $dataLabelOffset = $options['dataLabel']['offset'];
        }

        $dataLabel->setOffset($dataLabelOffset);

        switch ($type) {
            case 'DOUGHNUT':
            case 'PIE':
                $dataLabelFormatter = 'formatterDataLabelPie';
            break;

            case 'BAR':
                if (isset($options['axis']) === true
                    && empty($options['axis']) === false
                ) {
                    $dataLabelFormatter = 'formatterDataHorizontalBar';
                } else {
                    $dataLabelFormatter = 'formatterDataVerticalBar';
                }
            break;

            default:
                // Not possible.
            break;
        }

        if (isset($options['dataLabel']['formatter']) === true) {
            $dataLabelFormatter = $options['dataLabel']['formatter'];
        }

        $dataLabel->setFormatter($dataLabelFormatter);

        // Defaults fonts datalabel.
        $dataLabel->getFonts()->setFamily((empty($config['fontpath']) === true) ? 'lato' : $config['fontpath']);
        $dataLabel->getFonts()->setStyle('normal');
        $dataLabel->getFonts()->setWeight(600);
        $dataLabel->getFonts()->setSize(((int) $config['font_size'] + 2));

        if (isset($options['dataLabel']['fonts']) === true
            && empty($options['dataLabel']['fonts']) === false
            && is_array($options['dataLabel']['fonts']) === true
        ) {
            if (isset($options['dataLabel']['fonts']['size']) === true) {
                $dataLabel->getFonts()->setSize($options['dataLabel']['fonts']['size']);
            }

            if (isset($options['dataLabel']['fonts']['style']) === true) {
                $dataLabel->getFonts()->setStyle($options['dataLabel']['fonts']['style']);
            }

            if (isset($options['dataLabel']['fonts']['weight']) === true) {
                $dataLabel->getFonts()->setWeight($options['dataLabel']['fonts']['weight']);
            }

            if (isset($options['dataLabel']['fonts']['family']) === true) {
                $dataLabel->getFonts()->setFamily($options['dataLabel']['fonts']['family']);
            }
        }
    }

    // Title.
    if (isset($options['title']) === true
        && empty($options['title']) === false
        && is_array($options['title']) === true
    ) {
        $chartTitle = $chart->options()->getPlugins()->getTitle();

        $display = false;
        if (isset($options['title']['display']) === true) {
            $display = $options['title']['display'];
        }

        $chartTitle->setDisplay($display);

        $text = __('Title');
        if (isset($options['title']['text']) === true) {
            $text = $options['title']['text'];
        }

        $chartTitle->setText($text);

        $position = 'top';
        if (isset($options['title']['position']) === true) {
            $position = $options['title']['position'];
        }

        $chartTitle->setPosition($position);

        $color = 'top';
        if (isset($options['title']['color']) === true) {
            $color = $options['title']['color'];
        }

        $chartTitle->setColor($color);

        if (isset($options['title']['fonts']) === true
            && empty($options['title']['fonts']) === false
            && is_array($options['title']['fonts']) === true
        ) {
            if (isset($options['title']['fonts']['size']) === true) {
                $chartTitle->getFonts()->setSize($options['title']['fonts']['size']);
            }

            if (isset($options['title']['fonts']['style']) === true) {
                $chartTitle->getFonts()->setStyle($options['title']['fonts']['style']);
            }

            if (isset($options['title']['fonts']['family']) === true) {
                $chartTitle->getFonts()->setFamily($options['title']['fonts']['family']);
            }
        }
    }

    // Radius is null maximum possible.
    if (isset($options['radius']) === true
        && empty($options['radius']) === false
    ) {
        $chart->setRadius($options['radius']);
    }

    // Rotation is null 0º.
    if (isset($options['rotation']) === true
        && empty($options['rotation']) === false
    ) {
        $chart->setRotation($options['rotation']);
    }

    // Circumferende is null 360º.
    if (isset($options['circumference']) === true
        && empty($options['circumference']) === false
    ) {
        $chart->setCircumference($options['circumference']);
    }

    if (isset($options['scales']) === true
        && empty($options['scales']) === false
        && is_array($options['scales']) === true
    ) {
        $scales = $chart->options()->getScales();

        // Defaults scalesFont X.
        $scalesXFonts = $scales->getX()->ticks()->getFonts();
        $scalesXFonts->setFamily((empty($config['fontpath']) === true) ? 'lato' : $config['fontpath']);
        $scalesXFonts->setStyle('normal');
        $scalesXFonts->setWeight(600);
        $scalesXFonts->setSize(((int) $config['font_size'] + 2));

        // Defaults scalesFont Y.
        $scalesYFonts = $scales->getY()->ticks()->getFonts();
        $scalesYFonts->setFamily((empty($config['fontpath']) === true) ? 'lato' : $config['fontpath']);
        $scalesYFonts->setStyle('normal');
        $scalesYFonts->setWeight(600);
        $scalesYFonts->setSize(((int) $config['font_size'] + 2));

        if (isset($options['scales']['x']) === true
            && empty($options['scales']['x']) === false
            && is_array($options['scales']['x']) === true
        ) {
            if (isset($options['scales']['x']['bounds']) === true) {
                $scales->getX()->setBounds($options['scales']['x']['bounds']);
            }

            if (isset($options['scales']['x']['grid']) === true
                && empty($options['scales']['x']['grid']) === false
                && is_array($options['scales']['x']['grid']) === true
            ) {
                if (isset($options['scales']['x']['grid']['display']) === true) {
                    $scales->getX()->grid()->setDrawOnChartArea($options['scales']['x']['grid']['display']);
                }

                if (isset($options['scales']['x']['grid']['color']) === true) {
                    $scales->getX()->grid()->setColor($options['scales']['x']['grid']['color']);
                }
            }

            if (isset($options['scales']['x']['ticks']) === true
                && empty($options['scales']['x']['ticks']) === false
                && is_array($options['scales']['x']['ticks']) === true
            ) {
                if (isset($options['scales']['x']['ticks']['fonts']) === true
                    && empty($options['scales']['x']['ticks']['fonts']) === false
                    && is_array($options['scales']['x']['ticks']['fonts']) === true
                ) {
                    $scaleXTicksFonts = $scales->getX()->ticks()->getFonts();
                    if (isset($options['scales']['x']['ticks']['fonts']['size']) === true) {
                        $scaleXTicksFonts->setSize($options['scales']['x']['ticks']['fonts']['size']);
                    }

                    if (isset($options['scales']['x']['ticks']['fonts']['style']) === true) {
                        $scaleXTicksFonts->setStyle($options['scales']['x']['ticks']['fonts']['style']);
                    }

                    if (isset($options['scales']['x']['ticks']['fonts']['family']) === true) {
                        $scaleXTicksFonts->setFamily($options['scales']['x']['ticks']['fonts']['family']);
                    }
                }
            }
        }

        if (isset($options['scales']['y']) === true
            && empty($options['scales']['y']) === false
            && is_array($options['scales']['y']) === true
        ) {
            if (isset($options['scales']['y']['bounds']) === true) {
                $scales->getY()->setBounds($options['scales']['y']['bounds']);
            }

            if (isset($options['scales']['y']['grid']) === true
                && empty($options['scales']['y']['grid']) === false
                && is_array($options['scales']['y']['grid']) === true
            ) {
                if (isset($options['scales']['y']['grid']['display']) === true) {
                    $scales->getY()->grid()->setDrawOnChartArea($options['scales']['y']['grid']['display']);
                }

                if (isset($options['scales']['y']['grid']['color']) === true) {
                    $scales->getY()->grid()->setColor($options['scales']['y']['grid']['color']);
                }
            }

            if (isset($options['scales']['y']['ticks']) === true
                && empty($options['scales']['y']['ticks']) === false
                && is_array($options['scales']['y']['ticks']) === true
            ) {
                if (isset($options['scales']['y']['ticks']['fonts']) === true
                    && empty($options['scales']['y']['ticks']['fonts']) === false
                    && is_array($options['scales']['y']['ticks']['fonts']) === true
                ) {
                    $scaleYTicksFonts = $scales->getY()->ticks()->getFonts();
                    if (isset($options['scales']['y']['ticks']['fonts']['size']) === true) {
                        $scaleYTicksFonts->setSize($options['scales']['y']['ticks']['fonts']['size']);
                    }

                    if (isset($options['scales']['y']['ticks']['fonts']['style']) === true) {
                        $scaleYTicksFonts->setStyle($options['scales']['y']['ticks']['fonts']['style']);
                    }

                    if (isset($options['scales']['y']['ticks']['fonts']['family']) === true) {
                        $scaleYTicksFonts->setFamily($options['scales']['y']['ticks']['fonts']['family']);
                    }
                }
            }
        }
    }

    // Color.
    if (isset($options['colors']) === true
        && empty($options['colors']) === false
        && is_array($options['colors']) === true
    ) {
        $colors = $options['colors'];
        $borders = $options['colors'];
    } else {
        // Colors.
        $defaultColor = [];
        $defaultBorder = [];
        $defaultColorArray = color_graph_array();
        foreach ($defaultColorArray as $key => $value) {
            list($r, $g, $b) = sscanf($value['color'], '#%02x%02x%02x');
            $defaultColor[$key] = 'rgba('.$r.', '.$g.', '.$b.', 0.6)';
            $defaultBorder[$key] = $value['color'];
        }

        $colors = array_values($defaultColor);
        $borders = array_values($defaultBorder);
    }

    // Set labels.
    if (isset($options['labels']) === true
        && empty($options['labels']) === false
        && is_array($options['labels']) === true
    ) {
        $chart->labels()->exchangeArray($options['labels']);
    }

    // Add Datasets.
    $setData = $chart->createDataSet();
    switch ($type) {
        case 'DOUGHNUT':
        case 'PIE':
            $setData->setLabel('data')->setBackgroundColor($borders);
            $setData->setLabel('data')->data()->exchangeArray(array_values($data));
        break;

        case 'BAR':
            $setData->setLabel('data')->setBackgroundColor($colors);
            $setData->setLabel('data')->setBorderColor($borders);
            $setData->setLabel('data')->setBorderWidth(2);

            $setData->setLabel('data')->data()->exchangeArray(array_values($data));

            // Para las horizontales.
            if (isset($options['axis']) === true
                && empty($options['axis']) === false
            ) {
                $chart->options()->setIndexAxis($options['axis']);
                $setData->setAxis($options['axis']);
            }
        break;

        default:
            // Not possible.
        break;
    }

    $chart->addDataSet($setData);

    return $chart;
}
