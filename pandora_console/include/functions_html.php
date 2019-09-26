<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2011 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage HTML
 */

if (!isset($config)) {
    $working_dir = getcwd();
    $working_dir = str_replace('\\', '/', $working_dir);
    // Windows compatibility.
    $levels = substr_count($working_dir, '/');

    for ($i = 0; $i < $levels; $i++) {
        if (file_exists(str_repeat('../', $i).'config.php')) {
            include_once str_repeat('../', $i).'config.php';
            break;
            // Skip config.php loading after load the first one.
        } else if (file_exists(str_repeat('../', $i).'include/config.php')) {
            // For path from the enterprise structure dirs.
            include_once str_repeat('../', $i).'include/config.php';
            break;
            // Skip config.php loading after load the first one.
        }
    }
} else {
    include_once $config['homedir'].'/include/functions.php';
    include_once $config['homedir'].'/include/functions_users.php';
    include_once $config['homedir'].'/include/functions_groups.php';
    include_once $config['homedir'].'/include/functions_ui.php';
}


/**
 * Prints the print_r with < pre > tags
 */
function html_debug_print($var, $file='', $oneline=false)
{
    $more_info = '';
    if (is_string($var)) {
        $more_info = 'size: '.strlen($var);
    } else if (is_bool($var)) {
        $more_info = 'val: '.($var ? 'true' : 'false');
    } else if (is_null($var)) {
        $more_info = 'is null';
    } else if (is_array($var)) {
        $more_info = count($var);
    }

    if ($file === true) {
        $file = '/tmp/logDebug';
    }

    if ($oneline && is_string($var)) {
        $var = preg_replace("/[\t|\n| ]+/", ' ', $var);
    }

    if (strlen($file) > 0) {
        $f = fopen($file, 'a');
        ob_start();
        echo date('Y/m/d H:i:s').' ('.gettype($var).') '.$more_info."\n";
        print_r($var);
        echo "\n\n";
        $output = ob_get_clean();
        fprintf($f, '%s', $output);
        fclose($f);
    } else {
        echo '<pre style="z-index: 10000; background: #fff; padding: 1em;">'.date('Y/m/d H:i:s').' ('.gettype($var).') '.$more_info."\n";
        print_r($var);
        echo '</pre>';
    }
}


// Alias for "html_debug_print"
function html_debug($var, $file='', $oneline=false)
{
    html_debug_print($var, $file, $oneline);
}


// Alias for "html_debug_print"
function hd($var, $file='', $oneline=false)
{
    html_debug_print($var, $file, $oneline);
}


/**
 * Encapsulation (ob) for debug print function.
 *
 * @param mixed   $var     Variable to be dumped.
 * @param string  $file    Target file path.
 * @param boolean $oneline Show in oneline.
 *
 * @return string Dump string.
 */
function obhd($var, $file='', $oneline=false)
{
    ob_start();
    hd($var, $file, $oneline);
    return ob_get_clean();
}


function debug()
{
    $args_num = func_num_args();
    $arg_list = func_get_args();

    for ($i = 0; $i < $args_num; $i++) {
        html_debug_print($arg_list[$i], true);
    }
}


function html_f2str($function, $params)
{
    ob_start();

    call_user_func_array($function, $params);

    return ob_get_clean();
}


/**
 * Print side layer
 *
 * @params mixed Hash with all the params:
 *
 *     position: left or right
 *  width: width of the layer
 *     height: height of the layer
 *     icon_closed: icon showed when layer is hidden
 *     icon_open: icon showed when layer is showed
 *     top_text: text over the content
 *     body_text: content of layer
 *     bottom_text: text under the contet
 *
 * @return string HTML code if return parameter is true.
 */


function html_print_side_layer($params)
{
    global $config;

    // Check mandatory values, if any of them is missed, return ''
    $mandatory = [
        'icon_closed',
        'body_text',
    ];

    foreach ($mandatory as $man) {
        if (!isset($params[$man])) {
            return '';
        }
    }

    // Set default values if not setted
    $defaults = [
        'position'      => 'left',
        'width'         => '400',
        'height'        => '97%',
        'top_text'      => '',
        'bottom_text'   => '',
        'top'           => '0',
        'autotop'       => '',
        'right'         => '0',
        'autoright'     => '',
        'vertical_mode' => 'out',
        'icon_width'    => 50,
        'icon_height'   => 50,
        'icon_open'     => $params['icon_closed'],
    ];

    foreach ($defaults as $token => $value) {
        if (!isset($params[$token])) {
            $params[$token] = $value;
        }
    }

    // z-index is 1 because 2 made the calendar show under the side_layer
    switch ($params['position']) {
        case 'left':
            $round_class = 'menu_sidebar_radius_right';
            $body_float = 'left';
            $button_float = 'right';
        break;

        case 'right':
            $round_class = 'menu_sidebar_radius_left';
            $body_float = 'right';
            $button_float = 'left';
        break;

        case 'bottom':
            $round_class = 'menu_sidebar_radius_left menu_sidebar_radius_right';
            $body_float = 'right';
            $button_float = 'left';
        break;
    }

    $out_html = '<div id="side_layer" class="menu_sidebar '.$round_class.'" style="display:none; z-index:1; overflow: hidden; height: '.$params['height'].'; width: '.$params['width'].';">';

    $table = new stdClass();
    $table->id = 'side_layer_layout';
    $table->width = $params['width'].'px';
    $table->cellspacing = 2;
    $table->cellpadding = 2;
    $table->class = 'none';

    $top = '<div id="side_top_text" style="width: 100%";">'.$params['top_text'].'</div>';

    $button = '<div id="show_menu" style="vertical-align: middle; position: relative; width: '.$params['icon_width'].'px;  padding-right: 17px; text-align: right; height: '.$params['icon_height'].'px;">';
    // Use the no_meta parameter because this image is only in the base console
    $button .= html_print_image(
        $params['position'] == 'left' ? $params['icon_open'] : $params['icon_closed'],
        true,
        ['id' => 'graph_menu_arrow'],
        false,
        false,
        true
    );
    $button .= '</div>';

    $body = '<div id="side_body_text" style="width: 100%;">'.$params['body_text'].'</div>';

    $bottom = '<div id="side_bottom_text" style="text-align: '.$params['position'].';">'.$params['bottom_text'].'</div>';

    switch ($params['position']) {
        case 'left':
            $table->size[1] = '15%';

            $table->data[0][0] = $top;
            $table->data[0][1] = '';
            $table->rowclass[0] = '';

            $table->data[1][0] = $body;

            $table->data[1][1] = $button;
            $table->rowclass[1] = '';

            $table->data[2][0] = $bottom;
            $table->data[2][1] = '';
            $table->rowclass[2] = '';
        break;

        case 'right':
            $table->size[0] = '15%';

            $table->data[0][0] = '';
            $table->data[0][1] = $top;
            $table->rowclass[0] = '';

            $table->data[1][0] = $button;

            $table->data[1][1] = $body;
            $table->rowclass[1] = '';

            $table->data[2][0] = '';
            $table->data[2][1] = $bottom;
            $table->rowclass[2] = '';
        break;

        case 'bottom':
            $table->data[0][0] = $button;
            $table->cellstyle[0][0] = 'text-align: center;';
            $table->rowclass[0] = '';

            $table->data[1][0] = $top;
            $table->rowclass[1] = '';

            $table->data[2][0] = $body;
            $table->rowclass[2] = '';

            $table->data[3][0] = $bottom;
            $table->rowclass[3] = '';
        break;
    }

    $out_html .= html_print_table($table, true);

    $out_html .= '</div>';

    $out_js = "<script type='text/javascript'>
			<!--
			hidded_sidebar('".$params['position']."', ".$params['width'].", '".$params['height']."', ".$params['icon_width'].", 
				'".$params['top']."', '".$params['autotop']."', '".$params['right']."', 
				'".$params['autoright']."', '".$params['icon_closed']."', '".$params['icon_open']."', '".$config['homeurl']."'
				, '".$params['vertical_mode']."');
			//-->
		</script>";

    echo $out_html.$out_js;
}


/**
 * Prints an array of fields in a popup menu of a form.
 *
 * Based on choose_from_menu() from Moodle
 *
 * @param array Array with dropdown values. Example: $fields["value"] = "label"
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 * array of selected values (in combination with multiple)
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Set the input to allow multiple selections (optional, single selection by default).
 * @param bool Whether to sort the options or not (optional, unsorted by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select_style($fields, $name, $selected='', $style='', $script='', $nothing='', $nothing_value=0, $return=false, $multiple=false, $sort=true, $class='', $disabled=false)
{
    $output = "\n";

    static $idcounter = [];

    // If duplicate names exist, it will start numbering. Otherwise it won't
    if (isset($idcounter[$name])) {
        $idcounter[$name]++;
    } else {
        $idcounter[$name] = 0;
    }

    $id = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name.($idcounter[$name] ? $idcounter[$name] : ''));

    $attributes = '';
    if (!empty($script)) {
        $attributes .= ' onchange="'.$script.'"';
    }

    if (!empty($multiple)) {
        $attributes .= ' multiple="multiple" size="10"';
    }

    if (!empty($class)) {
        $attributes .= ' class="'.$class.'"';
    }

    if (!empty($disabled)) {
        $attributes .= ' disabled="disabled"';
    }

    $output .= '<select style="'.$style.'" id="'.$id.'" name="'.$name.'"'.$attributes.'>';

    if ($nothing != '' || empty($fields)) {
        if ($nothing == '') {
            $nothing = __('None');
        }

        $output .= '<option value="'.$nothing_value.'"';
        if ($nothing_value == $selected) {
            $output .= ' selected="selected"';
        }

        $output .= '>'.$nothing.'</option>';
    }

    if (!empty($fields)) {
        if ($sort !== false) {
            asort($fields);
        }

        foreach ($fields as $value => $label) {
            $output .= '<option value="'.$value.'"';
            if (is_array($selected) && in_array($value, $selected)) {
                $output .= ' selected="selected"';
            } else if (is_numeric($value) && is_numeric($selected) && $value == $selected) {
                // This fixes string ($value) to int ($selected) comparisons
                $output .= ' selected="selected"';
            } else if ($value === $selected) {
                // Needs type comparison otherwise if $selected = 0 and $value = "string" this would evaluate to true
                $output .= ' selected="selected"';
            }

            if ($label === '') {
                $output .= '>'.$value.'</option>';
            } else {
                $output .= '>'.$label.'</option>';
            }
        }
    }

    $output .= '</select>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints the groups of user of fields in a popup menu of a form.
 *
 * @param string User id
 * @param string The privilege to evaluate
 * @param boolean                                                                                 $returnAllGroup   Flag the return group, by default true.
 * @param boolean                                                                                 $returnAllColumns Flag to return all columns of groups.
 * @param array Array with dropdown values. Example: $fields["value"] = "label"
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 *        array of selected values (in combination with multiple)
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Set the input to allow multiple selections (optional, single selection by default).
 * @param bool Whether to sort the options or not (optional, unsorted by default).
 * @param string                                                                                  $style            The string of style.
 * @param integer                                                                                 $id_group         The id of node that must do not show the children and own.
 * @param string                                                                                  $keys_field       The field of the group used in the array keys. By default ID
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select_groups(
    $id_user=false,
    $privilege='AR',
    $returnAllGroup=true,
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value=0,
    $return=false,
    $multiple=false,
    $sort=true,
    $class='',
    $disabled=false,
    $style=false,
    $option_style=false,
    $id_group=false,
    $keys_field='id_grupo',
    $strict_user=false,
    $delete_groups=false,
    $include_groups=false,
    $size=false,
    $simple_multiple_options=false
) {
    global $config;

    $fields = users_get_groups_for_select(
        $id_user,
        $privilege,
        $returnAllGroup,
        true,
        $id_group,
        $keys_field
    );

    if ($delete_groups && is_array($delete_groups)) {
        foreach ($delete_groups as $value) {
            unset($fields[$value]);
        }
    }

    if (is_array($include_groups)) {
        $field = [];
        foreach ($include_groups as $value) {
            $field[$value] = $fields[$value];
        }

        $fields = array_intersect($fields, $field);
    }

    if ($strict_user) {
        $fields = users_get_strict_mode_groups($config['id_user'], $returnAllGroup);
    }

    $output = html_print_select(
        $fields,
        $name,
        $selected,
        $script,
        $nothing,
        $nothing_value,
        $return,
        $multiple,
        false,
        $class,
        $disabled,
        $style,
        $option_style,
        $size,
        false,
        '',
        false,
        $simple_multiple_options
    );

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints an array of fields in a popup menu of a form.
 *
 * Based on choose_from_menu() from Moodle
 *
 * @param array Array with dropdown values. Example: $fields["value"] = "label"
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 *        array of selected values (in combination with multiple)
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Set the input to allow multiple selections (optional, single selection by default).
 * @param bool Whether to sort the options or not (optional, unsorted by default).
 * @param string                                                                                  $style The string of style.
 * @param mixed                                                                                   $size  Max elements showed in the select or default (size=10).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select(
    $fields,
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value=0,
    $return=false,
    $multiple=false,
    $sort=true,
    $class='',
    $disabled=false,
    $style=false,
    $option_style=false,
    $size=false,
    $modal=false,
    $message='',
    $select_all=false,
    $simple_multiple_options=false
) {
    $output = "\n";

    static $idcounter = [];

    // If duplicate names exist, it will start numbering. Otherwise it won't
    if (isset($idcounter[$name])) {
        $idcounter[$name]++;
    } else {
        $idcounter[$name] = 0;
    }

    $id = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name.($idcounter[$name] ? $idcounter[$name] : ''));

    $attributes = '';
    if (!empty($script)) {
        $attributes .= ' onchange="'.$script.'"';
    }

    if (!empty($multiple)) {
        if ($size !== false) {
            $attributes .= ' multiple="multiple" size="'.$size.'"';
        } else {
            $attributes .= ' multiple="multiple" size="10"';
        }
    }

    if ($simple_multiple_options === true) {
        if ($size !== false) {
            $attributes .= ' size="'.$size.'"';
        } else {
            $attributes .= ' size="10"';
        }
    }

    if (!empty($class)) {
        $attributes .= ' class="'.$class.'"';
    }

    if (!empty($disabled)) {
        $attributes .= ' disabled="disabled"';
    }

    if ($style === false) {
        $styleText = 'style=""';
    } else {
        $styleText = 'style="'.$style.'"';
    }

    $output .= '<select id="'.$id.'" name="'.$name.'"'.$attributes.' '.$styleText.'>';

    if ($nothing != '' || empty($fields)) {
        if ($nothing == '') {
            $nothing = __('None');
        }

        $output .= '<option value="'.$nothing_value.'"';

        if ($nothing_value == $selected) {
            $output .= ' selected="selected"';
        } else if (is_array($selected)) {
            if (in_array($nothing_value, $selected)) {
                $output .= ' selected="selected"';
            }
        }

        $output .= '>'.$nothing.'</option>';
    }

    if (is_array($fields) && !empty($fields)) {
        if ($sort !== false) {
            // Sorting the fields in natural way and case insensitive preserving keys
            $first_elem = reset($fields);
            if (!is_array($first_elem)) {
                uasort($fields, 'strnatcasecmp');
            }
        }

        $lastopttype = '';
        foreach ($fields as $value => $label) {
            $optlabel = $label;
            if (is_array($label)) {
                if (isset($label['optgroup'])) {
                    if ($label['optgroup'] != $lastopttype) {
                        if ($lastopttype != '') {
                            $output .= '</optgroup>';
                        }

                        $output .= '<optgroup label="'.$label['optgroup'].'">';
                        $lastopttype = $label['optgroup'];
                    }
                }

                $optlabel = $label['name'];
            }

            $output .= '<option ';
            if ($select_all) {
                $output .= 'selected ';
            }

            $output .= 'value="'.$value.'"';

            if (is_array($selected) && in_array($value, $selected)) {
                $output .= ' selected="selected"';
            } else if (is_numeric($value) && is_numeric($selected)
                && $value == $selected
            ) {
                // This fixes string ($value) to int ($selected) comparisons
                $output .= ' selected="selected"';
            } else if ($value === $selected) {
                // Needs type comparison otherwise if $selected = 0 and $value = "string" this would evaluate to true
                $output .= ' selected="selected"';
            }

            if (is_array($option_style)
                && in_array($value, array_keys($option_style))
            ) {
                $output .= ' style="'.$option_style[$value].'"';
            }

            if ($optlabel === '') {
                $output .= '>None</option>';
            } else {
                $output .= '>'.$optlabel.'</option>';
            }
        }

        if (is_array($label)) {
            $output .= '</optgroup>';
        }
    }

    $output .= '</select>';
    if ($modal && !enterprise_installed()) {
        $output .= "
		<div id='".$message."' class='publienterprise' title='Community version' style='display:inline;position:relative;top:10px;left:0px;margin-top: -2px !important; margin-left: 2px !important;'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints an array of fields in a popup menu of a form based on a SQL query.
 * The first and second columns of the query will be used.
 *
 * The element will have an id like: "password-$value". Based on choose_from_menu() from Moodle.
 *
 * @param string  $sql            SQL sentence, the first field will be the identifier of the option.
 *             The second field will be the shown value in the dropdown.
 * @param string  $name           Select form name
 * @param string  $selected       Current selected value.
 * @param string  $script         Javascript onChange code.
 * @param string  $nothing        Label when nothing is selected.
 * @param string  $nothing_value  Value when nothing is selected
 * @param boolean $return         Whether to return an output string or echo now (optional, echo by default).
 * @param boolean $multiple       Whether to allow multiple selections or not. Single by default
 * @param boolean $sort           Whether to sort the options or not. Sorted by default.
 * @param boolean $disabled       if it's true, disable the select.
 * @param string  $style          The string of style.
 * @param mixed   $size           Max elements showed in select or default (size=10)
 * @param integer $truncante_size Truncate size of the element, by default is set to GENERIC_SIZE_TEXT constant
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_select_from_sql(
    $sql,
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value='0',
    $return=false,
    $multiple=false,
    $sort=true,
    $disabled=false,
    $style=false,
    $size=false,
    $trucate_size=GENERIC_SIZE_TEXT
) {
    global $config;

    $fields = [];
    $result = db_get_all_rows_sql($sql);
    if ($result === false) {
        $result = [];
    }

    foreach ($result as $row) {
        $id = array_shift($row);
        $value = array_shift($row);
        $fields[$id] = ui_print_truncate_text(
            $value,
            $trucate_size,
            false,
            true,
            false
        );
    }

    return html_print_select(
        $fields,
        $name,
        $selected,
        $script,
        $nothing,
        $nothing_value,
        $return,
        $multiple,
        $sort,
        '',
        $disabled,
        $style,
        '',
        $size
    );
}


function html_print_extended_select_for_unit(
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value='0',
    $size=false,
    $return=false,
    $select_style=false,
    $unique_name=true,
    $disabled=false,
    $no_change=0
) {
    global $config;

    // $fields = post_process_get_custom_values();
    $fields['_timeticks_'] = 'Timeticks';
    $fields['none'] = __('none');

    if ($no_change != 0) {
        $fields[-1] = __('No change');
    }

    // $selected_float = (float)$selected;
    // $found = false;
    //
    // if (array_key_exists($selected, $fields))
    // $found = true;
    //
    // if (!$found) {
    // $fields[$selected] = floatval($selected);
    // }
    if ($unique_name === true) {
        $uniq_name = uniqid($name);
    } else {
        $uniq_name = $name;
    }

    ob_start();

    echo '<div id="'.$uniq_name.'_default" style="width:100%;display:inline;">';
        html_print_select(
            $fields,
            $uniq_name.'_select',
            $selected,
            ''.$script,
            $nothing,
            $nothing_value,
            false,
            false,
            false,
            '',
            $disabled,
            'font-size: xx-small;'.$select_style
        );
        echo ' <a href="javascript:">'.html_print_image(
            'images/pencil.png',
            true,
            [
                'class' => $uniq_name.'_toggler',
                'alt'   => __('Custom'),
                'title' => __('Custom'),
                'style' => 'width: 18px;',
            ]
        ).'</a>';
    echo '</div>';

    echo '<div id="'.$uniq_name.'_manual" style="width:100%;display:inline;">';
        html_print_input_text($uniq_name.'_text', $selected, '', 20);

        html_print_input_hidden($name, $selected, false, $uniq_name);
        echo ' <a href="javascript:">'.html_print_image(
            'images/default_list.png',
            true,
            [
                'class' => $uniq_name.'_toggler',
                'alt'   => __('List'),
                'title' => __('List'),
                'style' => 'width: 18px;',
            ]
        ).'</a>';
    echo '</div>';

    echo "<script type='text/javascript'>
		$(document).ready (function () {
			post_process_select_init_unit('$uniq_name','$selected');
			post_process_select_events_unit('$uniq_name','$selected');
		});
		
	</script>";

    $returnString = ob_get_clean();

    if ($return) {
        return $returnString;
    } else {
        echo $returnString;
    }
}


function html_print_extended_select_for_post_process(
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value='0',
    $size=false,
    $return=false,
    $select_style=false,
    $unique_name=true,
    $disabled=false,
    $no_change=0
) {
    global $config;

    include_once $config['homedir'].'/include/functions_post_process.php';

    $fields = post_process_get_custom_values();

    if ($no_change != 0) {
        $fields[-1] = __('No change');
    }

    $selected_float = (float) $selected;
    $found = false;

    if ($selected) {
        if (array_key_exists(number_format($selected, 14, '.', ','), $fields)) {
            $found = true;
        }
    }

    if (!$found) {
        $fields[$selected] = floatval($selected);
    }

    if ($unique_name === true) {
        $uniq_name = uniqid($name);
    } else {
        $uniq_name = $name;
    }

    ob_start();

    echo '<div id="'.$uniq_name.'_default" style="width:100%;display:inline;">';
        html_print_select(
            $fields,
            $uniq_name.'_select',
            $selected,
            ''.$script,
            $nothing,
            $nothing_value,
            false,
            false,
            false,
            '',
            $disabled,
            'font-size: xx-small;'.$select_style
        );
        echo ' <a href="javascript:">'.html_print_image(
            'images/pencil.png',
            true,
            [
                'class' => $uniq_name.'_toggler',
                'alt'   => __('Custom'),
                'title' => __('Custom'),
                'style' => 'width: 18px;',
            ]
        ).'</a>';
    echo '</div>';

    echo '<div id="'.$uniq_name.'_manual" style="width:100%;display:inline;">';
        html_print_input_text($uniq_name.'_text', $selected, '', 20);

        html_print_input_hidden($name, $selected, false, $uniq_name);
        echo ' <a href="javascript:">'.html_print_image(
            'images/default_list.png',
            true,
            [
                'class' => $uniq_name.'_toggler',
                'alt'   => __('List'),
                'title' => __('List'),
                'style' => 'width: 18px;',
            ]
        ).'</a>';
    echo '</div>';

    echo "<script type='text/javascript'>
		$(document).ready (function () {
			post_process_select_init('$uniq_name');
			post_process_select_events('$uniq_name');
		});

	</script>";

    $returnString = ob_get_clean();

    if ($return) {
        return $returnString;
    } else {
        echo $returnString;
    }
}


/**
 * Render a pair of select for times and text box for set the time more fine.
 *
 * @param string Select form name
 * @param variant Current selected value. Can be a single value or an
 * array of selected values (in combination with multiple)
 * @param string Javascript onChange (select) code.
 * @param string Label when nothing is selected.
 * @param variant Value when nothing is selected
 * @param integer                                                                                            $size Size of the input.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Wherter to assign to combo a unique name (to have more than one on same page, like dashboard)
 *
 * @return string HTML code if return parameter is true.
 */


function html_print_extended_select_for_time(
    $name,
    $selected='',
    $script='',
    $nothing='',
    $nothing_value='0',
    $size=false,
    $return=false,
    $select_style=false,
    $unique_name=true,
    $class='',
    $readonly=false,
    $custom_fields=false,
    $style_icon='',
    $no_change=false
) {
    global $config;
    $admin = is_user_admin($config['id_user']);
    if ($custom_fields) {
        $fields = $custom_fields;
    } else {
        $fields = get_periods();
    }

    if ($no_change) {
        $fields['-2'] = __('No change');
    }

    if (! $selected) {
        foreach ($fields as $t_key => $t_value) {
            if ($t_key != -1) {
                if ($nothing == '') {
                    // -1 means 'custom'
                    $selected = $t_key;
                    break;
                } else {
                    $selected = $nothing;
                    break;
                }
            }
        }
    }

    if (($selected !== false) && (!isset($fields[$selected]) && $selected != 0)) {
        $fields[$selected] = human_time_description_raw($selected, true);
    }

    $units = [
        1               => __('seconds'),
        SECONDS_1MINUTE => __('minutes'),
        SECONDS_1HOUR   => __('hours'),
        SECONDS_1DAY    => __('days'),
        SECONDS_1WEEK   => __('weeks'),
        SECONDS_1MONTH  => __('months'),
        SECONDS_1YEAR   => __('years'),
    ];

    if ($unique_name === true) {
        $uniq_name = uniqid($name);
    } else {
        $uniq_name = $name;
    }

    if ($readonly) {
        $readonly = true;
    }

    ob_start();
    // Use the no_meta parameter because this image is only in the base console
    echo '<div id="'.$uniq_name.'_default" style="width:100%;display:inline;">';
        html_print_select(
            $fields,
            $uniq_name.'_select',
            $selected,
            ''.$script,
            $nothing,
            $nothing_value,
            false,
            false,
            false,
            $class,
            $readonly,
            'font-size: xx-small;'.$select_style
        );
        // The advanced control is only for admins
    if ($admin) {
        echo ' <a href="javascript:">'.html_print_image(
            'images/pencil.png',
            true,
            [
                'class' => $uniq_name.'_toggler '.$class,
                'alt'   => __('Custom'),
                'title' => __('Custom'),
                'style' => 'width: 18px; margin-bottom: -5px;'.$style_icon,
            ],
            false,
            false,
            true
        ).'</a>';
    }

    echo '</div>';

    echo '<div id="'.$uniq_name.'_manual" style="width:100%;display:inline;">';
        html_print_input_text($uniq_name.'_text', $selected, '', $size, 255, false, $readonly, false, '', $class);

        html_print_input_hidden($name, $selected, false, $uniq_name);
        html_print_select(
            $units,
            $uniq_name.'_units',
            1,
            ''.$script,
            $nothing,
            $nothing_value,
            false,
            false,
            false,
            $class,
            $readonly,
            'font-size: xx-small;'.$select_style
        );
        echo ' <a href="javascript:">'.html_print_image(
            'images/default_list.png',
            true,
            [
                'class' => $uniq_name.'_toggler',
                'alt'   => __('List'),
                'title' => __('List'),
                'style' => 'width: 18px;margin-bottom: -5px;'.$style_icon,
            ]
        ).'</a>';
    echo '</div>';
    echo "<script type='text/javascript'>
		$(document).ready (function () {
			period_select_init('$uniq_name');
			period_select_events('$uniq_name');
		});
		function period_select_".$name."_update(seconds) {
			$('#text-".$uniq_name."_text').val(seconds);
			adjustTextUnits('".$uniq_name."');
			calculateSeconds('".$uniq_name."');
			$('#".$uniq_name."_manual').show();
			$('#".$uniq_name."_default').hide();
		}
	</script>";
    $returnString = ob_get_clean();

    if ($return) {
        return $returnString;
    } else {
        echo $returnString;
    }
}


/**
 * Print selects to configure the cron of a module.
 *
 * @param string Run hour.
 * @param string Run minute.
 * @param string Run day of the month.
 * @param string Run month.
 * @param string Run day of the week.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Print cron grayed
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_extended_select_for_cron($hour='*', $minute='*', $mday='*', $month='*', $wday='*', $return=false, $disabled=false, $to=false)
{
    // Hours
    for ($i = 0; $i < 24; $i++) {
        $hours[$i] = $i;
    }

    // Minutes
    for ($i = 0; $i < 60; $i += 5) {
        $minutes[$i] = $i;
    }

    // Month days
    for ($i = 0; $i < 31; $i++) {
        $mdays[$i] = $i;
    }

    // Months
    for ($i = 1; $i <= 12; $i++) {
        $months[$i] = date('F', mktime(0, 0, 0, $i, 1));
    }

    // Days of the week
    $wdays = [
        __('Sunday'),
        __('Monday'),
        __('Tuesday'),
        __('Wednesday'),
        __('Thursday'),
        __('Friday'),
        __('Saturday'),
    ];

    // Print selectors
    $table = new stdClass();
    $table->id = 'cron';
    $table->width = '100%';
    $table->class = 'databox data';
    $table->head[0] = __('Hour');
    $table->head[1] = __('Minute');
    $table->head[2] = __('Month day');
    $table->head[3] = __('Month');
    $table->head[4] = __('Week day');

    if ($to) {
        $table->data[0][0] = html_print_select($hours, 'hour_to', $hour, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][1] = html_print_select($minutes, 'minute_to', $minute, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][2] = html_print_select($mdays, 'mday_to', $mday, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][3] = html_print_select($months, 'month_to', $month, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][4] = html_print_select($wdays, 'wday_to', $wday, '', __('Any'), '*', true, false, false, '', $disabled);
    } else {
        $table->data[0][0] = html_print_select($hours, 'hour_from', $hour, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][1] = html_print_select($minutes, 'minute_from', $minute, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][2] = html_print_select($mdays, 'mday_from', $mday, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][3] = html_print_select($months, 'month_from', $month, '', __('Any'), '*', true, false, false, '', $disabled);
        $table->data[0][4] = html_print_select($wdays, 'wday_from', $wday, '', __('Any'), '*', true, false, false, '', $disabled);
    }

    return html_print_table($table, $return);
}


/**
 * Render an input text element. Extended version, use html_print_input_text() to simplify.
 *
 * @param string  $name       Input name.
 * @param string  $value      Input value.
 * @param string  $id         Input HTML id.
 * @param string  $alt        Do not use, invalid for text and password. Use html_print_input_image
 * @param integer $size       Size of the input.
 * @param integer $maxlength  Maximum length allowed.
 * @param boolean $disabled   Disable the button (optional, button enabled by default).
 * @param mixed   $script     JavaScript to attach to this. It is array the index is event to set a script, it is only string for "onkeyup" event.
 * @param mixed   $attributes Attributes to add to this tag. Should be an array for correction.
 * @param boolean $return     Whether to return an output string or echo now (optional, echo by default).
 * @param boolean $password   Whether it is a password input or not. Not password by default.
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_text_extended($name, $value, $id, $alt, $size, $maxlength, $disabled, $script, $attributes, $return=false, $password=false, $function='', $autocomplete='off')
{
    static $idcounter = 0;

    if ($maxlength == 0) {
        $maxlength = 255;
    }

    if ($size === false) {
        $size = '';
    } else if ($size == 0) {
        $size = 10;
    }

    ++$idcounter;

    $valid_attrs = [
        'accept',
        'disabled',
        'maxlength',
        'name',
        'readonly',
        'placeholder',
        'size',
        'value',
        'accesskey',
        'class',
        'dir',
        'id',
        'lang',
        'style',
        'tabindex',
        'title',
        'xml:lang',
        'onfocus',
        'onblur',
        'onselect',
        'onchange',
        'onclick',
        'ondblclick',
        'onmousedown',
        'onmouseup',
        'onmouseover',
        'onmousemove',
        'onmouseout',
        'onkeypress',
        'onkeydown',
        'onkeyup',
        'required',
        'autocomplete',
    ];

    $output = '<input '.($password ? 'type="password" autocomplete="'.$autocomplete.'" ' : 'type="text" ');

    if ($disabled && (!is_array($attributes) || !array_key_exists('disabled', $attributes))) {
        $output .= 'readonly="readonly" ';
    }

    if (is_array($attributes)) {
        foreach ($attributes as $attribute => $attr_value) {
            if (! in_array($attribute, $valid_attrs)) {
                continue;
            }

            $output .= $attribute.'="'.$attr_value.'" ';
        }
    } else {
        $output .= trim($attributes).' ';
        $attributes = [];
    }

    if (!empty($alt)) {
        $output .= 'alt="'.$alt.'" ';
    }

    // Attributes specified by function call
    $attrs = [
        'name'      => 'unnamed',
        'value'     => '',
        'id'        => 'text-'.sprintf('%04d', $idcounter),
        'size'      => '',
        'maxlength' => '',
    ];

    foreach ($attrs as $attribute => $default) {
        if (array_key_exists($attribute, $attributes)) {
            continue;
        } //end if

        /*
         * Remember, this next code have a $$ that for example there is a var as
         * $a = 'john' then $$a is a var $john .
         *
         * In this case is use for example for $name and $atribute = 'name' .
         *
         */

        // Exact operator because we want to show "0" on the value
        if ($attribute !== '') {
            $output .= $attribute.'="'.$$attribute.'" ';
        } else if ($default != '') {
            $output .= $attribute.'="'.$default.'" ';
        }
    }

    if (!empty($script)) {
        if (is_string($script)) {
            $code = $script;
            $script = [];
            $script['onkeyup'] = $code;
        }

        foreach ($script as $event => $code) {
            $output .= ' '.$event.'="'.$code.'" ';
        }
    }

    $output .= $function.'/>';

    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Render an input password element.
 *
 * The element will have an id like: "password-$name"
 *
 * @param mixed parameters:
 *             - id: string
 *             - style: string
 *             - hidden: boolean
 *             - content: string
 * @param bool return or echo flag
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_div($options, $return=false)
{
    $output = '<div';

    // Valid attributes (invalid attributes get skipped)
    $attrs = [
        'id',
        'style',
        'class',
    ];

    if (isset($options['hidden'])) {
        if (isset($options['style'])) {
            $options['style'] .= 'display:none;';
        } else {
            $options['style'] = 'display:none;';
        }
    }

    foreach ($attrs as $attribute) {
        if (isset($options[$attribute])) {
            $output .= ' '.$attribute.'="'.io_safe_input_html($options[$attribute]).'"';
        }
    }

    $output .= '>';

    $output .= isset($options['content']) ? $options['content'] : '';

    $output .= '</div>';

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Render an input password element.
 *
 * The element will have an id like: "password-$name"
 *
 * @param string  $name      Input name.
 * @param string  $value     Input value.
 * @param string  $alt       Alternative HTML string (optional).
 * @param integer $size      Size of the input (optional).
 * @param integer $maxlength Maximum length allowed (optional).
 * @param boolean $return    Whether to return an output string or echo now (optional, echo by default).
 * @param boolean $disabled  Disable the button (optional, button enabled by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_password(
    $name,
    $value,
    $alt='',
    $size=50,
    $maxlength=255,
    $return=false,
    $disabled=false,
    $required=false,
    $class='',
    $autocomplete='off'
) {
    if ($maxlength == 0) {
        $maxlength = 255;
    }

    if ($size === false) {
        $size = false;
    } else if ($size == 0) {
        $size = 10;
    }

    $attr = [];
    if ($required) {
        $attr['required'] = 'required';
    }

    if ($class) {
        $attr['class'] = $class;
    }

    if ($disabled === false) {
        // Trick to avoid password completion on most browsers.
        if ($autocomplete !== 'on') {
            $disabled = true;
            $attr['onfocus'] = "this.removeAttribute('readonly');";
        }
    }

    return html_print_input_text_extended($name, $value, 'password-'.$name, $alt, $size, $maxlength, $disabled, '', $attr, $return, true, '', $autocomplete);
}


/**
 * Render an input text element.
 *
 * The element will have an id like: "text-$name"
 *
 * @param string  $name      Input name.
 * @param string  $value     Input value.
 * @param string  $alt       Alternative HTML string (invalid - not used).
 * @param integer $size      Size of the input (optional).
 * @param integer $maxlength Maximum length allowed (optional).
 * @param boolean $return    Whether to return an output string or echo now (optional, echo by default).
 * @param boolean $disabled  Disable the button (optional, button enabled by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_text($name, $value, $alt='', $size=50, $maxlength=255, $return=false, $disabled=false, $required=false, $function='', $class='', $onChange='', $autocomplete='')
{
    if ($maxlength == 0) {
        $maxlength = 255;
    }

    if ($size === false) {
        $size = false;
    } else if ($size == 0) {
        $size = 10;
    }

    $attr = [];
    if ($required) {
        $attr['required'] = 'required';
    }

    if ($class != '') {
        $attr['class'] = $class;
    }

    if ($onChange != '') {
        $attr['onchange'] = $onChange;
    }

    if ($autocomplete !== '') {
        $attr['autocomplete'] = $autocomplete;
    }

    return html_print_input_text_extended($name, $value, 'text-'.$name, $alt, $size, $maxlength, $disabled, '', $attr, $return, false, $function);
}


/**
 * Render an input image element.
 *
 * The element will have an id like: "image-$name"
 *
 * @param string  $name   Input name.
 * @param string  $src    Image source.
 * @param string  $value  Input value.
 * @param string  $style  HTML style property.
 * @param boolean $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_image($name, $src, $value, $style='', $return=false, $options=false)
{
    global $config;
    static $idcounter = 0;

    ++$idcounter;

    // Checks if user's skin is available
    $isFunctionSkins = enterprise_include_once('include/functions_skins.php');

    if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        $skin_path = enterprise_hook('skins_get_image_path', [$src]);
        if ($skin_path) {
            $src = $skin_path;
        }
    }

    // If metaconsole is activated and image doesn't exists try to search on normal console
    if (is_metaconsole()) {
        if (false === @file_get_contents($src, 0, null, 0, 1)) {
            $src = '../../'.$src;
        }
    }

    // path to image
    $src = ui_get_full_url($src);

    $output = '<input id="image-'.$name.$idcounter.'" src="'.$src.'" style="'.$style.'" name="'.$name.'" type="image"';

    // Valid attributes (invalid attributes get skipped)
    $attrs = [
        'alt',
        'accesskey',
        'lang',
        'tabindex',
        'title',
        'xml:lang',
        'onclick',
        'ondblclick',
        'onmousedown',
        'onmouseup',
        'onmouseover',
        'onmousemove',
        'onmouseout',
        'onkeypress',
        'onkeydown',
        'onkeyup',
        'class',
    ];

    foreach ($attrs as $attribute) {
        if (isset($options[$attribute])) {
            $output .= ' '.$attribute.'="'.io_safe_input_html($options[$attribute]).'"';
        }
    }

    $output .= ' value="'.$value.'" />';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render an input hidden element.
 *
 * The element will have an id like: "hidden-$name"
 *
 * @param string  $name   Input name.
 * @param string  $value  Input value.
 * @param boolean $return Whether to return an output string or echo now (optional, echo by default).
 * @param string  $class  Set the class of input.
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_hidden($name, $value, $return=false, $class=false)
{
    if ($class !== false) {
        $classText = 'class="'.$class.'"';
    } else {
        $classText = '';
    }

    $separator = '"';

    if (is_string($value)) {
        if (strstr($value, '"')) {
            $separator = "'";
        }
    }

    $output = '<input id="hidden-'.$name.'" '.'name="'.$name.'" '.'type="hidden" '.$classText.' '.'value='.$separator.$value.$separator.' />';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render an input hidden element. Extended version, use html_print_input_hidden() to simplify.
 *
 * The element will have an id like: "hidden-$name"
 *
 * @param string  $name   Input name.
 * @param string  $value  Input value.
 * @param string  $id     Input value.
 * @param boolean $return Whether to return an output string or echo now (optional, echo by default).
 * @param string  $class  Set the class of input.
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_hidden_extended($name, $value, $id, $return=false, $class=false)
{
    if ($class !== false) {
        $classText = 'class="'.$class.'"';
    } else {
        $classText = '';
    }

    if (empty($id)) {
        $ouput_id = 'hidden-'.$name;
    } else {
        $ouput_id = $id;
    }

    $output = '<input id="'.$ouput_id.'" name="'.$name.'" type="hidden" '.$classText.' value="'.$value.'" />';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render a color input element.
 *
 * The element will have an id like: "hidden-$name"
 *
 * @param string  $name   Input name.
 * @param integer $value  Input value. Decimal representation of the color's hexadecimal value.
 * @param string  $class  Set the class of input.
 * @param boolean $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_input_color($name, $value, $class=false, $return=false)
{
    $attr_type = 'type="color"';
    $attr_id = 'id="color-'.htmlspecialchars($name, ENT_QUOTES).'"';
    $attr_name = 'name="'.htmlspecialchars($name, ENT_QUOTES).'"';
    $attr_value = 'value="'.htmlspecialchars($value, ENT_QUOTES).'"';
    $attr_class = 'class="'.($class !== false ? htmlspecialchars($class, ENT_QUOTES) : '').'"';

    $output = '<input '.$attr_type.' '.$attr_id.' '.$attr_name.' '.$attr_value.' '.$attr_class.' />';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render an submit input button element.
 *
 * The element will have an id like: "submit-$name"
 *
 * @param string  $label      Input label.
 * @param string  $name       Input name.
 * @param boolean $disabled   Whether to disable by default or not. Enabled by default.
 * @param array   $attributes Additional HTML attributes.
 * @param boolean $return     Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_submit_button($label='OK', $name='', $disabled=false, $attributes='', $return=false)
{
    if (!$name) {
        $name = 'unnamed';
    }

    if (is_array($attributes)) {
        $attr_array = $attributes;
        $attributes = '';
        foreach ($attr_array as $attribute => $value) {
            $attributes .= $attribute.'="'.$value.'" ';
        }
    }

    $output = '<input type="submit" id="submit-'.$name.'" name="'.$name.'" value="'.$label.'" '.$attributes;
    if ($disabled) {
        $output .= ' disabled="disabled"';
    }

    $output .= ' />';
    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Render an submit input button element.
 *
 * The element will have an id like: "button-$name"
 *
 * @param string  $label       Input label.
 * @param string  $name        Input name.
 * @param boolean $disabled    Whether to disable by default or not. Enabled by default.
 * @param string  $script      JavaScript to attach
 * @param string  $attributes  Additional HTML attributes.
 * @param boolean $return      Whether to return an output string or echo now (optional, echo by default).
 * @param boolean $imageButton Set the button as a image button without text, by default is false.
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_button($label='OK', $name='', $disabled=false, $script='', $attributes='', $return=false, $imageButton=false, $modal=false, $message='')
{
    $output = '';

    $alt = $title = '';
    if ($imageButton) {
        $alt = $title = $label;
        $label = '';
    }

    $output .= '<input title="'.$title.'" alt="'.$alt.'" type="button" id="button-'.$name.'" name="'.$name.'" value="'.$label.'" onClick="'.$script.'" '.$attributes;
    if ($disabled) {
        $output .= ' disabled';
    }

    $output .= ' />';

    if ($modal && !enterprise_installed()) {
        $output .= "
		<div id='".$message."' class='publienterprise' title='Community version' style='display:inline;position:relative;top:10px;left:0px;margin-top: -2px !important; margin-left: 2px !important;'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render an input textarea element.
 *
 * The element will have an id like: "textarea_$name"
 *
 * @param string  $name       Input name.
 * @param integer $rows       How many rows (height)
 * @param integer $columns    How many columns (width)
 * @param string  $value      Text in the textarea
 * @param string  $attributes Additional attributes
 * @param boolean $return     Whether to return an output string or echo now (optional, echo by default). *
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_textarea($name, $rows, $columns, $value='', $attributes='', $return=false, $class='')
{
    $output = '<textarea id="textarea_'.$name.'" name="'.$name.'" cols="'.$columns.'" rows="'.$rows.'" '.$attributes.'" class="'.$class.'">';
    // $output .= io_safe_input ($value);
    $output .= ($value);
    $output .= '</textarea>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Return a table parameters predefined
 *
 * @param string model
 *     - Transparent: More basic template. No borders, all the columns with same width
 * @param int number of columns
 *
 * @return object Table object
 */
function html_get_predefined_table($model='transparent', $columns=4)
{
    $width_percent = (100 / $columns);

    switch ($model) {
        case 'transparent':
        default:
            $table = new stdClass();

            $table->class = 'none';
            $table->cellpadding = 0;
            $table->cellspacing = 0;
            $table->head = [];
            $table->data = [];
            $table->style = array_fill(0, 4, 'text-align:center; width: '.$width_percent.'%;');
            $table->width = '100%';
    }

    return $table;
}


/**
 * Print a nicely formatted table. Code taken from moodle.
 *
 * @param object Object with several properties:
 *    $table->head - An array of heading names.
 *    $table->head_colspan - An array of colspans of each head column.
 *    $table->headstyle - An array of styles of each head column.
 *    $table->align - An array of column alignments
 *    $table->valign - An array of column alignments
 *    $table->size - An array of column sizes
 *    $table->wrap - An array of "nowrap"s or nothing
 *    $table->style - An array of personalized style for each column.
 *    $table->rowid - An array of personalized ids of each row.
 *    $table->rowstyle - An array of personalized style of each row.
 *    $table->rowclass - An array of personalized classes of each row (odd-evens classes will be ignored).
 *    $table->colspan - An array of colspans of each column.
 *    $table->rowspan - An array of rowspans of each column.
 *    $table->data[] - An array of arrays containing the data.
 *    $table->width - A percentage of the page
 *    $table->border - Border of the table.
 *    $table->tablealign - Align the whole table (float left or right)
 *    $table->cellpadding - Padding on each cell
 *    $table->cellspacing - Spacing between cells
 *    $table->cellstyle - Style of a cell
 *    $table->cellclass - Class of a cell
 *    $table->class - CSS table class
 *    $table->id - Table ID (useful in JavaScript)
 *    $table->headclass[] - An array of classes for each heading
 *    $table->title - Title of the table is a single string that will be on top of the table in the head spanning the whole table
 *    $table->titlestyle - Title style
 *    $table->titleclass - Title class
 *    $table->styleTable - Table style
 *  $table->caption - Table title
 * @param bool Whether to return an output string or echo now
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_table(&$table, $return=false)
{
    $output = '';
    static $table_count = 0;

    $table_count++;
    if (isset($table->align)) {
        foreach ($table->align as $key => $aa) {
            if ($aa) {
                $align[$key] = ' text-align:'.$aa.';';
            } else {
                $align[$key] = '';
            }
        }
    }

    if (isset($table->valign)) {
        foreach ($table->valign as $key => $aa) {
            if ($aa) {
                $valign[$key] = ' vertical-align:'.$aa.';';
            } else {
                $valign[$key] = '';
            }
        }
    }

    if (isset($table->size)) {
        foreach ($table->size as $key => $ss) {
            if ($ss) {
                $size[$key] = ' width:'.$ss.';';
            } else {
                $size[$key] = '';
            }
        }
    }

    if (isset($table->style)) {
        foreach ($table->style as $key => $st) {
            if ($st) {
                $style[$key] = ' '.$st.';';
            } else {
                $style[$key] = '';
            }
        }
    }

    $styleTable = '';
    if (isset($table->styleTable)) {
        $styleTable = $table->styleTable;
    }

    if (isset($table->rowid)) {
        foreach ($table->rowid as $key => $id) {
            $rowid[$key] = $id;
        }
    }

    if (isset($table->rowstyle)) {
        foreach ($table->rowstyle as $key => $st) {
            $rowstyle[$key] = ' '.$st.';';
        }
    }

    if (isset($table->rowclass)) {
        foreach ($table->rowclass as $key => $class) {
            $rowclass[$key] = $class;
        }
    }

    if (isset($table->colspan)) {
        foreach ($table->colspan as $keyrow => $cspan) {
            foreach ($cspan as $key => $span) {
                $colspan[$keyrow][$key] = ' colspan="'.$span.'"';
            }
        }
    }

    if (isset($table->cellstyle)) {
        foreach ($table->cellstyle as $keyrow => $cstyle) {
            foreach ($cstyle as $key => $cst) {
                $cellstyle[$keyrow][$key] = $cst;
            }
        }
    }

    if (isset($table->cellclass)) {
        foreach ($table->cellclass as $keyrow => $cclass) {
            foreach ($cclass as $key => $ccl) {
                $cellclass[$keyrow][$key] = $ccl;
            }
        }
    }

    if (isset($table->rowspan)) {
        foreach ($table->rowspan as $keyrow => $rspan) {
            foreach ($rspan as $key => $span) {
                $rowspan[$keyrow][$key] = ' rowspan="'.$span.'"';
            }
        }
    }

    if (empty($table->width)) {
        // $table->width = '80%';
    }

    if (empty($table->border)) {
        if (empty($table)) {
            $table = new stdClass();
        }

        $table->border = '0';
    }

    if (empty($table->tablealign) || (($table->tablealign != 'left') && ($table->tablealign != 'right'))) {
        $table->tablealign = '"';
    } else {
        $table->tablealign = 'float:'.$table->tablealign.';"';
        // Align is deprecated. Use float instead
    }

    if (!isset($table->cellpadding)) {
        $table->cellpadding = '4';
    }

    if (!isset($table->cellspacing)) {
        $table->cellspacing = '4';
    }

    if (empty($table->class)) {
        $table->class = 'databox';
    }

    if (empty($table->titlestyle)) {
        $table->titlestyle = 'text-align:center;';
    }

    $tableid = empty($table->id) ? 'table'.$table_count : $table->id;

    if (!empty($table->width)) {
        $output .= '<table style="width:'.$table->width.'; '.$styleTable.' '.$table->tablealign;
    } else {
        $output .= '<table style="'.$styleTable.' '.$table->tablealign;
    }

    $output .= ' cellpadding="'.$table->cellpadding.'" cellspacing="'.$table->cellspacing.'"';
    $output .= ' border="'.$table->border.'" class="'.$table->class.'" id="'.$tableid.'">';

    $countcols = 0;

    if (!empty($table->caption)) {
        $output .= '<caption style="text-align: left"><h4>'.$table->caption.'</h4></caption>';
    }

    if (!empty($table->head)) {
        $countcols = count($table->head);
        $output .= '<thead><tr>';

        if (isset($table->title)) {
            $output .= '<th colspan="'.$countcols.'"';
            if (isset($table->titlestyle)) {
                $output .= ' style="'.$table->titlestyle.'"';
            }

            if (isset($table->titleclass)) {
                $output .= ' class="'.$table->titleclass.'"';
            }

            $output .= '>'.$table->title.'</th></tr><tr>';
        }

        foreach ($table->head as $key => $heading) {
            if (!isset($size[$key])) {
                $size[$key] = '';
            }

            if (!isset($align[$key])) {
                $align[$key] = '';
            }

            if (!isset($table->headclass[$key])) {
                $table->headclass[$key] = 'header c'.$key;
            }

            if (isset($table->head_colspan[$key])) {
                $headColspan = 'colspan = "'.$table->head_colspan[$key].'"';
            } else {
                $headColspan = '';
            }

            if (isset($table->headstyle[$key])) {
                $headStyle = ' style = "'.$table->headstyle[$key].'" ';
            } else {
                $headStyle = '';
            }

            $output .= '<th class="'.$table->headclass[$key].'" '.$headColspan.$headStyle.' scope="col">'.$heading.'</th>';
        }

        $output .= '</tr></thead>'."\n";
    }

    $output .= '<tbody>'."\n";
    if (!empty($table->data)) {
        $oddeven = 1;
        foreach ($table->data as $keyrow => $row) {
            if (!isset($rowstyle[$keyrow])) {
                $rowstyle[$keyrow] = '';
            }

            if (!isset($rowid[$keyrow])) {
                $rowid[$keyrow] = $tableid.'-'.$keyrow;
            }

            $oddeven = $oddeven ? 0 : 1;
            $class = 'datos'.($oddeven ? '' : '2');
            if (isset($rowclass[$keyrow])) {
                $class = $rowclass[$keyrow];
            }

            $output .= '<tr id="'.$rowid[$keyrow].'" style="'.$rowstyle[$keyrow].'" class="'.$class.'">'."\n";
            // Special separator rows
            if ($row == 'hr' and $countcols) {
                $output .= '<td colspan="'.$countcols.'"><div class="tabledivider"></div></td>';
                continue;
            }

            if (!is_array($row)) {
                $row = (array) $row;
            }

            // It's a normal row
            foreach ($row as $key => $item) {
                if (!isset($size[$key])) {
                    $size[$key] = '';
                }

                if (!isset($cellstyle[$keyrow][$key])) {
                    $cellstyle[$keyrow][$key] = '';
                }

                if (!isset($cellclass[$keyrow][$key])) {
                    $cellclass[$keyrow][$key] = '';
                }

                if (!isset($colspan[$keyrow][$key])) {
                    $colspan[$keyrow][$key] = '';
                }

                if (!isset($rowspan[$keyrow][$key])) {
                    $rowspan[$keyrow][$key] = '';
                }

                if (!isset($align[$key])) {
                    $align[$key] = '';
                }

                if (!isset($valign[$key])) {
                    $valign[$key] = '';
                }

                if (!isset($wrap[$key])) {
                    $wrap[$key] = '';
                }

                if (!isset($style[$key])) {
                    $style[$key] = '';
                }

                $output .= '<td id="'.$tableid.'-'.$keyrow.'-'.$key.'" style="'.$cellstyle[$keyrow][$key].$style[$key].$valign[$key].$align[$key].$size[$key].$wrap[$key].'" '.$colspan[$keyrow][$key].' '.$rowspan[$keyrow][$key].' class="'.$class.' '.$cellclass[$keyrow][$key].'">'.$item.'</td>'."\n";
            }

            $output .= '</tr>'."\n";
        }
    }

    $output .= '</tbody></table>'."\n";

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render a radio button input. Extended version, use html_print_radio_button() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be
    inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
 /*
     Hello there! :)
     We added some of what seems to be "buggy" messages to the openSource version recently. This is not to force open-source users to move to the enterprise version, this is just to inform people using Pandora FMS open source that it requires skilled people to maintain and keep it running smoothly without professional support. This does not imply open-source version is limited in any way. If you check the recently added code, it contains only warnings and messages, no limitations except one: we removed the option to add custom logo in header. In the Update Manager section, it warns about the 'danger’ of applying automated updates without a proper backup, remembering in the process that the Enterprise version comes with a human-tested package. Maintaining an OpenSource version with more than 500 agents is not so easy, that's why someone using a Pandora with 8000 agents should consider asking for support. It's not a joke, we know of many setups with a huge number of agents, and we hate to hear that “its becoming unstable and slow” :(
     You can of course remove the warnings, that's why we include the source and do not use any kind of trick. And that's why we added here this comment, to let you know this does not reflect any change in our opensource mentality of does the last 14 years.
 */

function html_print_radio_button_extended($name, $value, $label, $checkedvalue, $disabled, $script, $attributes, $return=false, $modal=false, $message='visualmodal')
{
    static $idcounter = 0;

    $output = '';

    $output = '<input type="radio" name="'.$name.'" value="'.$value.'"';
    $htmlid = 'radiobtn'.sprintf('%04d', ++$idcounter);
    $output .= ' id="'.$htmlid.'"';

    if ($value == $checkedvalue) {
        $output .= ' checked="checked"';
    }

    if ($disabled) {
        $output .= ' disabled="disabled"';
    }

    if ($script != '') {
        $output .= ' onClick="'.$script.'"';
    }

    $output .= ' '.$attributes;
    $output .= ' />';

    if ($label != '') {
        $output .= '<label for="'.$htmlid.'">'.$label.'</label>'."\n";
    }

    if ($modal && !enterprise_installed()) {
        $output .= "
		<div id='".$message."' class='publienterprise' title='Community version' style='display:inline;position:relative;top:10px;left:0px;margin-top: -2px !important; margin-left: 2px !important;'><img data-title='Enterprise version' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render a radio button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Label to add after the radio button (optional).
 * @param string Checked and selected value, the button will be selected if it matches    $value (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_radio_button($name, $value, $label='', $checkedvalue='', $return=false, $disabled=false)
{
    $output = html_print_radio_button_extended($name, $value, $label, $checkedvalue, $disabled, '', '', true);

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render a checkbox button input. Extended version, use html_print_checkbox() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button  (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be
    inserted into the HTML tag, use it carefully (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_checkbox_extended($name, $value, $checked, $disabled, $script, $attributes, $return=false, $id='')
{
    static $idcounter = [];

    // If duplicate names exist, it will start numbering. Otherwise it won't
    if (isset($idcounter[$name])) {
        $idcounter[$name]++;
    } else {
        $idcounter[$name] = 0;
    }

    $id_aux = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name.($idcounter[$name] ? $idcounter[$name] : ''));

    $output = '<input name="'.$name.'" type="checkbox" value="'.$value.'" '.($checked ? 'checked="checked"' : '');
    if ($id == '') {
        $output .= ' id="checkbox-'.$id_aux.'"';
    } else {
        $output .= ' '.$id.'"';
    }

    if ($script != '') {
        $output .= ' onclick="'.$script.'"';
    }

    if ($disabled) {
        $output .= ' disabled="disabled"';
    }

    $output .= ' '.$attributes;
    $output .= ' />';
    $output .= "\n";

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Render a checkbox button input.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param boolean                                                                         $disabled Disable the button (optional, button enabled by default).
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_checkbox($name, $value, $checked=false, $return=false, $disabled=false, $script='', $disabled_hidden=false)
{
    $output = html_print_checkbox_extended($name, $value, (bool) $checked, $disabled, $script, '', true);
    if (!$disabled_hidden) {
        $output .= html_print_input_hidden($name.'_sent', 1, true);
    }

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Render a checkbox button input switch type. Extended version, use html_print_checkbox_switch() to simplify.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Disable the button  (optional, button enabled by default).
 * @param string Script to execute when onClick event is triggered (optional).
 * @param string Optional HTML attributes. It's a free string which will be
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */


function html_print_checkbox_switch_extended($name, $value, $checked, $disabled, $script, $attributes, $return=false, $id='')
{
    static $idcounter = [];

    // If duplicate names exist, it will start numbering. Otherwise it won't
    if (isset($idcounter[$name])) {
        $idcounter[$name]++;
    } else {
        $idcounter[$name] = 0;
    }

    $id_aux = preg_replace('/[^a-z0-9\:\;\-\_]/i', '', $name.($idcounter[$name] ? $idcounter[$name] : ''));

    $output = '<label class="p-switch"><input name="'.$name.'" type="checkbox" value="'.$value.'" '.($checked ? 'checked="checked"' : '');
    if ($id == '') {
        $output .= ' id="checkbox-'.$id_aux.'"';
    } else {
        $output .= ' '.$id.'"';
    }

    if ($script != '') {
        $output .= ' onclick="'.$script.'"';
    }

    if ($disabled) {
        $output .= ' disabled="disabled"';
    }

    $output .= ' '.$attributes;
    $output .= ' /><span class="p-slider"></span></label>';
    $output .= "\n";

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Render a checkbox button input  switch type.
 *
 * @param string Input name.
 * @param string Input value.
 * @param string Set the button to be marked (optional, unmarked by default).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param boolean                                                                         $disabled Disable the button (optional, button enabled by default).
 *
 * @return string HTML code if return parameter is true.
 */


function html_print_checkbox_switch($name, $value, $checked=false, $return=false, $disabled=false, $script='', $disabled_hidden=false)
{
    $output = html_print_checkbox_switch_extended($name, $value, (bool) $checked, $disabled, $script, '', true);
    if (!$disabled_hidden) {
        $output .= html_print_input_hidden($name.'_sent', 1, true);
    }

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Prints an image HTML element.
 *
 * @param string  $src            Image source filename.
 * @param boolean $return         Whether to return or print.
 * @param array   $options        Array with optional HTML options to set.
 *          At this moment, the following options are supported:
 *          align, border, hspace, ismap, vspace, style, title, height,
 *          longdesc, usemap, width, id, class, lang, xml:lang, onclick,
 *          ondblclick, onmousedown, onmouseup, onmouseover, onmousemove,
 *          onmouseout, onkeypress, onkeydown, onkeyup, pos_tree, alt.
 * @param boolean $return_src     Whether to return src field of image
 *          ('images/*.*') or complete html img tag ('<img src="..." alt="...">').
 * @param boolean $relative       Whether to use relative path to image or not
 *          (i.e. $relative= true : /pandora/<img_src>).
 * @param boolean $no_in_meta     Do not show on metaconsole folder at first. Go
 *          directly to the node.
 * @param boolean $isExternalLink Do not shearch for images in Pandora.
 *
 * @return string HTML code if return parameter is true.
 */
function html_print_image(
    $src,
    $return=false,
    $options=false,
    $return_src=false,
    $relative=false,
    $no_in_meta=false,
    $isExternalLink=false
) {
    global $config;

    // If metaconsole is in use then don't use skins.
    if (!is_metaconsole()) {
        // Checks if user's skin is available.
        $isFunctionSkins = enterprise_include_once('include/functions_skins.php');

        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            $skin_path = enterprise_hook('skins_get_image_path', [$src]);

            if ($skin_path) {
                $src = $skin_path;
            }
        }
    }

    // If metaconsole is activated and image doesn't exists try to search on normal console.
    if (is_metaconsole()) {
        if (!$relative) {
            $working_dir = str_replace('\\', '/', getcwd());
            // Windows compatibility.
            if ($no_in_meta) {
                $src = '../../'.$src;
            } else if (strstr($working_dir, 'enterprise/meta') === false) {
                if ($src[0] !== '/') {
                    $src = '/'.$src;
                }

                if (!is_readable($working_dir.'/enterprise/meta'.$src)) {
                    if ($isExternalLink) {
                        $src = ui_get_full_url($src, false, false, false);
                    } else {
                        $src = ui_get_full_url('../..'.$src);
                    }
                } else {
                    $src = ui_get_full_url($src);
                }
            } else {
                if ($src[0] !== '/') {
                    $src = '/'.$src;
                }

                if (is_readable($working_dir.$src)) {
                    $src = ui_get_full_url($src);
                } else if (!is_readable($src)) {
                    $src = ui_get_full_url('../../'.$src);
                }
            }
        } else {
            $src = '../../'.$src;
        }
    } else {
        if (!$relative) {
            $src_tmp = $src;
            $src = ui_get_full_url($src);
        }
    }

    // Only return src field of image.
    if ($return_src) {
        if (!$return) {
            echo io_safe_input($src);
            return null;
        }

        return io_safe_input($src);
    }

    $output = '<img src="'.$src.'" ';
    // Dont use safe_input here or the performance will dead.
    $style = '';

    if (!empty($options)) {
        // Deprecated or value-less attributes.
        if (isset($options['align'])) {
            $style .= 'align:'.$options['align'].';';
            // Align is deprecated, use styles.
        }

        if (isset($options['border'])) {
            $style .= 'border:'.$options['border'].'px;';
            // Border is deprecated, use styles.
        }

        if (isset($options['hspace'])) {
            $style .= 'margin-left:'.$options['hspace'].'px;';
            // hspace is deprecated, use styles.
            $style .= 'margin-right:'.$options['hspace'].'px;';
        }

        if (isset($options['ismap'])) {
            $output .= 'ismap="ismap" ';
            // Defines the image as a server-side image map.
        }

        if (isset($options['vspace'])) {
            $style .= 'margin-top:'.$options['vspace'].'px;';
            // hspace is deprecated, use styles.
            $style .= 'margin-bottom:'.$options['vspace'].'px;';
        }

        if (isset($options['style'])) {
            $style .= $options['style'];
        }

        // If title is provided activate forced title.
        if (isset($options['title']) && $options['title'] != '') {
            if (isset($options['class'])) {
                $options['class'] .= ' forced_title';
            } else {
                $options['class'] = 'forced_title';
            }

            // New way to show the force_title (cleaner and better performance).
            $output .= 'data-title="'.io_safe_input_html($options['title']).'" ';
            $output .= 'data-use_title_for_force_title="1" ';
        }

        // Valid attributes (invalid attributes get skipped).
        $attrs = [
            'height',
            'longdesc',
            'usemap',
            'width',
            'id',
            'class',
            'lang',
            'xml:lang',
            'onclick',
            'ondblclick',
            'onmousedown',
            'onmouseup',
            'onmouseover',
            'onmousemove',
            'onmouseout',
            'onkeypress',
            'onkeydown',
            'onkeyup',
            'pos_tree',
        ];

        foreach ($attrs as $attribute) {
            if (isset($options[$attribute])) {
                $output .= $attribute.'="'.io_safe_input_html($options[$attribute]).'" ';
            }
        }
    } else {
        $options = [];
    }

    if (!isset($options['alt']) && isset($options['title'])) {
        $options['alt'] = io_safe_input_html($options['title']);
        // Set alt to title if it's not set.
    }

    if (!empty($style)) {
        $output .= 'style="'.$style.'" ';
    }

    if (isset($options['alt'])) {
        $output .= 'alt="'.io_safe_input_html($options['alt']).'" />';
    } else {
        $output .= '/>';
    }

    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Render an input text element. Extended version, use html_print_input_text() to simplify.
 *
 * @param string Input name.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param array An array with optional HTML parameters.
 *    Key size: HTML size attribute.
 *    Key disabled: Whether to disable the input or not.
 *    Key class: HTML class
 */
function html_print_input_file($name, $return=false, $options=false)
{
    $output = '';

    $output .= '<input type="file" value="" name="'.$name.'" id="file-'.$name.'" ';

    if ($options) {
        if (isset($options['size'])) {
            $output .= 'size="'.$options['size'].'"';
        }

        if (isset($options['disabled'])) {
            $output .= 'disabled="disabled"';
        }

        if (isset($options['class'])) {
            $output .= 'class="'.$options['class'].'"';
        }
    }

    $output .= ' />';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Render a label for a input elemennt.
 *
 * @param string Label text.
 * @param string Input id to refer.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param array An array with optional HTML parameters.
 *    Key html: Extra HTML to add after the label.
 *    Key class: HTML class
 */
function html_print_label($text, $id, $return=false, $options=false)
{
    $output = '';

    $output .= '<label id="label-'.$id.'" ';

    if ($options) {
        if (isset($options['class'])) {
            $output .= 'class="'.$options['class'].'" ';
        }

        if (isset($options['style'])) {
            $output .= 'style="'.$options['style'].'" ';
        }
    }

    $output .= 'for="'.$id.'" >';
    $output .= $text;
    $output .= '</label>';

    if ($options) {
        if (isset($options['html'])) {
            $output .= $options['html'];
        }
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Convert a html color like #FF00FF into the rgb values like (255,0,255).
 *
 * @param string color in format #FFFFFF, FFFFFF, #FFF or FFF
 */
function html_html2rgb($htmlcolor)
{
    if ($htmlcolor[0] == '#') {
        $htmlcolor = substr($htmlcolor, 1);
    }

    if (strlen($htmlcolor) == 6) {
        $r = hexdec($htmlcolor[0].$htmlcolor[1]);
        $g = hexdec($htmlcolor[2].$htmlcolor[3]);
        $b = hexdec($htmlcolor[4].$htmlcolor[5]);
        return [
            $r,
            $g,
            $b,
        ];
    } else if (strlen($htmlcolor) == 3) {
        $r = hexdec($htmlcolor[0].$htmlcolor[0]);
        $g = hexdec($htmlcolor[1].$htmlcolor[1]);
        $b = hexdec($htmlcolor[2].$htmlcolor[2]);
        return [
            $r,
            $g,
            $b,
        ];
    } else {
        return false;
    }
}


/**
 * Print a magic-ajax control to select the module.
 *
 * @param string  $name            The name of ajax control, by default is "module".
 * @param string  $default         The default value to show in the ajax control.
 * @param array   $id_agents       The array list of id agents as array(1,2,3), by default is false and the function use all agents (if the ACL desactive).
 * @param boolean $ACL             Filter the agents by the ACL list of user.
 * @param string  $scriptResult    The source code of script to call, by default is
 *     empty. And the example is:
 *             function (e, data, formatted) {
 *                ...
 *            }
 *
 *             And the formatted is the select item as string.
 *
 * @param array   $filter          Other filter of modules.
 * @param boolean $return          If it is true return a string with the output instead to echo the output.
 * @param integer $id_agent_module Id agent module.
 * @param string  $size            Size.
 *
 * @return mixed If the $return is true, return the output as string.
 */
function html_print_autocomplete_modules(
    $name='module',
    $default='',
    $id_agents=false,
    $ACL=true,
    $scriptResult='',
    $filter=[],
    $return=false,
    $id_agent_module=0,
    $size='30'
) {
    global $config;

    if ($id_agents === false) {
        $agents = agents_get_agents();

        if ($agents === false) {
            $agents = [];
        }

        $id_agents = [];
        foreach ($agents as $agent) {
            $id_agents[] = $agent['id_agente'];
        }
    } else {
        if ($ACL) {
            $agents = agents_get_agents();

            if ($agents === false) {
                $agents = [];
            }

            $id_agentsACL = [];
            foreach ($agents as $agent) {
                if (array_search($agent['id_agente'], $id_agents) !== false) {
                    $id_agentsACL[] = $agent['id_agente'];
                }
            }

            $id_agents = $id_agentsACL;
        }
    }

    ob_start();

    html_print_input_text_extended(
        $name,
        $default,
        'text-'.$name,
        '',
        $size,
        100,
        false,
        '',
        ['style' => 'background: url(images/search_module.png) no-repeat right;']
    );
    html_print_input_hidden($name.'_hidden', $id_agent_module);
    ui_print_help_tip(__('Type at least two characters to search the module.'), false);

    $javascript_ajax_page = ui_get_full_url('ajax.php', false, false, false, false);
    ?>
    <script type="text/javascript">
        function escapeHTML (str)
        {
            var div = document.createElement('div');
            var text = document.createTextNode(str);
            div.appendChild(text);
            return div.innerHTML;
        }
        
        $(document).ready (function () {
                $("#text-<?php echo $name; ?>").autocomplete({
                    minLength: 2,
                    source: function( request, response ) {
                            var term = request.term; //Word to search
                            
                            data_params = {
                                page: "include/ajax/module",
                                q: term,
                                search_modules: 1,
                                id_agents: '<?php echo json_encode($id_agents); ?>',
                                other_filter: '<?php echo json_encode($filter); ?>'
                            };
                            
                            jQuery.ajax ({
                                data: data_params,
                                async: false,
                                type: "POST",
                                url: action="<?php echo $javascript_ajax_page; ?>",
                                timeout: 10000,
                                dataType: "json",
                                success: function (data) {
                                        temp = [];
                                        $.each(data, function (id, module) {
                                                temp.push({
                                                    'value' : id,
                                                    'label' : module});
                                        });
                                        
                                        response(temp);
                                    }
                                });
                        },
                    change: function( event, ui ) {
                            if (!ui.item)
                                $("input[name='<?php echo $name; ?>_hidden']")
                                    .val(0);
                            return false;
                        },
                    select: function( event, ui ) {
                            $("input[name='<?php echo $name; ?>_hidden']")
                                .val(ui.item.value);
                            
                            $("#text-<?php echo $name; ?>").val( ui.item.label );
                            return false;
                        }
                    }
                );
            });
    </script>
    <?php
    $output = ob_get_clean();

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * @param string Select form name
 * @param string Current selected value
 *
 * @return string HTML code
 */
function html_print_timezone_select($name, $selected='')
{
    $timezones_index = timezone_identifiers_list();
    $timezones = timezone_identifiers_list();
    $timezones = array_combine($timezones_index, $timezones);
    return html_print_select($timezones, $name, $selected, '', __('None'), '', true, false, false);
}


/**
 * Enclose a text into a result_div
 *
 * @param string Text to enclose
 *
 * @return string Text inside the result_div
 */
function html_print_result_div($text)
{
    $text = preg_replace('/</', '&lt;', $text);
    $text = preg_replace('/>/', '&gt;', $text);
    $text = preg_replace('/\n/i', '<br>', $text);
    $text = preg_replace('/\s/i', '&nbsp;', $text);

    $enclose = "<div id='result_div' style='width: 100%; height: 100%; overflow: auto; padding: 10px; font-size: 14px; line-height: 16px; font-family: mono,monospace; text-align: left'>";
    $enclose .= $text;
    $enclose .= '</div>';
    return $enclose;
}


/**
 * Print order arrows links
 *
 * @param array Base tags to build url
 * @param string Order key to add to URL
 * @param string Value to sort ascendent
 * @param string Value to sort descendent
 *
 * @return string HTML code to display both arrows.
 */
function html_print_sort_arrows($params, $order_tag, $up='up', $down='down')
{
    // Build the queries
    $params[$order_tag] = $up;
    $url_up = 'index.php?'.http_build_query($params, '', '&amp;');
    $params[$order_tag] = $down;
    $url_down = 'index.php?'.http_build_query($params, '', '&amp;');

    // Build the links
    $out = '&nbsp;<a href="'.$url_up.'">';
    $out .= html_print_image('images/sort_up_black.png', true);
    $out .= '</a><a href="'.$url_down.'">';
    $out .= html_print_image('images/sort_down_black.png', true).'</a>';
}


/**
 * Print an input hidden with a new csrf token generated
 */
function html_print_csrf_hidden()
{
    html_print_input_hidden('csrf_code', generate_csrf_code());
}


/**
 * Print an error if csrf is incorrect
 */
function html_print_csrf_error()
{
    if (validate_csrf_code()) {
        return false;
    }

    ui_print_error_message(
        __(
            '%s cannot verify the origin of the request. Try again, please.',
            get_product_name()
        )
    );
    return true;
}


/**
 * Print an swith button
 *
 * @param  array $atributes. Valid params:
 *         name: Usefull to handle in forms
 *         value: If is checked or not
 *         disabled: Disabled. Cannot be pressed.
 *         id: Optional id for the switch.
 *         class: Additional classes (string).
 * @return string with HTML of button
 */
function html_print_switch($attributes=[])
{
    $html_expand = '';

    // Check the load values on status.
    $html_expand .= (bool) $attributes['value'] ? ' checked' : '';
    $html_expand .= (bool) $attributes['disabled'] ? ' disabled' : '';

    // Only load the valid attributes.
    $valid_attrs = [
        'id',
        'class',
        'name',
        'onclick',
        'onchange',
    ];
    foreach ($valid_attrs as $va) {
        if (!isset($attributes[$va])) {
            continue;
        }

        $html_expand .= ' '.$va.'="'.$attributes[$va].'"';
    }

    if (!isset($attributes['style'])) {
        $attributes['style'] = '';
    }

    return "<label class='p-switch' style='".$attributes['style']."'>
			<input type='checkbox' $html_expand>
			<span class='p-slider'></span>
		</label>";
}


/**
 * Print a link with post params.The component is really a form with a button
 *      with some inputs hidden.
 *
 * @param string $text   Text to show.
 * @param array  $params Params to be written like inputs hidden.
 * @param string $text   Text of image.
 * @param string $style  Additional style for the element.
 *
 * @return string With HTML code.
 */
function html_print_link_with_params($text, $params=[], $type='text', $style='')
{
    $html = '<form method=post>';
    switch ($type) {
        case 'image':
            $html .= html_print_input_image($text, $text, $text, $style, true);
        break;

        case 'text':
        default:
            if (!empty($style)) {
                $style = ' style="'.$style.'"';
            }

            $html .= html_print_submit_button(
                $text,
                $text,
                false,
                'class="button-as-link"'.$style,
                true
            );
        break;
    }

    foreach ($params as $param => $value) {
        $html .= html_print_input_hidden($param, $value, true);
    }

    $html .= '</form>';

    return $html;
}


/**
 * Print input using functions html lib.
 *
 * @param array   $data       Input definition.
 * @param string  $wrapper    Wrapper 'div' or 'li'.
 * @param boolean $input_only Return or print only input or also label.
 *
 * @return string HTML code for desired input.
 */
function html_print_input($data, $wrapper='div', $input_only=false)
{
    if (is_array($data) === false) {
        return '';
    }

    $output = '';

    if ($data['label'] && $input_only === false) {
        $output = '<'.$wrapper.' id="'.$wrapper.'-'.$data['name'].'" ';
        $output .= ' class="'.$data['input_class'].'">';
        $output .= '<label class="'.$data['label_class'].'">';
        $output .= $data['label'];
        $output .= '</label>';

        if (!$data['return']) {
            echo $output;
        }
    }

    switch ($data['type']) {
        case 'text':
            $output .= html_print_input_text(
                $data['name'],
                $data['value'],
                ((isset($data['alt']) === true) ? $data['alt'] : ''),
                ((isset($data['size']) === true) ? $data['size'] : 50),
                ((isset($data['maxlength']) === true) ? $data['maxlength'] : 255),
                ((isset($data['return']) === true) ? $data['return'] : true),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['function']) === true) ? $data['function'] : ''),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['onChange']) === true) ? $data['onChange'] : ''),
                ((isset($data['autocomplete']) === true) ? $data['autocomplete'] : '')
            );
        break;

        case 'image':
            $output .= html_print_input_image(
                $data['name'],
                $data['src'],
                $data['value'],
                ((isset($data['style']) === true) ? $data['style'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['options']) === true) ? $data['options'] : false)
            );
        break;

        case 'text_extended':
            $output .= html_print_input_text_extended(
                $data['name'],
                $data['value'],
                $data['id'],
                $data['alt'],
                $data['size'],
                $data['maxlength'],
                $data['disabled'],
                $data['script'],
                $data['attributes'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['password']) === true) ? $data['password'] : false),
                ((isset($data['function']) === true) ? $data['function'] : '')
            );
        break;

        case 'password':
            $output .= html_print_input_password(
                $data['name'],
                $data['value'],
                ((isset($data['alt']) === true) ? $data['alt'] : ''),
                ((isset($data['size']) === true) ? $data['size'] : 50),
                ((isset($data['maxlength']) === true) ? $data['maxlength'] : 255),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['autocomplete']) === true) ? $data['autocomplete'] : 'off')
            );
        break;

        case 'text':
            $output .= html_print_input_text(
                $data['name'],
                $data['value'],
                ((isset($data['alt']) === true) ? $data['alt'] : ''),
                ((isset($data['size']) === true) ? $data['size'] : 50),
                ((isset($data['maxlength']) === true) ? $data['maxlength'] : 255),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['required']) === true) ? $data['required'] : false),
                ((isset($data['function']) === true) ? $data['function'] : ''),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['onChange']) === true) ? $data['onChange'] : ''),
                ((isset($data['autocomplete']) === true) ? $data['autocomplete'] : '')
            );
        break;

        case 'hidden':
            $output .= html_print_input_hidden(
                $data['name'],
                $data['value'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['class']) === true) ? $data['class'] : false)
            );
        break;

        case 'hidden_extended':
            $output .= html_print_input_hidden_extended(
                $data['name'],
                $data['value'],
                $data['id'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['class']) === true) ? $data['class'] : false)
            );
        break;

        case 'color':
            $output .= html_print_input_color(
                $data['name'],
                $data['value'],
                ((isset($data['class']) === true) ? $data['class'] : false),
                ((isset($data['return']) === true) ? $data['return'] : false)
            );
        break;

        case 'file':
            $output .= html_print_input_file(
                $data['name'],
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['options']) === true) ? $data['options'] : false)
            );
        break;

        case 'select':
            $output .= html_print_select(
                $data['fields'],
                $data['name'],
                ((isset($data['selected']) === true) ? $data['selected'] : ''),
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['nothing']) === true) ? $data['nothing'] : ''),
                ((isset($data['nothing_value']) === true) ? $data['nothing_value'] : 0),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['multiple']) === true) ? $data['multiple'] : false),
                ((isset($data['sort']) === true) ? $data['sort'] : true),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['style']) === true) ? $data['style'] : false),
                ((isset($data['option_style']) === true) ? $data['option_style'] : false),
                ((isset($data['size']) === true) ? $data['size'] : false),
                ((isset($data['modal']) === true) ? $data['modal'] : false),
                ((isset($data['message']) === true) ? $data['message'] : ''),
                ((isset($data['select_all']) === true) ? $data['select_all'] : false)
            );
        break;

        case 'select_from_sql':
            $output .= html_print_select_from_sql(
                $data['sql'],
                $data['name'],
                ((isset($data['selected']) === true) ? $data['selected'] : ''),
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['nothing']) === true) ? $data['nothing'] : ''),
                ((isset($data['nothing_value']) === true) ? $data['nothing_value'] : '0'),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['multiple']) === true) ? $data['multiple'] : false),
                ((isset($data['sort']) === true) ? $data['sort'] : true),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['style']) === true) ? $data['style'] : false),
                ((isset($data['size']) === true) ? $data['size'] : false),
                ((isset($data['trucate_size']) === true) ? $data['trucate_size'] : GENERIC_SIZE_TEXT)
            );
        break;

        case 'select_groups':
            $output .= html_print_select_groups(
                ((isset($data['id_user']) === true) ? $data['id_user'] : false),
                ((isset($data['privilege']) === true) ? $data['privilege'] : 'AR'),
                ((isset($data['returnAllGroup']) === true) ? $data['returnAllGroup'] : true),
                $data['name'],
                ((isset($data['selected']) === true) ? $data['selected'] : ''),
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['nothing']) === true) ? $data['nothing'] : ''),
                ((isset($data['nothing_value']) === true) ? $data['nothing_value'] : 0),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['multiple']) === true) ? $data['multiple'] : false),
                ((isset($data['sort']) === true) ? $data['sort'] : true),
                ((isset($data['class']) === true) ? $data['class'] : ''),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['style']) === true) ? $data['style'] : false),
                ((isset($data['option_style']) === true) ? $data['option_style'] : false),
                ((isset($data['id_group']) === true) ? $data['id_group'] : false),
                ((isset($data['keys_field']) === true) ? $data['keys_field'] : 'id_grupo'),
                ((isset($data['strict_user']) === true) ? $data['strict_user'] : false),
                ((isset($data['delete_groups']) === true) ? $data['delete_groups'] : false),
                ((isset($data['include_groups']) === true) ? $data['include_groups'] : false),
                ((isset($data['size']) === true) ? $data['size'] : false),
                ((isset($data['simple_multiple_options']) === true) ? $data['simple_multiple_options'] : false)
            );
        break;

        case 'submit':
            $output .= '<'.$wrapper.' class="action-buttons" style="width: 100%">'.html_print_submit_button(
                ((isset($data['label']) === true) ? $data['label'] : 'OK'),
                ((isset($data['name']) === true) ? $data['name'] : ''),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['attributes']) === true) ? $data['attributes'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false)
            ).'</'.$wrapper.'>';
        break;

        case 'checkbox':
            $output .= html_print_checkbox(
                $data['name'],
                $data['value'],
                ((isset($data['checked']) === true) ? $data['checked'] : false),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['disabled_hidden']) === true) ? $data['disabled_hidden'] : false)
            );
        break;

        case 'switch':
            $output .= html_print_switch($data);
        break;

        case 'interval':
            $output .= html_print_extended_select_for_time(
                $data['name'],
                $data['value'],
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['nothing']) === true) ? $data['nothing'] : ''),
                ((isset($data['nothing_value']) === true) ? $data['nothing_value'] : 0),
                ((isset($data['size']) === true) ? $data['size'] : false),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['style']) === true) ? $data['selected'] : false),
                ((isset($data['unique']) === true) ? $data['unique'] : false)
            );
        break;

        case 'textarea':
            $output .= html_print_textarea(
                $data['name'],
                $data['rows'],
                $data['columns'],
                ((isset($data['value']) === true) ? $data['value'] : ''),
                ((isset($data['attributes']) === true) ? $data['attributes'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['class']) === true) ? $data['class'] : '')
            );
        break;

        case 'button':
            $output .= html_print_button(
                ((isset($data['label']) === true) ? $data['label'] : 'OK'),
                ((isset($data['name']) === true) ? $data['name'] : ''),
                ((isset($data['disabled']) === true) ? $data['disabled'] : false),
                ((isset($data['script']) === true) ? $data['script'] : ''),
                ((isset($data['attributes']) === true) ? $data['attributes'] : ''),
                ((isset($data['return']) === true) ? $data['return'] : false),
                ((isset($data['imageButton']) === true) ? $data['imageButton'] : false),
                ((isset($data['modal']) === true) ? $data['modal'] : false),
                ((isset($data['message']) === true) ? $data['message'] : '')
            );
        break;

        default:
            // Ignore.
        break;
    }

    if ($data['label'] && $input_only === false) {
        $output .= '</'.$wrapper.'>';
        if (!$data['return']) {
            echo '</'.$wrapper.'>';
        }
    }

    return $output;
}


/**
 * Print an autocomplete input filled out with Integria IMS users.
 *
 * @param string  $name    The name of ajax control, by default is "users".
 * @param string  $default The default value to show in the ajax control.
 * @param boolean $return  If it is true return a string with the output instead to echo the output.
 * @param string  $size    Size.
 *
 * @return mixed If the $return is true, return the output as string.
 */
function html_print_autocomplete_users_from_integria(
    $name='users',
    $default='',
    $return=false,
    $size='30',
    $disable=false,
    $required=false
) {
    global $config;

    ob_start();

    $attrs = ['style' => 'background: url(images/user_green.png) no-repeat right;'];

    if ($required) {
        $attrs['required'] = 'required';
    }

    html_print_input_text_extended(
        $name,
        $default,
        'text-'.$name,
        '',
        $size,
        100,
        $disable,
        '',
        $attrs
    );
    html_print_input_hidden($name.'_hidden', $id_agent_module);

    ui_print_help_tip(__('Type at least two characters to search the user.'), false);

    $javascript_ajax_page = ui_get_full_url('ajax.php', false, false, false, false);
    ?>
    <script type="text/javascript">
        function escapeHTML (str)
        {
            var div = document.createElement('div');
            var text = document.createTextNode(str);
            div.appendChild(text);
            return div.innerHTML;
        }
        
        $(document).ready (function () {
                $("#text-<?php echo $name; ?>").autocomplete({
                    minLength: 2,
                    source: function( request, response ) {
                            var term = request.term; //Word to search
                            
                            data_params = {
                                page: "include/ajax/integria_incidents.ajax",
                                search_term: term,
                                get_users: 1,
                            };
                            
                            jQuery.ajax ({
                                data: data_params,
                                async: false,
                                type: "POST",
                                url: action="<?php echo $javascript_ajax_page; ?>",
                                timeout: 10000,
                                dataType: "json",
                                success: function (data) {
                                        temp = [];
                                        $.each(data, function (id, module) {
                                                temp.push({
                                                    'value' : id,
                                                    'label' : module});
                                        });
                                        
                                        response(temp);
                                    }
                                });
                        },
                    change: function( event, ui ) {
                            if (!ui.item)
                                $("input[name='<?php echo $name; ?>_hidden']")
                                    .val(0);
                            return false;
                        },
                    select: function( event, ui ) {
                            $("input[name='<?php echo $name; ?>_hidden']")
                                .val(ui.item.value);
                            
                            $("#text-<?php echo $name; ?>").val( ui.item.label );
                            return false;
                        }
                    }
                );
            });
    </script>
    <?php
    $output = ob_get_clean();

    if ($return) {
        return $output;
    } else {
        echo $output;
    }
}
