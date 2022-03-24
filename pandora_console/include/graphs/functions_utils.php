<?php
// Copyright (c) 2011-2011 Ártica Soluciones Tecnológicas
// http://www.artica.es  <info@artica.es>
// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
function serialize_in_temp($array=[], $serial_id=null, $ttl=1)
{
    $json = json_encode($array);

    if ($serial_id === null) {
        $serial_id = uniqid();
    }

    $file_path = sys_get_temp_dir().'/pandora_serialize_'.$serial_id.'__1__'.$ttl;

    if (file_put_contents($file_path, $json) === false) {
        return false;
    }

    return $serial_id;
}


function unserialize_in_temp($serial_id=null, $delete=true, $ttl=1)
{
    if ($serial_id === null) {
        return false;
    }

    $volume = -1;

    for ($i = 1; $i <= $ttl; $i++) {
        $file_path = sys_get_temp_dir().'/pandora_serialize_'.$serial_id.'__'.$i.'__'.$ttl;

        if (file_exists($file_path)) {
            $volume = $i;
            break;
        }
    }

    $content = file_get_contents($file_path);

    if ($content === false) {
        return false;
    }

    $array = json_decode($content, true);

    if ($delete) {
        if ($volume == $ttl) {
            unlink($file_path);
        } else {
            $next_volume = ($volume + 1);
            rename($file_path, sys_get_temp_dir().'/pandora_serialize_'.$serial_id.'__'.$next_volume.'__'.$ttl);
        }
    }

    return $array;
}


function delete_unserialize_in_temp($serial_id=null)
{
    if ($serial_id === null) {
        return false;
    }

    $file_path = sys_get_temp_dir().'/pandora_serialize_'.$serial_id;

    return unlink($file_path);
}


function reverse_data($array)
{
    $array2 = [];
    foreach ($array as $index => $values) {
        foreach ($values as $index2 => $value) {
            $array2[$index2][$index] = $value;
        }
    }

    return $array2;
}


function stack_data(&$chart_data, &$legend=null, &$color=null)
{
    foreach ($chart_data as $val_x => $graphs) {
        $prev_val = 0;
        $key = 1000;
        foreach ($graphs as $graph => $val_y) {
            $chart_data[$val_x][$graph] += $prev_val;
            $prev_val = $chart_data[$val_x][$graph];
            $temp_data[$val_x][$key] = $chart_data[$val_x][$graph];
            if (isset($color[$graph])) {
                $temp_color[$key] = $color[$graph];
            }

            if (isset($legend[$graph])) {
                $temp_legend[$key] = $legend[$graph];
            }

            $key--;
        }

        ksort($temp_data[$val_x]);
    }

    $chart_data = $temp_data;
    if (isset($legend)) {
        $legend = $temp_legend;
        ksort($legend);
    }

    if (isset($color)) {
        $color = $temp_color;
        ksort($color);
    }
}


function graph_get_max_index($legend_values)
{
    $max_chars = 0;
    foreach ($legend_values as $string_legend) {
        if (empty($string_legend)) {
            continue;
        }

        $string_legend = explode("\n", $string_legend);

        foreach ($string_legend as $st_lg) {
            $len = strlen($st_lg);
            if ($len > $max_chars) {
                $max_chars = $len;
            }
        }
    }

    return $max_chars;
}


function setup_watermark($water_mark, &$water_mark_file, &$water_mark_url)
{
    if (!is_array($water_mark)) {
        $water_mark_file = $water_mark;
        $water_mark_url = '';

        return;
    }

    if (isset($water_mark['file'])) {
        $water_mark_file = $water_mark['file'];
    } else {
        $water_mark_file = '';
    }

    if (isset($water_mark['url'])) {
        $water_mark_url = $water_mark['url'];
    } else {
        $water_mark_url = '';
    }
}


// Function to convert hue to RGB
function hue_2_rgb($v1, $v2, $vh)
{
    if ($vh < 0) {
        $vh += 1;
    };

    if ($vh > 1) {
        $vh -= 1;
    };

    if ((6 * $vh) < 1) {
        return ($v1 + ($v2 - $v1) * 6 * $vh);
    };

    if ((2 * $vh) < 1) {
        return ($v2);
    };

    if ((3 * $vh) < 2) {
        return ($v1 + ($v2 - $v1) * ((2 / 3 - $vh) * 6));
    };

    return ($v1);
};


function hex_2_rgb($hexcode)
{
    $hexcode = str_replace('#', '', $hexcode);

    // $hexcode is the six digit hex colour code we want to convert
    $redhex  = substr($hexcode, 0, 2);
    $greenhex = substr($hexcode, 2, 2);
    $bluehex = substr($hexcode, 4, 2);

    // $var_r, $var_g and $var_b are the three decimal fractions to be input to our RGB-to-HSL conversion routine
    $var_r = hexdec($redhex);
    $var_g = hexdec($greenhex);
    $var_b = hexdec($bluehex);

    return [
        'R' => $var_r,
        'G' => $var_g,
        'B' => $var_b,
    ];
}


function get_complementary_rgb($hexcode)
{
    $rgb = hex_2_rgb($hexcode);

    $var_r = ($rgb['R'] / 255);
    $var_g = ($rgb['G'] / 255);
    $var_b = ($rgb['B'] / 255);

    // Now plug these values into the rgb2hsl routine. Below is my PHP version of EasyRGB.com's generic code for that conversion:
    // Input is $var_r, $var_g and $var_b from above
    // Output is HSL equivalent as $h, $s and $l — these are again expressed as fractions of 1, like the input values
    $var_min = min($var_r, $var_g, $var_b);
    $var_max = max($var_r, $var_g, $var_b);
    $del_max = ($var_max - $var_min);

    $l = (($var_max + $var_min) / 2);

    if ($del_max == 0) {
        $h = 0;
        $s = 0;
    } else {
        if ($l < 0.5) {
            $s = ($del_max / ($var_max + $var_min));
        } else {
            $s = ($del_max / (2 - $var_max - $var_min));
        };

        $del_r = (((($var_max - $var_r) / 6) + ($del_max / 2)) / $del_max);
        $del_g = (((($var_max - $var_g) / 6) + ($del_max / 2)) / $del_max);
        $del_b = (((($var_max - $var_b) / 6) + ($del_max / 2)) / $del_max);

        if ($var_r == $var_max) {
            $h = ($del_b - $del_g);
        } else if ($var_g == $var_max) {
            $h = ((1 / 3) + $del_r - $del_b);
        } else if ($var_b == $var_max) {
            $h = ((2 / 3) + $del_g - $del_r);
        };

        if ($h < 0) {
            $h += 1;
        };

        if ($h > 1) {
            $h -= 1;
        };
    };

    // So now we have the colour as an HSL value, in the variables $h, $s and $l. These three output variables are again held as fractions of 1 at this stage, rather than as degrees and percentages. So e.g., cyan (180° 100% 50%) would come out as $h = 0.5, $s = 1, and $l =  0.5.
    // Next find the value of the opposite Hue, i.e., the one that's 180°, or 0.5, away (I'm sure the mathematicians have a more elegant way of doing this, but):
    // Calculate the opposite hue, $h2
    $h2 = ($h + 0.5);

    if ($h2 > 1) {
        $h2 -= 1;
    };

    // The HSL value of the complementary colour is now in $h2, $s, $l. So we're ready to convert this back to RGB (again, my PHP version of the EasyRGB.com formula). Note the input and output formats are different this time, see my comments at the top of the code:
    // Input is HSL value of complementary colour, held in $h2, $s, $l as fractions of 1
    // Output is RGB in normal 255 255 255 format, held in $r, $g, $b
    // Hue is converted using function hue_2_rgb, shown at the end of this code
    if ($s == 0) {
        $r = ($l * 255);
        $g = ($l * 255);
        $b = ($l * 255);
    } else {
        if ($l < 0.5) {
            $var_2 = ($l * (1 + $s));
        } else {
            $var_2 = (($l + $s) - ($s * $l));
        };
        $var_1 = (2 * $l - $var_2);

        $r = (255 * hue_2_rgb($var_1, $var_2, ($h2 + (1 / 3))));
        $g = (255 * hue_2_rgb($var_1, $var_2, $h2));
        $b = (255 * hue_2_rgb($var_1, $var_2, ($h2 - (1 / 3))));
    };

    // And after that routine, we finally have $r, $g and $b in 255 255 255 (RGB) format, which we can convert to six digits of hex:
    $rhex = sprintf('%02X', round($r));
    $ghex = sprintf('%02X', round($g));
    $bhex = sprintf('%02X', round($b));

    $rgbhex = $rhex.$ghex.$bhex;

    return $rgbhex;
}


/**
 * Returns convert array multidimensional to string whit gluer.
 *
 * @param array                  $array to convert
 * @param string glue to implode
 */
function convert_array_multi($array, $glue)
{
    $result = '';
    foreach ($array as $item) {
        if (is_array($item)) {
            $result .= convert_array_multi($item, $glue).$glue;
        } else {
            $result .= $item.$glue;
        }
    }

    $result = substr($result, 0, (0 - strlen($glue)));
    return $result;
}


/**
 * Evaluate if the chars of coming variable has in the range stablished.
 *
 * @param string $string String for be evaluated.
 * @param array  $ranges Ranges for valid chars. Min: [ x <= Y ] Max: [ Y > x ].
 * Example of valid ranges: [ '32:126', '150:188' ].
 *
 * @return boolean.
 */
function evaluate_ascii_valid_string(string $string='', array $ranges=[ '33:38', '40:126' ])
{
    if (empty($string) === true) {
        return false;
    }

    $countChars = strlen($string);
    // Let's explore all the chars.
    for ($i = 0; $i < $countChars; $i++) {
        // Get ascii number of the char.
        $asciiNumber = ord($string[$i]);
        // Check in all ranges.
        $rangeValidation = false;
        foreach ($ranges as $range) {
            list($minRangeValue, $maxRangeValue) = explode(':', $range, 2);
            // Check if is in range.
            if ($asciiNumber > (int) $minRangeValue && $asciiNumber < (int) $maxRangeValue) {
                $rangeValidation = true;
            }
        }

        // None of the ranges was validated.
        if ($rangeValidation === false) {
            return false;
        }
    }

    return true;
}
