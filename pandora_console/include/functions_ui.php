<?php
/**
 * Library. User interface functions.
 *
 * @category   Library.
 * @package    Pandora FMS
 * @subpackage User interface.
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 * |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 * |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2023 Pandora FMS
 * Please see https://pandorafms.com/community/ for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

use Mpdf\Tag\Tr;

// Begin.
global $config;

// Check to avoid error when load this library in error screen situations.
if (isset($config['homedir'])) {
    include_once $config['homedir'].'/include/functions_agents.php';
    include_once $config['homedir'].'/include/functions_modules.php';
    include_once $config['homedir'].'/include/functions.php';
    include_once $config['homedir'].'/include/functions_groups.php';
    include_once $config['homedir'].'/include/functions_users.php';
    include_once $config['homedir'].'/include/functions_html.php';
}


/**
 * Transform bbcode to HTML and truncate log.
 *
 * @param string $text         Text.
 * @param array  $allowed_tags Allowed_tags.
 *
 * @return string HTML.
 */
function ui_bbcode_to_html($text, $allowed_tags=['[url]'])
{
    if (array_search('[url]', $allowed_tags) !== false || a) {
        // Replace bbcode format [url=www.example.org] String [/url] with or without http and slashes
        preg_match('/\[url(?|](((?:https?:\/\/)?[^[]+))|(?:=[\'"]?((?:https?:\/\/)?[^]]+?)[\'"]?)](.+?))\[\/url]/', $text, $matches);
        if ($matches) {
            $url = $matches[1];
            // Truncate text
            $t_text = ui_print_truncate_text($matches[2]);
             // If link hasn't http, add it.
            if (preg_match('/https?:\/\//', $text)) {
                $return = '<a target="_blank" rel="noopener noreferrer" href="'.$matches[1].'">'.$t_text.'</a>';
            } else {
                $return = '<a target="_blank" rel="noopener noreferrer" href="http://'.$matches[1].'">'.$t_text.'</a>';
            }
        } else {
            $return = ui_print_truncate_text($text);
        }
    }

    return $return;
}


/**
 * Truncate a text to num chars (pass as parameter) and if flag show tooltip is
 * true the html artifal to show the tooltip with rest of text.
 *
 * @param string  $text               The text to truncate.
 * @param mixed   $numChars           Number chars (-3 for char "[...]") max the text. Or the strings "agent_small", "agent_medium", "module_small", "module_medium", "description" or "generic" for to take the values from user config.
 * @param boolean $showTextInAToopTip Flag to show the tooltip.
 * @param boolean $return             Flag to return as string or not.
 * @param boolean $showTextInTitle    Flag to show the text on title.
 * @param string  $suffix             String at the end of a strimmed string.
 * @param string  $style              Style associated to the text.
 *
 * @return string Truncated text.
 */
function ui_print_truncate_text(
    $text,
    $numChars=GENERIC_SIZE_TEXT,
    $showTextInAToopTip=true,
    $return=true,
    $showTextInTitle=true,
    $suffix='&hellip;',
    $style=false,
    $forced_title=false,
    $text_title=''
) {
    global $config;
    $truncate_at_end = false;
    if (is_string($numChars)) {
        switch ($numChars) {
            case 'agent_small':
                $numChars = $config['agent_size_text_small'];
                $truncate_at_end = (bool) $config['truncate_agent_at_end'];
            break;

            case 'agent_medium':
                $numChars = $config['agent_size_text_medium'];
                $truncate_at_end = (bool) $config['truncate_agent_at_end'];
            break;

            case 'module_small':
                $numChars = $config['module_size_text_small'];
                $truncate_at_end = (bool) $config['truncate_module_at_end'];
            break;

            case 'module_medium':
                $numChars = $config['module_size_text_medium'];
                $truncate_at_end = (bool) $config['truncate_module_at_end'];
            break;

            case 'description':
                $numChars = $config['description_size_text'];
            break;

            case 'item_title':
                $numChars = $config['item_title_size_text'];
            break;

            default:
                $numChars = (int) $numChars;
            break;
        }
    }

    if ($numChars == 0) {
        if ($return == true) {
            return $text;
        } else {
            echo $text;
        }
    }

    $text_html_decoded = io_safe_output($text);
    $text_has_entities = $text != $text_html_decoded;

    if (isset($text_html_decoded) === true && mb_strlen($text_html_decoded, 'UTF-8') > ($numChars)) {
        // '/2' because [...] is in the middle of the word.
        $half_length = intval(($numChars - 3) / 2);

        if ($truncate_at_end === true) {
            // Recover the html entities to avoid XSS attacks.
            $truncateText = ($text_has_entities) ? io_safe_input(substr($text_html_decoded, 0, $numChars)) : substr($text_html_decoded, 0, $numChars);
            if (strlen($text_html_decoded) > $numChars) {
                $truncateText .= '...';
            }
        } else {
            // Depending on the strange behavior of mb_strimwidth() itself,
            // the 3rd parameter is not to be $numChars but the length of
            // original text (just means 'large enough').
            $truncateText2 = mb_strimwidth(
                $text_html_decoded,
                (mb_strlen($text_html_decoded, 'UTF-8') - $half_length),
                mb_strlen($text_html_decoded, 'UTF-8'),
                '',
                'UTF-8'
            );

            $truncateText = mb_strimwidth(
                $text_html_decoded,
                0,
                ($numChars - $half_length),
                '',
                'UTF-8'
            );

            // Recover the html entities to avoid XSS attacks.
            $truncateText = ($text_has_entities) ? io_safe_input($truncateText).$suffix.io_safe_input($truncateText2) : $truncateText.$suffix.$truncateText2;
        }

        if ($showTextInTitle) {
            if ($style === null) {
                $truncateText = $truncateText;
            } else if ($style !== false) {
                $truncateText = '<span style="'.$style.'" title="'.$text.'">'.$truncateText.'</span>';
            } else {
                $truncateText = '<span title="'.$text.'">'.$truncateText.'</span>';
            }
        }

        if ($showTextInAToopTip) {
            if (is_string($showTextInAToopTip)) {
                $text = ui_print_truncate_text($showTextInAToopTip, ($numChars * 2), false, true, false);
            }

            $truncateText = $truncateText.ui_print_help_tip(htmlspecialchars($text), true);
        } else {
            if ($style !== false) {
                $truncateText = '<span style="'.$style.'">'.$truncateText.'</span>';
            }
        }
    } else {
        if ($style !== false) {
            $truncateText = '<span style="'.$style.'">'.$text.'</span>';
        } else {
            $truncateText = $text;
        }
    }

    if ($forced_title === true) {
        if ($text_title !== '') {
            $truncateText = '<span class="forced_title" style="'.$style.'" data-title="'.$text_title.'" data-use_title_for_force_title="1">'.$truncateText.'</span>';
        } else {
            $truncateText = '<span class="forced_title" style="'.$style.'" data-title="'.$text.'" data-use_title_for_force_title="1">'.$truncateText.'</span>';
        }
    }

    if ($return == true) {
        return $truncateText;
    } else {
        echo $truncateText;
    }
}


/**
 * Print a string with a smaller font depending on its size.
 *
 * @param string  $string String to be display with a smaller font.
 * @param boolean $return Flag to return as string or not.
 *
 * @return string HTML.
 */
function printSmallFont($string, $return=true)
{
    $str = io_safe_output($string);
    $length = strlen($str);
    if ($length >= 30) {
        $size = 0.7;
    } else if ($length >= 20) {
        $size = 0.8;
    } else if ($length >= 10) {
        $size = 0.9;
    } else if ($length < 10) {
        $size = 1;
    }

    $s = '<span style="font-size: '.$size.'em;">';
    $s .= $string;
    $s .= '</span>';
    if ($return) {
        return $s;
    } else {
        echo $s;
    }
}


/**
 * Prints a generic message between tags.
 *
 * @param mixed   $message    The string message or array [
 *      'title', 'message', 'icon', 'no_close', 'force_style'] to be displayed.
 * @param string  $class      The class to be used.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *      than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_message($message, $class='', $attributes='', $return=false, $tag='h3')
{
    global $config;

    static $first_execution = true;
    $text_title = '';
    $text_message = '';
    $icon_image = '';
    $no_close_bool = false;
    $force_style = '';
    $force_class = '';
    $classes = [];
    $autoclose = ($class === 'suc');
    if (is_array($message) === true) {
        if (empty($message['title']) === false) {
            $text_title = $message['title'];
        }

        if (empty($message['message']) === false) {
            $text_message = $message['message'];
        }

        if (empty($message['icon']) === false) {
            $icon_image = $message['icon'];
        }

        if (empty($message['no_close']) === false) {
            // Workaround.
            $no_close_bool = false;
            // $no_close_bool = (bool) $message['no_close'];
        }

        if (empty($message['force_style']) === false) {
            $force_style = $message['force_style'];
        }

        if (empty($message['force_class']) === false) {
            $force_class = $message['force_class'];
        }

        if (isset($message['autoclose']) === true) {
            if ($message['autoclose'] === true) {
                $autoclose = true;
            } else {
                $autoclose = false;
            }
        }
    } else {
        $text_message = $message;
    }

    if (empty($text_title) === true) {
        switch ($class) {
            default:
            case 'info':
                $classes[] = 'info_box_information';
                $text_title = __('Information');
            break;
            case 'error':
                $text_title = __('Error');
            break;

            case 'suc':
                $text_title = __('Success');
            break;

            case 'warning':
                $text_title = __('Warning');
            break;
        }
    }

    if (empty($icon_image) === true) {
        switch ($class) {
            default:
            case 'info':
                $icon_image = 'images/information_big.png';
            break;
            case 'error':
                $icon_image = 'images/err.png';
            break;

            case 'suc':
                $icon_image = 'images/suc.png';
            break;

            case 'warning':
                $icon_image = 'images/warning_big.png';
            break;
        }

        $icon_image = $icon_image;
    }

    $id = 'info_box_'.uniqid();

    if (empty($force_class) === false) {
        $class = $class.' '.$force_class;
    }

    if ($no_close_bool === false) {
        // Use the no_meta parameter because this image is only in
        // the base console.
        $iconCloseButton = html_print_anchor(
            [
                'href'    => 'javascript: close_info_box(\''.$id.'\')',
                'content' => html_print_image(
                    'images/close@svg.svg',
                    true,
                    false,
                    false,
                    false,
                ),
            ],
            true
        );

        $closeButton = html_print_div(
            [
                'class'   => 'icon right pdd_r_3px',
                'content' => $iconCloseButton,
            ],
            true
        );
    } else {
        $closeButton = '';
    }

    $messageTable = new stdClass();
    $messageTable->cellpadding = 0;
    $messageTable->cellspacing = 0;
    $messageTable->id = 'table_'.$id;
    $messageTable->class = 'info_box '.$class.' textodialogo';
    $messageTable->styleTable = $force_style;

    $messageTable->rowclass = [];
    $messageTable->rowclass[0] = 'title font_16pt text_left';
    $messageTable->rowclass[1] = 'black font_10pt invert_filter';
    $messageTable->colspan[1][0] = 2;

    $messageTable->data = [];
    $messageTable->data[0][0] = '<b>'.$text_title.'</b>'.$closeButton;
    $messageTable->data[1][0] = '<span>'.$text_message.'</b>';

    // JavaScript help vars.
    $messageCreated = html_print_table($messageTable, true);
    $autocloseTime = ((int) $config['notification_autoclose_time'] * 1000);

    if (empty($message['div_class']) === false) {
        $classes[] = $message['div_class'];
    } else {
        $classes[] = 'info_box_container';
    }

    $classes[] = (($autoclose === true) && ($autocloseTime > 0)) ? ' info_box_autoclose' : '';

    // This session var is defined in index.
    if (isset($_SESSION['info_box_count']) === false) {
        $_SESSION['info_box_count'] = 1;
    } else {
        $_SESSION['info_box_count']++;
    }

    $position = (20 + ((int) $_SESSION['info_box_count'] * 100));

    $output = html_print_div(
        [
            'id'      => $id,
            'style'   => 'top: '.$position.'px;',
            'class'   => implode(' ', $classes),
            'content' => $messageCreated,
        ],
        true
    );

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints an error message.
 *
 * @param mixed   $message    The string error message or array
 *         ('title', 'message', 'icon', 'no_close') to be displayed.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *         than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_error_message($message, $attributes='', $return=false, $tag='h3')
{
    return ui_print_message($message, 'error', $attributes, $return, $tag);
}


/**
 * Prints an operation success message.
 *
 * @param mixed   $message    The string message or array
 *         ('title', 'message', 'icon', 'no_close') to be displayed.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *         than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_success_message($message, $attributes='', $return=false, $tag='h3')
{
    return ui_print_message($message, 'suc', $attributes, $return, $tag);
}


/**
 * Prints an operation info message.
 *
 * @param mixed   $message    The string message or array
 *         ('title', 'message', 'icon', 'no_close') to be displayed.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *         than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_info_message($message, $attributes='', $return=false, $tag='h3')
{
    return ui_print_message($message, 'info', $attributes, $return, $tag);
}


/**
 * Prints an operation info message - empty data.
 *
 * @param mixed   $message    The string message or array
 *         ('title', 'message', 'icon', 'no_close') to be displayed.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *         than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_empty_data($message, $attributes='', $return=false, $tag='h3')
{
    return ui_print_message($message, 'info', $attributes, $return, $tag);
}


/**
 * Evaluates a result using empty() and then prints an error or success message
 *
 * @param mixed   $result     The results to evaluate. 0, NULL, false, '' or
 *                            array() is bad, the rest is good.
 * @param mixed   $good       The string or array ('title', 'message') to be
 *                            displayed if the result was good.
 * @param mixed   $bad        The string or array ('title', 'message') to be
 *                            displayed if the result was bad.
 * @param string  $attributes Any other attributes to be set for the h3.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *                            than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_result_message($result, $good='', $bad='', $attributes='', $return=false, $tag='h3')
{
    if (empty($good) === true || $good === false) {
        $good = __('Request successfully processed');
    }

    if (empty($bad) === true || $bad === false) {
        $bad = __('Error processing request');
    }

    if (empty($result) === true) {
        return ui_print_error_message($bad, $attributes, $return, $tag);
    } else {
        return ui_print_success_message($good, $attributes, $return, $tag);
    }
}


/**
 * Prints an warning message.
 *
 * @param mixed   $message    The string message or array
 *         ('title', 'message', 'icon', 'no_close') to be displayed.
 * @param string  $attributes Any other attributes to be set for the tag.
 * @param boolean $return     Whether to output the string or return it.
 * @param string  $tag        What tag to use (you could specify something else
 *         than h3 like div or h2).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_warning_message($message, $attributes='', $return=false, $tag='h3')
{
    return ui_print_message($message, 'warning', $attributes, $return, $tag);
}


/**
 * Evaluates a unix timestamp and returns a span (or whatever tag specified)
 * with as title the correctly formatted full timestamp and a time comparation
 * in the tag
 *
 * @param integer $unixtime Any type of timestamp really, but we prefer unixtime.
 * @param boolean $return   Whether to output the string or return it.
 * @param array   $option   An array with different options for this function
 *        Key html_attr: which html attributes to add (defaults to none)
 *        Key tag: Which html tag to use (defaults to span)
 *        Key prominent: Overrides user preference and display "comparation" or "timestamp"
 *        key units: The type of units.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_timestamp($unixtime, $return=false, $option=[])
{
    global $config;

    // TODO: Add/use a javascript timer for the seconds so it automatically
    // updates as time passes by.
    if (isset($option['html_attr']) === true) {
        $attributes = $option['html_attr'];
    } else {
        $attributes = '';
    }

    if (isset($option['tag']) === true) {
        $tag = $option['tag'];
    } else {
        $tag = 'span';
    }

    if (empty($option['class']) === false) {
        $class = 'class="nowrap '.$option['class'].'"';
    } else {
        $class = 'class="nowrap"';
    }

    if (empty($option['style']) === false) {
        $style = 'style="'.$option['style'].'"';
    } else {
        $style = 'style=""';
    }

    $style .= ' '.$class;

    if (empty($option['prominent']) === false) {
        $prominent = $option['prominent'];
    } else {
        $prominent = $config['prominent_time'];
    }

    if (is_numeric($unixtime) === false) {
        $unixtime = time_w_fixed_tz($unixtime);
    }

    // Prominent_time is either timestamp or comparation.
    if ($unixtime <= 0) {
        $title = __('Unknown').'/'.__('Never');
        $data = __('Unknown');
    } else if ($prominent == 'timestamp') {
        pandora_setlocale();

        $title = human_time_comparation($unixtime);
        $date = new DateTime();
        $date->setTimestamp($unixtime);
        $data = $date->format($config['date_format']);
    } else if ($prominent == 'compact') {
        $units = 'tiny';
        $title = date($config['date_format'], $unixtime);
        $data = human_time_comparation($unixtime, $units);
    } else {
        $title = date($config['date_format'], $unixtime);
        $units = 'large';
        if (isset($option['units']) === true) {
            $units = $option['units'];
        }

        $data = human_time_comparation($unixtime, $units);
    }

    $output = '<'.$tag;
    switch ($tag) {
        default:
            // Usually tags have title attributes, so by default we add,
            // then fall through to add attributes and data.
            $output .= ' title="'.$title.'" '.$style.'>'.$data.'</'.$tag.'>';
        break;
        case 'h1':
        case 'h2':
        case 'h3':
            // Above tags don't have title attributes.
            $output .= ' '.$attributes.' '.$style.'>'.$data.'</'.$tag.'>';
        break;
    }

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints a username with real name, link to the user_edit page etc.
 *
 * @param string  $username The username to render.
 * @param boolean $fullname If true, returns the user fullname.
 * @param boolean $return   Whether to return or print.
 *
 * @return void|string HTML code if return parameter is true.
 */
function ui_print_username($username, $fullname=false, $return=false)
{
    return html_print_anchor(
        [
            'href'    => sprintf('index.php?sec=gusuarios&sec2=godmode/users/configure_user&edit_user=1&pure=0&id_user=%s', $username),
            'content' => ($fullname === true) ? get_user_fullname($username) : $username,
        ],
        $return
    );
}


/**
 * Show a notification.
 *
 * @param boolean $return Return or direct echo.
 *
 * @return string HTML.
 */
function ui_print_tags_warning($return=false)
{
    $msg = '<div id="notify_conf" class="notify">';
    $msg .= __('Is possible that this view uses part of information which your user has not access');
    $msg .= '</div>';

    if ($return) {
        return $msg;
    } else {
        echo $msg;
    }
}


/**
 * Print group icon within a link
 *
 * @param integer $id_group         Group id.
 * @param boolean $return           Whether to return or print.
 * @param string  $path             What path to use (relative to images/).
 *                                  Defaults to groups_small.
 * @param string  $style            Style for group image.
 * @param boolean $link             Whether the group have link or not.
 * @param boolean $force_show_image Force show image.
 * @param boolean $show_as_image    Show as image.
 * @param string  $class            Overrides the default class.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_group_icon($id_group, $return=false, $path='', $style='', $link=true, $force_show_image=false, $show_as_image=false, $class='', $tactical_view=false)
{
    global $config;

    $output = '';

    $icon = 'world@svg.svg';
    if ($id_group > 0) {
        $icon = db_get_value('icon', 'tgrupo', 'id_grupo', (int) $id_group);
        if (empty($icon) === true) {
            $icon = 'unknown@groups.svg';
        }
    }

    $extension = pathinfo($icon, PATHINFO_EXTENSION);
    if (empty($extension) === true) {
        $icon .= '.png';
    }

    // Don't show link in metaconsole.
    if (is_metaconsole() === true) {
        $link = false;
    }

    if ($link === true) {
        if ($tactical_view === true) {
            $output = '<a href="'.$config['homeurl'].'index.php?sec=gagent&sec2=godmode/groups/tactical&id_group='.$id_group.'">';
        } else {
            $output = '<a href="'.$config['homeurl'].'index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
        }
    }

    if ((bool) $config['show_group_name'] === true) {
        $output .= '<span title="'.groups_get_name($id_group, true).'">'.groups_get_name($id_group, true).'&nbsp;</span>';
    } else {
        if (empty($icon) === true) {
            $output .= '<span title="'.groups_get_name($id_group, true).'">';
            $output .= '</span>';
            $output .= html_print_image(
                'images/unknown@groups.svg',
                true,
                [
                    'style' => $style,
                    'class' => 'main_menu_icon invert_filter '.$class,
                    'alt'   => groups_get_name($id_group, true),
                    'title' => groups_get_name($id_group, true),
                ],
                false,
                false,
                false,
                true
            );
            $output .= '</span>';
        } else {
            if (empty($class) === true) {
                $class = 'bot';
                if ($icon === 'transmit') {
                    $class .= ' invert_filter';
                }
            }

            $icon = (str_contains($icon, '.svg') === true || str_contains($icon, '.png') === true) ? $icon : $icon.'.svg';
            $folder = '';
            if (str_contains($icon, '.png')) {
                $folder = 'groups_small/';
            }

            $output .= html_print_image(
                'images/'.$folder.$icon,
                true,
                [
                    'style' => $style,
                    'class' => 'main_menu_icon invert_filter '.$class,
                    'alt'   => groups_get_name($id_group, true),
                    'title' => groups_get_name($id_group, true),
                ],
                false,
                false,
                false,
                true
            );
        }
    }

    if ($link === true) {
        $output .= '</a>';
    }

    if ($return === false) {
        echo $output;
    } else {
        return $output;
    }
}


/**
 * Print group icon within a link. Other version.
 *
 * @param integer $id_group Group id.
 * @param boolean $return   Whether to return or print.
 * @param string  $path     What path to use (relative to images/).
 *                          Defaults to groups_small.
 * @param string  $style    Extra styles.
 * @param boolean $link     Add anchor.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_group_icon_path($id_group, $return=false, $path='images', $style='', $link=true)
{
    if ($id_group > 0) {
        $icon = (string) db_get_value('icon', 'tgrupo', 'id_grupo', (int) $id_group);
    } else {
        $icon = 'world';
    }

    if ($style == '') {
        $style = 'width: 16px; height: 16px;';
    }

    $output = '';
    if ($link) {
        $output = '<a href="index.php?sec=estado&amp;sec2=operation/agentes/estado_agente&amp;refr=60&amp;group_id='.$id_group.'">';
    }

    if (empty($icon)) {
        $output .= '<span title="'.groups_get_name($id_group, true).'">&nbsp;-&nbsp</span>';
    } else {
        $output .= '<img style="'.$style.'" class="bot" src="'.$path.'/'.$icon.'.png" alt="'.groups_get_name($id_group, true).'" title="'.groups_get_name($id_group, true).'" />';
    }

    if ($link) {
        $output .= '</a>';
    }

    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Get the icon of an operating system.
 *
 * @param integer $id_os      Operating system id.
 * @param boolean $name       Whether to also append the name of OS after icon.
 * @param boolean $return     Whether to return or echo the result.
 * @param boolean $apply_skin Whether to apply skin or not.
 * @param boolean $networkmap Networkmap.
 * @param boolean $only_src   Only_src.
 * @param boolean $relative   Relative.
 * @param boolean $options    Options.
 * @param boolean $big_icons  Big_icons.
 *
 * @return string HTML with icon of the OS
 */
function ui_print_os_icon(
    $id_os,
    $name=true,
    $return=false,
    $apply_skin=true,
    $networkmap=false,
    $only_src=false,
    $relative=false,
    $options=[],
    $big_icons=false
) {
    $subfolder = '.';
    if ($networkmap) {
        $subfolder = 'networkmap';
    }

    if ($big_icons) {
        $subfolder .= '/so_big_icons';
    }

    if (isset($options['class']) === false) {
        $options['class'] = 'main_menu_icon invert_filter';
    }

    $no_in_meta = (is_metaconsole() === false);

    $icon = (string) db_get_value('icon_name', 'tconfig_os', 'id_os', (int) $id_os);
    $extension = pathinfo($icon, PATHINFO_EXTENSION);
    if (empty($extension) === true) {
        $icon .= '.png';
    }

    if (empty($extension) === true || $extension === 'png'
        || $extension === 'jpg' || $extension === 'gif' && $subfolder === '.'
    ) {
        $subfolder = 'os_icons';
    }

    $os_name = get_os_name($id_os);
    if (empty($icon) === true) {
        if ($only_src) {
            $output = html_print_image(
                'images/'.$subfolder.'/unknown.png',
                true,
                $options,
                true,
                $relative,
                $no_in_meta,
                true
            );
        } else {
            return '-';
        }
    } else if ($apply_skin) {
        if ($only_src) {
            $output = html_print_image(
                'images/'.$subfolder.'/'.$icon,
                true,
                $options,
                true,
                $relative,
                $no_in_meta,
                true
            );
        } else {
            if (!isset($options['title'])) {
                $options['title'] = $os_name;
            }

            if ($icon === '.png') {
                $output = html_print_image(
                    'images/os@svg.svg',
                    true,
                    $options,
                    false,
                    $relative,
                    $no_in_meta,
                    true
                );
            } else {
                $output = html_print_image(
                    'images/'.$subfolder.'/'.$icon,
                    true,
                    $options,
                    false,
                    $relative,
                    $no_in_meta,
                    true
                );
            }
        }
    } else {
        // $output = "<img src='images/os_icons/" . $icon . "' alt='" . $os_name . "' title='" . $os_name . "'>";
        $output = 'images/'.$subfolder.'/'.$icon;
    }

    if ($name === true) {
        $output .= '&nbsp;&nbsp;'.$os_name;
    }

    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Print type agent icon.
 *
 * @param boolean $id_os          Id_os.
 * @param boolean $remote_contact Remote_contact.
 * @param boolean $contact        Contact.
 * @param boolean $return         Return.
 * @param integer $remote         Remote.
 * @param string  $version        Version.
 *
 * @return string HTML.
 */
function ui_print_type_agent_icon(
    $id_os=false,
    $remote_contact=false,
    $contact=false,
    $return=false,
    $remote=0,
    $version=''
) {
    global $config;

    if ((int) $id_os === SATELLITE_OS_ID) {
        // Satellite.
        $options['title'] = __('Satellite');
        $output = html_print_image(
            'images/satellite@os.svg',
            true,
            [
                'class' => 'main_menu_icon invert_filter',
                'style' => 'padding-right: 10px;',
            ],
            false,
            false,
            false,
            true
        );
    } else if ($remote_contact === $contact && $remote === 0 && empty($version) === true) {
        // Network.
        $options['title'] = __('Network');
        $output = html_print_image(
            'images/network-server@os.svg',
            true,
            [
                'class' => 'main_menu_icon invert_filter',
                'style' => 'padding-right: 10px;',
            ],
            false,
            false,
            false,
            true
        );
    } else {
        // Software.
        $options['title'] = __('Software');
        $output = html_print_image(
            'images/data-server@svg.svg',
            true,
            [
                'class' => 'main_menu_icon invert_filter',
                'style' => 'padding-right: 10px;',
            ],
            false,
            false,
            false,
            true
        );
    }

    return $output;
}


/**
 * Prints an agent name with the correct link
 *
 * @param integer $id_agent         Agent id.
 * @param boolean $return           Whether to return the string or echo it too.
 * @param integer $cutoff           Now uses styles to accomplish this.
 * @param string  $style            Style of name in css.
 * @param boolean $cutname          Cut names.
 * @param string  $server_url       Server url to concatenate at the begin of the link.
 * @param string  $extra_params     Extra parameters to concatenate in the link.
 * @param string  $known_agent_name Name of the agent to avoid the query in some cases.
 * @param boolean $link             If the agent will provided with link or not.
 * @param boolean $alias            Use the agent alias or the name.
 *
 * @return string HTML with agent name and link
 */
function ui_print_agent_name(
    $id_agent,
    $return=false,
    $cutoff='agent_medium',
    $style='',
    $cutname=false,
    $server_url='',
    $extra_params='',
    $known_agent_name=false,
    $link=true,
    $alias=true
) {
    if ($known_agent_name === false) {
        if ($alias) {
            $agent_name = (string) agents_get_alias($id_agent);
        } else {
            $agent_name = (string) agents_get_name($id_agent);
        }
    } else {
        $agent_name = $known_agent_name;
    }

    if ($alias) {
        $agent_name_full = (string) agents_get_name($id_agent);
    } else {
        $agent_name_full = $agent_name;
    }

    if ($cutname) {
        $agent_name = ui_print_truncate_text($agent_name, $cutoff, true, true, true, '[&hellip;]', $style);
    }

    if ($link) {
        $url = $server_url.'index.php?sec=estado&amp;sec2=operation/agentes/ver_agente&amp;id_agente='.$id_agent.$extra_params;

        $output = '<a style="'.$style.'" href="'.$url.'" title="'.$agent_name_full.'"><b><span style="'.$style.'">'.$agent_name.'</span></b></a>';
    } else {
        $output = '<b><span style="'.$style.'">'.$agent_name.'</span></b>';
    }

    // TODO: Add a pretty javascript (using jQuery) popup-box with agent details.
    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Formats a row from the alert table and returns an array usable in the table function
 *
 * @param array   $alert       A valid (non empty) row from the alert table.
 * @param boolean $agent       Whether or not this is a combined alert.
 * @param string  $url         Tab where the function was called from (used for urls).
 * @param mixed   $agent_style Style for agent name or default (false).
 *
 * @return array A formatted array with proper html for use in $table->data (6 columns)
 */
function ui_format_alert_row(
    $alert,
    $agent=true,
    $url='',
    $agent_style=false
) {
    global $config;

    if (!isset($alert['server_data'])) {
        $server_name = '';
        $server_id = '';
        $url_hash = '';
        $console_url = '';
    } else {
        $server_data = $alert['server_data'];
        $server_name = $server_data['server_name'];
        $server_id = $server_data['id'];
        $console_url = $server_data['server_url'].'/';
        $url_hash = metaconsole_get_servers_url_hash($server_data);
    }

    $actionText = '';
    include_once $config['homedir'].'/include/functions_alerts.php';
    $isFunctionPolicies = enterprise_include_once('include/functions_policies.php');
    $id_group = (int) get_parameter('ag_group', 0);
    // 0 is the All group (selects all groups).
    if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
        if ($agent) {
            $index = [
                'policy'          => 0,
                'standby'         => 1,
                'force_execution' => 2,
                'agent_name'      => 3,
                'module_name'     => 4,
                'description'     => 5,
                'template'        => 6,
                'action'          => 7,
                'last_fired'      => 8,
                'status'          => 9,
                'validate'        => 10,
                'actions'         => 11,
            ];
        } else {
            $index = [
                'policy'          => 0,
                'standby'         => 1,
                'force_execution' => 2,
                'agent_name'      => 3,
                'module_name'     => 4,
                'description'     => 5,
                'template'        => 6,
                'action'          => 7,
                'last_fired'      => 8,
                'status'          => 9,
                'validate'        => 10,
            ];
        }
    } else {
        if ($agent) {
            $index = [
                'standby'         => 0,
                'force_execution' => 1,
                'agent_name'      => 2,
                'module_name'     => 3,
                'description'     => 4,
                'template'        => 5,
                'action'          => 6,
                'last_fired'      => 7,
                'status'          => 8,
                'validate'        => 9,
            ];
        } else {
            $index = [
                'standby'         => 0,
                'force_execution' => 1,
                'agent_name'      => 2,
                'module_name'     => 3,
                'description'     => 4,
                'template'        => 5,
                'action'          => 6,
                'last_fired'      => 7,
                'status'          => 8,
                'validate'        => 9,
            ];
        }
    }

    if ($alert['disabled']) {
        $disabledHtmlStart = '<span class="italic_a">';
        $disabledHtmlEnd = '</span>';
        $styleDisabled = 'font-style: italic; color: #aaaaaa;';
    } else {
        $disabledHtmlStart = '';
        $disabledHtmlEnd = '';
        $styleDisabled = '';
    }

    if (empty($alert) === true) {
        if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
            return [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        } else {
            return [
                '',
                '',
                '',
                '',
                '',
                '',
                '',
            ];
        }
    }

    if (is_metaconsole() === true && (int) $server_id !== 0) {
        $server = db_get_row('tmetaconsole_setup', 'id', $alert['server_data']['id']);

        if (metaconsole_connect($server) == NOERR) {
            // Get agent data from node.
            $agente = db_get_row('tagente', 'id_agente', $alert['id_agent']);

            metaconsole_restore_db();
        }
    } else {
        // Get agent id.
        $id_agent = modules_get_agentmodule_agent($alert['id_agent_module']);
        $agente = db_get_row('tagente', 'id_agente', $id_agent);
    }

    $template = alerts_get_alert_template($alert['id_alert_template']);
    $description = io_safe_output($template['name']);

    $data = [];

    // Validate checkbox.
    if (is_metaconsole() === false) {
        if (check_acl($config['id_user'], $id_group, 'LW')
            || check_acl($config['id_user'], $id_group, 'LM')
        ) {
            $data[$index['validate']] = '';

            $data[$index['validate']] .= html_print_checkbox(
                'validate[]',
                $alert['id'],
                false,
                true,
                false,
                '',
                true
            );
        }
    }

    if ($isFunctionPolicies !== ENTERPRISE_NOT_HOOK) {
        if (is_metaconsole() === true && (int) $alert['server_data']['id'] !== 0) {
            $node = metaconsole_get_connection_by_id($alert['server_data']['id']);
            if (metaconsole_load_external_db($node) !== NOERR) {
                // Restore the default connection.
                metaconsole_restore_db();
                $errors++;
                return false;
            }
        }

        $policyInfo = policies_is_alert_in_policy2($alert['id'], false);
        $module_linked = policies_is_module_linked($alert['id_agent_module']);
        if ((is_array($policyInfo) === false && $module_linked === false)
            || (is_array($policyInfo) === false && $module_linked === '1')
        ) {
            $data[$index['policy']] = '';
        } else {
            $module_linked = policies_is_module_linked($alert['id_agent_module']);
            if ($module_linked === '0') {
                $img = 'images/unlinkpolicy.png';
            } else {
                $img = 'images/policy@svg.svg';
            }

            if (is_metaconsole() === false) {
                $data[$index['policy']] = '<a href="?sec=gmodules&amp;sec2=enterprise/godmode/policies/policies&amp;id='.$policyInfo['id'].'">'.html_print_image($img, true, ['title' => $policyInfo['name'], 'class' => 'invert_filter main_menu_icon']).'</a>';
            } else {
                $data[$index['policy']] = '<a href="?sec=gmodules&amp;sec2=advanced/policymanager&amp;id='.$policyInfo['id'].'">'.html_print_image($img, true, ['title' => $policyInfo['name'], 'class' => 'invert_filter main_menu_icon']).'</a>';
            }
        }

        if (is_metaconsole() === true) {
            metaconsole_restore_db();
        }
    }

    // Standby.
    $data[$index['standby']] = '';
    if (isset($alert['standby']) && $alert['standby'] == 1) {
        $data[$index['standby']] = html_print_image('images/bell_pause.png', true, ['title' => __('Standby on')]);
    }

    if (is_metaconsole() === false) {
        // Force alert execution.
        if ((bool) check_acl($config['id_user'], $id_group, 'AW') === true || (bool) check_acl($config['id_user'], $id_group, 'LM') === true) {
            if ((int) $alert['force_execution'] === 0) {
                $forceTitle = __('Force check');
                $additionUrl = '&amp;force_execution=1';
            } else {
                $forceTitle = __('Refresh');
                $additionUrl = '';
            }

            $forceExecButtons['force_check'] = html_print_anchor(
                [
                    'href'    => $url.'&amp;id_alert='.$alert['id'].'&amp;refr=60'.$additionUrl,
                    'content' => html_print_image(
                        'images/force@svg.svg',
                        true,
                        [
                            'title' => $forceTitle,
                            'class' => 'main_menu_icon invert_filter',
                        ]
                    ),
                ],
                true
            );
        }

        $forceExecButtons['template'] = html_print_anchor(
            [
                'href'    => 'ajax.php?page=godmode/alerts/alert_templates&get_template_tooltip=1&id_template='.$template['id'],
                'style'   => 'margin-left: 5px;',
                'class'   => 'template_details',
                'content' => html_print_image(
                    'images/details.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ),
            ],
            true
        );
    } else {
        $forceExecButtons['template'] = html_print_anchor(
            [
                'href'    => ui_get_full_url('/', false, false, false).'/ajax.php?page=enterprise/meta/include/ajax/tree_view.ajax&action=get_template_tooltip&id_template='.$template['id'].'&server_name='.$alert['server_data']['server_name'],
                'style'   => 'margin-left: 5px;',
                'class'   => 'template_details',
                'content' => html_print_image(
                    'images/details.svg',
                    true,
                    ['class' => 'main_menu_icon invert_filter']
                ),
            ],
            true
        );
    }

    if (isset($forceExecButtons['force_check'])) {
        $data[$index['force_execution']] = html_print_div(
            [
                'class'   => 'table_action_buttons flex',
                'content' => $forceExecButtons['force_check'],
            ],
            true
        );
    }

    if (isset($forceExecButtons['template'])) {
        $data[$index['template']] = $forceExecButtons['template'];
    }

    $data[$index['agent_name']] = $disabledHtmlStart;
    if ($agent == 0) {
        $data[$index['module_name']] .= ui_print_truncate_text(isset($alert['agent_module_name']) ? $alert['agent_module_name'] : modules_get_agentmodule_name($alert['id_agent_module']), 'module_small', false, true, true, '[&hellip;]', '');
    } else {
        if (is_metaconsole() === true) {
            $agent_name = $alert['agent_name'];
            $id_agent = $alert['id_agent'];
        } else {
            $agent_name = false;
            $id_agent = modules_get_agentmodule_agent($alert['id_agent_module']);
        }

        if (is_metaconsole() === true) {
            // Do not show link if user cannot access node
            if ((bool) can_user_access_node() === true) {
                $hashdata = metaconsole_get_server_hashdata($server);
                $url = $server['server_url'].'/index.php?sec=estado&sec2=operation/agentes/ver_agente&amp;loginhash=auto&loginhash_data='.$hashdata.'&loginhash_user='.str_rot13($config['id_user']).'&id_agente='.$agente['id_agente'];
                $data[$index['agent_name']] .= html_print_anchor(
                    [
                        'href'    => $url,
                        'content' => '<span class="bolder" title="'.$agente['nombre'].'">'.$agente['alias'].'</span>',
                        'target'  => '_blank',
                    ],
                    true
                );
            } else {
                $data[$index['agent_name']] .= '<span class="bolder" title="'.$agente['nombre'].'">'.$agente['alias'].'</span>';
            }
        } else {
            $data[$index['agent_name']] .= html_print_anchor(
                [
                    'href'    => 'index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente='.$id_agent,
                    'content' => '<span class="bolder" title="'.$agente['nombre'].'">'.$agente['alias'].'</span>',
                ],
                true
            );
        }

        $alert_module_name = isset($alert['agent_module_name']) ? $alert['agent_module_name'] : modules_get_agentmodule_name($alert['id_agent_module']);
        $data[$index['module_name']] = ui_print_truncate_text($alert_module_name, 'module_small', false, true, true, '[&hellip;]', '');
    }

    $data[$index['agent_name']] .= $disabledHtmlEnd;

    $data[$index['description']] = '';

    $actionDefault = db_get_value_sql(
        'SELECT id_alert_action
		FROM talert_templates WHERE id = '.$alert['id_alert_template']
    );

    $data[$index['description']] .= $disabledHtmlStart.ui_print_truncate_text(io_safe_output($description), 'description', false, true, true, '[&hellip;]', '').$disabledHtmlEnd;

    if (is_metaconsole()) {
        if (enterprise_include_once('include/functions_metaconsole.php') !== ENTERPRISE_NOT_HOOK) {
            $connection = metaconsole_get_connection($agente['server_name']);
            if (metaconsole_load_external_db($connection) !== NOERR) {
                echo json_encode(false);
                // Restore db connection.
                metaconsole_restore_db();
                return;
            }
        }
    }

    $actions = alerts_get_alert_agent_module_actions($alert['id'], false, -1, true);

    if (is_metaconsole()) {
        // Restore db connection.
        metaconsole_restore_db();
    }

    if (empty($actions) === false || $actionDefault != '') {
        $actionText = '<div><ul class="action_list">';
        foreach ($actions as $action) {
            $actionText .= '<div class="mrgn_btn_5px" ><span class="action_name"><li>'.$action['name'];
            if ($action['fires_min'] != $action['fires_max']) {
                $actionText .= ' ('.$action['fires_min'].' / '.$action['fires_max'].')';
            }

            $actionText .= ui_print_help_tip(__('The default actions will be executed every time that the alert is fired and no other action is executed'), true);
            // Is possible manage actions if have LW permissions in the agent group of the alert module.
            if (is_metaconsole() === true) {
                if (check_acl($config['id_user'], $id_group, 'LM')) {
                    $actionText .= '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&delete_action=1&id_alert='.$alert['id'].'&id_agent='.$agente['alias'].'&id_action='.$action['original_id'].'" onClick="if (!confirm(\' '.__('Are you sure you want to delete alert action?').'\')) return false;">'.html_print_image(
                        'images/delete.svg',
                        true,
                        [
                            'alt'   => __('Delete action'),
                            'title' => __('Delete action'),
                            'class' => 'main_menu_icon invert_filter vertical_baseline',
                        ]
                    ).'</a>';
                }

                if (check_acl($config['id_user'], $id_group, 'LW')) {
                    $actionText .= html_print_input_image(
                        'update_action',
                        '/images/edit.svg',
                        1,
                        'padding:0px;',
                        true,
                        [
                            'title'   => __('Update action'),
                            'class'   => 'main_menu_icon invert_filter',
                            'onclick' => 'show_display_update_action(\''.$action['original_id'].'\',\''.$alert['id'].'\',\''.$alert['id_agent_module'].'\',\''.$action['original_id'].'\',\''.$alert['agent_name'].'\')',
                        ]
                    );
                    $actionText .= html_print_input_hidden('id_agent_module', $alert['id_agent_module'], true);
                }
            }

            $actionText .= '<div id="update_action-div-'.$alert['id'].'" class="invisible">';
            $actionText .= '</div>';
            $actionText .= '</li></span></div>';
        }

        $actionText .= '</ul></div>';

        if ($actionDefault !== '' && $actionDefault !== false) {
            $actionDefault_name = db_get_sql(
                sprintf(
                    'SELECT name FROM talert_actions WHERE id = %d',
                    $actionDefault
                )
            );
            foreach ($actions as $action) {
                if ($actionDefault_name === $action['name']) {
                    $hide_actionDefault = true;
                } else {
                    $hide_actionDefault = false;
                }
            }

            if ($hide_actionDefault !== true) {
                $actionText .= $actionDefault_name.' <i>('.__('Default').')</i>';
            }
        }
    }

    $data[$index['action']] = $actionText;

    $data[$index['last_fired']] = $disabledHtmlStart.ui_print_timestamp($alert['last_fired'], true).$disabledHtmlEnd;

    $status = STATUS_ALERT_NOT_FIRED;
    $title = '';

    if ($alert['times_fired'] > 0) {
        $status = STATUS_ALERT_FIRED;
        $title = __('Alert fired').' '.$alert['internal_counter'].' '.__('time(s)');
    } else if ($alert['disabled'] > 0) {
        $status = STATUS_ALERT_DISABLED;
        $title = __('Alert disabled');
    } else {
        $status = STATUS_ALERT_NOT_FIRED;
        $title = __('Alert not fired');
    }

    $data[$index['status']] = ui_print_status_image($status, $title, true);
    $data[$index['status']] .= '<div id="update_action-div-'.$alert['id'].'" class="invisible">';

    if (is_metaconsole()) {
        if (enterprise_include_once('include/functions_metaconsole.php') !== ENTERPRISE_NOT_HOOK) {
            $connection = metaconsole_get_connection($agente['server_name']);
            if (metaconsole_load_external_db($connection) !== NOERR) {
                echo json_encode(false);
                // Restore db connection.
                metaconsole_restore_db();
                return;
            }
        }

        $action = db_get_all_rows_filter(
            'talert_template_module_actions',
            ['id_alert_template_module' => $alert['id']],
            'id'
        )[0];

        if (is_metaconsole()) {
            // Restore db connection.
            metaconsole_restore_db();
        }

        $tableActionButtons[] = '';

        // Edit.
        if (check_acl($config['id_user'], $id_group, 'LM')) {
            $tableActionButtons[] = html_print_input_hidden('id_agent_module', $alert['id_agent_module'], true);

            $tableActionButtons[] = '<a href="index.php?sec=galertas&sec2=godmode/alerts/alert_list&tab=list&delete_alert=1&id_alert='.$alert['id'].'&id_agent='.$alert['agent_name'].'" onClick="if (!confirm(\' '.__('Are you sure you want to delete alert?').'\')) return false;">'.html_print_image(
                'images/delete.svg',
                true,
                [
                    'alt'   => __('Delete'),
                    'title' => __('Delete'),
                    'class' => 'main_menu_icon invert_filter vertical_baseline',
                ]
            ).'</a>';

            $tableActionButtons[] = '<a href="javascript:show_add_action(\''.$alert['id'].'\');">'.html_print_image(
                'images/plus-black.svg',
                true,
                [
                    'title' => __('Add action'),
                    'class' => 'invert_filter main_menu_icon',
                    'style' => 'margin-bottom: 12px;',
                ]
            ).'</a>';
        }

        $data[$index['actions']] = html_print_div(
            [
                'style'   => 'padding-top: 8px;',
                'content' => implode('', $tableActionButtons),
            ],
            true
        );
    }

    // Is possible manage actions if have LW permissions in the agent group of the alert module.
    if (check_acl_one_of_groups($config['id_user'], $all_groups, 'LW') || check_acl($config['id_user'], $template_group, 'LM')) {
        if (check_acl($config['id_user'], $template_group, 'LW')) {
            $own_groups = users_get_groups($config['id_user'], 'LW', true);
        } else if (check_acl($config['id_user'], $template_group, 'LM')) {
            $own_groups = users_get_groups($config['id_user'], 'LM', true);
        }

        $filter_groups = '';
        $filter_groups = implode(',', array_keys($own_groups));
        if ($filter_groups != null) {
            $actions = alerts_get_alert_actions_filter(true, 'id_group IN ('.$filter_groups.')');
        }

        $data[$index['actions']] .= '<div id="add_action-div-'.$alert['id'].'" class="invisible">';
            $data[$index['actions']] .= '<form id="add_action_form-'.$alert['id'].'" method="post" style="height:85%;" action="index.php?sec=galertas&sec2=godmode/alerts/alert_list">';
                $data[$index['actions']] .= '<table class="w100p bg_color222 filter-table-adv">';
                    $data[$index['actions']] .= html_print_input_hidden('add_action', 1, true);
                    $data[$index['actions']] .= html_print_input_hidden('id_agent', $agente['alias'], true);
                    $data[$index['actions']] .= html_print_input_hidden('id_alert_module', $alert['id'], true);

        if (! $id_agente) {
            $data[$index['actions']] .= '<tr class="datos2">';
                $data[$index['actions']] .= '<td class="w50p">'.html_print_label_input_block(
                    __('Agent'),
                    ui_print_truncate_text($agente['alias'], 'agent_medium', false, true, true, '[&hellip;]')
                ).'</td>';
                $data[$index['actions']] .= '<td class="w50p">'.html_print_label_input_block(
                    __('Module'),
                    ui_print_truncate_text($alert_module_name, 'module_small', false, true, true, '[&hellip;]')
                ).'</td>';
            $data[$index['actions']] .= '</tr>';
        }

                    $data[$index['actions']] .= '<tr class="datos2">';
                        $data[$index['actions']] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Action'),
                            html_print_select(
                                $actions,
                                'action_select',
                                '',
                                '',
                                __('None'),
                                0,
                                true,
                                false,
                                true,
                                '',
                                false,
                                'width:100%'
                            )
                        ).'</td>';

                        $data[$index['actions']] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Number of alerts match from'),
                            '<div class="inline">'.html_print_input_text(
                                'fires_min',
                                0,
                                '',
                                4,
                                10,
                                true,
                                false,
                                false,
                                '',
                                'w40p'
                            ).' '.__('to').' '.html_print_input_text(
                                'fires_max',
                                0,
                                '',
                                4,
                                10,
                                true,
                                false,
                                false,
                                '',
                                'w40p'
                            ).'</div>'
                        ).'</td>';
                    $data[$index['actions']] .= '</tr>';
                    $data[$index['actions']] .= '<tr class="datos2">';
                        $data[$index['actions']] .= '<td class="w50p">'.html_print_label_input_block(
                            __('Threshold'),
                            html_print_extended_select_for_time(
                                'module_action_threshold',
                                0,
                                '',
                                '',
                                '',
                                false,
                                true,
                                false,
                                true,
                                '',
                                false,
                                false,
                                '',
                                false,
                                true
                            )
                        ).'</td>';
                    $data[$index['actions']] .= '</tr>';
                $data[$index['actions']] .= '</table>';
                $data[$index['actions']] .= html_print_submit_button(
                    __('Add'),
                    'addbutton',
                    false,
                    [
                        'icon'  => 'next',
                        'class' => 'mini float-right',
                    ],
                    true
                );
            $data[$index['actions']] .= '</form>';
        $data[$index['actions']] .= '</div>';
    }

    return $data;
}


/**
 * Prints a substracted string, length specified by cutoff, the full string will be in a rollover.
 *
 * @param string  $string   The string to be cut..
 * @param integer $cutoff   At how much characters to cut.
 * @param boolean $return   Whether to return or print it out.
 * @param integer $fontsize Size font (fixed) in px, applyed as CSS style (optional).
 *
 * @return string HTML string.
 */
function ui_print_string_substr($string, $cutoff=16, $return=false, $fontsize=0)
{
    if (empty($string)) {
        return '';
    }

    $string2 = io_safe_output($string);
    if (mb_strlen($string2, 'UTF-8') > $cutoff) {
        $string3 = '...';
    } else {
        $string3 = '';
    }

    $font_size_mod = '';

    if ($fontsize > 0) {
        $font_size_mod = "style='font-size: ".$fontsize."pt'";
    }

    $string = '<span '.$font_size_mod.' title="'.io_safe_input($string2).'">';
    $string .= mb_substr($string2, 0, $cutoff, 'UTF-8').$string3.'</span>';

    if ($return === false) {
        echo $string;
    }

    return $string;
}


/**
 * Gets a helper text explaining the requirement needs for an alert template
 * to get it fired.
 *
 * @param integer $id_alert_template Alert template id.
 * @param boolean $return            Wheter to return or print it out.
 * @param boolean $print_values      Wheter to put the values in the string or not.
 *
 * @return An HTML string if return was true.
 */
function ui_print_alert_template_example($id_alert_template, $return=false, $print_values=true, $print_icon=true)
{
    $output = '';

    if ($print_icon === true) {
        $output .= html_print_image('images/information.png', true, ['class' => 'invert_filter']);
    }

    $output .= '<span id="example">';
    $template = alerts_get_alert_template($id_alert_template);

    switch ($template['type']) {
        case 'equal':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the value is <span id="value"></span>');
        break;

        case 'not_equal':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the value is not <span id="value"></span>');
        break;

        case 'regex':
            if ($template['matches_value']) {
                // Do not translate the HTML attributes.
                $output .= __('The alert would fire when the value matches <span id="value"></span>');
            } else {
                // End if.
                $output .= __('The alert would fire when the value doesn\'t match <span id="value"></span>');
            }

            $value = $template['value'];
        break;

        case 'max_min':
            if ($template['matches_value']) {
                // Do not translate the HTML attributes.
                $output .= __('The alert would fire when the value is between <span id="min"></span> and <span id="max"></span>');
            } else {
                // End if.
                $output .= __('The alert would fire when the value is not between <span id="min"></span> and <span id="max"></span>');
            }
        break;

        case 'max':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the value is over <span id="max"></span>');
        break;

        case 'min':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the value is under <span id="min"></span>');
        break;

        case 'warning':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the module is in warning status');
        break;

        case 'critical':
            // Do not translate the HTML attributes.
            $output .= __('The alert would fire when the module is in critical status');
        break;

        default:
            // Do nothing.
            $output .= __('Unknown option.');
        break;
    }

    if ($print_values) {
        /*
         *   Replace span elements with real values. This is done in such way to avoid
         * duplicating strings and make it easily modificable via Javascript.
         */

        $output = str_replace('<span id="value"></span>', $template['value'], $output);
        $output = str_replace('<span id="max"></span>', $template['max_value'], $output);
        $output = str_replace('<span id="min"></span>', $template['min_value'], $output);
    }

    $output .= '</span>';
    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints a help tip icon.
 *
 * @param string  $help_id     Id of the help article.
 * @param boolean $return      Whether to return or output the result.
 * @param string  $home_url    Home url if its necessary.
 * @param string  $image       Image path.
 * @param boolean $is_relative Route is relative or not.
 * @param string  $id          Target id.
 * @param string  $isHeader    If true, the view is header.
 *
 * @return string The help tip
 */
function ui_print_help_icon(
    $help_id,
    $return=false,
    $home_url='',
    $image='images/info@svg.svg',
    $is_relative=false,
    $id='',
    $isHeader=false
) {
    global $config;

    if (empty($image) === true) {
        $image = 'images/info@svg.svg';
    }

    $iconClass = ($isHeader === true) ? 'header_help_icon' : 'main_menu_icon';

    // Do not display the help icon if help is disabled.
    if ((bool) $config['disable_help'] === true) {
        return '';
    }

    if (empty($home_url) === true) {
        $home_url = '';
    }

    if (is_metaconsole() === true) {
        $home_url = '../../'.$home_url;
    }

    $url = get_help_info($help_id);
    $b = base64_encode($url);

    $help_handler = 'index.php?sec=view&sec2=general/help_feedback';
    // Needs to use url encoded to avoid anchor lost.
    $help_handler .= '&b='.$b;
    $help_handler .= '&pure=1&url='.$url;
    $output = html_print_image(
        $image,
        true,
        [
            'class'   => 'img_help '.$iconClass,
            'title'   => __('Help'),
            'onclick' => "open_help ('".ui_get_full_url($help_handler)."')",
            'id'      => $id,
        ],
        false,
        $is_relative && is_metaconsole()
    );
    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Add a CSS file to the HTML head tag.
 *
 * To make a CSS file available just put it in include/styles. The
 * file name should be like "name.css". The "name" would be the value
 * needed to pass to this function.
 *
 * @param string $name Script name to add without the "jquery." prefix and the ".js"
 * suffix. Example:
 * <code>
 * ui_require_css_file ('pandora');
 * // Would include include/styles/pandora.js
 * </code>.
 * @param string $path Path where script is placed.
 *
 * @return boolean True if the file was added. False if the file doesn't exist.
 */
function ui_require_css_file($name, $path='include/styles/', $echo_tag=false, $return=false)
{
    global $config;

    $filename = $path.$name.'.css';

    if ($echo_tag === true) {
        $filename .= '?v='.$config['current_package'];
        $tag_name = '<link type="text/css" rel="stylesheet" href="'.ui_get_full_url($filename, false, false, false).'">';
        if ($return === false) {
            echo $tag_name;
            return null;
        } else {
            return $tag_name;
        }
    }

    if (! isset($config['css'])) {
        $config['css'] = [];
    }

    if (isset($config['css'][$name])) {
        return true;
    }

    if (! file_exists($filename)
        && ! file_exists($config['homedir'].'/'.$filename)
        && ! file_exists($config['homedir'].'/'.ENTERPRISE_DIR.'/'.$filename)
    ) {
        return false;
    }

    if (is_metaconsole()
        && (isset($config['requirements_use_base_url']) === false
        || $config['requirements_use_base_url'] === false)
    ) {
        $config['css'][$name] = '/../../'.$filename;
    } else {
        $config['css'][$name] = $filename;
    }

    return true;
}


/**
 * Add a javascript file to the HTML head tag.
 *
 * To make a javascript file available just put it in include/javascript. The
 * file name should be like "name.js". The "name" would be the value
 * needed to pass to this function.
 *
 * @param string  $name     Script name to add without the "jquery." prefix and the ".js"
 *      suffix. Example:
 *      <code>
 *      ui_require_javascript_file ('pandora');
 *      // Would include include/javascript/pandora.js
 *      </code>.
 * @param string  $path     Path where script is placed.
 * @param boolean $echo_tag Just echo the script tag of the file.
 *
 * @return boolean True if the file was added. False if the file doesn't exist.
 */
function ui_require_javascript_file($name, $path='include/javascript/', $echo_tag=false, $return=false)
{
    global $config;
    $filename = $path.$name.'.js';

    if ($echo_tag === true) {
        $filename .= '?v='.$config['current_package'];
        $tag_name = '<script type="text/javascript" src="'.ui_get_full_url($filename, false, false, false).'"></script>';
        if ($return === false) {
            echo $tag_name;
            return null;
        } else {
            return $tag_name;
        }
    }

    if (! isset($config['js'])) {
        $config['js'] = [];
    }

    if (isset($config['js'][$name])) {
        return true;
    }

    // We checks two paths because it may fails on enterprise.
    if (! file_exists($filename) && ! file_exists($config['homedir'].'/'.$filename)) {
        return false;
    }

    if (is_metaconsole()
        && (isset($config['requirements_use_base_url']) === false
        || $config['requirements_use_base_url'] === false)
    ) {
        $config['js'][$name] = '../../'.$filename;
    } else {
        $config['js'][$name] = $filename;
    }

    return true;
}


/**
 * Add a enteprise javascript file to the HTML head tag.
 *
 * * THIS FUNCTION COULD PRODUCE ISSUES WHILE INCLUDING JS FILES.
 * * USE ui_require_javascript_file('file', ENTERPRISE_DIR.'/location') INSTEAD.
 *
 * To make a javascript file available just put it in <ENTERPRISE_DIR>/include/javascript. The
 * file name should be like "name.js". The "name" would be the value
 * needed to pass to this function.
 *
 * @param string  $name                 Script name to add without the "jquery."
 *                                      prefix and the ".js"
 *                  suffix. Example:
 *                  <code>
 *                  ui_require_javascript_file ('pandora');
 *                  // Would include include/javascript/pandora.js
 *                  </code>.
 * @param boolean $disabled_metaconsole Disabled metaconsole.
 *
 * @return boolean True if the file was added. False if the file doesn't exist.
 */
function ui_require_javascript_file_enterprise($name, $disabled_metaconsole=false)
{
    global $config;

    $metaconsole_hack = '';
    if ($disabled_metaconsole) {
        $metaconsole_hack = '../../';
    }

    $filename = $metaconsole_hack.ENTERPRISE_DIR.'/include/javascript/'.$name.'.js';

    if (! isset($config['js'])) {
        $config['js'] = [];
    }

    if (isset($config['js'][$name])) {
        return true;
    }

    // We checks two paths because it may fails on enterprise.
    if (!file_exists($filename)
        && !file_exists($config['homedir'].'/'.$filename)
    ) {
        return false;
    }

    $config['js'][$name] = $filename;

    return true;
}


/**
 * Add a jQuery file to the HTML head tag.
 *
 * To make a jQuery script available just put it in include/javascript. The
 * file name should be like "jquery.name.js". The "name" would be the value
 * needed to pass to this function. Notice that this function does not manage
 * jQuery denpendencies.
 *
 * @param string  $name     Script name to add without the "jquery." prefix and the ".js"
 *      suffix. Example:
 *      <code>
 *      ui_require_jquery_file ('form');
 *      // Would include include/javascript/jquery.form.js
 *      </code>.
 * @param string  $path     Path where script is placed.
 * @param boolean $echo_tag Just echo the script tag of the file.
 *
 * @return boolean True if the file was added. False if the file doesn't exist.
 */
function ui_require_jquery_file($name, $path='include/javascript/', $echo_tag=false)
{
    global $config;

    $filename = $path.'jquery.'.$name.'.js';

    if ($echo_tag) {
        echo '<script type="text/javascript" src="'.ui_get_full_url(false, false, false, false).$filename.'"></script>';
        return null;
    }

    if (! isset($config['jquery'])) {
        $config['jquery'] = [];
    }

    if (isset($config['jquery'][$name])) {
        return true;
    }

    // We checks two paths because it may fails on enterprise.
    if (! file_exists($filename)
        && ! file_exists($config['homedir'].'/'.$filename)
    ) {
        return false;
    }

    $config['jquery'][$name] = $filename;

    return true;
}


/**
 * Callback function to add stuff to the head. This allows us to add scripts
 * to the header after the fact as well as extensive validation.
 *
 * DO NOT CALL print_f, echo, ob_start, ob_flush, ob_end functions here.
 *
 * To add css just put them in include/styles and then add them to the
 * $config['css'] array
 *
 * @param string $string   Callback will fill this with the current buffer.
 * @param mixed  $bitfield Callback will fill this with a bitfield (see ob_start).
 *
 * @return string String to return to the browser
 */
function ui_process_page_head($string, $bitfield)
{
    global $config;
    global $vc_public_view;

    if (isset($config['ignore_callback']) && $config['ignore_callback'] == true) {
        return '';
    }

    $output = '';

    $config_refr = -1;
    if (isset($config['refr'])) {
        $config_refr = $config['refr'];
    }

    // If user is logged or displayed view is the public view of visual console.
    if ($config_refr > 0
        && (isset($config['id_user']) || $vc_public_view == 1)
    ) {
        if ($config['enable_refr']
            || $_GET['sec2'] == 'operation/agentes/estado_agente'
            || $_GET['sec2'] == 'operation/agentes/tactical'
            || $_GET['sec2'] == 'operation/agentes/group_view'
            || $_GET['sec2'] == 'operation/events/events'
            || $_GET['sec2'] == 'operation/snmpconsole/snmp_view'
            || $_GET['sec2'] == 'operation/dashboard/dashboard'
        ) {
            $query = ui_get_url_refresh(false, false);

            /*
             * $output .= '<meta http-equiv="refresh" content="' .
             * $config_refr . '; URL=' . $query . '" />';
             */

            // End.
        }
    }

    $text_subtitle = isset($config['rb_product_name_alt']) ? '' : ' - '.__('the Flexible Monitoring System');

    $output .= "\n\t";
    $output .= '<title>'.get_product_name().$text_subtitle.'</title>
		<meta http-equiv="expires" content="never" />
		<meta http-equiv="content-type" content="text/html; charset=utf-8" />
		<meta http-equiv="Content-Style-Type" content="text/css" />
		<meta name="resource-type" content="document" />
		<meta name="distribution" content="global" />
		<meta name="author" content="'.get_copyright_notice().'" />
		<meta name="copyright" content="(c) '.get_copyright_notice().'" />
		<meta name="robots" content="index, follow" />';
        $output .= '<link rel="icon" href="'.ui_get_full_url('/').ui_get_favicon().'" type="image/ico" />';
        $output .= '<link rel="shortcut icon" href="'.ui_get_full_url('/').ui_get_favicon().'" type="image/x-icon" />
		<link rel="alternate" href="operation/events/events_rss.php" title="Pandora RSS Feed" type="application/rss+xml" />';

    if ($config['language'] != 'en') {
        // Load translated strings - load them last so they overload all
        // the objects.
        ui_require_javascript_file('time_'.$config['language']);
        ui_require_javascript_file('date'.$config['language']);
        ui_require_javascript_file('countdown_'.$config['language']);
    }

    $output .= "\n\t";

    /*
     * Load CSS
     */

    if (empty($config['css'])) {
        $config['css'] = [];
    }

    $login_ok = true;
    if (! isset($config['id_user']) && isset($_GET['login'])) {
        if (isset($_POST['nick']) && isset($_POST['pass'])) {
            $nick = get_parameter_post('nick');
            // This is the variable with the login.
            $pass = get_parameter_post('pass');
            // This is the variable with the password.
            $nick = db_escape_string_sql($nick);
            $pass = db_escape_string_sql($pass);

            // Process_user_login is a virtual function which should be defined
            // in each auth file.
            // It accepts username and password. The rest should be internal to
            // the auth file.
            // The auth file can set $config["auth_error"] to an informative
            // error output or reference their internal error messages to it
            // process_user_login should return false in case of errors or
            // invalid login, the nickname if correct.
            $nick_in_db = process_user_login($nick, $pass);

            if ($nick_in_db === false) {
                $login_ok = false;
            }
        }
    }

    // First, if user has assigned a skin then try to use css files of
    // skin subdirectory.
    $isFunctionSkins = enterprise_include_once('include/functions_skins.php');
    if (!$login_ok) {
        if ($isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
            enterprise_hook('skins_cleanup');
        }
    }

    $exists_css = false;
    if ($login_ok && $isFunctionSkins !== ENTERPRISE_NOT_HOOK) {
        // Checks if user's skin is available.
        $exists_skin = enterprise_hook('skins_is_path_set');
        if ($exists_skin) {
            $skin_path = enterprise_hook('skins_get_skin_path');
            $skin_styles = themes_get_css($skin_path.'include/styles/');
            $exists_css = !empty($skin_styles);
        }
    }

    // Add the jquery UI styles CSS.
    $config['css']['jquery-UI'] = 'include/styles/js/jquery-ui.min.css';
    $config['css']['jquery-UI-custom'] = 'include/styles/js/jquery-ui_custom.css';
    // Add the dialog styles CSS.
    $config['css']['dialog'] = 'include/styles/dialog.css';
    // Add the dialog styles CSS.
    $config['css']['dialog'] = 'include/styles/js/introjs.css';

    // If the theme is the default, we don't load it twice.
    if ($config['style'] !== 'pandora') {
        // It loads the last of all.
        $config['css']['theme'] = 'include/styles/'.$config['style'].'.css';
    }

    // If skin's css files exists then add them.
    if ($exists_css) {
        foreach ($skin_styles as $filename => $name) {
            $style = substr($filename, 0, (strlen($filename) - 4));
            $config['css'][$style] = $skin_path.'include/styles/'.$filename;
        }
    } else {
        // Otherwise assign default and user's css.
        // User style should go last so it can rewrite common styles.
        $config['css'] = array_merge(
            [
                'common'  => 'include/styles/common.css',
                'menu'    => 'include/styles/menu.css',
                'tables'  => 'include/styles/tables.css',
                'general' => 'include/styles/pandora.css',
            ],
            $config['css']
        );
    }

    // We can't load empty and we loaded (conditionally) ie.
    $loaded = [
        '',
        'ie',
    ];

    foreach ($config['css'] as $name => $filename) {
        if (in_array($name, $loaded)) {
            continue;
        }

        array_push($loaded, $name);

        $url_css = ui_get_full_url($filename, false, false, false);
        $output .= '<link rel="stylesheet" href="'.$url_css.'?v='.$config['current_package'].'" type="text/css" />'."\n\t";
    }

    /*
     * End load CSS
     */

    /*
     * Load jQuery
     */

    if (empty($config['jquery'])) {
        $config['jquery'] = [];
        // If it's empty, false or not init set array to empty just in case.
    }

    // Pandora specific jquery should go first.
    $black_list_pages_old_jquery = ['operation/gis_maps/index'];
    if (in_array(get_parameter('sec2'), $black_list_pages_old_jquery)) {
        $config['jquery'] = array_merge(
            [
                'jquery'  => 'include/javascript/jquery.js',
                'ui'      => 'include/javascript/jquery.ui.core.js',
                'dialog'  => 'include/javascript/jquery.ui.dialog.js',
                'pandora' => 'include/javascript/jquery.pandora.js',
            ],
            $config['jquery']
        );
    } else {
        $config['jquery'] = array_merge(
            [
                'jquery'    => 'include/javascript/jquery.current.js',
                'pandora'   => 'include/javascript/jquery.pandora.js',
                'jquery-ui' => 'include/javascript/jquery-ui.min.js',
            ],
            $config['jquery']
        );
    }

    // Include the datapicker language if exists.
    if (file_exists('include/languages/datepicker/jquery.ui.datepicker-'.$config['language'].'.js')) {
        $config['jquery']['datepicker_language'] = 'include/languages/datepicker/jquery.ui.datepicker-'.$config['language'].'.js';
    }

    // Include countdown library.
    $config['jquery']['countdown'] = 'include/javascript/jquery.countdown.js';

    // Then add each script as necessary.
    $loaded = [''];
    foreach ($config['jquery'] as $name => $filename) {
        if (in_array($name, $loaded)) {
            continue;
        }

        array_push($loaded, $name);

        $url_js = ui_get_full_url($filename, false, false, false);
        $output .= '<script type="text/javascript" src="'.$url_js.'?v='.$config['current_package'].'"></script>'."\n\t";
    }

    /*
     * End load JQuery
     */

    /*
     * Load JS
     */

    if (empty($config['js'])) {
        $config['js'] = [];
        // If it's empty, false or not init set array to empty just in case.
    }

    // Pandora specific JavaScript should go first.
    $config['js'] = array_merge(
        [
            'pandora'    => 'include/javascript/pandora.js',
            'pandora_ui' => 'include/javascript/pandora_ui.js',
        ],
        $config['js']
    );
    // Load base64 javascript library.
    $config['js']['base64'] = 'include/javascript/encode_decode_base64.js';
    // Load qrcode library.
    $config['js']['qrcode'] = 'include/javascript/qrcode.js';
    // Load intro.js library (for bubbles and clippy).
    $config['js']['intro'] = 'include/javascript/intro.js';
    $config['js']['clippy'] = 'include/javascript/clippy.js';
    // Load Underscore.js library.
    $config['js']['underscore'] = 'include/javascript/underscore-min.js';

    // Load other javascript.
    // We can't load empty.
    $loaded = [''];
    foreach ($config['js'] as $name => $filename) {
        if (in_array($name, $loaded)) {
            continue;
        }

        array_push($loaded, $name);

        $url_js = ui_get_full_url($filename, false, false, false);
        $output .= '<script type="text/javascript" src="'.$url_js.'?v='.$config['current_package'].'"></script>'."\n\t";
    }

    /*
     * End load JS
     */

    include_once __DIR__.'/graphs/functions_flot.php';
    $output .= include_javascript_dependencies_flot_graph(true);

    $output .= '<!--[if gte IE 6]>
		<link rel="stylesheet" href="include/styles/ie.css" type="text/css"/>
		<![endif]-->';

    $output .= $string;

    return $output;
}


/**
 * Callback function to add stuff to the body
 *
 * @param string $string   Callback will fill this with the current buffer.
 * @param mixed  $bitfield Callback will fill this with a bitfield (see ob_start).
 *
 * @return string String to return to the browser
 */
function ui_process_page_body($string, $bitfield)
{
    global $config;

    if (isset($config['ignore_callback'])
        && $config['ignore_callback'] == true
    ) {
        return null;
    }

    // Show custom background.
    $output = '<body'.(($config['pure']) ? ' class="pure"' : '').'>';

    $output .= $string;

    $output .= '</body>';

    return $output;
}


/**
 * Prints a pagination menu to browse into a collection of data.
 *
 * @param integer $count             Number of elements in the collection.
 * @param string  $url               URL of the pagination links. It must include all form
 *                values as GET form.
 * @param integer $offset            Current offset for the pagination. Default value would be
 *                taken from $_REQUEST['offset'].
 * @param integer $pagination        Current pagination size. If a user requests a larger
 *            pagination than config["block_size"].
 * @param boolean $return            Whether to return or print this.
 * @param string  $offset_name       The name of parameter for the offset.
 * @param boolean $print_total_items Show the text with the total items. By default true.
 * @param mixed   $other_class       Other_class.
 * @param mixed   $script            Script.
 * @param mixed   $parameter_script  Parameter_script.
 * @param string  $set_id            Set id of div.
 *
 * @return string The pagination div or nothing if no pagination needs to be done
 */
function ui_pagination(
    $count,
    $url=false,
    $offset=0,
    $pagination=0,
    $return=false,
    $offset_name='offset',
    $print_total_items=true,
    $other_class='',
    $script='',
    $parameter_script=[
        'count'  => '',
        'offset' => 'offset_param',
    ],
    $set_id=''
) {
    global $config;
    ui_require_css_file('tables');

    if (empty($pagination) === true) {
        $pagination = (int) $config['block_size'];
    }

    if (is_string($offset) === true) {
        $offset_name = $offset;
        $offset = (int) get_parameter($offset_name);
    }

    if (empty($offset) === true) {
        $offset = (int) get_parameter($offset_name);
    }

    if (empty($url) === true) {
        $url = ui_get_url_refresh([$offset_name => false]);
    }

    if (empty($set_id) === false) {
        $set_id = " id = '".$set_id."'";
    }

    // Pagination links for users include delete, create and other params,
    // now not use these params, and not retry the previous action when go to
    // pagination link.
    $remove = [
        'user_del',
        'disable_user',
        'delete_user',
    ];

    // Check if url has &#x20; blankspace and replace it.
    preg_replace('/\&#x20;/', '%20', $url);

    $url = explode('&', $url);

    $finalUrl = [];
    foreach ($url as $key => $value) {
        if (strpos($value, $remove[0]) === false
            && strpos($value, $remove[1]) === false
            && strpos($value, $remove[2]) === false
        ) {
            array_push($finalUrl, $value);
        }
    }

    $url = implode('&', $finalUrl);

    /*
        URL passed render links with some parameter
        &offset - Offset records passed to next page
        &counter - Number of items to be blocked
        Pagination needs $url to build the base URL to render links, its a base url, like
        " http://pandora/index.php?sec=godmode&sec2=godmode/admin_access_logs "
    */

    $block_limit = PAGINATION_BLOCKS_LIMIT;
    // Visualize only $block_limit blocks.
    if ($count <= $pagination) {
        if ($print_total_items) {
            $output = "<div class='".$other_class."' ".$set_id.'>';
            // Show the count of items.
            $output .= '<div class="total_pages">'.sprintf(__('Total items: %s'), $count).'</div>';
            // End div and layout.
            $output .= '</div>';

            if ($return === false) {
                echo $output;
            }

            return $output;
        }

        return false;
    }

    $number_of_pages = ceil($count / $pagination);
    $actual_page = floor($offset / $pagination);
    $ini_page = (floor($actual_page / $block_limit) * $block_limit);
    $end_page = ($ini_page + $block_limit - 1);
    if ($end_page >= $number_of_pages) {
        $end_page = ($number_of_pages - 1);
    }

    $output = "<div class='".$other_class."' ".$set_id.'>';

    // Show the count of items.
    if ($print_total_items) {
        $output .= '<div class="total_pages">'.sprintf(__('Total items: %s'), $count).'</div>';
    }

    $output .= "<div class='total_number'>";

    // Show GOTO FIRST PAGE button.
    if ($number_of_pages > $block_limit) {
        if (empty($script) === false) {
            $script_modified = $script;
            $script_modified = str_replace(
                $parameter_script['count'],
                $count,
                $script_modified
            );
            $script_modified = str_replace(
                $parameter_script['offset'],
                0,
                $script_modified
            );

            $firstHref = 'javascript: '.$script_modified.';';
        } else {
            $firstHref = io_safe_output($url).'&amp;'.$offset_name.'=0';
        }

        $output .= html_print_anchor(
            [
                'href'    => $firstHref,
                'class'   => 'pandora_pagination '.$other_class.' offset_0 previous',
                'content' => __('First'),
            ],
            true
        );
    }

    /*
     * Show PREVIOUS PAGE GROUP OF PAGES
     * For example
     * You are in the 12 page with a block of 5 pages
     * << < 10 - 11 - [12] - 13 - 14 > >>
     * Click in <
     * Result << < 5 - 6 - 7 - 8 - [9] > >>
     */

    if ($ini_page >= $block_limit) {
        $offset_previous_page = (($ini_page - 1) * $pagination);

        if (!empty($script)) {
            $script_modified = $script;
            $script_modified = str_replace(
                $parameter_script['count'],
                $count,
                $script_modified
            );
            $script_modified = str_replace(
                $parameter_script['offset'],
                $offset_previous_page,
                $script_modified
            );

            $previousHref = 'javascript: '.$script_modified.';';
        } else {
            $previousHref = io_safe_output($url).'&amp;'.$offset_name.'='.$offset_previous_page;
        }

        $output .= html_print_anchor(
            [
                'href'    => $previousHref,
                'class'   => 'pandora_pagination '.$other_class.' offset_'.$offset_previous_page,
                'content' => __('Previous'),
            ],
            true
        );
    }

    // Show pages.
    for ($iterator = $ini_page; $iterator <= $end_page; $iterator++) {
        $actual_page = (int) ($offset / $pagination);

        $activePageClass = ((int) $iterator === (int) $actual_page) ? 'current' : '';

        $offset_page = ($iterator * $pagination);

        if (empty($script) === false) {
            $script_modified = $script;
            $script_modified = str_replace(
                $parameter_script['count'],
                $count,
                $script_modified
            );
            $script_modified = str_replace(
                $parameter_script['offset'],
                $offset_page,
                $script_modified
            );

            $anchorHref = 'javascript: '.$script_modified.';';
        } else {
            $anchorHref = $url.'&amp;'.$offset_name.'='.$offset_page;
        }

        $output .= html_print_anchor(
            [
                'href'    => $anchorHref,
                'class'   => sprintf(
                    'pandora_pagination %s offset_%s %s',
                    $other_class,
                    $offset_page,
                    $activePageClass
                ),
                'content' => $iterator,
            ],
            true
        );
    }

    /*
     * Show NEXT PAGE GROUP OF PAGES
     * For example
     * You are in the 12 page with a block of 5 pages
     * << < 10 - 11 - [12] - 13 - 14 > >>
     * Click in >
     * Result << < [15] - 16 - 17 - 18 - 19 > >>
     */

    if (($number_of_pages - $ini_page) > $block_limit) {
        $offset_next_page = (($end_page + 1) * $pagination);

        if (!empty($script)) {
            $script_modified = $script;
            $script_modified = str_replace(
                $parameter_script['count'],
                $count,
                $script_modified
            );
            $script_modified = str_replace(
                $parameter_script['offset'],
                $offset_next_page,
                $script_modified
            );

            $nextHref = 'javascript: '.$script_modified.';';
        } else {
            $nextHref = $url.'&amp;'.$offset_name.'='.$offset_next_page;
        }

        $output .= html_print_anchor(
            [
                'href'    => $nextHref,
                'class'   => 'pandora_pagination '.$other_class.' offset_'.$offset_next_page,
                'content' => __('Next'),
            ],
            true
        );
    }

    // Show GOTO LAST PAGE button.
    if ($number_of_pages > $block_limit) {
        $offset_lastpage = (($number_of_pages - 1) * $pagination);

        if (!empty($script)) {
            $script_modified = $script;
            $script_modified = str_replace(
                $parameter_script['count'],
                $count,
                $script_modified
            );
            $script_modified = str_replace(
                $parameter_script['offset'],
                $offset_lastpage,
                $script_modified
            );

            $lastHref = 'javascript: '.$script_modified.';';
        } else {
            $lastHref = $url.'&amp;'.$offset_name.'='.$offset_lastpage;
        }

        $output .= html_print_anchor(
            [
                'href'    => $lastHref,
                'class'   => 'pandora_pagination '.$other_class.' offset_'.$offset_lastpage.' next',
                'content' => __('Last'),
            ],
            true
        );
    }

    $output .= '</div>';
    // End div and layout
    // total_number.
    $output .= '</div>';

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 *
 * @param string  $action Complete text to show in the tip.
 * @param boolean $return Whether to return an output string or echo now.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_session_action_icon($action, $return=false)
{
    global $config;

    $key_icon = [
        'acl'             => 'images/delete.svg',
        'agent'           => 'images/agents@svg.svg',
        'module'          => 'images/modules@svg.svg',
        'alert'           => 'images/alerts.svg',
        'incident'        => 'images/logs@svg.svg',
        'logon'           => 'images/house@svg.svg',
        'logoff'          => 'images/house@svg.svg',
        'massive'         => 'images/configuration@svg.svg',
        'hack'            => 'images/custom-input@svg.svg',
        'event'           => 'images/event.svg',
        'policy'          => 'images/policy@svg.svg',
        'report'          => 'images/agent-fields.svg',
        'file collection' => 'images/file-collection@svg.svg',
        'user'            => 'images/user.svg',
        'password'        => 'images/password.svg',
        'session'         => 'images/star@svg.svg',
        'snmp'            => 'images/SNMP-network-numeric-data@svg.svg',
        'command'         => 'images/external-tools@svg.svg',
        'category'        => 'images/tag@svg.svg',
        'dashboard'       => 'images/workstation@groups.svg',
        'api'             => 'images/enable.svg',
        'db'              => 'images/data-server@svg.svg',
        'setup'           => 'images/configuration@svg.svg',
    ];

    $output = '';
    foreach ($key_icon as $key => $icon) {
        if (stristr($action, $key) !== false) {
            $output = html_print_image($icon, true, ['title' => $action, 'class' => 'main_menu_icon invert_filter'], false, false, false, true).' ';
            break;
        }
    }

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Prints only a tip button which shows a text when the user puts the mouse over it.
 *
 * @param string  $text        Complete text to show in the tip.
 * @param boolean $return      Whether to return an output string or echo now.
 * @param string  $img         Displayed image.
 * @param boolean $is_relative Print image in relative way.
 * @param string  $style       Specific style.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_help_tip(
    $text,
    $return=false,
    $img='images/info@svg.svg',
    $is_relative=false,
    $style='',
    $blink=false
) {
    if (empty($img) === true) {
        $img = 'images/info@svg.svg';
    }

    $text_title = (strlen($text) >= 60) ? substr($text, 0, 60).'...' : $text;

    $id = random_int(1, 99999);
    $output = '<div id="div_tip_'.$id.'" class="tip" style="'.$style.'" >';
    $output .= '<div id="tip_dialog_'.$id.'" class="invisible margin-15" data-title="'.__('Help').'"><span class="font_13px">'.$text.'</span></div>';
    $output .= html_print_image(
        $img,
        true,
        [
            'title' => $text_title,
            'class' => $blink === true ? 'blink' : '',
            'style' => 'width: 16px; height: 16px;',
        ],
        false,
        $is_relative && is_metaconsole()
    ).'</div>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Generate a placeholder for inputs.
 *
 * @param string  $text    Text for show.
 * @param boolean $return  Text for show.
 * @param array   $options Text for show.
 *
 * @return string|void Formed element.
 */
function ui_print_input_placeholder(
    string $text,
    bool $return=false,
    array $options=[]
) {
    $wrapper = (isset($options['wrapper']) === true) ? $options['wrapper'] : 'span';
    $attibutes = [];
    $attibutes[] = 'class="'.((isset($options['class']) === true) ? $options['class'] : 'input_sub_placeholder').'"';
    $attibutes[] = (isset($options['rawattributes']) === true) ? $options['rawattributes'] : '';
    $attibutes[] = (isset($options['style']) === true) ? 'style="'.$options['style'].'"' : '';
    $attibutes[] = (isset($options['id']) === true) ? 'id="'.$options['id'].'"' : '';
    $attibutes[] = (isset($options['title']) === true) ? 'title="'.$options['title'].'"' : '';

    $output = sprintf(
        '<%s %s>%s</%s>',
        $wrapper,
        implode(' ', $attibutes),
        $text,
        $wrapper
    );

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Prints link to show something.
 *
 * @param string  $text        Complete text to show in the tip.
 * @param boolean $return      Whether to return an output string or echo now.
 * @param string  $img         Displayed image.
 * @param boolean $is_relative Print image in relative way.
 *
 * @return string HTML.
 */
function ui_print_help_tip_border(
    $text,
    $return=false,
    $img='images/tip_border.png',
    $is_relative=false
) {
    $output = '<a href="javascript:" class="tip" >'.html_print_image(
        $img,
        true,
        ['title' => $text],
        false,
        $is_relative && is_metaconsole()
    ).'</a>';

    if ($return) {
        return $output;
    }

    echo $output;
}


/**
 * Powerful debug function that also shows a backtrace.
 *
 * This functions need to have active $config['debug'] variable to work.
 *
 * @param mixed   $var       Variable name to debug.
 * @param boolean $backtrace Wheter to print the backtrace or not.
 *
 * @return boolean Tru if the debug was actived. False if not.
 */
function ui_debug($var, $backtrace=true)
{
    global $config;
    if (! isset($config['debug'])) {
        return false;
    }

    static $id = 0;
    static $trace_id = 0;

    $id++;

    if ($backtrace) {
        echo '<div class="debug">';
        echo '<a href="#" onclick="$(\'#trace-'.$id.'\').toggle ();return false;">Backtrace</a>';
        echo '<div id="trace-'.$id.'" class="backtrace invisible">';
        echo '<ol>';
        $traces = debug_backtrace();
        // Ignore debug function.
        unset($traces[0]);
        foreach ($traces as $trace) {
            $trace_id++;

            /*
             *   Many classes are used to allow better customization.
             * Please, do not remove them
             */

            echo '<li>';
            if (isset($trace['class'])) {
                echo '<span class="class">'.$trace['class'].'</span>';
            }

            if (isset($trace['type'])) {
                echo '<span class="type">'.$trace['type'].'</span>';
            }

            echo '<span class="function">';
            echo '<a href="#" onclick="$(\'#args-'.$trace_id.'\').toggle ();return false;">'.$trace['function'].'()</a>';
            echo '</span>';
            if (isset($trace['file'])) {
                echo ' - <span class="filename">';
                echo str_replace($config['homedir'].'/', '', $trace['file']);
                echo ':'.$trace['line'].'</span>';
            } else {
                echo ' - <span class="filename"><em>Unknown file</em></span>';
            }

            echo '<pre id="args-'.$trace_id.'" class="invisible">';
            echo '<div class="parameters">Parameter values:</div>';
            echo '<ol>';
            foreach ($trace['args'] as $arg) {
                echo '<li>';
                print_r($arg);
                echo '</li>';
            }

            echo '</ol>';
            echo '</pre>';
            echo '</li>';
        }

        echo '</ol>';
        echo '</div></div>';
    }

    // Actually print the variable given.
    echo '<pre class="debug">';
    print_r($var);
    echo '</pre>';

    return true;
}


/**
 * Prints icon of a module type
 *
 * @param integer $id_moduletype Module Type ID.
 * @param boolean $return        Whether to return or print.
 * @param boolean $relative      Whether to use relative path to image or not (i.e. $relative= true : /pandora/<img_src>).
 * @param boolean $options       Whether to use image options like style, border or title on the icon.
 * @param boolean $src           Src.
 *
 * @return string An HTML string with the icon. Printed if return is false
 */
function ui_print_moduletype_icon(
    $id_moduletype,
    $return=false,
    $relative=false,
    $options=true,
    $src=false
) {
    global $config;

    $type = db_get_row(
        'ttipo_modulo',
        'id_tipo',
        (int) $id_moduletype,
        [
            'descripcion',
            'icon',
        ]
    );
    if ($type === false) {
        $type = [];
        $type['descripcion'] = __('Unknown type');
        $type['icon'] = 'b_down.png';
    }

    $imagepath = 'images/'.$type['icon'];
    if (! file_exists($config['homedir'].'/'.$imagepath)) {
        $imagepath = ENTERPRISE_DIR.'/'.$imagepath;
    }

    if ($src) {
        return $imagepath;
    }

    if ($options) {
        return html_print_image(
            $imagepath,
            $return,
            [
                'border' => 0,
                'title'  => $type['descripcion'],
                'class'  => 'invert_filter main_menu_icon',
            ],
            false,
            $relative
        );
    } else {
        return html_print_image(
            $imagepath,
            $return,
            false,
            false,
            $relative
        );
    }
}


/**
 * Print module max/min values for warning/critical state
 *
 * @param float  $max_warning  Max value for warning state.
 * @param float  $min_warning  Min value for warning state.
 * @param string $str_warning  String warning state.
 * @param float  $max_critical Max value for critical state.
 * @param float  $min_critical Min value for critical state.
 * @param string $str_critical String for critical state.
 * @param string $attributes   String with raw format attributes for span.
 *
 * @return string HTML string
 */
function ui_print_module_warn_value(
    $max_warning,
    $min_warning,
    $str_warning,
    $max_critical,
    $min_critical,
    $str_critical,
    $warning_inverse=0,
    $critical_inverse=0,
    $attributes=''
) {
    $war_inv = '';
    $crit_inv = '';

    if ($warning_inverse == 1) {
        $war_inv = ' (inv)';
    }

    if ($critical_inverse == 1) {
        $crit_inv = ' (inv)';
    }

    $data = '<span '.$attributes.' title="'.__('Warning').': '.__('Max').$max_warning.'/'.__('Min').$min_warning.$war_inv.' - '.__('Critical').': '.__('Max').$max_critical.'/'.__('Min').$min_critical.$crit_inv.'">';

    if ($max_warning != $min_warning) {
        $data .= format_for_graph($max_warning).'/'.format_for_graph($min_warning);
    } else {
        $data .= __('N/A');
    }

    $data .= ' - ';

    if ($max_critical != $min_critical) {
        $data .= format_for_graph($max_critical).'/'.format_for_graph($min_critical);
    } else {
        $data .= __('N/A');
    }

    $data .= '</span>';
    return $data;
}


/**
 * Format a file size from bytes to a human readable meassure.
 *
 * @param integer $bytes File size in bytes.
 *
 * @return string Bytes converted to a human readable meassure.
 */
function ui_format_filesize($bytes)
{
    $bytes = (int) $bytes;
    $strs = [
        'B',
        'kB',
        'MB',
        'GB',
        'TB',
    ];
    if ($bytes <= 0) {
        return '0 '.$strs[0];
    }

    $con = 1024;
    $log = (int) (log($bytes, $con));

    return format_numeric(($bytes / pow($con, $log)), 1).' '.$strs[$log];
}


/**
 * Returns the current path to the selected image set to show the
 * status of agents and alerts.
 *
 * @return array An array with the image path, image width and image height.
 */
function ui_get_status_images_path()
{
    global $config;

    $imageset = $config['status_images_set'];

    if (strpos($imageset, ',') === false) {
        $imageset .= ',40x18';
    }

    $array_split = preg_split('/\,/', $imageset);
    $imageset = $array_split[0];
    $sizes    = $array_split[1];

    if (strpos($sizes, 'x') === false) {
        $sizes .= 'x18';
    }

    $array_split_size = preg_split('/x/', $sizes);
    $imagewidth  = $array_split_size[0];
    $imageheight = $array_split_size[1];

    $imagespath = 'images/status_sets/'.$imageset;

    return [$imagespath];
}


/**
 * Prints an image representing a status.
 *
 * @param string  $type           Type.
 * @param string  $title          Title.
 * @param boolean $return         Whether to return an output string or echo now (optional, echo by default).
 * @param array   $options        Options to set image attributes: I.E.: style.
 * @param string  $path           Path of the image, if not provided use the status path.
 * @param boolean $image_with_css Don't use an image. Draw an image with css styles.
 * @param string  $extra_text     Text that is displayed after title (i.e. time elapsed since last status change of module).
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_status_image(
    $type,
    $title='',
    $return=false,
    $options=false,
    $path=false,
    $image_with_css=false,
    $extra_info=''
) {
    if ($path === false) {
        $imagepath_array = ui_get_status_images_path();
        $imagepath = $imagepath_array[0];
    } else {
        $imagepath = $path;
    }

    if ($imagepath === 'images/status_sets/default') {
        $image_with_css = true;
    }

    if ($image_with_css === true) {
        $shape_status = get_shape_status_set($type);
        // $shape_status['is_tree_view'] = true;
        return ui_print_status_sets($type, $title, $return, $shape_status, $extra_info);
    } else {
        $imagepath .= '/'.$type;
        if ($options === false) {
            $options = [];
        }

        $options['title'] = $title;

        return html_print_image($imagepath, $return, $options, false, false, false, true);
    }
}


/**
 * Returns html code to print a shape for a module.
 *
 * @param integer $status      Module status.
 * @param boolean $return      True or false.
 * @param string  $class       Custom class or use defined.
 * @param string  $title       Custom title or inherit from module status.
 * @param string  $div_content Content.
 *
 * @return string HTML code for shape.
 */
function ui_print_module_status(
    $status,
    $return=false,
    $class='status_rounded_rectangles',
    $title=null,
    $div_content=''
) {
    $color = modules_get_color_status($status, true);
    if ($title === null) {
        $title = modules_get_modules_status($status);
    }

    $output = '<div style="background: '.$color;
    $output .= '" class="'.$class;
    $output .= ' forced_title" data-title="'.$title.'" title="';
    $output .= $title.'" data-use_title_for_force_title="1">'.$div_content.'</div>';

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Returns html code to print a shape for a module.
 *
 * @param integer $color       Hex color.
 * @param boolean $return      True or false.
 * @param string  $class       Custom class or use defined.
 * @param string  $div_content Content.
 *
 * @return string HTML code for shape.
 */
function ui_print_diagnosis_status(
    $color,
    $return=false,
    $class='status_rounded_rectangles',
    $div_content=''
) {
    $output = '<div style="background: '.$color.'" class="'.$class.'">'.$div_content.'</div>';

    if ($return === false) {
        echo $output;
    }

    return $output;
}


/**
 * Get the shape of an image by assigning it a CSS class. Prints an image with CSS representing a status.
 *
 * @param string $type Module/Agent/Alert status.
 *
 * @return array With CSS class.
 */
function get_shape_status_set($type)
{
    switch ($type) {
        // Rounded rectangles.
        case STATUS_ALERT_NOT_FIRED:
        case STATUS_ALERT_FIRED:
        case STATUS_ALERT_DISABLED:
        case STATUS_MODULE_OK:
        case STATUS_AGENT_OK:
        case STATUS_MODULE_NO_DATA:
        case STATUS_AGENT_NO_DATA:
        case STATUS_MODULE_CRITICAL:
        case STATUS_MODULE_ALERT_TRIGGERED:
        case STATUS_AGENT_CRITICAL:
        case STATUS_MODULE_WARNING:
        case STATUS_AGENT_WARNING:
        case STATUS_MODULE_UNKNOWN:
        case STATUS_AGENT_UNKNOWN:
        case STATUS_AGENT_DOWN:
        case STATUS_AGENT_NO_MONITORS:
            $return = ['class' => 'status_rounded_rectangles'];
        break;

        // Small squares.
        case STATUS_SERVER_OK:
        case STATUS_SERVER_DOWN:
        case STATUS_SERVER_CRASH:
        case STATUS_SERVER_STANDBY:
            $return = ['class' => 'status_small_squares'];
        break;

        // Balls.
        case STATUS_AGENT_CRITICAL_BALL:
        case STATUS_AGENT_WARNING_BALL:
        case STATUS_AGENT_DOWN_BALL:
        case STATUS_AGENT_UNKNOWN_BALL:
        case STATUS_AGENT_OK_BALL:
        case STATUS_AGENT_NO_DATA_BALL:
        case STATUS_AGENT_NO_MONITORS_BALL:
        case STATUS_SERVER_OK_BALL:
        case STATUS_SERVER_DOWN_BALL:
        case STATUS_SERVER_CRASH_BALL:
            $return = ['class' => 'status_balls'];
        break;

        // Small Balls.
        case STATUS_MODULE_OK_BALL:
        case STATUS_MODULE_CRITICAL_BALL:
        case STATUS_MODULE_WARNING_BALL:
        case STATUS_MODULE_NO_DATA_BALL:
        case STATUS_MODULE_UNKNOWN_BALL:
        case STATUS_ALERT_FIRED_BALL:
        case STATUS_ALERT_NOT_FIRED_BALL:
        case STATUS_ALERT_DISABLED_BALL:
            $return = ['class' => 'status_small_balls'];
            // $return = ['class' => 'status_balls'];
        break;

        default:
            // Ignored.
        break;
    }

    return $return;
}


/**
 * Prints an image representing a status.
 *
 * @param string  $status     Module status.
 * @param string  $title      Title.
 * @param boolean $return     Whether to return an output string or echo now (optional, echo by default).
 * @param array   $options    Options to set image attributes: I.E.: style.
 * @param string  $extra_info Text that is displayed after title (i.e. time elapsed since last status change of module).
 *
 * @return string HTML.
 */
function ui_print_status_sets(
    $status,
    $title='',
    $return=false,
    $options=false,
    $extra_info='',
    $get_status_color=true
) {
    global $config;

    if ($options === false) {
        $options = [];
    }

    if (isset($options['style']) === true) {
        $options['style'] .= ' display: inline-block;';
    } else {
        $options['style'] = 'display: inline-block;';
    }

    if ($get_status_color === true) {
        $options['style'] .= ' background: '.modules_get_color_status($status).';';
    }

    if (isset($options['is_tree_view']) === true) {
        $options['class'] = 'item_status_tree_view';
    } else if (isset($options['class']) === true) {
        $options['class'] = $options['class'];
    }

    if (empty($title) === false) {
        $options['title'] = (empty($extra_info) === true) ? $title : $title.'&#10'.$extra_info;
        $options['data-title'] = (empty($extra_info) === true) ? $title : $title.'<br>'.$extra_info;
        $options['data-use_title_for_force_title'] = 1;
        if (isset($options['class']) === true) {
            $options['class'] .= ' forced_title';
        } else {
            $options['class'] = 'forced_title';
        }
    }

    $output = '<div ';
    foreach ($options as $k => $v) {
        $output .= $k.'="'.$v.'"';
    }

    $output .= '>&nbsp;</div>';

    if (isset($options['is_tree_view']) === true) {
        $output = html_print_div(
            [
                'class'   => '',
                'content' => $output,
            ],
            true
        );
    }

    if ($return === false) {
        echo $output;
    } else {
        return $output;
    }
}


/**
 * Generates a progress bar CSS based.
 * Requires css progress.css
 *
 * @param integer $progress    Progress.
 * @param string  $width       Width.
 * @param integer $height      Height in 'em'.
 * @param string  $color       Color.
 * @param boolean $return      Return or paint (if false).
 * @param boolean $text        Text to be displayed,by default progress %.
 * @param array   $ajax        Ajax: [ 'page' => 'page', 'data' => 'data' ] Sample:
 *   [
 *       'page'     => 'operation/agentes/ver_agente', Target page.
 *       'interval' => 100 / $agent["intervalo"], Ask every interval seconds.
 *       'simple'   => 0,
 *       'data'     => [ Data to be sent to target page.
 *           'id_agente'       => $id_agente,
 *           'refresh_contact' => 1,
 *       ],
 *   ].
 *
 * @param string  $otherStyles Raw styles for control.
 *
 * @return string HTML code.
 */
function ui_progress(
    $progress,
    $width='100%',
    $height='2.5',
    $color='#82b92e',
    $return=true,
    $text='',
    $ajax=false,
    $otherStyles=''
) {
    if (!$progress) {
        $progress = 0;
    }

    if ($progress > 100) {
        $progress = 100;
    }

    if ($progress < 0) {
        $progress = 0;
    }

    if (empty($text)) {
        $text = $progress.'%';
    }

    $id = uniqid();

    ui_require_css_file('progress');
    $output = '<span id="'.$id.'" class="progress_main" data-label="'.$text;
    $output .= '" style="width: '.$width.'; height: '.$height.'em; border-color: '.$color.'; '.$otherStyles.'">';
    $output .= '<span id="'.$id.'_progress" class="progress" style="width: '.$progress.'%; background: '.$color.'"></span>';
    $output .= '</span>';

    if ($ajax !== false && is_array($ajax)) {
        if ($ajax['simple']) {
            $output .= '<script type="text/javascript">
    $(document).ready(function() {
        setInterval(() => {
                $.post({
                    url: "'.ui_get_full_url('ajax.php', false, false, false).'",
                    data: {';
            if (is_array($ajax['data'])) {
                foreach ($ajax['data'] as $token => $value) {
                    $output .= '
                            '.$token.':"'.$value.'",';
                }
            }

            $output .= '
                        page: "'.$ajax['page'].'"
                    },
                    success: function(data) {
                        let data_text = data;
                        if (data.includes("script")) {
                            const data_array = data_text.split("/script>");
                            data = data_array[1];
                        }
                        try {
                            if (isNaN(data) === true) {
                                val = JSON.parse(data);
                            } else {
                                val = data;
                            }

                            $("#'.$id.'").attr("data-label", val + " %");
                            $("#'.$id.'_progress").width(val+"%");
                            let parent_id = $("#'.$id.'").parent().parent().attr("id");
                            if (val == 100) {
                                $("#"+parent_id+"-5").html("'.__('Finish').'");
                            }';
            if (isset($ajax['oncomplete'])) {
                $output .= '
                            if (val == 100) {
                                '.$ajax['oncomplete'].'($("#'.$id.'"));
                            }
                ';
            }

            $output .= '
                        } catch (e) {
                            console.error(e);
                        }
                    }
                });
            }, '.($ajax['interval'] > 0 ? $ajax['interval'] * 1000 : 30000 ).');
    });
    </script>';
        } else {
            $output .= '<script type="text/javascript">
    $(document).ready(function() {
        setInterval(() => {
                last = $("#'.$id.'").attr("data-label").split(" ")[0]*1;
                width = $("#'.$id.'_progress").width() / $("#'.$id.'_progress").parent().width() * 100;
                width_interval = '.$ajax['interval'].';
                if (last % 10 == 0) {
                    $.post({
                        url: "'.ui_get_full_url('ajax.php', false, false, false).'",
                        data: {';
            if (is_array($ajax['data'])) {
                foreach ($ajax['data'] as $token => $value) {
                    $output .= '
                                '.$token.':"'.$value.'",';
                }
            }

            $output .= '
                            page: "'.$ajax['page'].'"
                        },
                        success: function(data) {
                            try {
                                val = JSON.parse(data);
                                if (isNaN(val["last_contact"]) == false) {
                                    $("#'.$id.'").attr("data-label", val["last_contact"]+" s");
                                }

                                $("#'.$id.'_progress").width(val["progress"]+"%");
                            } catch (e) {
                                console.error(e);
                                $(".progress_text").attr("data-label", (last -1) + " s");
                                if (width < 100) {
                                    $("#'.$id.'_progress").width((width+width_interval) + "%");
                                }
                            }
                        }
                    });
                } else {
                    if (isNaN(last) === false) {
                        $("#'.$id.'").attr("data-label", (last -1) + " s");
                    }

                    if (width < 100) {
                        $("#'.$id.'_progress").width((width+width_interval) + "%");
                    }
                }
            }, 1000);
    });
    </script>';
        }
    }

    if (!$return) {
        echo $output;
    }

    return $output;
}


/**
 * Generates a progress bar CSS based.
 * Requires css progress.css
 *
 * @param array $data With following content:
 *
 *  'slices' => [
 *  'label' => [ // Name of the slice
 * 'value' => value
 * 'color' => color of the slice.
 *  ]
 *  ],
 *  'width'  => Width
 *  'height' => Height in 'em'
 *  'return' => Boolean, return or paint.
 *
 * @return string HTML code.
 */
function ui_progress_extend(
    array $data
) {
    if (is_array($data) === false) {
        // Failed.
        return false;
    }

    if (is_array($data['slices']) === false) {
        // Failed.
        return false;
    }

    if (isset($data['width']) === false) {
        $data['width'] = '100';
    }

    if (isset($data['height']) === false) {
        $data['height'] = '1.3';
    }

    $total = array_reduce(
        $data['slices'],
        function ($carry, $item) {
            $carry += $item['value'];
            return $carry;
        }
    );
    if ($total == 0) {
        return null;
    }

    ui_require_css_file('progress');

    // Main container.
    $output = '<div class="progress_main_noborder" ';
    $output .= '" style="width:'.$data['width'].'%;';
    $output .= ' height:'.$data['height'].'em;">';

    foreach ($data['slices'] as $label => $def) {
        $width = ($def['value'] * 100 / $total);
        $output .= '<div class="progress forced_title" ';
        $output .= ' data-title="'.$label.': '.$def['value'].'" ';
        $output .= ' data-use_title_for_force_title="1"';
        $output .= ' style="width:'.$width.'%;';
        $output .= ' background-color:'.$def['color'].';';
        $output .= '">';
        $output .= '</div>';
    }

    $output .= '</div>';

    if (!$data['return']) {
        echo $output;
    }

    return $output;
}


/**
 * Generate needed code to print a datatables jquery plugin.
 *
 * @param array $parameters All desired data using following format:
 *
 * ```php
 * $parameters = [
 * // JS Parameters
 * 'serverside' => true,
 * 'paging' => true,
 * 'default_pagination' => $config['block_size'],
 * 'searching' => false,
 * 'dom_elements' => "plfrtiB",
 * 'pagination_options' => [default_pagination, 5, 10, 20, 100, 200, 500, 1000, "All"],
 * 'ordering' => true,
 * 'order' => [[0, "asc"]], //['field' => 'column_name', 'direction' => 'asc/desc']
 * 'zeroRecords' => "No matching records found",
 * 'emptyTable' => "No data available in table",
 * 'no_sortable_columns' => [], //Allows the column name (db) from "columns" parameter
 * 'csv_field_separator' => ",",
 * 'csv_header' => true,
 * 'mini_csv' => false,
 * 'mini_pagination' => false,
 * 'mini_search' => false,
 * 'drawCallback' => undefined, //'console.log(123),'
 * 'data_element' => undefined, //Rows processed
 * 'ajax_postprocess' => undefined, //'process_datatables_item(item)'
 * 'ajax_data' => undefined, //Extra data to be sent ['field1' => 1, 'field2 => 0]
 * 'ajax_url' => undefined,
 * 'caption' => undefined,
 *
 * // PHP Parameters
 * 'id' => undefined, //Used for table and form id,
 * 'columns' =>,
 * 'column_names' =>,
 * 'filter_main_class' =>,
 * 'toggle_collapsed' =>true,
 * 'search_button_class' => 'sub filter',
 * 'csv' =>=1,
 * 'form' =>
 * ..[
 * ....'id'            => $form_id,
 * ....'class'         => 'flex-row',
 * ....'style'         => 'width: 100%,',
 * ....'js'            => '',
 * ....'html'          => $filter,
 * ....'inputs'        => [],
 * ....'extra_buttons' => $buttons,
 * ..],
 * 'no_toggle'     => false,
 * 'form_html' => undefined,
 * 'toggle_collapsed' => true,
 * 'class' => "", //Datatable class.
 * 'style' => "" ,//Datatable style.
 * 'return' => false,
 * 'print' => true,
 * ]
 *
 * ```
 *
 * ```php
 * ajax_postprocess => a javscript function to postprocess data received
 *                        by ajax call. It is applied foreach row and must
 *                        use following format:
 * function (item) {
 *       // Process received item, for instance, name:
 *       tmp = '<span class=label>' + item.name + '</span>';
 *       item.name = tmp;
 *   }
 *   'columns_names' => [
 *      'column1'  :: Used as th text. Direct text entry. It could be array:
 *      OR
 *      [
 *        'id' => th id.
 *        'class' => th class.
 *        'style' => th style.
 *        'text' => 'column1'.
 *        'title'  => 'column title'.
 *      ]
 *   ],
 *   'columns' => [
 *      'column1',
 *      'column2',
 *      ...
 *   ],
 *   'form' => [
 *      'html' => 'html code' a directly defined inputs in HTML.
 *      'extra_buttons' => [
 *          [
 *              'id' => button id,
 *              'class' => button class,
 *              'style' => button style,
 *              'text' => button text,
 *              'onclick' => button onclick,
 *          ]
 *      ],
 *      'search_button_class' => search button class.
 *      'class' => form class.
 *      'id' => form id.
 *      'style' => form style.
 *      'js' => optional extra actions onsubmit.
 *      'inputs' => [
 *          'label' => Input label.
 *          'type' => Input type.
 *          'value' => Input value.
 *          'name' => Input name.
 *          'id' => Input id.
 *          'options' => [
 *             'option1'
 *             'option2'
 *             ...
 *          ]
 *       'no_toggle' => Pint form withouth UI toggle.
 *      ]
 *   ],
 *   'extra_html' => HTML content to be placed after 'filter' section.
 * ```
 *
 * @return string HTML code with datatable.
 * @throws Exception On error.
 */
function ui_print_datatable(array $parameters)
{
    global $config;

    if (isset($parameters['id'])) {
        $table_id = $parameters['id'];
        $form_id = 'form_'.$parameters['id'];
    } else {
        $table_id = uniqid('datatable_');
        $form_id = uniqid('datatable_filter_');
    }

    $parameters['table_id'] = $table_id;
    $parameters['form_id'] = $form_id;

    if (!isset($parameters['columns']) || !is_array($parameters['columns'])) {
        throw new Exception('[ui_print_datatable]: You must define columns for datatable');
    }

    if (isset($parameters['column_names'])
        && is_array($parameters['column_names'])
        && count($parameters['columns']) != count($parameters['column_names'])
    ) {
        throw new Exception('[ui_print_datatable]: Columns and columns names must have same length');
    }

    if (!isset($parameters['ajax_url']) && !isset($parameters['data_element'])) {
        throw new Exception('[ui_print_datatable]: Parameter ajax_url or data_element is required');
    }

    if (!isset($parameters['default_pagination'])) {
        $parameters['default_pagination'] = $config['block_size'];
    }

    if (!isset($parameters['filter_main_class'])) {
        $parameters['filter_main_class'] = '';
    }

    if (!isset($parameters['toggle_collapsed'])) {
        $parameters['toggle_collapsed'] = true;
    }

    $parameters['startDisabled'] = false;
    if (isset($parameters['start_disabled']) && $parameters['start_disabled'] === true) {
        $parameters['startDisabled'] = true;
    }

    $columns_tmp = [];
    foreach ($parameters['columns'] as $k_column => $v_column) {
        if (isset($parameters['columns'][$k_column]['text']) === true) {
            array_push($columns_tmp, $v_column['text']);
        } else {
            array_push($columns_tmp, $v_column);
        }
    }

    if (!is_array($parameters['order'])) {
        $order = 0;
        $direction = 'asc';
    } else {
        if (!isset($parameters['order']['direction'])) {
            $direction = 'asc';
        }

        if (!isset($parameters['order']['field'])) {
            $order = 0;
        } else {
            $order = array_search(
                $parameters['order']['field'],
                $columns_tmp
            );

            if ($order === false) {
                $order = 0;
            }
        }

        $direction = $parameters['order']['direction'];
    }

    $parameters['order']['order'] = $order;
    $parameters['order']['direction'] = $direction;

    if (isset($parameters['no_sortable_columns']) === true) {
        foreach ($parameters['no_sortable_columns'] as $key => $find) {
            $found = array_search(
                $parameters['no_sortable_columns'][$key],
                $columns_tmp
            );

            if ($found !== false) {
                unset($parameters['no_sortable_columns'][$key]);
                array_push($parameters['no_sortable_columns'], $found);
            }

            if (is_int($parameters['no_sortable_columns'][$key]) === false) {
                unset($parameters['no_sortable_columns'][$key]);
            }
        }
    }

    $parameters['csvTextInfo'] = __('Export current page to CSV');
    $parameters['csvFileTitle'] = sprintf(__('export_%s_current_page_%s'), $table_id, date('Y-m-d'));

    if (isset($parameters['search_button_class'])) {
        $search_button_class = $parameters['search_button_class'];
    } else {
        $search_button_class = 'sub filter';
    }

    if (isset($parameters['datacolumns']) === false
        || is_array($parameters['datacolumns']) === false
    ) {
        $parameters['datacolumns'] = $parameters['columns'];
    }

    if (isset($parameters['csv']) === false) {
        $parameters['csv'] = 1;
    }

    if (isset($parameters['no_move_elements_to_action']) === false) {
        $parameters['no_move_elements_to_action'] = false;
    }

    $filter = '';
    // Datatable filter.
    if (isset($parameters['form']) && is_array($parameters['form'])) {
        if (isset($parameters['form']['id'])) {
            $form_id = $parameters['form']['id'];
            $parameters['form_id'] = $form_id;
        }

        if (isset($parameters['form']['class'])) {
            $form_class = $parameters['form']['class'];
        } else {
            $form_class = '';
        }

        if (isset($parameters['form']['style'])) {
            $form_style = $parameters['form']['style'];
        } else {
            $form_style = '';
        }

        if (isset($parameters['form']['js'])) {
            $form_js = $parameters['form']['js'];
        } else {
            $form_js = '';
        }

        $filter = '<form class="'.$form_class.'" ';
        $filter .= ' id="'.$form_id.'" ';
        $filter .= ' style="'.$form_style.'" ';
        $filter .= ' onsubmit="'.$form_js.';return false;">';

        if (isset($parameters['form']['html'])) {
            $filter .= $parameters['form']['html'];
        }

        $filter .= '<ul class="datatable_filter content filter_table no_border">';

        if (isset($parameters['form']['inputs']) === true) {
            foreach ($parameters['form']['inputs'] as $input) {
                if ($input['type'] === 'date_range') {
                    $filter .= '<li><label>'.$input['label'].'</label>'.html_print_select_date_range('date', true).'</li>';
                } else {
                    $filter .= html_print_input(($input + ['return' => true]), 'li');
                }
            }
        }

        $filter .= '</ul>';
        // Extra buttons.
        $extra_buttons = '';
        if (isset($parameters['form']['extra_buttons']) === true
            && is_array($parameters['form']['extra_buttons']) === true
        ) {
            foreach ($parameters['form']['extra_buttons'] as $button) {
                $extra_buttons .= html_print_button(
                    $button['text'],
                    $button['id'],
                    false,
                    $button['onclick'],
                    [
                        'style' => ($button['style'] ?? ''),
                        'mode'  => 'secondary mini',
                        'class' => $button['class'],
                        'icon'  => $button['icon'],
                    ],
                    true
                );
            }
        }

        // Search button.
        $filter .= html_print_div(
            [
                'class'   => 'action-buttons',
                'content' => html_print_submit_button(
                    __('Filter'),
                    $form_id.'_search_bt',
                    false,
                    [
                        'icon'  => 'search',
                        'mode'  => 'mini',
                        'class' => $search_button_class,
                    ],
                    true
                ).$extra_buttons,
            ],
            true
        );

        $filter .= '<div id="both"></div></form>';
        if (isset($parameters['form']['no_toggle']) === false) {
            $filter = ui_toggle(
                $filter,
                '<span class="subsection_header_title">'.__('Filters').'</span>',
                '',
                '',
                $parameters['toggle_collapsed'],
                (isset($parameters['form']['return_filter']) === false) ? false : $parameters['form']['return_filter'],
                '',
                'no-border filter-datatable-submain',
                'filter-datatable-main '.$parameters['filter_main_class']
            );
        }
    } else if (isset($parameters['form_html'])) {
        $filter = ui_toggle(
            $parameters['form_html'],
            '<span class="subsection_header_title">'.__('Filters').'</span>',
            '',
            '',
            $parameters['toggle_collapsed'],
            false,
            '',
            'no-border filter-datatable-submain',
            'box-float white_table_graph filter-datatable-main '.$parameters['filter_main_class']
        );
    }

    // Languages.
    $processing = '<div class=\'processing-datatables-inside\'>';
    $processing .= '<i>'.__('Processing').'</i> ';
    $processing .= str_replace(
        '"',
        "'",
        html_print_image(
            'images/spinner.gif',
            true
        )
    );
    $processing .= '</div>';
    $parameters['processing'] = $processing;

    $zeroRecords = isset($parameters['zeroRecords']) === true ? $parameters['zeroRecords'] : __('No matching records found');
    $emptyTable = isset($parameters['emptyTable']) === true ? $parameters['emptyTable'] : __('No data available in table');

    $parameters['zeroRecords'] = $zeroRecords;
    $parameters['emptyTable'] = $emptyTable;
    // Extra html.
    $extra = '';
    if (isset($parameters['extra_html']) && !empty($parameters['extra_html'])) {
        $extra = $parameters['extra_html'];
    }

    // Base table.
    $table = '<table id="'.$table_id.'" ';
    $table .= 'class="invisible '.$parameters['class'].'"';
    $table .= 'style="box-sizing: border-box;'.$parameters['style'].'">';
    $table .= '<thead><tr class="datatables_thead_tr">';

    if (isset($parameters['column_names'])
        && is_array($parameters['column_names'])
    ) {
        $names = $parameters['column_names'];
    } else {
        $names = $parameters['columns'];
    }

    foreach ($names as $column) {
        if (is_array($column)) {
            $table .= '<th id="'.($column['id'] ?? '').'" class="'.($column['class'] ?? '').'" ';
            if (isset($column['title']) === true) {
                $table .= 'title="'.__($column['title']).'" ';
            }

            $table .= ' style="'.($column['style'] ?? '').'">'.__($column['text']);
            $table .= ($column['extra'] ?? '');
            $table .= '</th>';
        } else {
            $table .= '<th>'.__($column).'</th>';
        }
    }

    $table .= '</tr></thead>';
    $table .= '</table>';

    $parameters['ajax_url_full'] = ui_get_full_url('ajax.php', false, false, false);

    $parameters['spinnerLoading'] = html_print_image(
        'images/spinner.gif',
        true,
        [
            'id'    => $form_id.'_loading',
            'class' => 'loading-search-datatables-button',
        ]
    );

    $language = substr(get_user_language(), 0, 2);
    if (is_metaconsole() === false) {
        $parameters['language'] = 'include/javascript/i18n/dataTables.'.$language.'.json';
    } else {
        $parameters['language'] = '../../include/javascript/i18n/dataTables.'.$language.'.json';
    }

    $parameters['phpDate'] = date('Y-m-d');
    $parameters['dataElements'] = (isset($parameters['data_element']) === true) ? json_encode($parameters['data_element']) : '';

    // * START JAVASCRIPT.
    $file_path = $config['homedir'].'/include/javascript/datatablesFunction.js';

    $file_content = file_get_contents($file_path);
    $json_data = json_encode($parameters);
    $json_config = json_encode($config);

    $js = '<script>';
    $js .= 'var dt = '.$json_data.';';
    $js .= 'var config = '.$json_config.';';
    $js .= '</script>';

    $js .= '<script>';
    $js .= 'function '.$table_id.'(dt, config) {  ';
    $js .= $file_content;
    $js .= '}';
    $js .= $table_id.'(dt, config)';
    $js .= '</script>';
    // * END JAVASCRIPT.
    $info_msg_arr = [];
    $info_msg_arr['message'] = $emptyTable;
    $info_msg_arr['div_class'] = 'info_box_container invisible_important datatable-info-massage datatable-msg-info-'.$table_id;

    $info_msg_arr_filter = [];
    $info_msg_arr_filter['message'] = __('Please apply a filter to display the data.');
    $info_msg_arr_filter['div_class'] = 'info_box_container invisible_important datatable-info-massage datatable-msg-info-filter-'.$table_id;

    $spinner = '<div id="'.$table_id.'-spinner" class="invisible spinner-fixed"><span></span><span></span><span></span><span></span></div>';

    $info_msg = '<div class="datatable-container-info-massage">'.ui_print_info_message($info_msg_arr, '', true).'</div>';

    $info_msg_filter = '<div>'.ui_print_info_message($info_msg_arr_filter, true).'</div>';

    $err_msg = '<div id="error-'.$table_id.'"></div>';
    $output = $info_msg.$info_msg_filter.$err_msg.$filter.$extra.$spinner.$table.$js;
    if (is_ajax() === false) {
        ui_require_css_file('datatables.min', 'include/styles/js/');
        ui_require_css_file('tables');
        if (is_metaconsole()) {
            ui_require_css_file('meta_tables', ENTERPRISE_DIR.'/include/styles/');
        }

        ui_require_javascript_file('datatables.min');
        ui_require_javascript_file('buttons.dataTables.min');
        ui_require_javascript_file('dataTables.buttons.min');
        ui_require_javascript_file('buttons.html5.min');
        ui_require_javascript_file('buttons.print.min');
    } else {
        // Load datatable.min.css.
        $output .= '<link rel="stylesheet" href="';
        $output .= ui_get_full_url(
            'include/styles/js/datatables.min.css',
            false,
            false,
            false
        );
        $output .= '"/>';
        // Load tables.css.
        $output .= '<link rel="stylesheet" href="';
        $output .= ui_get_full_url(
            'include/styles/tables.css',
            false,
            false,
            false
        );
        $output .= '?v='.$config['current_package'].'"/>';
        // if (is_metaconsole() === true) {
        // Load meta_tables.css.
        // $output .= '<link rel="stylesheet" href="';
        // $output .= ui_get_full_url(
        // ENTERPRISE_DIR.'/include/styles/meta_tables.css',
        // false,
        // false,
        // false
        // );
        // $output .= '?v='.$config['current_package'].'"/>';
        // }
        // Load datatables.js.
        $output .= '<script src="';
        $output .= ui_get_full_url(
            'include/javascript/datatables.min.js',
            false,
            false,
            false
        );
        $output .= '" type="text/javascript"></script>';
        // Load buttons.dataTables.min.js.
        $output .= '<script src="';
        $output .= ui_get_full_url(
            'include/javascript/buttons.dataTables.min.js',
            false,
            false,
            false
        );
        $output .= '" type="text/javascript"></script>';
        // Load dataTables.buttons.min.js.
        $output .= '<script src="';
        $output .= ui_get_full_url(
            'include/javascript/dataTables.buttons.min.js',
            false,
            false,
            false
        );
        $output .= '" type="text/javascript"></script>';
        // Load buttons.html5.min.js.
        $output .= '<script src="';
        $output .= ui_get_full_url(
            'include/javascript/buttons.html5.min.js',
            false,
            false,
            false
        );
        $output .= '" type="text/javascript"></script>';
        // Load buttons.print.min.js.
        $output .= '<script src="';
        $output .= ui_get_full_url(
            'include/javascript/buttons.print.min.js',
            false,
            false,
            false
        );
        $output .= '" type="text/javascript"></script>';
    }

    if (isset($parameters['return']) && $parameters['return'] == true) {
        // Compat.
        $parameters['print'] = false;
    }

    // Print datatable if needed.
    if (isset($parameters['print']) === false || $parameters['print'] === true) {
        echo $output;
    }

    return $output;
}


/**
 * Returns a div wich represents the type received.
 *
 * Requires ui_require_css_file('events');.
 *
 * @param integer $type   Event type.
 * @param boolean $return Or print.
 * @param boolean $mini   Show mini div.
 *
 * @return string HTML.
 */
function ui_print_event_type(
    $type,
    $return=false,
    $mini=false
) {
    global $config;

    $output = '';
    switch ($type) {
        case EVENTS_ALERT_FIRED:
        case EVENTS_ALERT_RECOVERED:
        case EVENTS_ALERT_CEASED:
        case EVENTS_ALERT_MANUAL_VALIDATION:
            $text = __('ALERT');
            $color = COL_ALERTFIRED;
        break;

        case EVENTS_RECON_HOST_DETECTED:
        case EVENTS_SYSTEM:
        case EVENTS_ERROR:
        case EVENTS_NEW_AGENT:
        case EVENTS_CONFIGURATION_CHANGE:
            $text = __('SYSTEM');
            $color = COL_MAINTENANCE;
        break;

        case EVENTS_GOING_UP_WARNING:
        case EVENTS_GOING_DOWN_WARNING:
            $color = COL_WARNING;
            $text = __('WARNING');
        break;

        case EVENTS_GOING_DOWN_NORMAL:
        case EVENTS_GOING_UP_NORMAL:
            $color = COL_NORMAL;
            $text = __('NORMAL');
        break;

        case EVENTS_GOING_DOWN_CRITICAL:
        case EVENTS_GOING_UP_CRITICAL:
            $color = COL_CRITICAL;
            $text = __('CRITICAL');
        break;

        case EVENTS_UNKNOWN:
        case EVENTS_GOING_UNKNOWN:
        default:
            $color = COL_UNKNOWN;
            $text = __('UNKNOWN');
        break;
    }

    if ($mini === true) {
        $output = html_print_div(
            [
                'class'   => 'status_rounded_rectangles',
                'style'   => sprintf('display: inline-block; background: %s', $color),
                'content' => '',
                'title'   => $text,
            ],
            true
        );
    } else {
        $output = '<div data-title="';
        $output .= $text;
        $output .= '" data-use_title_for_force_title="1" ';
        $output .= 'class="forced_title mini-criticity" ';
        $output .= 'style="background: '.$color.'">';
        $output .= '</div>';
    }

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Returns a div wich represents the priority received.
 *
 * Requires ui_require_css_file('events');.
 *
 * @param integer $priority Priority level.
 * @param boolean $return   Or print.
 * @param boolean $mini     Show mini div.
 *
 * @return string HTML.
 */
function ui_print_event_priority(
    $priority,
    $return=false,
    $mini=false
) {
    global $config;

    $output = '';
    switch ($priority) {
        case EVENT_CRIT_MAINTENANCE:
            $color = COL_MAINTENANCE;
            $criticity = __('MAINTENANCE');
        break;

        case EVENT_CRIT_INFORMATIONAL:
            $color = COL_INFORMATIONAL;
            $criticity = __('INFORMATIONAL');
        break;

        case EVENT_CRIT_NORMAL:
            $color = COL_NORMAL;
            $criticity = __('NORMAL');
        break;

        case EVENT_CRIT_WARNING:
            $color = COL_WARNING;
            $criticity = __('WARNING');
        break;

        case EVENT_CRIT_CRITICAL:
            $color = COL_CRITICAL;
            $criticity = __('CRITICAL');
        break;

        case EVENT_CRIT_MINOR:
            $color = COL_MINOR;
            $criticity = __('MINOR');
        break;

        case EVENT_CRIT_MAJOR:
            $color = COL_MAJOR;
            $criticity = __('MAJOR');
        break;

        default:
            $color = COL_UNKNOWN;
            $criticity = __('UNKNOWN');
        break;
    }

    if ($mini === true) {
        $output = '<div class="criticity" style="background: '.$color.'">';
        $output .= $criticity;
        $output .= '</div>';
    } else {
        $output = '<div data-title="';
        $output .= $criticity;
        $output .= '" data-use_title_for_force_title="1" ';
        $output .= 'class="forced_title mini-criticity" ';
        $output .= 'style="background: '.$color.'">';
        $output .= '</div>';
    }

    return $output;
}


/**
 * Print a code into a DIV and enable a toggle to show and hide it.
 *
 * @param string       $code              Html code.
 * @param string       $name              Name of the link.
 * @param string       $title             Title of the link.
 * @param string       $id                Block id.
 * @param boolean      $hidden_default    If the div will be hidden by default (default: true).
 * @param boolean      $return            Whether to return an output string or echo now (default: true).
 * @param string       $toggle_class      Toggle class.
 * @param string       $container_class   Container class.
 * @param string       $main_class        Main object class.
 * @param string       $img_a             Image (closed).
 * @param string       $img_b             Image (opened).
 * @param string       $clean             Do not encapsulate with class boxes, clean print.
 * @param boolean      $reverseImg        Reverse img.
 * @param boolean      $switch            Use switch.
 * @param string       $attributes_switch Switch attributes.
 * @param string       $toggl_attr        Main box extra attributes.
 * @param boolean|null $switch_on         Switch enabled disabled or depending on hidden_Default.
 * @param string|null  $switch_name       Use custom switch input name or generate one.
 * @param boolean|null $disableToggle     If True, the toggle is disabled.
 * @param string       $id_table          ID of the table to apply w100p class.
 *
 * @return string HTML.
 */
function ui_toggle(
    $code,
    $name,
    $title='',
    $id='',
    $hidden_default=true,
    $return=false,
    $toggle_class='',
    $container_class='white-box-content',
    $main_class='box-flat white_table_graph',
    $img_a='images/arrow@svg.svg',
    $img_b='images/arrow@svg.svg',
    $clean=false,
    $reverseImg=false,
    $switch=false,
    $attributes_switch='',
    $toggl_attr='',
    $switch_on=null,
    $switch_name=null,
    $disableToggle=false,
    $id_table=false,
    $position_tgl_div=false
) {
    // Generate unique Id.
    $uniqid = uniqid('');
    $rotateA = '90deg';
    $rotateB = '180deg';

    // Options.
    $style = 'overflow:hidden;width: -webkit-fill-available;width: -moz-available;';
    $style = 'overflow:hidden;';
    if ($hidden_default === true) {
        $imageRotate = $rotateB;
        $style .= 'height:0;position:absolute;';
        $original = $img_b;
    } else {
        $imageRotate = $rotateA;
        $style .= 'height:auto;position:relative;';
        $original = $img_a;
    }

    $header_class = '';
    if ($clean === false) {
        $header_class = 'white_table_graph_header';
    } else {
        if ($main_class == 'box-flat white_table_graph') {
            // Default value, clean class.
            $main_class = '';
        }

        if ($container_class == 'white-box-content') {
            $container_class = 'white-box-content-clean';
        }
    }

    // Link to toggle.
    $output = '<div class="'.$main_class.'" id="'.$id.'" '.$toggl_attr.'>';
    $output .= '<div class="'.$header_class.'" '.(($disableToggle === false) ? 'style="cursor: pointer;" ' : '').' id="tgl_ctrl_'.$uniqid.'">';
    if ($reverseImg === false) {
        if ($switch === true) {
            if (empty($switch_name) === true) {
                $switch_name = 'box_enable_toggle'.$uniqid;
            }

            $output .= html_print_div(
                [
                    'class'   => 'float-left',
                    'content' => html_print_checkbox_switch_extended(
                        $switch_name,
                        1,
                        ($switch_on === null) ? (($hidden_default === true) ? 0 : 1) : $switch_on,
                        false,
                        '',
                        $attributes_switch,
                        true
                    ),
                ],
                true
            );
        } else {
            $output .= html_print_image(
                $original,
                true,
                [
                    'class' => 'float-left main_menu_icon mrgn_right_10px invert_filter',
                    'style' => 'object-fit: contain; margin-right:10px; rotate:'.$imageRotate,
                    'title' => $title,
                    'id'    => 'image_'.$uniqid,
                ]
            );
        }

        $output .= $name;
    } else {
        $output .= $name;
        if ($switch === true) {
            $output .= html_print_div(
                [
                    'class'   => 'float-left',
                    'content' => html_print_checkbox_switch_extended(
                        'box_enable_toggle'.$uniqid,
                        1,
                        ($hidden_default === true) ? 0 : 1,
                        false,
                        '',
                        '',
                        true
                    ),
                ],
                true
            );
        } else {
            $output .= html_print_image(
                $original,
                true,
                [
                    'class' => 'main_menu_icon mrgn_right_10px invert_filter',
                    'style' => 'object-fit: contain; float:right; margin-right:10px; rotate:'.$imageRotate,
                    'title' => $title,
                    'id'    => 'image_'.$uniqid,
                ]
            );
        }
    }

    $output .= '</div>';

    // Code into a div
    $output .= "<div id='tgl_div_".$uniqid."' style='".$style.";margin-top: -1px;' class='".$toggle_class."'>\n";
    $output .= '<div class="'.$container_class.'">';
    $output .= $code;
    $output .= '</div>';
    $output .= '</div>';

    $class_table = '';
    if ($id_table !== false) {
        $class_table = '$("#'.$id_table.'_wrapper").addClass("w100p");'."\n";
    }

    if ($disableToggle === false) {
        $position_div = 'relative';
        if ($position_tgl_div !== false) {
            $position_div = $position_tgl_div;
        }

        // JQuery Toggle.
        $output .= '<script type="text/javascript">'."\n";
        $output .= '	var hide_tgl_ctrl_'.$uniqid.' = '.(int) $hidden_default.";\n";
        $output .= '	var is_metaconsole = '.(int) is_metaconsole().";\n";
        $output .= '	/* <![CDATA[ */'."\n";
        $output .= "	$(document).ready (function () {\n";
        $output .= '	    var switch_enable = '.(int) $switch.";\n";
        $output .= "		$('#checkbox-".$switch_name."').click(function() {\n";
        $output .= '            if (hide_tgl_ctrl_'.$uniqid.") {\n";
        $output .= '			    hide_tgl_ctrl_'.$uniqid." = 0;\n";
        $output .= "			    $('#tgl_div_".$uniqid."').css('height', 'auto');\n";
        $output .= "			    $('#tgl_div_".$uniqid."').css('position', 'relative');\n";
        $output .= "			}\n";
        $output .= "			else {\n";
        $output .= '			    hide_tgl_ctrl_'.$uniqid." = 1;\n";
        $output .= "			    $('#tgl_div_".$uniqid."').css('height', 0);\n";
        $output .= "			    $('#tgl_div_".$uniqid."').css('position', 'absolute');\n";
        $output .= "			}\n";
        $output .= "		});\n";
        $output .= '        if (switch_enable === 0) {';
        $output .= "		    $('#tgl_ctrl_".$uniqid."').click(function() {\n";
        $output .= '			    if (hide_tgl_ctrl_'.$uniqid.") {\n";
        $output .= '				    hide_tgl_ctrl_'.$uniqid." = 0;\n";
        $output .= "				    $('#tgl_div_".$uniqid."').css('height', 'auto');\n";
        $output .= "				    $('#tgl_div_".$uniqid."').css('position', '".$position_div."');\n";
        $output .= "				    $('#image_".$uniqid."').attr('style', 'rotate: ".$rotateA."');\n";
        $output .= "				    $('#checkbox-".$switch_name."').prop('checked', true);\n";
        $output .= $class_table;
        $output .= "			    }\n";
        $output .= "			    else {\n";
        $output .= '				    hide_tgl_ctrl_'.$uniqid." = 1;\n";
        $output .= "				    $('#tgl_div_".$uniqid."').css('height', 0);\n";
        $output .= "				    $('#tgl_div_".$uniqid."').css('position', 'absolute');\n";
        $output .= "				    $('#image_".$uniqid."').attr('style', 'rotate: ".$rotateB."');\n";
        $output .= "				    $('#checkbox-".$switch_name."').prop('checked', false);\n";
        $output .= "			    }\n";
        $output .= "		    });\n";
        $output .= "	    }\n";
        $output .= "	});\n";
        $output .= '/* ]]> */';
        $output .= '</script>';
        $output .= '</div>';
    }

    if (!$return) {
        echo $output;
    } else {
        return $output;
    }
}


/**
 * Simplified way of ui_toggle ussage.
 *
 * @param array $data Arguments:
 *  - content
 *  - name
 *  - title
 *  - id
 *  - hidden_default
 *  - return
 *  - toggle_class
 *  - container_class
 *  - main_class
 *  - img_a
 *  - img_b
 *  - clean
 *  - reverseImg
 *  - switch
 *  - attributes_switch
 *  - toggl_attr
 *  - switch_on
 *  - switch_name.
 *
 * @return string HTML code with toggle content.
 */
function ui_print_toggle($data)
{
    return ui_toggle(
        $data['content'],
        $data['name'],
        (isset($data['title']) === true) ? $data['title'] : '',
        (isset($data['id']) === true) ? $data['id'] : '',
        (isset($data['hidden_default']) === true) ? $data['hidden_default'] : true,
        (isset($data['return']) === true) ? $data['return'] : false,
        (isset($data['toggle_class']) === true) ? $data['toggle_class'] : '',
        (isset($data['container_class']) === true) ? $data['container_class'] : 'white-box-content',
        (isset($data['main_class']) === true) ? $data['main_class'] : 'box-flat white_table_graph',
        (isset($data['img_a']) === true) ? $data['img_a'] : 'images/arrow@svg.svg',
        (isset($data['img_b']) === true) ? $data['img_b'] : 'images/arrow@svg.svg',
        (isset($data['clean']) === true) ? $data['clean'] : false,
        (isset($data['reverseImg']) === true) ? $data['reverseImg'] : false,
        (isset($data['switch']) === true) ? $data['switch'] : false,
        (isset($data['attributes_switch']) === true) ? $data['attributes_switch'] : '',
        (isset($data['toggl_attr']) === true) ? $data['toggl_attr'] : '',
        (isset($data['switch_on']) === true) ? $data['switch_on'] : null,
        (isset($data['switch_name']) === true) ? $data['switch_name'] : null
    );
}


/**
 * Construct and return the URL to be used in order to refresh the current page correctly.
 *
 * @param array   $params   Extra parameters to be added to the URL. It has prevalence over
 *     GET and POST. False values will be ignored.
 * @param boolean $relative Whether to return the relative URL or the absolute URL. Returns
 *    relative by default.
 * @param boolean $add_post Whether to add POST values to the URL.
 *
 * @return string Url.
 */
function ui_get_url_refresh($params=false, $relative=true, $add_post=true)
{
    // Agent selection filters and refresh.
    global $config;

    // Slerena, 8/Ene/2015 - Need to put index.php on URL which have it.
    if (strpos($_SERVER['REQUEST_URI'], 'index.php') === false) {
        $url = '';
    } else {
        $url = 'index.php';
    }

    if (count($_REQUEST)) {
        // Some (old) browsers don't like the ?&key=var.
        $url .= '?';
    }

    if (! is_array($params)) {
        $params = [];
    }

    // Avoid showing login info.
    $params['pass'] = false;
    $params['nick'] = false;
    $params['unnamed'] = false;

    // We don't clean these variables up as they're only being passed along.
    foreach ($_GET as $key => $value) {
        if (isset($params[$key])) {
            continue;
        }

        if (strstr($key, 'create')) {
            continue;
        }

        if (strstr($key, 'update')) {
            continue;
        }

        if (strstr($key, 'new')) {
            continue;
        }

        if (strstr($key, 'delete')) {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $url .= $key.'['.$k.']='.$v.'&';
            }
        } else {
            $aux = (empty($value) === false)
                ? io_safe_input(rawurlencode($value))
                : '';
            $url .= $key.'='.$aux.'&';
        }
    }

    if ($add_post) {
        foreach ($_POST as $key => $value) {
            if (isset($params[$key])) {
                continue;
            }

            if (strstr($key, 'create')) {
                continue;
            }

            if (strstr($key, 'update')) {
                continue;
            }

            if (strstr($key, 'new')) {
                continue;
            }

            if (strstr($key, 'delete')) {
                continue;
            }

            if (is_array($value)) {
                foreach ($value as $k => $v) {
                    $url .= $key.'['.$k.']='.$v.'&';
                }
            } else {
                $url .= $key.'='.$value.'&';
            }
        }
    }

    foreach ($params as $key => $value) {
        if ($value === false) {
            continue;
        }

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $url .= $key.'['.$k.']='.$v.'&';
            }
        } else {
            $url .= $key.'='.$value.'&';
        }
    }

    // Removes final &.
    $pos = strrpos($url, '&', 0);
    if ($pos) {
        $url = substr_replace($url, '', $pos, 5);
    }

    $url = (isset($params['alert_flag']) && $params['alert_flag']) ? $url : htmlspecialchars($url);

    if (! $relative) {
        return ui_get_full_url($url, false, false, false);
    }

    return $url;
}


/**
 * Checks if public_url usage is being forced to target 'visitor'.
 *
 * @return boolean
 */
function ui_forced_public_url()
{
    global $config;

    $exclusions = [];
    if (empty($config['public_url_exclusions']) === false) {
        $exclusions = preg_split("/[\n\s,]+/", io_safe_output($config['public_url_exclusions']));
    }

    if (isset($_SERVER['REMOTE_ADDR']) === true
        && in_array($_SERVER['REMOTE_ADDR'], $exclusions)
    ) {
        return false;
    }

    return isset($config['force_public_url']) && (bool) $config['force_public_url'];
}


/**
 * Returns a full built url for given section.
 *
 * @param  string $url
 * @return void
 */
function ui_get_meta_url($url)
{
    global $config;

    if (is_metaconsole() === true) {
        return ui_get_full_url($url);
    }

    $mc_db_conn = \enterprise_hook(
        'metaconsole_load_external_db',
        [
            [
                'dbhost' => $config['replication_dbhost'],
                'dbuser' => $config['replication_dbuser'],
                'dbpass' => \io_output_password(
                    $config['replication_dbpass']
                ),
                'dbname' => $config['replication_dbname'],
            ],
        ]
    );

    if ($mc_db_conn === NOERR) {
        $public_url_meta = \db_get_value(
            'value',
            'tconfig',
            'token',
            'public_url',
            false,
            false
        );

        // Restore the default connection.
        \enterprise_hook('metaconsole_restore_db');

        if (empty($public_url_meta) === false
            && $public_url_meta !== $config['metaconsole_base_url']
        ) {
            config_update_value(
                'metaconsole_base_url',
                $public_url_meta
            );
        }
    }

    if (isset($config['metaconsole_base_url']) === true) {
        return $config['metaconsole_base_url'].'enterprise/meta/'.$url;
    }

    return $url;
}


/**
 * Returns a full URL in Pandora. (with the port and https in some systems)
 *
 * An example of full URL is http:/localhost/pandora_console/index.php?sec=gsetup&sec2=godmode/setup/setup
 *
 * @param mixed   $url               If provided, it will be added after the index.php, but it is false boolean value, put the homeurl in the url.
 * @param boolean $no_proxy          To avoid the proxy checks, by default it is false.
 * @param boolean $add_name_php_file Something.
 * @param boolean $metaconsole_root  Set the root to the metaconsole dir if the metaconsole is enabled, true by default.
 *
 * @return string A full URL in Pandora.
 */
function ui_get_full_url($url='', $no_proxy=false, $add_name_php_file=false, $metaconsole_root=true)
{
    global $config;

    $port = null;
    // Null means 'use the starndard port'.
    $proxy = false;
    // By default Pandora FMS doesn't run across proxy.
    if (isset($_SERVER['HTTP_X_FORWARDED_PROTO'])
        && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https'
    ) {
        $_SERVER['HTTPS'] = 'on';
    }

    if (isset($_SERVER['HTTPS'])
        && ($_SERVER['HTTPS'] === true
        || $_SERVER['HTTPS'] == 'on')
    ) {
        $protocol = 'https';
        if ($_SERVER['SERVER_PORT'] != 443) {
            $port = $_SERVER['SERVER_PORT'];
        }
    } else if ($config['https']) {
        // When $config["https"] is set, enforce https.
        $protocol = 'https';
    } else {
        $protocol = 'http';

        if (($_SERVER['SERVER_PORT'] ?? 80) != 80) {
            $port = $_SERVER['SERVER_PORT'];
        }
    }

    if (!$no_proxy) {
        // Check proxy.
        $proxy = false;
        if (ui_forced_public_url()) {
            $proxy = true;
            $fullurl = $config['public_url'];
            if (substr($fullurl, -1) != '/') {
                $fullurl .= '/';
            }
        } else if (!empty($config['public_url'])
            && (!empty($_SERVER['HTTP_X_FORWARDED_HOST']))
        ) {
            // Forced to use public url when being forwarder by a reverse proxy.
            $fullurl = $config['public_url'];
            if (substr($fullurl, -1) != '/') {
                $fullurl .= '/';
            }

            $proxy = true;
        } else {
            $fullurl = $protocol.'://'.($_SERVER['SERVER_NAME'] ?? '');
        }
    } else {
        $fullurl = $protocol.'://'.($_SERVER['SERVER_NAME'] ?? '');
    }

    // Using a different port than the standard.
    if (!$proxy) {
        // Using a different port than the standard.
        if ($port != null) {
            $fullurl .= ':'.$port;
        }
    }

    $skip_meta_tag = false;
    if ($url === '') {
        if ($proxy === false) {
            $url = $_SERVER['REQUEST_URI'];
            // Already inserted in request_uri.
            $skip_meta_tag = true;
        } else {
            // Redirect to main.
            $url = '?'.$_SERVER['QUERY_STRING'];
        }
    } else if (empty($url) === true) {
        if ($proxy === false) {
            $url = $config['homeurl_static'].'/';
            if ($metaconsole_root === true
                && is_metaconsole()
            ) {
                $url = $config['homeurl_static'].'/'.ENTERPRISE_DIR.'/meta/';
            }

            $skip_meta_tag = true;
        } else {
            $url = '';
        }
    } else if (!strstr($url, '.php')) {
        if ($proxy) {
            $fullurl .= '/';
        } else {
            $fullurl .= $config['homeurl_static'].'/';
        }
    } else {
        if ((bool) $proxy === false) {
            if ($add_name_php_file) {
                $fullurl .= $_SERVER['SCRIPT_NAME'];
            } else {
                $fullurl .= $config['homeurl_static'].'/';
            }
        }
    }

    // Add last slash if missing.
    if (substr($fullurl, -1, 1) !== '/') {
        $fullurl .= '/';
    }

    // Remove starting slash if present.
    if (substr($url, 0, 1) === '/') {
        $url = substr($url, 1);
    }

    if ($skip_meta_tag === false
        && $metaconsole_root
        && is_metaconsole()
    ) {
        $fullurl .= ENTERPRISE_DIR.'/meta/';
    }

    return $fullurl.$url;
}


/**
 * Generates the Pandora 75x Standard views header.
 * This function should be the standard for
 * generating the headers of all PFMS views.
 *
 * @param string  $title       The title of this view.
 * @param string  $icon        Icon for show.
 * @param boolean $return      If true, the string with the formed header is returned.
 * @param string  $help        String for attach at end a link for help.
 * @param boolean $godmode     If false, it will created like operation mode.
 * @param array   $options     Tabs allowed
 * @param array   $breadcrumbs Breadcrumbs with the walk.
 *
 *  EXAMPLE:
 *  ```
 *   $buttons['option_1'] = [
 *     'active' => false,
 *     'text'   => '<a href="'.$url.'">'.html_print_image(
 *         'images/wand.png',
 *         true,
 *         [ 'title' => __('Option 1 for show'), 'class' => 'invert_filter' ]
 *     ).'</a>',
 *    ];
 *
 *    ui_print_standard_header(
 *      __('Favorites'),
 *      'images/op_reporting.png',
 *      false,
 *      '',
 *      true,
 *      $buttons,
 *      [
 *         [ 'link'  => '', 'label' => __('Topology maps') ],
 *         [ 'link'  => '', 'label' => __('Visual console') ],
 *      ]
 *  );
 *  ```
 *
 * @return string If apply
 */
function ui_print_standard_header(
    string $title,
    string $icon='',
    bool $return=false,
    string $help='',
    bool $godmode=false,
    array $options=[],
    array $breadcrumbs=[],
    array $fav_menu_config=[],
    string $dots='',
) {
    // For standard breadcrumbs.
    ui_require_css_file('discovery');
    // Create the breadcrumb.
    $headerInformation = new HTML();
    $headerInformation->setBreadcrum([]);
    // Prepare the breadcrumbs.
    $countBreadcrumbs = count($breadcrumbs);
    $countUnitBreadcrumb = 0;
    $applyBreadcrumbs = [];
    foreach ($breadcrumbs as $unitBreadcrumb) {
        // Count new breadcrumb.
        $countUnitBreadcrumb++;
        // Apply selected if is the last.
        $unitBreadcrumb['selected'] = ($countBreadcrumbs === $countUnitBreadcrumb);
        // Apply for another breadcrumb.
        $applyBreadcrumbs[] = $unitBreadcrumb;
    }

    // Attach breadcrumbs.
    $headerInformation->prepareBreadcrum(
        $applyBreadcrumbs,
        true
    );

    $output = ui_print_page_header(
        $title,
        $icon,
        true,
        $help,
        $godmode,
        $options,
        false,
        '',
        GENERIC_SIZE_TEXT,
        '',
        $headerInformation->printHeader(true),
        false,
        $fav_menu_config,
        $dots
    );
    if ($return !== true) {
        echo $output;
    } else {
        return $output;
    }
}


/**
 * Return a standard page header (Pandora FMS 3.1 version)
 *
 * @param  string  $title           Title.
 * @param  string  $icon            Icon path.
 * @param  boolean $return          Return (false will print using a echo).
 * @param  boolean $help            Help (Help ID to print the Help link).
 * @param  boolean $godmode         Godmode (false = operation mode).
 * @param  string  $options         Options (HTML code for make tabs or just a brief
 *     info string.
 * @param  mixed   $modal           Modal.
 * @param  mixed   $message         Message.
 * @param  mixed   $numChars        NumChars.
 * @param  mixed   $alias           Alias.
 * @param  mixed   $breadcrumbs     Breadcrumbs.
 * @param  boolean $hide_left_small Hide title id screen is small.
 * @return string Header HTML
 */
function ui_print_page_header(
    $title,
    $icon='',
    $return=false,
    $help='',
    $godmode=false,
    $options='',
    $modal=false,
    $message='',
    $numChars=GENERIC_SIZE_TEXT,
    $alias='',
    $breadcrumbs='',
    $hide_left_small=false,
    $fav_menu_config=[],
    $dots='',
) {
    global $config;

    $title = io_safe_input_html($title);
    if (($icon == '') && ($godmode == true)) {
        $icon = 'images/gm_setup.png';
    }

    if (($icon == '') && ($godmode == false)) {
        $icon = '';
    }

    if ($godmode == true) {
        $type = 'view';
        $type2 = 'menu_tab_frame_view';
        $separator_class = 'separator';
        $div_style = '';
    } else {
        $type = 'view';
        $type2 = 'menu_tab_frame_view';
        $separator_class = 'separator_view';
        $div_style = '';
        if ($config['pure'] === true) {
            $div_style = 'top:0px;';
        }
    }

    $buffer = '<div id="'.$type2.'" style="'.$div_style.'" >';

    if (!empty($breadcrumbs)) {
        $buffer .= '<div class="menu_tab_left_bc">';
        $buffer .= '<div class="breadcrumbs_container">'.$breadcrumbs.'</div>';
    }

    $buffer .= '<div id="menu_tab_left">';
    $buffer .= '<ul class="mn"><li class="'.$type.'">';

    if (strpos($title, 'Monitoring » Services »') != -1) {
        $title = str_replace('Monitoring » Services » Service Map » ', '', $title);
    }

    $buffer .= '<span>';
    $buffer .= '<span class="breadcrumbs-title">';
    if (empty($alias)) {
        $buffer .= ui_print_truncate_text($title, $numChars);
    } else {
        $buffer .= ui_print_truncate_text($alias, $numChars);
    }

    $buffer .= '</span>';

    if ($modal && enterprise_installed() === false) {
        $buffer .= "
		<div id='".$message."' class='publienterprise right mrgn_top-2px' title='Community version'><img data-title='".__('Enterprise version not installed')."' class='img_help forced_title' data-use_title_for_force_title='1' src='images/alert_enterprise.png'></div>
		";
    }

    if (is_metaconsole() === false) {
        if ($help != '') {
            $buffer .= "<div class='head_help head_tip'>".ui_print_help_icon($help, true, '', '', false, '', true).'</div>';
        }
    }

    if (is_array($fav_menu_config) === true && is_metaconsole() === false) {
        if (count($fav_menu_config) > 0) {
            $buffer .= ui_print_fav_menu(
                $fav_menu_config['id_element'],
                $fav_menu_config['url'],
                $fav_menu_config['label'],
                $fav_menu_config['section']
            );
        }
    }

    $buffer .= '</span>';

    if (is_metaconsole() === true) {
        if ($help != '') {
            $buffer .= "<div class='head_help'>".ui_print_help_icon($help, true, '', 'images/help_30.png').'</div>';
        }
    }

    $buffer .= '</li></ul></div>';

    if (!empty($breadcrumbs)) {
        $buffer .= '</div>';
    }

    if (is_array($options)) {
        $buffer .= '<div id="menu_tab"><ul class="mn">';
        foreach ($options as $key => $option) {
            if (empty($option)) {
                continue;
            } else if ($key === 'separator') {
                continue;
                // $buffer .= '<li class='.$separator_class.'>';
                // $buffer .= '</li>';
            } else {
                if (is_array($option)) {
                    $class = 'nomn';
                    if (isset($option['active'])) {
                        if ($option['active']) {
                            $class = 'nomn_high';
                        }
                    }

                    // Tabs forced to other styles.
                    if (isset($option['godmode']) && $option['godmode']) {
                        $class .= ' tab_godmode';
                    } else if (isset($option['operation']) && ($option['operation'])) {
                        $class .= ' tab_operation';
                    } else {
                        $class .= ($godmode) ? ' tab_godmode' : ' tab_operation';
                    }

                    $buffer .= '<li class="'.$class.' ">';
                    $buffer .= $option['text'];
                    if (isset($option['sub_menu'])) {
                        $buffer .= $option['sub_menu'];
                    }

                    $buffer .= '</li>';
                } else {
                    $buffer .= '<li class="nomn">';
                    $buffer .= $option;
                    $buffer .= '</li>';
                }
            }
        }

        $buffer .= '</ul>';
        if (isset($dots) === true) {
            $buffer .= '<div id="menu_dots">'.$dots.'</div>';
        }

        $buffer .= '</div>';
    } else {
        if ($options != '') {
            $buffer .= '<div id="menu_tab"><ul class="mn"><li>';
            $buffer .= $options;
            $buffer .= '</li></ul>';
            if (isset($dots) === true) {
                $buffer .= '<div id="menu_dots">'.$dots.'</div>';
            }

            $buffer .= '</div>';
        }
    }

    $buffer .= '</div>';

    if ($hide_left_small) {
        $buffer .= '<script>
        $(window).resize(function () {
            hideLeftHeader()
        });

        $(document).ready(function () {
           hideLeftHeader();
        });

        function hideLeftHeader() {
            var right_width = 0;
            $("#menu_tab").find("li").each(function(index) {
                right_width += parseInt($(this).outerWidth(), 10);
            });
          
            if($("#menu_tab").outerWidth() < right_width) {
                $("#menu_tab_left").children().hide()
            } else {
                $("#menu_tab_left").children().show();
            }
        }
    </script>';
    }

    if (!$return) {
        echo $buffer;
    }

    return $buffer;
}


/**
 * Print a input for agent autocomplete, this input search into your
 * pandora DB (or pandoras DBs when you have metaconsole) for agents
 * that have name near to equal that you are writing into the input.
 *
 * This generate a lot of lines of html and javascript code.
 *
 * @param array $parameters Array with several properties:
 * - $parameters['return'] boolean, by default is false
 *   true  - return as html string the code (html and js)
 *   false - print the code.
 *
 * - $parameters['input_name'] the input name (needs to get the value)
 *   string  - The name.
 *   default - "agent_autocomplete_<aleatory_uniq_raw_letters/numbers>"
 *
 * - $parameters['input_id'] the input id (needs to get the value)
 *   string  - The ID.
 *   default - "text-<input_name>"
 *
 * - $parameters['selectbox_group'] the id of selectbox with the group
 *   string  - The ID of selectbox.
 *   default - "" empty string
 *
 * - $parameters['icon_image'] the small icon to show into the input in
 *   the right side.
 *   string  - The url for the image.
 *   default - "images/lightning.png"
 *
 * - $parameters['value'] The initial value to set the input.
 *   string  - The value.
 *   default - "" emtpy string
 *
 * - $parameters['show_helptip'] boolean, by  default is false
 *   true  - print the icon out the field in side right the tiny star
 *           for tip.
 *   false - does not print
 *
 * - $parameters['helptip_text'] The text to show in the tooltip.
 *   string  - The text to show into the tooltip.
 *   default - "Type at least two characters to search." (translate)
 *
 * - $parameters['use_hidden_input_idagent'] boolean, Use a field for
 *   store the id of agent from the ajax query. By default is false.
 *   true  - Use the field for id agent and the sourcecode work with
 *           this.
 *   false - Doesn't use the field (maybe this doesn't exist outer)
 *
 * - $parameters['print_hidden_input_idagent'] boolean, Print a field
 *   for store the id of agent from the ajax query. By default is
 *   false.
 *   true  - Print the field for id agent and the sourcecode work with
 *           this.
 *   false - Doesn't print the field (maybe this doesn't exist outer)
 *
 * - $parameters['hidden_input_idagent_name'] The name of hidden input
 *   for to store the id agent.
 *   string  - The name of hidden input.
 *   default - "agent_autocomplete_idagent_<aleatory_uniq_raw_letters/numbers>"
 *
 * - $parameters['hidden_input_idagent_id'] The id of hidden input
 *   for to store the id agent.
 *   string  - The id of hidden input.
 *   default - "hidden-<hidden_input_idagent_name>"
 *
 * - $parameters['hidden_input_idagent_value'] The initial value to set
 *   the input id agent for store the id agent.
 *   string  - The value.
 *   default - 0
 *
 * - $parameters['size'] The size in characters for the input of agent.
 *   string  - A number of characters.
 *   default - 30
 *
 * - $parameters['maxlength'] The max characters that can store the
 *   input of agent.
 *   string  - A number of characters max to store
 *   default - 100
 *
 * - $parameters['disabled'] Set as disabled the input of agent. By
 *   default is false
 *   true  - Set disabled the input of agent.
 *   false - Set enabled the input of agent.
 *
 * - $parameters['selectbox_id'] The id of select box that stores the
 *   list of modules of agent select.
 *   string - The id of select box.
 *   default - "id_agent_module"
 *
 * - $parameters['add_none_module'] Boolean, add the list of modules
 *   the "none" entry, with value 0. By default is true
 *   true  - add the none entry.
 *   false - does not add the none entry.
 *
 * - $parameters['none_module_text'] Boolean, add the list of modules
 *   the "none" entry, with value 0.
 *   string  - The text to put for none module for example "select a
 *             module"
 *   default - "none" (translate)
 *
 * - $parameters['print_input_server'] Boolean, print the hidden field
 *   to store the server (metaconsole). By default false.
 *   true  - Print the hidden input for the server.
 *   false - Does not print.
 *
 * - $parameters['use_input_server'] Boolean, use the hidden field
 *   to store the server (metaconsole). By default false.
 *   true  - Use the hidden input for the server.
 *   false - Does not print.
 *
 * - $parameters['input_server_name'] The name for hidden field to
 *   store the server.
 *   string  - The name of field for server.
 *   default - "server_<aleatory_uniq_raw_letters/numbers>"
 *
 * - $parameters['input_server_id'] The id for hidden field to store
 *   the server.
 *   string  - The id of field for server.
 *   default - "hidden-<input_server_name>"
 *
 * - $parameters['input_server_value'] The value to store into the
 *   field server.
 *   string  - The name of server.
 *   default - "" empty string
 *
 * - $parameters['metaconsole_enabled'] Boolean, set the sourcecode for
 *   to make some others things that run of without metaconsole. By
 *   default false.
 *   true  - Set the gears for metaconsole.
 *   false - Run as without metaconsole.
 *
 * - $parameters['javascript_ajax_page'] The page to send the ajax
 *   queries.
 *   string  - The url to ajax page, remember the url must be into your
 *             domain (ajax security).
 *   default - "ajax.php"
 *
 * - $parameters['javascript_function_action_after_select'] The name of
 *   function to call after the user select a agent into the list in
 *   the autocomplete field.
 *   string  - The name of function.
 *   default - ""
 *
 * - $parameters['javascript_function_action_after_select_js_call'] The
 *   call of this function to call after user select a agent into the
 *   list in the autocomplete field. Instead the
 *   $parameters['javascript_function_action_after_select'], this is
 *   overwrite the previous element. And this is necesary when you need
 *   to set some params in your custom function.
 *   string  - The call line as javascript code.
 *   default - ""
 *
 * - $parameters['javascript_function_action_into_source'] The source
 *   code as block string to call when the autocomplete starts to get
 *   the data from ajax.
 *   string  - A huge string with your function as javascript.
 *   default - ""
 *
 * - $parameters['javascript'] Boolean, set the autocomplete agent to
 *   use javascript or enabled javascript. By default true.
 *   true  - Enabled the javascript.
 *   false - Disabled the javascript.
 *
 * - $parameters['javascript_is_function_select'] Boolean, set to
 *   enable to call a function when user select a agent in the
 *   autocomplete list. By default false.
 *   true  - Enabled this feature.
 *   false - Disabled this feature.
 *
 * - $parameters['javascript_code_function_select'] The name of
 *   function to call when user select a agent in the autocomplete
 *   list.
 *   string  - The name of function but remembers this function pass
 *             the parameter agent_name.
 *   default - "function_select_<input_name>"
 *
 * - $parameters['javascript_name_function_select'] The source
 *   code as block string to call when user select a agent into the
 *   list in the autocomplete field. Althought use this element, you
 *   need use the previous parameter to set name of your custom
 *   function or call line.
 *   string  - A huge string with your function as javascript.
 *   default - A lot of lines of source code into a string, please this
 *             lines you can read in the source code of function.
 *
 * - $parameters['javascript_change_ajax_params'] The params to pass in
 *   the ajax query for the list of agents.
 *   array   - The associative array with the key and value to pass in
 *             the ajax query.
 *   default - A lot of lines of source code into a string, please this
 *             lines you can read in the source code of function.
 *
 * - $parameters['javascript_function_change'] The source code as block
 *   string with all javascript code to run autocomplete field.
 *   string - The source code javascript into a string.
 *   default - A lot of lines of source code into a string, please this
 *             lines you can read in the source code of function.
 *
 * - $parameters['javascript_document_ready'] Boolean, set the
 *   javascript sourcecode to run with the document is ready. By
 *   default is true.
 *   true  - Set to run when document is ready.
 *   false - Not set to run.
 *
 * - $parameters['javascript_tags'] Boolean, print the html tags for
 *   javascript. By default is true.
 *   true  - Print the javascript tags.
 *   false - Doesn't print the tags.
 *
 * - $parameters['javascript_tags'] Boolean, print the html tags for
 *   javascript. By default is true.
 *   true  - Print the javascript tags.
 *   false - Doesn't print the tags.
 *
 * - $parameters['input_style'] String, Set additional styles to input.
 *
 * @return string HTML code if return parameter is true.
 */
function ui_print_agent_autocomplete_input($parameters)
{
    global $config;

    $text_color = '';
    // Normalize and extract the data from $parameters
    // ------------------------------------------------------------------.
    $return = false;
    // Default value.
    if (isset($parameters['return'])) {
        $return = $parameters['return'];
    }

    $input_name = uniqid('agent_autocomplete_');
    // Default value.
    if (isset($parameters['input_name'])) {
        $input_name = $parameters['input_name'];
    }

    $input_id = 'text-'.$input_name;
    // Default value.
    if (isset($parameters['input_id'])) {
        $input_id = $parameters['input_id'];
    }

    $selectbox_group = '';
    // Default value.
    if (isset($parameters['selectbox_group'])) {
        $selectbox_group = $parameters['selectbox_group'];
    }

    // Default value.
    $icon_agent = 'images/agents@svg.svg';

    if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
        $text_color = 'style="color: white"';
        $icon_agent = 'images/agent_mc.menu.png';
        $background_results = 'background: #111;';
    } else {
        $background_results = 'background: #a8e7eb;';
    }

    $icon_image = html_print_image($icon_agent, true, false, true);
    if (isset($parameters['icon_image'])) {
        $icon_image = $parameters['icon_image'];
    }

    $value = '';
    // Default value.
    if (isset($parameters['value'])) {
        $value = $parameters['value'];
    }

    $show_helptip = true;
    // Default value.
    if (isset($parameters['show_helptip'])) {
        $show_helptip = $parameters['show_helptip'];
    }

    $helptip_text = __('Type at least two characters to search.');
    // Default value.
    if (isset($parameters['helptip_text'])) {
        $helptip_text = $parameters['helptip_text'];
    }

    $use_hidden_input_idagent = false;
    // Default value.
    if (isset($parameters['use_hidden_input_idagent'])) {
        $use_hidden_input_idagent = $parameters['use_hidden_input_idagent'];
    }

    $print_hidden_input_idagent = false;
    // Default value.
    if (isset($parameters['print_hidden_input_idagent'])) {
        $print_hidden_input_idagent = $parameters['print_hidden_input_idagent'];
    }

    $hidden_input_idagent_name = uniqid('agent_autocomplete_idagent_');
    // Default value.
    if (isset($parameters['hidden_input_idagent_name'])) {
        $hidden_input_idagent_name = $parameters['hidden_input_idagent_name'];
    }

    $hidden_input_idagent_id = 'hidden-'.$input_name;
    // Default value.
    if (isset($parameters['hidden_input_idagent_id'])) {
        $hidden_input_idagent_id = $parameters['hidden_input_idagent_id'];
    }

    $hidden_input_idagent_value = (int) get_parameter($hidden_input_idagent_name, 0);
    // Default value.
    if (isset($parameters['hidden_input_idagent_value'])) {
        $hidden_input_idagent_value = $parameters['hidden_input_idagent_value'];
    }

    $size = 30;
    // Default value.
    if (isset($parameters['size'])) {
        $size = $parameters['size'];
    }

    $maxlength = 100;
    // Default value.
    if (isset($parameters['maxlength'])) {
        $maxlength = $parameters['maxlength'];
    }

    $disabled = false;
    // Default value.
    if (isset($parameters['disabled'])) {
        $disabled = $parameters['disabled'];
    }

    $selectbox_id = 'id_agent_module';
    // Default value.
    if (isset($parameters['selectbox_id'])) {
        $selectbox_id = $parameters['selectbox_id'];
    }

    $add_none_module = true;
    // Default value.
    if (isset($parameters['add_none_module'])) {
        $add_none_module = $parameters['add_none_module'];
    }

    $none_module_text = '--';
    // Default value.
    if (isset($parameters['none_module_text'])) {
        $none_module_text = $parameters['none_module_text'];
    }

    $print_input_server = false;
    // Default value.
    if (isset($parameters['print_input_server'])) {
        $print_input_server = $parameters['print_input_server'];
    }

    $print_input_id_server = false;
    // Default value.
    if (isset($parameters['print_input_id_server'])) {
        $print_input_id_server = $parameters['print_input_id_server'];
    }

    $use_input_server = false;
    // Default value.
    if (isset($parameters['use_input_server'])) {
        $use_input_server = $parameters['use_input_server'];
    }

    $use_input_id_server = false;
    // Default value.
    if (isset($parameters['use_input_id_server'])) {
        $use_input_id_server = $parameters['use_input_id_server'];
    }

    $input_server_name = uniqid('server_');
    // Default value.
    if (isset($parameters['input_server_name'])) {
        $input_server_name = $parameters['input_server_name'];
    }

    $input_id_server_name = uniqid('server_');
    // Default value.
    if (isset($parameters['input_id_server_name'])) {
        $input_id_server_name = $parameters['input_id_server_name'];
    }

    $input_server_id = 'hidden-'.$input_server_name;
    // Default value.
    if (isset($parameters['input_server_id'])) {
        $input_server_id = $parameters['input_server_id'];
    }

    $input_id_server_id = 'hidden-'.$input_id_server_name;
    // Default value.
    if (isset($parameters['input_id_server_id'])) {
        $input_id_server_id = $parameters['input_id_server_id'];
    }

    $input_server_value = '';
    // Default value.
    if (isset($parameters['input_server_value'])) {
        $input_server_value = $parameters['input_server_value'];
    }

    $input_id_server_value = '';
    // Default value.
    if (isset($parameters['input_id_server_value'])) {
        $input_id_server_value = $parameters['input_id_server_value'];
    }

    $from_ux_transaction = '';
    // Default value.
    if (isset($parameters['from_ux'])) {
        $from_ux_transaction = $parameters['from_ux'];
    }

    $from_wux_transaction = '';
    // Default value.
    if (isset($parameters['from_wux'])) {
        $from_wux_transaction = $parameters['from_wux'];
    }

    $cascade_protection = false;
    // Default value.
    if (isset($parameters['cascade_protection'])) {
        $cascade_protection = $parameters['cascade_protection'];
    }

    $metaconsole_enabled = false;
    // Default value.
    if (isset($parameters['metaconsole_enabled'])) {
        $metaconsole_enabled = $parameters['metaconsole_enabled'];
    } else {
        // If metaconsole_enabled param is not setted then pick source configuration.
        $metaconsole_enabled = is_metaconsole();
    }

    $get_only_string_modules = false;
    if (isset($parameters['get_only_string_modules'])) {
        $get_only_string_modules = true;
    }

    $no_disabled_modules = true;
    if (isset($parameters['no_disabled_modules'])) {
        $no_disabled_modules = $parameters['no_disabled_modules'];
    }

    $spinner_image = html_print_image('images/spinner.gif', true, false, true);
    if (isset($parameters['spinner_image'])) {
        $spinner_image = $parameters['spinner_image'];
    }

    // Javascript configurations
    // ------------------------------------------------------------------.
    $javascript_ajax_page = ui_get_full_url('ajax.php', false, false, false);
    // Default value.
    if (isset($parameters['javascript_ajax_page'])) {
        $javascript_ajax_page = $parameters['javascript_ajax_page'];
    }

    $javascript_function_action_after_select = '';
    // Default value.
    $javascript_function_action_after_select_js_call = '';
    // Default value.
    if (isset($parameters['javascript_function_action_after_select'])) {
        $javascript_function_action_after_select = $parameters['javascript_function_action_after_select'];
        $javascript_function_action_after_select_js_call = $javascript_function_action_after_select.'();';
    }

    if (isset($parameters['javascript_function_action_after_select_js_call'])) {
        if ($javascript_function_action_after_select_js_call != $parameters['javascript_function_action_after_select_js_call']
        ) {
            $javascript_function_action_after_select_js_call = $parameters['javascript_function_action_after_select_js_call'];
        }
    }

    $javascript_function_action_into_source = '';
    // Default value.
    $javascript_function_action_into_source_js_call = '';
    // Default value.
    if (isset($parameters['javascript_function_action_into_source'])) {
        $javascript_function_action_into_source = $parameters['javascript_function_action_into_source'];
        $javascript_function_action_into_source_js_call = $javascript_function_action_into_source.'();';
    }

    if (isset($parameters['javascript_function_action_into_source_js_call'])) {
        if ($javascript_function_action_into_source_js_call != $parameters['javascript_function_action_into_source_js_call']
        ) {
            $javascript_function_action_into_source_js_call = $parameters['javascript_function_action_into_source_js_call'];
        }
    }

    $javascript = true;
    // Default value.
    if (isset($parameters['javascript'])) {
        $javascript = $parameters['javascript'];
    }

    $get_order_json = false;
    if (isset($parameters['get_order_json'])) {
        $get_order_json = true;
    }

    $javascript_is_function_select = false;
    // Default value.
    if (isset($parameters['javascript_is_function_select'])) {
        $javascript_is_function_select = $parameters['javascript_is_function_select'];
    }

    $javascript_name_function_select = 'function_select_'.$input_name;
    // Default value.
    if (isset($parameters['javascript_name_function_select'])) {
        $javascript_name_function_select = $parameters['javascript_name_function_select'];
    }

    $inputStyles = ($parameters['input_style'] ?? '');

    if ($from_ux_transaction != '') {
        $javascript_code_function_select = '
		function function_select_'.$input_name.'(agent_name) {
			$("#'.$selectbox_id.'").empty();
			
			var inputs = [];
			inputs.push ("id_agent=" + $("#'.$hidden_input_idagent_id.'").val());
			inputs.push ("get_agent_transactions=1");
			inputs.push ("page=enterprise/include/ajax/ux_transaction.ajax");
			
			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="'.$javascript_ajax_page.'",
				dataType: "json",
				success: function (data) {
					if (data) {
						$("#'.$selectbox_id.'").append ($("<option value=0>None</option>"));
						jQuery.each (data, function (id, value) {
							$("#'.$selectbox_id.'").append ($("<option value=" + id + ">" + value + "</option>"));
						});
					}
				}
			});
			
			return false;
		}
		';
    } else if ($from_wux_transaction != '') {
        if (is_metaconsole() === true) {
            $inputNode = 'inputs.push ("server_id=" + $("#'.$input_id_server_id.'").val());';
        } else {
            $inputNode = '';
        }

        $javascript_code_function_select = '
		function function_select_'.$input_name.'(agent_name) {
			$("#'.$selectbox_id.'").empty();
			
			var inputs = [];
			inputs.push ("id_agent=" + $("#'.$hidden_input_idagent_id.'").val());
            inputs.push ("get_agent_transactions=1");
			inputs.push ("page=enterprise/include/ajax/wux_transaction.ajax");
			'.$inputNode.'

			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="'.$javascript_ajax_page.'",
				dataType: "json",
				success: function (data) {
					if (data) {
						$("#'.$selectbox_id.'").append ($("<option value=0>None</option>"));
						jQuery.each (data, function (id, value) {
							$("#'.$selectbox_id.'").append ($("<option value=" + id + ">" + value + "</option>"));
						});
					}
				}
			});
			
			return false;
		}
		';
    } else {
        $javascript_code_function_select = '
		function function_select_'.$input_name.'(agent_name) {
			
			$("#'.$selectbox_id.'").empty ();
			
			var inputs = [];
			inputs.push ("agent_name=" + agent_name);
			inputs.push ("delete_pending=0");
			inputs.push ("get_agent_modules_json=1");
			inputs.push ("page=operation/agentes/ver_agente");
			
			if ('.((int) !$metaconsole_enabled).') {
				inputs.push ("force_local_modules=1");
			}

			inputs.push ("get_order_json=1");

			if ('.((int) $get_only_string_modules).') {
				inputs.push ("get_only_string_modules=1");
			}

            if ('.((int) $no_disabled_modules).') {
                inputs.push ("disabled=0");
            }

			if ('.((int) $metaconsole_enabled).') {
				if (('.((int) $use_input_server).')
						|| ('.((int) $print_input_server).')) {
					inputs.push ("server_name=" + $("#'.$input_server_id.'").val());
				}
				
				if (('.((int) $use_input_id_server).')
						|| ('.((int) $print_input_id_server).')) {
					inputs.push ("server_id=" + $("#'.$input_id_server_id.'").val());
				}
				
			}
			
			if (('.((int) $print_hidden_input_idagent).')
				|| ('.((int) $use_hidden_input_idagent).')) {
				
				inputs.push ("id_agent=" + $("#'.$hidden_input_idagent_id.'").val());
			}
			
			jQuery.ajax ({
				data: inputs.join ("&"),
				type: "POST",
				url: action="'.$javascript_ajax_page.'",
				dataType: "json",
				success: function (data) {
                    if (Array.isArray(data) === true) {
                        data.sort(function(a, b) {
                            var textA = a.nombre.toUpperCase();
                            var textB = b.nombre.toUpperCase();
                            return (textA < textB) ? -1 : (textA > textB) ? 1 : 0;
                        });
                    }

					if ('.((int) $add_none_module).') {
						$("#'.$selectbox_id.'")
							.append($("<option></option>")
							.attr("value", 0).text("'.$none_module_text.'"));
					}
					
					jQuery.each (data, function(i, val) {
						s = js_html_entity_decode(val["nombre"]);
						$("#'.$selectbox_id.'")
							.append ($("<option></option>")
							.attr("value", val["id_agente_modulo"]).text (s));
					});
					if('.(int) $cascade_protection.' == 0){
						$("#'.$selectbox_id.'").enable();
					}
					$("#'.$selectbox_id.'").fadeIn ("normal");
				}
			});
			
			return false;
		}
		';
    }

    if (isset($parameters['javascript_code_function_select'])) {
        $javascript_code_function_select = $parameters['javascript_code_function_select'];
    }

    // ============ INIT javascript_change_ajax_params ==================
    // Default value.
    $javascript_page = 'include/ajax/agent';
    if (isset($parameters['javascript_page'])) {
        $javascript_page = $parameters['javascript_page'];
    }

    $javascript_change_ajax_params_original = [
        'page'          => '"'.$javascript_page.'"',
        'search_agents' => 1,
        'id_group'      => 'function() {
				var group_id = 0;
				
				if ('.((int) !empty($selectbox_group)).') {
					group_id = $("#'.$selectbox_group.'").val();
				}
				
				return group_id;
			}',
        'q'             => 'term',
    ];

    if (isset($parameters['delete_offspring_agents']) === true) {
        $javascript_change_ajax_params_original['delete_offspring_agents'] = $parameters['delete_offspring_agents'];
    }

    if (!$metaconsole_enabled) {
        $javascript_change_ajax_params_original['force_local'] = 1;
    }

    if (isset($parameters['javascript_change_ajax_params'])) {
        $javascript_change_ajax_params = [];

        $found_page = false;
        foreach ($parameters['javascript_change_ajax_params'] as $key => $param_ajax) {
            if ($key == 'page') {
                $found_page = true;
                if ($javascript_page != $param_ajax) {
                    $javascript_change_ajax_params['page'] = $param_ajax;
                } else {
                    $javascript_change_ajax_params['page'] = $javascript_page;
                }
            } else {
                $javascript_change_ajax_params[$key] = $param_ajax;
            }
        }

        if (!$found_page) {
            $javascript_change_ajax_params['page'] = $javascript_page;
        }
    } else {
        $javascript_change_ajax_params = $javascript_change_ajax_params_original;
    }

    $first = true;
    $javascript_change_ajax_params_text = 'var data_params = {';
    foreach ($javascript_change_ajax_params as $key => $param_ajax) {
        if (!$first) {
            $javascript_change_ajax_params_text .= ",\n";
        } else {
            $first = false;
        }

        $javascript_change_ajax_params_text .= '"'.$key.'":'.$param_ajax;
    }

    $javascript_change_ajax_params_text .= '};';
    // ============ END javascript_change_ajax_params ===================
    $javascript_function_change = '';
    // Default value.
    $javascript_function_change .= '
        function setInputBackground(inputId, image) {
            $("#"+inputId)
            .attr("style", "background-image: url(\'"+image+"\'); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$inputStyles.'");
        }

        $(document).ready(function () {
            $("#'.$input_id.'").focusout(function (e) {
                setTimeout(() => {
                    let iconImage = "'.$icon_image.'";
                    $("#'.$input_id.'").attr("style", "background-image: url(\'"+iconImage+"\'); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$inputStyles.'");
                }, 100);
            });
        });

		function set_functions_change_autocomplete_'.$input_name.'() {
			var cache_'.$input_name.' = {};

			$("#'.$input_id.'").autocomplete({
				minLength: 2,
				source: function( request, response ) {
					var term = request.term; //Word to search
					'.$javascript_change_ajax_params_text.'
					var groupId = data_params.id_group();

					// Index cache by group Id
					if (cache_'.$input_name.'[groupId] == null) {
						cache_'.$input_name.'[groupId] = {};
					}

					//Set loading
                    setInputBackground("'.$input_id.'", "'.$spinner_image.'");
					
					//Function to call when the source
					if ('.((int) !empty($javascript_function_action_into_source_js_call)).') {
						'.$javascript_function_action_into_source_js_call.'
					}
					
					//==== CACHE CODE ==================================
					//Check the cache
					var found = false;
					if (term in cache_'.$input_name.'[groupId]) {
						response(cache_'.$input_name.'[groupId][term]);
						
						//Set icon
						setInputBackground("'.$input_id.'", "'.$icon_image.'");
						return;
					}
					else {
						//Check if other terms cached start with same
						//letters.
						//TODO: At the moment DISABLED CODE.
						/*
						for (i = 1; i < term.length; i++) {
							var term_match = term.substr(0, term.length - i);
							
							$.each(cache_'.$input_name.'[groupId], function (oldterm, olddata) {
								var pattern = new RegExp("^" + term_match + ".*","gi");
								
								if (oldterm.match(pattern)) {
									response(cache_'.$input_name.'[groupId][oldterm]);
									
									found = true;

									//Set icon
                                    setInputBackground("'.$input_id.'", "'.$icon_image.'");
									return;
								}
							});
							
							if (found) {
								break;
							}
						}
						*/
					}
					//==================================================
					
					
					if (found) {
						//Set icon
                        setInputBackground("'.$input_id.'", "'.$icon_image.'");
						
						select_item_click = 0;
      
						return;
					}
					
					jQuery.ajax ({
						data: data_params,
						type: "POST",
						url: action="'.$javascript_ajax_page.'",
						dataType: "json",
						success: function (data) {
								cache_'.$input_name.'[groupId][term] = data; //Save the cache
								
								response(data);
								
								//Set icon
                                setInputBackground("'.$input_id.'", "'.$icon_image.'");
                                select_item_click = 0;
								
								return;
							}
						});

					return;
				},
				//---END source-----------------------------------------
				
				
				select: function( event, ui ) {
					var agent_name = ui.item.alias;
					var agent_id = ui.item.id;
					var server_name = "";
					var server_id = "";
					
					
					if ('.((int) $metaconsole_enabled).') {
						server_name = ui.item.server;
					}
					else {
						server_name = ui.item.ip;
					}
					
					if (('.((int) $use_input_id_server).')
						|| ('.((int) $print_input_id_server).')) {
						server_id = ui.item.id_server;
					}

					//Put the name
					$(this).val(agent_name);
					
					if (('.((int) $print_hidden_input_idagent).')
						|| ('.((int) $use_hidden_input_idagent).')) {
						$("#'.$hidden_input_idagent_id.'").val(agent_id);
					}
					
					//Put the server id into the hidden input
					if (('.((int) $use_input_server).')
						|| ('.((int) $print_input_server).')) {
						$("#'.$input_server_id.'").val(server_name);
					}
					
					//Put the server id into the hidden input
					if (('.((int) $use_input_id_server).')
						|| ('.((int) $print_input_id_server).')) {
						$("#'.$input_id_server_id.'").val(server_id);
					}
					
					//Call the function to select (example fill the modules)
					if ('.((int) $javascript_is_function_select).') {
						'.$javascript_name_function_select.'(agent_name);
					}
					
					//Function to call after the select
					if ('.((int) !empty($javascript_function_action_after_select_js_call)).') {
						'.$javascript_function_action_after_select_js_call.'
					}
					
					select_item_click = 1;
					
					return false;
				}
				})
			.data("ui-autocomplete")._renderItem = function( ul, item ) {
				if (item.ip == "") {
					text = "<a>" + item.alias+ "</a>";
				}
				else {
					text = "<a>" + item.alias
						+ "<br><span style=\"font-size: 70%; font-style: italic;\">IP:" + item.ip + "</span></a>";
				}
				
				switch (item.filter) {
					default:
					case \'agent\':
						return $("<li style=\'background: #DFFFC4;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'address\':
						return $("<li style=\'background: #F7CFFF;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'description\':
						return $("<li style=\'background: #FEFCC6;\'></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
					case \'alias\':
						return $("<li style=\"'.$background_results.'\"></li>")
							.data("item.autocomplete", item)
							.append(text)
							.appendTo(ul);
						break;
				}
				
				
			};
			
			//Force the size of autocomplete
			$(".ui-autocomplete").css("max-height", "100px");
			$(".ui-autocomplete").css("overflow-y", "auto");
			/* prevent horizontal scrollbar */
			$(".ui-autocomplete").css("overflow-x", "hidden");
			/* add padding to account for vertical scrollbar */
			$(".ui-autocomplete").css("padding-right", "20px");
			
			//Force to style of items
			$(".ui-autocomplete").css("text-align", "left");
		}';

    if (isset($parameters['javascript_function_change'])) {
        $javascript_function_change = $parameters['javascript_function_change'];
    }

    $javascript_document_ready = true;
    // Default value.
    if (isset($parameters['javascript_document_ready'])) {
        $javascript_document_ready = $parameters['javascript_document_ready'];
    }

    $javascript_tags = true;
    // Default value.
    if (isset($parameters['javascript_tags'])) {
        $javascript_tags = $parameters['javascript_tags'];
    }

    $disabled_javascript_on_blur_function = false;
    // Default value.
    if (isset($parameters['disabled_javascript_on_blur_function'])) {
        $disabled_javascript_on_blur_function = $parameters['disabled_javascript_on_blur_function'];
    }

    $javascript_on_blur_function_name = 'function_on_blur_'.$input_name;
    // Default value.
    if (isset($parameters['javascript_on_blur_function_name'])) {
        $javascript_on_blur_function_name = $parameters['javascript_on_blur_function_name'];
    }

    $check_only_empty_javascript_on_blur_function = false;
    // Default value.
    if (isset($parameters['check_only_empty_javascript_on_blur_function'])) {
        $check_only_empty_javascript_on_blur_function = $parameters['check_only_empty_javascript_on_blur_function'];
    }

    // Default value.
    $javascript_on_blur = '
		/*
		This function is a callback when the autocomplete agent
		input lost the focus.
		*/
		function '.$javascript_on_blur_function_name.'() {
			input_value = $("#'.$input_id.'").val();
			
			if (input_value.length < 2) {
				if (('.((int) $print_hidden_input_idagent).')
					|| ('.((int) $use_hidden_input_idagent).')) {
					$("#'.$hidden_input_idagent_id.'").val(0);
				}
				
				//Put the server id into the hidden input
				if (('.((int) $use_input_server).')
					|| ('.((int) $print_input_server).')) {
					$("#'.$input_server_id.'").val("");
				}
				
				//Put the server id into the hidden input
				if (('.((int) $use_input_id_server).')
					|| ('.((int) $print_input_id_server).')) {
					$("#'.$input_id_server_id.'").val("");
				}
				
				return;
			}
			
			if (select_item_click) {
                select_item_click = 0;
                $("#'.$input_id.'")
                .attr("style", "background-image: url(\"'.$spinner_image.'\"); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$inputStyles.'");
				return;
			} else {
                // Clear selectbox if item is not selected.
                $("#'.$selectbox_id.'").empty();
                $("#'.$selectbox_id.'").append($("<option value=0>'.__('Select an Agent first').'</option>"));
                $("#'.$selectbox_id.'").attr("disabled", "disabled");
                // Not allow continue on blur .
                if ('.((int) $check_only_empty_javascript_on_blur_function).') {
                    return
                }
            }

			//Set loading
			$("#'.$input_id.'")
                .attr("style", "background-image: url(\"'.$spinner_image.'\"); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$inputStyles.'");
			var term = input_value; //Word to search
			
			'.$javascript_change_ajax_params_text.'
			
			if ('.((int) !$metaconsole_enabled).') {
				data_params[\'force_local\'] = 1;
			}
			
			jQuery.ajax ({
				data: data_params,
				type: "POST",
				url: action="'.$javascript_ajax_page.'",
				dataType: "json",
				success: function (data) {
						if (data.length < 2) {
							//Set icon
							$("#'.$input_id.'").attr("style", "background-image: url(\"'.$spinner_image.'\"); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$inputStyles.'");
							return;
						}

						var agent_name = data[0].name;
						var agent_id = data[0].id;
						var server_name = "";
						var server_id = "";
						
						if ('.((int) $metaconsole_enabled).') {
							server_name = data[0].server;
						}
						else {
							server_name = data[0].ip;
						}
						
						if (('.((int) $use_input_id_server).')
						|| ('.((int) $print_input_id_server).')) {
							server_id = data[0].id_server;
						}
						
						if (('.((int) $print_hidden_input_idagent).')
							|| ('.((int) $use_hidden_input_idagent).')) {
							$("#'.$hidden_input_idagent_id.'").val(agent_id);
						}
						
						//Put the server id into the hidden input
						if (('.((int) $use_input_server).')
							|| ('.((int) $print_input_server).')) {
							$("#'.$input_server_id.'").val(server_name);
						}
						
						//Put the server id into the hidden input
						if (('.((int) $use_input_id_server).')
							|| ('.((int) $print_input_id_server).')) {
							$("#'.$input_id_server_id.'").val(server_id);
						}
						
						//Call the function to select (example fill the modules)
						if ('.((int) $javascript_is_function_select).') {
							'.$javascript_name_function_select.'(agent_name);
						}
						
						//Function to call after the select
						if ('.((int) !empty($javascript_function_action_after_select_js_call)).') {
							'.$javascript_function_action_after_select_js_call.'
						}

						//Set icon
						$("#'.$input_id.'")
                            .attr("style", "background: url(\"'.$icon_image.'\") 97% center no-repeat; background-size: 20px; width:100%; '.$inputStyles.'")
						return;
					}
				});
		}
		';
    if (isset($parameters['javascript_on_blur'])) {
        $javascript_on_blur = $parameters['javascript_on_blur'];
    }

    // ------------------------------------------------------------------.
    $html = '';

    $text_color = '';
    if ($config['style'] === 'pandora_black' && is_metaconsole() === false) {
        $text_color = 'color: white';
    }

    $attrs = [];
    $attrs['style'] = 'background-image: url('.$icon_image.'); background-repeat: no-repeat; background-position: 97% center; background-size: 20px; width:100%; '.$text_color.' '.$inputStyles.'';

    if (!$disabled_javascript_on_blur_function) {
        $attrs['onblur'] = $javascript_on_blur_function_name.'()';
    }

    $html = html_print_input_text_extended(
        $input_name,
        $value,
        $input_id,
        $helptip_text,
        $size,
        $maxlength,
        $disabled,
        '',
        $attrs,
        true
    );
    if ($show_helptip) {
        $html .= ui_print_input_placeholder($helptip_text, true);
    }

    if ($print_hidden_input_idagent) {
        $html .= html_print_input_hidden_extended(
            $hidden_input_idagent_name,
            $hidden_input_idagent_value,
            $hidden_input_idagent_id,
            true
        );
    }

    if ($print_input_server) {
        $html .= html_print_input_hidden_extended(
            $input_server_name,
            $input_server_value,
            $input_server_id,
            true
        );
    }

    if ($print_input_id_server) {
        $html .= html_print_input_hidden_extended(
            $input_id_server_name,
            $input_id_server_value,
            $input_id_server_id,
            true
        );
    }

    // Write the javascript.
    if ($javascript) {
        if ($javascript_tags) {
            $html .= '<script type="text/javascript">
				/* <![CDATA[ */';
        }

        $html .= 'var select_item_click = 0;'."\n";

        $html .= $javascript_function_change;
        if ($javascript_is_function_select) {
            $html .= $javascript_code_function_select;
        }

        $html .= $javascript_on_blur;

        if ($javascript_document_ready) {
            $html .= '$(document).ready (function () {
				set_functions_change_autocomplete_'.$input_name.'();
				});';
        }

        if ($javascript_tags) {
            $html .= '/* ]]> */
				</script>';
        }
    }

    if ($return) {
        return $html;
    } else {
        echo $html;
    }
}


/**
 * Return error strings (title and message) for each error code
 *
 * @param string $error_code Error code.
 *
 * @return array.
 */
function ui_get_error($error_code='')
{
    // XXX: Deprecated. Pandora shouldn't go inside this.
    return [
        'title'   => __('Unhandled error'),
        'message' => __('An unhandled error occurs'),
    ];
}


/**
 * Include time picker.
 *
 * @param boolean $echo_tags Tags.
 *
 * @return void
 */
function ui_include_time_picker($echo_tags=false)
{
    if (is_ajax() || $echo_tags) {
        echo '<script type="text/javascript" src="'.ui_get_full_url(false, false, false, false).'include/javascript/jquery.ui-timepicker-addon.js"></script>';
    } else {
        ui_require_jquery_file('ui-timepicker-addon');
    }

    if (file_exists('include/javascript/i18n/jquery-ui-timepicker-'.substr(get_user_language(), 0, 2).'.js')) {
        echo '<script type="text/javascript" src="'.ui_get_full_url('include/javascript/i18n/jquery-ui-timepicker-'.substr(get_user_language(), 0, 2).'.js', false, false, false).'"></script>';
    }
}


/**
 * Print string value.
 *
 * @param string  $value            Value.
 * @param integer $id_agente_module Id_agente_module.
 * @param integer $current_interval Current_interval.
 * @param string  $module_name      Module_name.
 *
 * @return string HTML.
 */
function ui_print_module_string_value(
    $value,
    $id_agente_module,
    $current_interval,
    $module_name=null,
    $server_id=0
) {
    global $config;

    if ($module_name == null) {
        $module_name = modules_get_agentmodule_name($id_agente_module);
    }

    $id_type_web_content_string = db_get_value(
        'id_tipo',
        'ttipo_modulo',
        'nombre',
        'web_content_string'
    );

    $is_web_content_string = (bool) db_get_value_filter(
        'id_agente_modulo',
        'tagente_modulo',
        [
            'id_agente_modulo' => $id_agente_module,
            'id_tipo_modulo'   => $id_type_web_content_string,
        ]
    );

    // Fixed the goliat sends the strings from web
    // without HTML entities.
    if ($is_web_content_string) {
        $value = io_safe_input($value);
    }

    if (isset($module) === false) {
        $module['datos'] = '';
    }

    $is_snapshot = is_snapshot_data($module['datos']);
    $is_large_image = is_text_to_black_string($module['datos']);
    if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
        $row[7] = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
    }

    $is_snapshot = is_snapshot_data($value);
    $is_large_image = is_text_to_black_string($value);
    if (($config['command_snapshot']) && ($is_snapshot || $is_large_image)) {
        $link = ui_get_snapshot_link(
            [
                'id_module'   => $id_agente_module,
                'last_data'   => $value,
                'interval'    => $current_interval,
                'module_name' => $module_name,
                'id_node'     => $server_id ? $server_id : 0,
            ]
        );
        $salida = ui_get_snapshot_image($link, $is_snapshot).'&nbsp;&nbsp;';
    } else {
        $sub_string = substr(io_safe_output($value), 0, 12);
        if ($value == $sub_string) {
            if ($value == 0 && !$sub_string) {
                $salida = 0;
            } else {
                $salida = $value;
            }
        } else {
            // Fixed the goliat sends the strings from web
            // without HTML entities.
            if ($is_web_content_string) {
                $sub_string = substr($value, 0, 12);
            } else {
                // Fixed the data from Selenium Plugin.
                if ($value != strip_tags($value)) {
                    $value = io_safe_input($value);
                    $sub_string = substr($value, 0, 12);
                } else {
                    $sub_string = substr(io_safe_output($value), 0, 12);
                }
            }

            if ($value == $sub_string) {
                $salida = $value;
            } else {
                $value = preg_replace('/</', '&lt;', $value);
                $value = preg_replace('/>/', '&gt;', $value);
                $value = preg_replace('/\n/i', '<br>', $value);
                $value = preg_replace('/\s/i', '&nbsp;', $value);

                $title_dialog = modules_get_agentmodule_agent_alias($id_agente_module).' / '.$module_name;
                $salida = '<div '."id='hidden_value_module_".$id_agente_module."'
					class='title_dialog' title='".$title_dialog."'>".$value.'</div><span '."id='value_module_".$id_agente_module."'
					class='nowrap'>".'<span id="value_module_text_'.$id_agente_module.'">'.$sub_string.'</span> '."<a href='javascript: toggle_full_value(".$id_agente_module.")'>".html_print_image('images/zoom.png', true, ['style' => 'max-height: 20px; vertical-align: middle;', 'class' => 'invert_filter']).'</a></span>';
            }
        }
    }

    return $salida;
}


/**
 * Displays a tag list.
 *
 * @param string $title Title.
 * @param array  $tags  Tags.
 *
 * @return void
 */
function ui_print_tags_view($title='', $tags=[])
{
    $tv = '';
    if (!empty($title)) {
        $tv .= '<div class="tag-wrapper">';
        $tv .= '<h3>'.$title.'</h3>';
    } else {
        $tv .= '<div class="tag-wrapper pdd_t_10px">';
    }

    foreach ($tags as $tag) {
        $tv .= '<div class=pandora-tag>';
            $tv .= '<span class=pandora-tag-title>';
                $tv .= $tag['title'];
            $tv .= '</span>';

            $tv .= '<span class=pandora-tag-value>';
                $tv .= $tag['value'];
            $tv .= '</span>';
        $tv .= '</div>';
    }

    $tv .= '</div>';
    echo $tv;
}


/**
 * Gets the link to open a snapshot into a new page.
 *
 * @param array   $params      Params to build the link (see  $default_params).
 * @param boolean $only_params Flag to choose de return value:
 *            true: Get the four params required in the function of pandora.js winopen_var (js use)
 *            false: Get an inline winopen_var function call (php user).
 *
 * @return string Link.
 */
function ui_get_snapshot_link($params, $only_params=false)
{
    global $config;

    $default_params = [
        // Id_agente_modulo.
        'id_module'   => 0,
        'module_name' => '',
        'interval'    => 300,
        'timestamp'   => 0,
        'id_node'     => 0,
    ];

    // Merge default params with passed params.
    $params = array_merge($default_params, $params);

    // First parameter of js winopeng_var.
    $page = ui_get_full_url('operation/agentes/snapshot_view.php', false, false, false);

    $url = $page.'?id='.$params['id_module'].'&label='.rawurlencode(urlencode(io_safe_output($params['module_name']))).'&id_node='.$params['id_node'];

    if ($params['timestamp'] != 0) {
        $url .= '&timestamp='.$params['timestamp'];
    }

    if ($params['interval'] != 0) {
        $url .= '&refr='.$params['interval'];
    }

    // Second parameter of js winopeng_var.
    $win_handle = dechex(crc32('snapshot_'.$params['id_module']));

    $link_parts = [
        $url,
        $win_handle,
        700,
        480,
    ];

    // Return only the params to js execution.
    if ($only_params) {
        return $link_parts;
    }

    // Return the function call to inline js execution.
    return "winopeng_var('".implode("', '", $link_parts)."')";
}


/**
 * Get the snapshot image with the link to open a snapshot into a new page
 *
 * @param string  $link     Built link.
 * @param boolean $is_image Picture image or list image.
 *
 * @return string HTML anchor link with image.
 */
function ui_get_snapshot_image($link, $is_image)
{
    $image_name = ($is_image) ? 'photo.png' : 'default_list.png';

    $link = '<a href="javascript:'.$link.'">'.html_print_image(
        'images/'.$image_name,
        true,
        [
            'border' => '0',
            'alt'    => '',
            'title'  => __('Snapshot view'),
            'style'  => 'max-height: 20px; vertical-align: middle;',
            'class'  => 'invert_filter',
        ]
    ).'</a>';

    return $link;
}


/**
 * Show warning timezone missmatch.
 *
 * @param string  $tag    Tag.
 * @param boolean $return Return.
 *
 * @return string HTML.
 */
function ui_get_using_system_timezone_warning($tag='h3', $return=true)
{
    global $config;

    $user_offset = ((-get_fixed_offset() / 60) / 60);

    if ($config['timezone'] != date_default_timezone_get()) {
        $message = sprintf(
            __('These controls are using the timezone of the system (%s) instead of yours (%s). The difference with your time zone in hours is %s.'),
            $config['timezone'],
            date_default_timezone_get(),
            ($user_offset > 0) ? '+'.$user_offset : $user_offset
        );
        return ui_print_info_message($message, '', $return, $tag);
    } else {
        return '';
    }

}


/**
 * Get the custom docs logo
 *
 * @return string with the path to logo. False if it should not be displayed.
 */
function ui_get_docs_logo()
{
    global $config;

    // Default logo to open version (enterprise_installed function only works in login status).
    if (!file_exists(ENTERPRISE_DIR.'/load_enterprise.php') || !isset($config['custom_docs_logo'])) {
        if (is_metaconsole() === true) {
            return '../../images/icono_docs.png';
        }

        return 'images/icono_docs.png';
    }

    if ($config['custom_docs_logo'] === '') {
        return false;
    }

    return 'enterprise/images/custom_general_logos/'.$config['custom_docs_logo'];
}


/**
 * Get the custom support logo
 *
 * @return string with the path to logo. False if it should not be displayed.
 */
function ui_get_support_logo()
{
    global $config;

    // Default logo to open version (enterprise_installed function only works in login status).
    if (!file_exists(ENTERPRISE_DIR.'/load_enterprise.php') || !isset($config['custom_support_logo'])) {
        if (is_metaconsole() === true) {
            return '../../images/icono_support.png';
        }

        return 'images/icono_support.png';
    }

    if ($config['custom_support_logo'] === '') {
        return false;
    }

    return 'enterprise/images/custom_general_logos/'.$config['custom_support_logo'];
}


/**
 * Get the custom header logo
 *
 * @param boolean $white_bg Using white bg or not.
 *
 * @return string with the path to logo. If it is not set, return the default value.
 */
function ui_get_custom_header_logo($white_bg=false)
{
    global $config;

    if (empty($config['enterprise_installed'])) {
        return 'images/pandora_tinylogo_open.png';
    }

    $stored_logo = (is_metaconsole()) ? (($white_bg) ? $config['meta_custom_logo_white_bg'] : $config['meta_custom_logo']) : (($white_bg) ? $config['custom_logo_white_bg'] : $config['custom_logo']);
    if (empty($stored_logo)) {
        return 'images/pandora_tinylogo.png';
    }

    return 'enterprise/images/custom_logo/'.$stored_logo;
}


/**
 * Get the central networkmap logo
 *
 * @return string with the path to logo. If it is not set, return the default.
 */
function ui_get_logo_to_center_networkmap()
{
    global $config;

    if ((!enterprise_installed()) || empty($config['custom_network_center_logo'])) {
        return 'images/networkmap/bola_pandora_network_maps.png';
    }

    return 'enterprise/images/custom_general_logos/'.$config['custom_support_logo'];
}


/**
 * Get the mobile console login logo
 *
 * @return string with the path to logo. If it is not set, return the default.
 */
function ui_get_mobile_login_icon()
{
    global $config;

    if ((!enterprise_installed()) || empty($config['custom_mobile_console_logo'])) {
        return is_metaconsole() ? 'mobile/images/metaconsole_mobile.png' : 'mobile/images/pandora_mobile_console.png';
    }

    return 'enterprise/images/custom_general_logos/'.$config['custom_mobile_console_logo'];
}


/**
 * Get the favicon
 *
 * @return string with the path to logo. If it is not set, return the default.
 */
function ui_get_favicon()
{
    global $config;

    if (empty($config['custom_favicon'])) {
        return (!is_metaconsole()) ? 'images/pandora.ico' : '/images/custom_favicon/favicon_meta.ico';
    }

    return 'images/custom_favicon/'.$config['custom_favicon'];
}


/**
 * Show sorting arrows for tables
 *
 * @param string $url_up     Url_up.
 * @param string $url_down   Url_down.
 * @param string $selectUp   SelectUp.
 * @param string $selectDown SelectDown.
 *
 * @return string  HTML anchor link with the arrow icon.
 */
function ui_get_sorting_arrows($url_up, $url_down, $selectUp, $selectDown)
{
    $arrow_up = 'images/sort_up_black.png';
    $arrow_down = 'images/sort_down_black.png';

    if (is_metaconsole()) {
        $arrow_up = 'images/sort_up_black.png';
        $arrow_down = 'images/sort_down_black.png';
    }

    // Green arrows for the selected.
    if ($selectUp === true) {
        $arrow_up = 'images/sort_up_green.png';
    }

    if ($selectDown === true) {
        $arrow_down = 'images/sort_down_green.png';
    }

    return '<span class="sort_arrow">
                <a href="'.$url_up.'">'.html_print_image($arrow_up, true, ['alt' => 'up']).'</a>
                <a href="'.$url_down.'">'.html_print_image($arrow_down, true, ['alt' => 'down']).'</a>
            </span>';
}


/**
 * Show breadcrums in the page titles
 *
 * @param string $tab_name Tab name.
 *
 * @return string  HTML anchor with the name of the section.
 */
function ui_print_breadcrums($tab_name)
{
    if (is_array($tab_name)) {
        return join(' / ', $tab_name);
    } else if ($tab_name != '') {
        $section = str_replace('_', ' ', $tab_name);
        $section = ucwords($section);
        $section = ' / '.___($section);
    }

    return $section;
}


/**
 * Show last comment
 *
 * @param string $comments String with comments.
 *
 * @return string  HTML string with the last comment of the events.
 */
function ui_print_comments($comment, $truncate_limit=255)
{
    global $config;

    if (empty($comment) === true) {
        return '';
    }

    // Only show the last comment. If commment its too long,the comment will short with ...
    // Forced time commentary to use copact date for optimize space in table.
    // Else show comments hours ago
    if ($comment['action'] != 'Added comment') {
        $comment['comment'] = $comment['action'];
    }

    $comment['comment'] = io_safe_output($comment['comment']);

    $short_comment = substr($comment['comment'], 0, 20);
    $comentario = $comment['comment'];

    if (strlen($comentario) >= $truncate_limit) {
        $comentario = ui_print_truncate_text(
            $comentario,
            $truncate_limit,
            false,
            true,
            false,
            '&hellip;',
            true,
            true,
        );
    }

    $comentario = '<i class="forced_title" data-use_title_for_force_title="1" data-title="'.date($config['date_format'], $comment['utimestamp']).'">'.ui_print_timestamp($comment['utimestamp'], true, ['style' => 'font-size: 10px; display: contents;', 'prominent' => 'compact']).'&nbsp;('.$comment['id_user'].'):&nbsp;'.$comment['comment'].'';

    if (strlen($comentario) > '200px' && $truncate_limit >= 255) {
        $comentario = '<i class="forced_title" data-use_title_for_force_title="1" data-title="'.date($config['date_format'], $comment['utimestamp']).'">'.ui_print_timestamp($comment['utimestamp'], true, ['style' => 'font-size: 10px; display: contents;', 'prominent' => 'compact']).'&nbsp;('.$comment['id_user'].'):&nbsp;'.$short_comment.'...';
    }

    return $comentario;
}


/**
 * Get complete external pandora url.
 *
 * @param string $url Url to be parsed.
 *
 * @return string Full url.
 */
function ui_get_full_external_url(string $url)
{
    $url_parsed = parse_url($url);
    if ($url_parsed) {
        if (!isset($url_parsed['scheme'])) {
            $url = 'http://'.$url;
        }
    }

        return $url;
}


function ui_print_message_dialog($title, $text, $id='', $img='', $text_button='', $hidden=true)
{
    if ($hidden == true) {
        $style = 'display:none';
    }

    echo '<div id="message_dialog_'.$id.'" title="'.$title.'" style="'.$style.'">';
        echo '<div class="content_dialog">';
            echo '<div class="icon_message_dialog">';
                echo html_print_image($img, true, ['alt' => $title, 'border' => 0, 'class' => 'icon_connection_check']);
            echo '</div>';
            echo '<div class="content_message_dialog">';
                echo '<div class="text_message_dialog">';
                    echo '<h1>'.$title.'</h1>';
                    echo '<p>'.$text.'</p>';
                    echo '<div id="err_msg"></div>';
                echo '</div>';
            echo '</div>';
        echo '</div>';
    echo '</div>';
}


/**
 * Build a Query-Result editor structure
 *
 * @param string $name Name of the structure
 *
 * @return null
 */
function ui_query_result_editor($name='default')
{
    $editorSubContainer = html_print_div(
        [
            'id'      => $name.'_editor_title',
            'content' => '<p>'.__('Query').'</p>',
        ],
        true
    );

    $editorSubContainer .= html_print_div(
        [
            'id'    => $name.'_editor',
            'class' => 'query_result_editor',
        ],
        true
    );

    $editorContainer = html_print_div(
        [
            'id'      => $name.'_editor_container',
            'class'   => 'query_result_editor_container',
            'content' => $editorSubContainer,
        ],
        true
    );

    $viewSubContainer = html_print_div(
        [
            'id'      => $name.'_view_title',
            'content' => '<p>'.__('Results').'</p>',
        ],
        true
    );

    $viewSubContainer .= html_print_div(
        [
            'id'    => $name.'_view',
            'class' => 'query_result_view',
        ],
        true
    );

    $viewSubContainer .= html_print_div(
        [
            'class'   => 'action-buttons',
            'content' => '',
        ],
        true
    );

    $viewContainer = html_print_div(
        [
            'id'      => $name.'_view_container',
            'class'   => 'query_result_view_container',
            'content' => $viewSubContainer,
        ],
        true
    );

    html_print_div(
        [
            'id'      => 'query_result_container',
            'class'   => 'databox',
            'content' => $editorContainer.$viewContainer,
        ]
    );
    // This is needed for Javascript
    html_print_div(
        [
            'id'      => 'pandora_full_url',
            'hidden'  => true,
            'content' => ui_get_full_url(false, false, false, false),
        ]
    );

    $execute_button = html_print_submit_button(__('Execute query'), 'execute_query', false, ['icon' => 'update'], true);
    html_print_action_buttons($execute_button);

}


/**
 * Generate a button for reveal the content of the password field.
 *
 * @param string  $name   Name of the field.
 * @param boolean $return If true, return the string with the formed element.
 *
 * @return string
 */
function ui_print_reveal_password(string $name, bool $return=false)
{
    if (is_metaconsole()) {
        $imagePath = '../../images/';
    } else {
        $imagePath = 'images/';
    }

    $output = '&nbsp;<img class="clickable forced_title invert_filter" id="reveal_password_'.$name.'" src="'.$imagePath.'eye_show.png" onclick="reveal_password(\''.$name.'\')" data-use_title_for_force_title="1" data-title="'.__('Show password').'">';

    if ($return === true) {
        return $output;
    }

    echo $output;
}


/**
 * Generate a spinner box for waiting times
 * TIP: It's made for Massive Operations, but it migth used in entire project.
 *
 * @param string  $text   Text for show in spinner. English term Loading for default.
 * @param boolean $return If true, return the string with the formed element.
 *
 * @return string
 */
function ui_print_spinner(string $text='Loading', bool $return=false)
{
    $output = '';

    $output .= '<center>';

    $output .= html_print_div(
        [
            'id'      => 'loading_spinner',
            'class'   => 'white_box invisible',
            'content' => '<span style="font-size:25px;">'.$text.'...</span>'.html_print_image(
                'images/spinner.gif',
                true,
                [
                    'class'  => 'main_menu_icon',
                    'border' => '0',
                    'width'  => '25px',
                    'heigth' => '25px',
                ]
            ),
        ],
        true
    );

    $output .= '</center>';

    $output .= '
			<script type="text/javascript">
				function hideSpinner() {
                    document.getElementById("loading_spinner").classList.add("invisible");
				}
                function showSpinner() {
                    document.getElementById("loading_spinner").classList.remove("invisible");
                }
			</script>
    ';

    if ($return === true) {
        return $output;
    } else {
        echo $output;
    }
}


/**
 * Return a formed server type icon.
 *
 * @param integer $id Id of server type.
 *
 * @return string.
 */
function ui_print_servertype_icon(int $id)
{
    switch ($id) {
        case MODULE_DATA:
            $title = __('Data server');
            $image = 'images/data-server@svg.svg';
        break;

        case MODULE_NETWORK:
            $title = __('Network server');
            $image = 'images/network-server@os.svg';
        break;

        case MODULE_PLUGIN:
            $title = __('Plugin server');
            $image = 'images/plugins@svg.svg';
        break;

        case MODULE_PREDICTION:
            $title = __('Prediction server');
            $image = 'images/prediction@svg.svg';
        break;

        case MODULE_WMI:
            $title = __('WMI server');
            $image = 'images/WMI@svg.svg';
        break;

        case MODULE_WEB:
            $title = __('WEB server');
            $image = 'images/server-web@svg.svg';
        break;

        case MODULE_WUX:
            $title = __('WUX server');
            $image = 'images/wux@svg.svg';
        break;

        case MODULE_WIZARD:
            $title = __('Wizard Module');
            $image = 'images/wizard@svg.svg';
        break;

        default:
            $title = '';
            $image = '';
        break;
    }

    if (empty($title) === true && empty($image) === true) {
        $return = '--';
    } else {
        $return = html_print_image(
            $image,
            true,
            [
                'title' => sprintf('%s %s', get_product_name(), $title),
                'class' => 'invert_filter main_menu_icon',
            ]
        );
    }

    return $return;
}


function ui_get_inventory_module_add_form(
    $form_action,
    $form_buttons='',
    $inventory_module_id=0,
    $os_id=false,
    $target=false,
    $interval=3600,
    $username='',
    $password='',
    $custom_fields_enabled=false,
    $custom_fields=[]
) {
    $table = new stdClass();
    $table->id = 'inventory-module-form';
    $table->width = '100%';
    $table->class = 'databox filters filter-table-adv';
    $table->size['module'] = '50%';
    $table->size['interval'] = '50%';
    $table->size['target'] = '50%';
    $table->size['chkbx-custom-fields'] = '50%';
    $table->size['username'] = '50%';
    $table->size['password'] = '50%';
    $table->rowstyle = [];
    $table->rowstyle['hidden-custom-field-row'] = 'display: none;';
    $table->rowstyle['custom-fields-button'] = 'display: none;';
    $table->data = [];

    $row = [];
    if (empty($inventory_module_id)) {
        if (empty($os_id)) {
            $sql = 'SELECT mi.id_module_inventory AS id, mi.name AS name, co.name AS os
					FROM tmodule_inventory mi, tconfig_os co
					WHERE co.id_os = mi.id_os
					ORDER BY co.name, mi.name';
            $inventory_modules_raw = db_get_all_rows_sql($sql);

            $inventory_modules = [];
            foreach ($inventory_modules_raw as $im) {
                $inventory_modules[$im['id']] = [
                    'name'     => $im['name'],
                    'optgroup' => $im['os'],
                ];
            }
        } else {
            $sql = sprintf(
                'SELECT id_module_inventory AS id, name
				FROM tmodule_inventory
				WHERE id_os = %d
				ORDER BY name',
                $os_id
            );
            $inventory_modules_raw = db_get_all_rows_sql($sql);

            $inventory_modules = [];
            foreach ($inventory_modules_raw as $im) {
                $inventory_modules[$im['id']] = $im['name'];
            }
        }

        $row['module'] = html_print_label_input_block(
            __('Module'),
            html_print_select(
                $inventory_modules,
                'id_module_inventory',
                0,
                '',
                __('Select inventory module'),
                '',
                true,
                false,
                false,
                'w100p',
                false,
                'width: 100%',
                false,
                false,
                false,
                '',
                false,
                false,
                true
            )
        );
    } else {
        $row['module'] = html_print_label_input_block(
            __('Module'),
            db_get_sql('SELECT name FROM tmodule_inventory WHERE id_module_inventory = '.$inventory_module_id)
        );
    }

    $row['interval'] = html_print_label_input_block(
        __('Interval'),
        html_print_extended_select_for_time(
            'interval',
            $interval,
            '',
            '',
            '',
            false,
            true,
            false,
            true,
            'w100p'
        )
    );

    $table->data['first-row'] = $row;

    $row = [];

    if ($target !== false) {
        $row['target'] = html_print_label_input_block(
            __('Target'),
            html_print_input_text(
                'target',
                $target,
                '',
                25,
                40,
                true,
                false,
                false,
                '',
                'w100p'
            )
        );
    }

    $row['chkbx-custom-fields'] = html_print_label_input_block(
        __('Use custom fields'),
        html_print_checkbox(
            'custom_fields_enabled',
            1,
            $custom_fields_enabled,
            true
        )
    );

    $table->data['second-row'] = $row;

    $row = [];
    $row['username'] = html_print_label_input_block(
        __('Username'),
        html_print_input_text(
            'username',
            $username,
            '',
            25,
            40,
            true,
            false,
            false,
            '',
            'w100p'
        )
    );

    $row['password'] = html_print_label_input_block(
        __('Password'),
        html_print_input_password(
            'password',
            $password,
            '',
            25,
            40,
            true,
            false,
            false,
            'w100p'
        )
    );

    $table->data['userpass-row'] = $row;

    $row = [];

    $table->data['hidden-custom-field-row'] = html_print_label_input_block(
        '',
        '<div class="agent_details_agent_data">'.html_print_input_hidden(
            'hidden-custom-field-name',
            '',
            true
        ).html_print_input_hidden(
            'hidden-custom-field-is-secure',
            0,
            true
        ).html_print_input_text(
            'hidden-custom-field-input',
            '',
            '',
            25,
            40,
            true,
            false,
            false,
            '',
            'w100p'
        ).html_print_image(
            'images/delete.svg',
            true,
            [
                'border' => '0',
                'title'  => __('Remove'),
                'style'  => 'cursor: pointer;',
                'class'  => 'remove-custom-field invert_filter main_menu_icon',
            ]
        ).'</div>'
    );
    $table->colspan['hidden-custom-field-row'][0] = 2;

    if ($custom_fields_enabled) {
        foreach ($custom_fields as $i => $field) {
            if ($field['secure']) {
                $secure = html_print_input_password(
                    'custom_fields['.$i.'][value]',
                    io_safe_input($field['value']),
                    '',
                    false,
                    40,
                    true,
                    false,
                    false,
                    '',
                    'off',
                    false,
                    'w100p'
                );
            } else {
                $secure = html_print_input_text(
                    'custom_fields['.$i.'][value]',
                    io_safe_input($field['value']),
                    '',
                    false,
                    40,
                    true,
                    false,
                    false,
                    '',
                    'w100p'
                );
            }

            $table->colspan['custom-field-row-'.$i][0] = 2;
            $table->data['custom-field-row-'.$i] = html_print_label_input_block(
                $field['name'],
                '<div class="agent_details_agent_data">'.html_print_input_hidden(
                    'custom_fields['.$i.'][name]',
                    $field['name'],
                    true
                ).html_print_input_hidden(
                    'custom_fields['.$i.'][secure]',
                    $field['secure'],
                    true
                ).$secure.html_print_image(
                    'images/delete.svg',
                    true,
                    [
                        'border' => '0',
                        'title'  => __('Remove'),
                        'style'  => 'cursor: pointer;',
                        'class'  => 'remove-custom-field invert_filter main_menu_icon',
                    ]
                ).'</div>'
            );
        }
    }

    $row = [];
    $row['custom-fields-column'] = html_print_label_input_block(
        __('Field name'),
        '<div class="flex">'.html_print_input_text(
            'field-name',
            '',
            '',
            25,
            40,
            true,
            false,
            false,
            '',
            'w60p mrgn_right_10px'
        ).html_print_checkbox(
            'field-is-password',
            1,
            false,
            true
        ).'&nbsp;'.__("It's a password").'</div>'
    );

    $table->data['custom-fields-row'] = $row;

    $row = [];
    $row['custom-fields-button-title'] = '';
    $row['custom-fields-button'] = html_print_button(
        __('Add field'),
        'add-field',
        false,
        '',
        [
            'class' => 'mini float-right',
            'icon'  => 'plus',
        ],
        true
    );

    $table->data['custom-fields-button'] = $row;

    ob_start();

    echo '<form id="policy_inventory_module_edit_form" name="modulo" method="post" action="'.$form_action.'" class="max_floating_element_size">';
    echo html_print_table($table);
    echo $form_buttons;
    echo '</form>';
    ?>

<script type="text/javascript">
(function () {
    function toggle_custom_fields () {
        if ($("#checkbox-custom_fields_enabled").prop("checked")) {
            $("#inventory-module-form-userpass-row").hide();
            $("#inventory-module-form-custom-fields-row").show();
            $("tr[id^=inventory-module-form-custom-field-row-]").show();
            $('#inventory-module-form-custom-fields-button').show();
        } else {
            $("#inventory-module-form-userpass-row").show();
            $("#inventory-module-form-custom-fields-row").hide();
            $("tr[id^=inventory-module-form-custom-field-row-]").hide();
            $('#inventory-module-form-custom-fields-button').hide();
        }
    }

    function add_row_for_custom_field (fieldName, isSecure) {
        var custom_fields_num = $("tr[id^=inventory-module-form-custom-field-row-]").length;
        $("#inventory-module-form-hidden-custom-field-row")
        .clone()
        .prop("id", "inventory-module-form-custom-field-row-" + custom_fields_num)
        .children("[id^='inventory-module-form-hidden-custom-field-row']") // go to TD
            .prop("id", "inventory-module-form-hidden-custom-field-row-"+ custom_fields_num)
                .children() // go to DIV
                    .find('label')
                        .html(fieldName)
                .parent() // up to DIV padre
                .find('div') //go to DIV no label
                    .children("[id^=hidden-hidden-custom-field-name]")
                    .prop("id", "custom-field-name-" + custom_fields_num)
                    .prop("name", "custom_fields[" + custom_fields_num + "][name]")
                    .prop("value", fieldName)
                    .parent()
                    .children("input[name=hidden-custom-field-is-secure]")
                        .prop("id", "custom-field-is-secure-" + custom_fields_num)
                        .prop("name", "custom_fields[" + custom_fields_num + "][secure]")
                        .val(isSecure ? 1 : 0)
                        .parent()
                    .children("input[name=hidden-custom-field-input]")
                        .prop("id", "custom-field-input-" + custom_fields_num)
                        .prop("type", isSecure ? "password" : "text")
                        .prop("name", "custom_fields[" + custom_fields_num + "][value]")
                        .parent()
                    .children("img.remove-custom-field")
                        .click(remove_custom_field)
                        .parent()
                        .parent()
                .parent() // up to TD
            .parent() // up to TR
        .insertBefore($("#inventory-module-form-custom-fields-row"))
        .show();
    }

    function add_custom_field () {
        var fieldName = $("#text-field-name").val();
        var isSecure = $("#checkbox-field-is-password").prop("checked");
        
        if (fieldName.length === 0) return;

        add_row_for_custom_field(fieldName, isSecure);
        // Clean the fields
        $("#text-field-name").val("");
        $("#checkbox-field-is-password").prop("checked", false);
    }
    
    function remove_custom_field (event) {
        $(event.target).parents("tr[id^=inventory-module-form-custom-field-row-]").remove();
    }
    
    $("#checkbox-custom_fields_enabled").click(toggle_custom_fields);
    $("#button-add-field").click(add_custom_field);
    $("img.remove-custom-field").click(remove_custom_field);

    toggle_custom_fields();
})();
</script>
    <?php
    return ob_get_clean();
}


/**
 * Print Fullscreen Bar.
 *
 * @param array   $options Fullsreen options.
 * @param boolean $return  If true, return the formed element.
 *
 * @return void|string
 */
function ui_print_fullscreen_bar(array $options, bool $return=false)
{
    // Always requery file.
    ui_require_jquery_file('countdown');
    // Vars.
    $url = ($options['url'] ?? '');
    $normalScreenTitle = ($options['normal_screen_title'] ?? 'Back to normal mode');
    $mainTitle = ($options['title'] ?? 'Full screen mode');
    $title = '<span class="">'.__('Refresh').'</span>';
    $select = html_print_select(
        get_refresh_time_array(),
        'refresh',
        (int) get_parameter('refresh'),
        '',
        '',
        0,
        true,
        false,
        false,
        '',
        false,
        'margin-top: 3px;'
    );

    $vcRefrDivContent = [];
    $vcRefrDivContent[] = html_print_div(['class' => 'vc-countdown inline_line'], true);
    $vcRefrDivContent[] = html_print_div(
        [
            'id'      => 'vc-refr-form',
            'content' => $title.$select,
        ],
        true
    );
    // Floating menu - Start.
    $menuTabContent[] = '<ul class="mn">';
    // Quit fullscreen.
    $menuTabContent[] = '<li class="nomn">';
    $menuTabContent[] = html_print_anchor(
        [
            'href'    => $url,
            'content' => html_print_image(
                'images/exit_fullscreen@svg.svg',
                true,
                [
                    'title' => __($normalScreenTitle),
                    'class' => 'invert_filter main_menu_icon',
                ]
            ),
        ],
        true
    );
    $menuTabContent[] = '</li>';
    // Countdown.
    $menuTabContent[] = '<li class="nomn">';
    $menuTabContent[] = html_print_div(
        [
            'class'   => 'vc-refr',
            'content' => implode('', $vcRefrDivContent),
        ],
        true
    );
    $menuTabContent[] = '</li>';

    // Console name.
    $menuTabContent[] = '<li class="nomn">';
    $menuTabContent[] = html_print_div(
        [
            'class'   => 'vc-title',
            'content' => __($mainTitle),
        ],
        true
    );
    $menuTabContent[] = '</li>';
    $menuTabContent[] = '</ul>';

    return html_print_div(
        [
            'id'      => 'menu_tab',
            'class'   => 'full_screen_control_bar',
            'content' => implode('', $menuTabContent),
        ],
        $return
    );
}


function ui_print_status_div($status)
{
    switch ((int) $status) {
        case 0:
            $return = '<div class="status_rounded_rectangles forced_title" style="display: inline-block; background: #82b92e;" title="OK" data-title="OK" data-use_title_for_force_title="1">&nbsp;</div>';
        break;

        case 1:
            $return = '<div class="status_rounded_rectangles forced_title" style="display: inline-block; background: #e63c52;" title="FAILED" data-title="FAILED" data-use_title_for_force_title="1">&nbsp;</div>';
        break;

        default:
            $return = '<div class="status_rounded_rectangles forced_title" style="display: inline-block; background: #fff;" title="UNDEFINED" data-title="UNDEFINED" data-use_title_for_force_title="1">&nbsp;</div>';
        break;
    }

    return $return;
}


function ui_print_div(?string $class='', ?string $title='')
{
    $return = '<div class="'.$class.'" title="'.$title.'" data-title="'.$title.'" data-use_title_for_force_title="1">';
    $return .= '&nbsp';
    $return .= '</div>';

    return $return;
}


function ui_print_status_agent_div(int $status, ?string $title=null)
{
    $return = '';
    $class = 'status_rounded_rectangles forced_title';
    switch ((int) $status) {
        case AGENT_STATUS_CRITICAL:
            $return = ui_print_div('group_view_crit '.$class, $title);
        break;

        case AGENT_STATUS_NORMAL:
            $return = ui_print_div('group_view_ok '.$class, $title);
        break;

        case AGENT_STATUS_NOT_INIT:
            $return = ui_print_div('group_view_not_init '.$class, $title);
        break;

        case AGENT_STATUS_UNKNOWN:
            $return = ui_print_div('group_view_unk '.$class, $title);
        break;

        case AGENT_STATUS_WARNING:
            $return = ui_print_div('group_view_warn '.$class, $title);
        break;

        case AGENT_STATUS_ALERT_FIRED:
            $return = ui_print_div('group_view_alrm '.$class, $title);
        break;

        default:
            // Not posible.
        break;
    }

    return $return;
}


function ui_print_fav_menu($id_element, $url, $label, $section)
{
    global $config;
    $label = io_safe_output($label);
    if (strlen($label) > 18) {
        $label = io_safe_input(substr($label, 0, 18).'...');
    }

    $fav = db_get_row_filter(
        'tfavmenu_user',
        [
            'url'     => $url,
            'id_user' => $config['id_user'],
        ],
        ['*']
    );
    $config_fav_menu = [
        'id_element' => $id_element,
        'url'        => $url,
        'label'      => $label,
        'section'    => $section,
    ];

    $output = '<span class="fav-menu">';
    $output .= html_print_input_image(
        'fav-menu-action',
        (($fav !== false) ? 'images/star_fav_menu.png' : 'images/star_dark.png'),
        base64_encode(json_encode($config_fav_menu)),
        '',
        true,
        [
            'onclick' => 'favMenuAction(this)',
            'class'   => (($fav !== false) ? 'active' : ''),
        ]
    );
    $output .= '</span>';
    $output .= '<div id="dialog-fav-menu">';
    $output .= '<p><b>'.__('Title').'</b></p>';
    $output .= html_print_input_text('label_fav_menu', '', '', 25, 255, true, false, true);
    $output .= '</div>';
    return $output;
}


function ui_print_tree(
    $tree,
    $id=0,
    $depth=0,
    $last=0,
    $last_array=[],
    $sufix=false,
    $descriptive_ids=false,
    $previous_id=''
) {
    static $url = false;
    $output = '';

    // Get the base URL for images.
    if ($url === false) {
        $url = ui_get_full_url('operation/tree', false, false, false);
    }

    // Leaf.
    if (empty($tree['__LEAVES__'])) {
        return '';
    }

    $count = 0;
    $total = (count(array_keys($tree['__LEAVES__'])) - 1);
    $last_array[$depth] = $last;
    $class = 'item_'.$depth;

    if ($depth > 0) {
        $output .= '<ul id="ul_'.$id.'" class="mrgn_0px pdd_0px invisible">';
    } else {
        $output .= '<ul id="ul_'.$id.'" class="mrgn_0px pdd_0px">';
    }

    foreach ($tree['__LEAVES__'] as $level => $sub_level) {
        // Id used to expand leafs.
        $sub_id = time().rand(0, getrandmax());
        // Display the branch.
        $output .= '<li id="li_'.$sub_id.'" class="'.$class.' mrgn_0px pdd_0px">';

        // Indent sub branches.
        for ($i = 1; $i <= $depth; $i++) {
            if ($last_array[$i] == 1) {
                $output .= '<img src="'.$url.'/no_branch.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/branch.png" class="vertical_middle">';
            }
        }

        // Branch.
        if (! empty($sub_level['sublevel']['__LEAVES__'])) {
            $output .= "<a id='anchor_$sub_id' onfocus='javascript: this.blur();' href='javascript: toggleTreeNode(\"$sub_id\", \"$id\");'>";
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/one_closed.png" class="vertical_middle">';
                } else {
                    $output .= '<img src="'.$url.'/first_closed.png" class="vertical_middle">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_closed.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/closed.png" class="vertical_middle">';
            }

            $output .= '</a>';
        }

        // Leave.
        else {
            if ($depth == 0 && $count == 0) {
                if ($count == $total) {
                    $output .= '<img src="'.$url.'/no_branch.png" class="vertical_middle">';
                } else {
                    $output .= '<img src="'.$url.'/first_leaf.png" class="vertical_middle">';
                }
            } else if ($count == $total) {
                $output .= '<img src="'.$url.'/last_leaf.png" class="vertical_middle">';
            } else {
                $output .= '<img src="'.$url.'/leaf.png" class="vertical_middle">';
            }
        }

        $checkbox_name_sufix = ($sufix === true) ? '_'.$level : '';
        if ($descriptive_ids === true) {
            $checkbox_name = 'create_'.$sub_id.$previous_id.$checkbox_name_sufix;
        } else {
            $checkbox_name = 'create_'.$sub_id.$checkbox_name_sufix;
        }

        $previous_id = $checkbox_name_sufix;
        if ($sub_level['selectable'] === true) {
            $output .= html_print_checkbox(
                $sub_level['name'],
                $sub_level['value'],
                $sub_level['checked'],
                true,
                false,
                '',
                true
            );
        }

        $output .= '&nbsp;<span>'.$sub_level['label'].'</span>';

        $output .= '</li>';

        // Recursively print sub levels.
        $output .= ui_print_tree(
            $sub_level['sublevel'],
            $sub_id,
            ($depth + 1),
            (($count == $total) ? 1 : 0),
            $last_array,
            $sufix,
            $descriptive_ids,
            $previous_id
        );

        $count++;
    }

    $output .= '</ul>';

    return $output;
}


function ui_update_name_fav_element($id_element, $section, $label)
{
    $label = io_safe_output($label);
    if (strlen($label) > 18) {
        $label = io_safe_input(substr($label, 0, 18).'...');
    }

    db_process_sql_update(
        'tfavmenu_user',
        ['label' => $label],
        [
            'section'    => $section,
            'id_element' => $id_element,
        ]
    );
}


function ui_print_status_vulnerability_div(float $score)
{
    $return = '';
    $class = 'status_rounded_rectangles forced_title';
    if (((float) $score) <= 5) {
        return ui_print_div('group_view_ok '.$class, $score);
    }

    if (((float) $score) > 5 && ((float) $score) <= 7.5) {
        return ui_print_div('group_view_warn '.$class, $score);
    }

    if (((float) $score) > 7.5) {
        return ui_print_div('group_view_crit '.$class, $score);
    }

    return $return;
}


function ui_print_status_secmon_div($status, $title=false)
{
    $class = 'status_rounded_rectangles forced_title';
    if (($status) === 'normal') {
        $title = ($title === false) ? __('normal') : $title;
        return ui_print_div('group_view_ok '.$class, $title);
    }

    if (($status) === 'warning') {
        $title = ($title === false) ? __('warning') : $title;
        return ui_print_div('group_view_warn '.$class, $title);
    }

    if (($status) === 'critical') {
        $title = ($title === false) ? __('critical') : $title;
        return ui_print_div('group_view_crit '.$class, $title);
    }
}