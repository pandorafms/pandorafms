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
 * Prints an array of fields in a popup menu of a form.
 * 
 * Based on choose_from_menu() from Moodle 
 * 
 * @param array $fields Array with dropdown values. Example: $fields["value"] = "label"
 * @param string $name Select form name
 * @param variant $selected Current selected value. Can be a single value or an
 * array of selected values (in combination with multiple)
 * @param string $script Javascript onChange code.
 * @param string $nothing Label when nothing is selected.
 * @param variant $nothing_value Value when nothing is selected
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 * @param bool $multiple Set the input to allow multiple selections (optional, single selection by default).
 * @param bool $sort Whether to sort the options or not (optional, unsorted by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_select ($fields, $name, $selected = '', $script = '', $nothing = '', $nothing_value = '0', $return = false, $multiple = false, $sort = true, $class = '', $disabled = false) {
	$output = "\n";
	
	$attributes = "";
	if (!empty ($script)) {
		$attributes .= ' onchange="'.$script.'"';
	}
	if (!empty ($multiple)) {
		$attributes .= ' multiple="yes" size="10"';
	}
	if (!empty ($class)) {
		$attributes .= ' class="'.$class.'"';
	}
	if (!empty ($disabled)) {
		$attributes .= ' disabled';
	}

	$output .= '<select id="'.$name.'" name="'.$name.'"'.$attributes.'>';

	if ($nothing != '') {
		$output .= '<option value="'.$nothing_value.'"';
		if ($nothing_value == $selected) {
			$output .= " selected";
		}
		$output .= '>'.$nothing."</option>"; //You should pass a translated string already
	}

	if (!empty ($fields)) {
		if ($sort !== false) {
			asort ($fields);
		}
		foreach ($fields as $value => $label) {
			$output .= '<option value="'.$value.'"';
			if (is_array ($selected) && in_array ($value, $selected)) {
				$output .= ' selected';
			} elseif (!is_array ($selected) && $value == $selected) {
				$output .= ' selected';
			}
			if ($label === '') {
				$output .= '>'.$value."</option>";
			} else {
				$output .= '>'.$label."</option>";
			}
		}
	}

	$output .= "</select>";

	if ($return)
		return $output;

	echo $output;
}

/**
 * Prints an array of fields in a popup menu of a form based on a SQL query.
 * The first and second columns of the query will be used.
 * 
 * The element will have an id like: "password-$value". Based on choose_from_menu() from Moodle.
 * 
 * @param string SQL sentence, the first field will be the identifier of the option. 
 *      The second field will be the shown value in the dropdown.
 * @param string Select form name
 * @param string Current selected value.
 * @param string Javascript onChange code.
 * @param string Label when nothing is selected.
 * @param string Value when nothing is selected
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Whether to allow multiple selections or not. Single by default
 * @param bool Whether to sort the options or not. Sorted by default.
 *
 * @return string HTML code if return parameter is true.
 */
function print_select_from_sql ($sql, $name, $selected = '', $script = '', $nothing = '', $nothing_value = '0', $return = false, $multiple = false, $sort = true) {
	
	$fields = array ();
	$result = get_db_all_rows_sql ($sql);
	if ($result === false)
		$result = array ();
	
	foreach ($result as $row) {
		$fields[$row[0]] = $row[1];
	}
	
	return print_select ($fields, $name, $selected, $script, $nothing, $nothing_value, $return, $multiple, $sort);
}

/**
 * Render an input text element. Extended version, use print_input_text() to simplify.
 * 
 * @param string Input name.
 * @param string Input value.
 * @param string Input HTML id.
 * @param string Alternative HTML string.
 * @param int Size of the input.
 * @param int Maximum length allowed.
 * @param bool Disable the button (optional, button enabled by default).
 * @param string Alternative HTML string.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 * @param bool Whether it is a password input or not. Not password by default.
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_text_extended ($name, $value, $id, $alt, $size, $maxlength, $disabled, $script, $attributes, $return = false, $password = false) {
	static $idcounter = 0;
	
	++$idcounter;
	
	$type = $password ? 'password' : 'text';

	if (empty ($name)) {
		$name = 'unnamed';
	}
	
	if (empty ($alt)) {
		$alt = 'textfield';
	}
	
	if (! empty ($maxlength)) {
		$maxlength = ' maxlength="'.$maxlength.'" ';
	}
	
	$output = '<input name="'.$name.'" type="'.$type.'" value="'.$value.'" size="'.$size.'" '.$maxlength.' alt="'.$alt.'" ';

	if ($id != '') {
		$output .= ' id="'.$id.'"';
	} else {
		$htmlid = 'text-'.sprintf ('%04d', $idcounter);
		$output .= ' id="'.$htmlid.'"';
	}
	if ($disabled)
		$output .= ' disabled';
	
	if ($attributes != '')
		$output .= ' '.$attributes;
	$output .= ' />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input password element.
 *
 * The element will have an id like: "password-$name"
 * 
 * @param string Input name.
 * @param string Input value.
 * @param string Alternative HTML string (optional).
 * @param int Size of the input (optional).
 * @param int Maximum length allowed (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_password ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false) {
	$output = print_input_text_extended ($name, $value, 'password-'.$name, $alt, $size, $maxlength, false, '', '', true, true);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input text element.
 *
 * The element will have an id like: "text-$name"
 * 
 * @param string Input name.
 * @param string Input value.
 * @param string Alternative HTML string (optional).
 * @param int Size of the input (optional).
 * @param int Maximum length allowed (optional).
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_text ($name, $value, $alt = '', $size = 50, $maxlength = 0, $return = false) {
	$output = print_input_text_extended ($name, $value, 'text-'.$name, $alt, $size, $maxlength, false, '', '', true);

	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input image element.
 *
 * The element will have an id like: "image-$name"
 * 
 * @param string Input name.
 * @param string Image source.
 * @param string Input value.
 * @param string HTML style property.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_image ($name, $src, $value, $style = '', $return = false) {
	$output = '<input id="image-'.$name.'" src="'.$src.'" style="'.$style.'" name="'.$name.'" type="image" value="'.$value.'" />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an input hidden element.
 *
 * The element will have an id like: "hidden-$name"
 * 
 * @param string Input name.
 * @param string Input value.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_input_hidden ($name, $value, $return = false) {
	$output = '<input id="hidden-'.$name.'" name="'.$name.'" type="hidden" value="'.$value.'" />';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render an submit input button element.
 *
 * The element will have an id like: "submit-$name"
 * 
 * @param string Input label.
 * @param string Input name.
 * @param bool Whether to disable by default or not. Enabled by default.
 * @param string Additional HTML attributes.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_submit_button ($label = 'OK', $name = '', $disabled = false, $attributes = '', $return = false) {
	$output = '';

	$output .= '<input type="submit" id="submit-'.$name.'" name="'.$name.'" value="'. $label .'" '. $attributes;
	if ($disabled)
		$output .= ' disabled';
	$output .= ' />';
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render an submit input button element.
 *
 * The element will have an id like: "button-$name"
 * 
 * @param string Input label.
 * @param string Input name.
 * @param bool Whether to disable by default or not. Enabled by default.
 * @param string Additional HTML attributes.
 * @param bool Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_button ($label = 'OK', $name = '', $disabled = false, $script = '', $attributes = '', $return = false) {
	$output = '';

	$output .= '<input type="button" id="button-'.$name.'" name="'.$name.'" value="'. $label .'" onClick="'. $script.'" '.$attributes;
	if ($disabled)
		$output .= ' disabled';
	$output .= ' />';
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render an input textarea element.
 *
 * The element will have an id like: "textarea_$name"
 * 
 * @param string Input name.
 * @param string Input value.
 * @param bool Whether to return an output string or echo now (optional, echo by default). *
 * @return string HTML code if return parameter is true.
 */
function print_textarea ($name, $rows, $columns, $value = '', $attributes = '', $return = false) {
	$output = '<textarea id="textarea_'.$name.'" name="'.$name.'" cols="'.$columns.'" rows="'.$rows.'" '.$attributes.' >';
	$output .= $value;
	$output .= '</textarea>';
	
	if ($return)
		return $output;
	echo $output;
}

/**
 * Print a nicely formatted table. Code taken from moodle.
 *
 * @param array $table is an object with several properties:
 *     $table->head - An array of heading names.
 *     $table->align - An array of column alignments
 *     $table->valign - An array of column alignments
 *     $table->size  - An array of column sizes
 *     $table->wrap - An array of "nowrap"s or nothing
 *     $table->style  - An array of personalized style for each column.
 *     $table->rowstyle  - An array of personalized style of each row.
 *     $table->rowclass  - An array of personalized classes of each row (odd-evens classes will be ignored).
 *     $table->colspan  - An array of colspans of each column.
 *     $table->data[] - An array of arrays containing the data.
 *     $table->width  - A percentage of the page
 *     $table->border  - Border of the table.
 *     $table->tablealign  - Align the whole table
 *     $table->cellpadding  - Padding on each cell
 *     $table->cellspacing  - Spacing between cells
 *     $table->class  - CSS table class
 *	   $table->id - Table ID (useful in JavaScript)
 *	   $table->headclass[] - An array of classes for each heading
 * @param  bool $return whether to return an output string or echo now
 *
 * @return string HTML code if return parameter is true.
 */
function print_table (&$table, $return = false) {
	$output = '';
	static $table_count = 0;

	$table_count++;
	if (isset ($table->align)) {
		foreach ($table->align as $key => $aa) {
			if ($aa) {
				$align[$key] = ' text-align:'. $aa.';';
			} else {
				$align[$key] = '';
			}
		}
	}
	if (isset ($table->valign)) {
		foreach ($table->valign as $key => $aa) {
			if ($aa) {
				$valign[$key] = ' vertical-align:'. $aa.';';
			} else {
				$valign[$key] = '';
			}
		}
	}
	if (isset ($table->size)) {
		foreach ($table->size as $key => $ss) {
			if ($ss) {
				$size[$key] = ' width:'. $ss .';';
			} else {
				$size[$key] = '';
			}
		}
	}
	if (isset ($table->style)) {
		foreach ($table->style as $key => $st) {
			if ($st) {
				$style[$key] = ' '. $st .';';
			} else {
				$style[$key] = '';
			}
		}
	}
	if (isset ($table->rowstyle)) {
		foreach ($table->rowstyle as $key => $st) {
			$rowstyle[$key] = ' '. $st .';';
		}
	}
	if (isset ($table->rowclass)) {
		foreach ($table->rowclass as $key => $class) {
			$rowclass[$key] = $class;
		}
	}
	if (isset ($table->colspan)) {
		foreach ($table->colspan as $keyrow => $cspan) {
			foreach ($cspan as $key => $span) {
				$colspan[$keyrow][$key] = ' colspan="'.$span.'"';
			}
		}
	}
	if (empty ($table->width)) {
		$table->width = '80%';
	}
	
	if (empty ($table->border)) {
		$table->border = '0px';
	}
	
	if (empty ($table->tablealign)) {
		$table->tablealign = 'center';
	}

	if (!isset ($table->cellpadding)) {
		$table->cellpadding = '4';
	}

	if (!isset ($table->cellspacing)) {
		$table->cellspacing = '4';
	}

	if (empty ($table->class)) {
		$table->class = 'databox';
	}
	
	$tableid = empty ($table->id) ? 'table'.$table_count : $table->id;

	$output .= '<table width="'.$table->width.'" ';
	$output .= ' cellpadding="'.$table->cellpadding.'" cellspacing="'.$table->cellspacing.'"';
	$output .= ' border="'.$table->border.'" class="'.$table->class.'" id="'.$tableid.'">';
	$countcols = 0;
	if (!empty ($table->head)) {
		$countcols = count ($table->head);
		$output .= '<thead><tr>';
		foreach ($table->head as $key => $heading) {
			if (!isset ($size[$key])) {
				$size[$key] = '';
			}
			if (!isset ($align[$key])) {
				$align[$key] = '';
			}
			if (!isset ($table->headclass[$key])) {
				$table->headclass[$key] = 'header c'.$key;
			}	
			$output .= '<th class="'.$table->headclass[$key].'" scope="col">'. $heading .'</th>';
		}
		$output .= '</tr></thead>'."\n";
	}

	$output .= '<tbody>'."\n";
	if (!empty ($table->data)) {
		$oddeven = 1;
		foreach ($table->data as $keyrow => $row) {
			
			if (!isset ($rowstyle[$keyrow])) {
				$rowstyle[$keyrow] = '';
			}
			$oddeven = $oddeven ? 0 : 1;
			$class = 'datos'.($oddeven ? "" : "2");
			if (isset ($rowclass[$keyrow])) {
				$class = $rowclass[$keyrow];
			}
			$output .= '<tr id="'.$tableid."-".$keyrow.'" style="'.$rowstyle[$keyrow].'" class="'.$class.'">'."\n";
			/* Special separator rows */
			if ($row == 'hr' and $countcols) {
				$output .= '<td colspan="'. $countcols .'"><div class="tabledivider"></div></td>';
				continue;
			}
			/* It's a normal row */
			foreach ($row as $key => $item) {
				if (!isset ($size[$key])) {
					$size[$key] = '';
				}
				if (!isset ($colspan[$keyrow][$key])) {
					$colspan[$keyrow][$key] = '';
				}
				if (!isset ($align[$key])) {
					$align[$key] = '';
				}
				if (!isset ($valign[$key])) {
					$valign[$key] = '';
				}
				if (!isset ($wrap[$key])) {
					$wrap[$key] = '';
				}
				if (!isset ($style[$key])) {
					$style[$key] = '';
				}
				
				$output .= '<td id="'.$tableid.'-'.$keyrow.'-'.$key.'" style="'. $style[$key].$valign[$key].$align[$key].$size[$key].$wrap[$key] .'" '.$colspan[$keyrow][$key].' class="'.$class.'">'. $item .'</td>'."\n";
			}
			$output .= '</tr>'."\n";
		}
	}
	$output .= '</tbody></table>'."\n";

	if ($return) 
		return $output;
	
	echo $output;
}

/**
 * Render a radio button input. Extended version, use print_radio_button() to simplify.
 *
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $checked Set the button to be marked (optional, unmarked by default).
 * @param bool $disabled Disable the button (optional, button enabled by default).
 * @param string $script Script to execute when onClick event is triggered (optional).
 * @param string $attributes Optional HTML attributes. It's a free string which will be 
	inserted into the HTML tag, use it carefully (optional).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_radio_button_extended ($name, $value, $label, $checkedvalue, $disabled, $script, $attributes, $return = false) {
	static $idcounter = 0;

	$output = '';
	
	$output = '<input type="radio" name="'.$name.'" value="'.$value.'"';
	$htmlid = 'radiobtn'.sprintf ('%04d', ++$idcounter);
	$output .= ' id="'.$htmlid.'"';

	if ($value == $checkedvalue) {
		 $output .= ' checked="checked"';
	}
	if ($disabled) {
		 $output .= ' disabled';
	}
	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}
	$output .= ' ' . $attributes ;
	$output .= ' />';
	
	if ($label != '') {
		$output .= '<label for="'.$htmlid.'">'.  $label .'</label>' .  "\n";
	}
	
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a radio button input.
 *
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $label Label to add after the radio button (optional).
 * @param string $checkedvalue Checked and selected value, the button will be selected if it matches $value (optional).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_radio_button ($name, $value, $label = '', $checkedvalue = '', $return = false) {
	$output = print_radio_button_extended ($name, $value, $label, $checkedvalue, false, '', '', true);
	
	if ($return)
		return $output;

	echo $output;
}

/**
 * Render a checkbox button input. Extended version, use print_checkbox() to simplify.
 *
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $checked Set the button to be marked (optional, unmarked by default).
 * @param bool $disabled Disable the button  (optional, button enabled by default).
 * @param string $script Script to execute when onClick event is triggered (optional).
 * @param string $attributes Optional HTML attributes. It's a free string which will be 
	inserted into the HTML tag, use it carefully (optional).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_checkbox_extended ($name, $value, $checked, $disabled, $script, $attributes, $return = false) {
	static $idcounter = 0;

	$htmlid = 'checkbox'.sprintf ('%04d', ++$idcounter);
	$output = '<input name="'.$name.'" type="checkbox" value="'.$value.'" '. ($checked ? 'checked': '');
	$output .= ' id="'.$htmlid.'"';
	
	if ($script != '') {
		 $output .= ' onClick="'. $script . '"';
	}
	
	if ($disabled) {
		 $output .= ' disabled';
	}
	
	$output .= ' />';
	$output .= "\n";
	if ($return)
		return $output;
	echo $output;
}

/**
 * Render a checkbox button input.
 *
 * @param string $name Input name.
 * @param string $value Input value.
 * @param string $checked Set the button to be marked (optional, unmarked by default).
 * @param bool $return Whether to return an output string or echo now (optional, echo by default).
 *
 * @return string HTML code if return parameter is true.
 */
function print_checkbox ($name, $value, $checked = false, $return = false) {
	$output = print_checkbox_extended ($name, $value, (bool) $checked, false, '', '', true);

	if ($return)
		return $output;
	echo $output;
}

/** 
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 * 
 * @param  string $text Complete text to show in the tip
 * @param  bool $return whether to return an output string or echo now
 *
 * @return string HTML code if return parameter is true.
 */
function print_help_tip ($text, $return = false) {
	$output = '<a href="#" class="tip">&nbsp;<span>'.$text.'</span></a>';
	
	if ($return)
		return $output;
	echo $output;
}


/**
 * Prints an image HTML element.
 *
 * @param string Image source filename.
 * @param array Array with optional HTML options to set. At this moment, the 
 * following options are supported: alt, style, title, width, height, class.
 *
 * @return string HTML code if return parameter is true.
 */
function print_image ($src, $return = false, $options = false) {
	$output = '<img src="'.$src.'"" ';
	
	if ($options) {
		if (isset ($options['alt']))
			$output .= 'alt="'.$options['alt'].'" ';
		
		if (isset ($options['style']))
			$output .= 'style="'.$options['style'].'" ';
		
		if (isset ($options['title']))
			$output .= 'title="'.$options['title'].'" ';
		
		if (isset ($options['width']))
			$output .= 'width="'.$options['width'].'" ';
		
		if (isset ($options['height']))
			$output .= 'height="'.$options['height'].'" ';
		
		if (isset ($options['class']))
			$output .= 'class="'.$options['class'].'" ';
		
		if (isset ($options['id']))
			$output .= 'id="'.$options['id'].'" ';
	}
	
	$output .= '/>';
	
	if ($return)
		return $output;
	echo $output;
}

/** 
 * Evaluates a result using empty () and then prints an error message or a
 * success message
 * 
 * @param mixed $result the results to evaluate. 0, NULL, false, '' or array()
 * is bad, the rest is good
 * @param string $good the string to be displayed if the result was good
 * @param string $bad the string to be displayed if the result was bad
 * @param string $attributes any other attributes to be set for the h3
 * @param bool $return whether to output the string or return it
 * @param string $tag what tag to use (you could specify something else than
 * h3 like div or h2
 *
 * @return string HTML code if return parameter is true.
*/
function print_error_message ($result, $good = '', $bad = '', $attributes = '', $return = false, $tag = 'h3') {
	if ($good == '' || $good === false)
		$good = __('Request successfully processed');
	
	if ($bad == '' || $bad === false)
		$bad = __('Error processing request');
	
	if (empty ($result)) {
		$output = '<'.$tag.' class="error" '.$attributes.'>'.$bad.'</'.$tag.'>';
	} else {
		$output = '<'.$tag.' class="suc" '.$attributes.'>'.$good.'</'.$tag.'>';
	}

	if ($return === false)
		echo $output;
	
	return $output;
}

/**
 * Evaluates a unix timestamp and returns a span (or whatever tag specified)
 * with as title the correctly formatted full timestamp and a time comparation
 * in the tag
 *
 * @param int $unixtime: Any type of timestamp really, but we prefer unixtime
 * @param string $attributes: Any additional attributes (class, script etc.)
 * @param string $tag: If it should be in a different tag than span
 * @param bool $return whether to output the string or return it
 *
 * @return string HTML code if return parameter is true.
 */
function print_timestamp ($unixtime, $attributes = "", $tag = "span", $return = false) {
	global $config;
	
	if (!is_numeric ($unixtime)) {
		$unixtime = strtotime ($unixtime);
	}

	//prominent_time is either timestamp or comparation
	if ($config["prominent_time"] == "timestamp") {
		$title = human_time_comparation ($unixtime);
		$data = date ($config["date_format"], $unixtime);
	} else {
		$title = date ($config["date_format"], $unixtime);
		$data = human_time_comparation ($unixtime);
	}
	
	$output = '<'.$tag;
	switch ($tag) {
		default:
		//Usually tags have title attributes, so by default we add, then fall through to add attributes and data
			$output .= ' title="'.$title.'"';
		case "h1":
		case "h2":
		case "h3":
		//Above tags don't have title attributes
			$output .= ' '.$attributes.'>'.$data.'</'.$tag.'>';
	}

	if ($return === false) {
		echo $output;
	}
	return $output;
}

/**
 * Prints a username with real name, link to the user_edit page etc.
 *
 * @param string The username to render
 * @param bool Whether to return or print
 *
 * @return string HTML code if return parameter is true.
 */
function print_username ($username, $return = false) {
	$string = '<a href="index.php?sec=usuario&sec2=operation/users/user_edit&ver='.$username.'">'.dame_nombre_real ($username).'</a>';
	if ($return === false) {
		echo $string;
	}
	return $string;
}
?>
