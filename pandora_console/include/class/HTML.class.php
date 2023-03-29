<?php
/**
 * Class to handle HTML output.
 *
 * @category   Class
 * @package    Pandora FMS
 * @subpackage HTML
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
 * Please see http://pandorafms.org for full contribution list
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation for version 2.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * ============================================================================
 */

// Begin.


 /**
  * Global HTML generic class.
  */
class HTML
{

    /**
     * Breadcrum
     *
     * @var array
     */
    public $breadcrum;

    /**
     * Current page
     *
     * @var integer
     */
    public $page;

        /**
         * Target icon to be shown in discovery wizard list.
         *
         * @var string
         */
    public $icon;

    /**
     * Target label to be shown in discovery wizard list.
     *
     * @var string
     */
    public $label;

    /**
     * This wizard's url.
     *
     * @var string
     */
    public $url;

    /**
     * Result of wizard execution (0 - ok, 1 - not ok).
     *
     * @var integer
     */
    public $result;

    /**
     * Message to be delivered to user.
     *
     * @var string
     */
    public $msg;

    /**
     * Defines access level to use this util.
     *
     * @var string
     */
    public $access = 'AR';


    /**
     * Setter for breadcrum
     *
     * @param array $str Breadcrum.
     *
     * @return void
     */
    public function setBreadcrum(array $str)
    {
        $this->breadcrum = $str;
    }


    /**
     * Getter for breadcrum
     *
     * @return array Breadcrum.
     */
    public function getBreadcrum()
    {
        return $this->breadcrum;
    }


    /**
     * Add an element to breadcrum array.
     *
     * @param array $breads Elements to add to breadcrum.
     *
     * @return void
     */
    protected function addBreadcrum($breads)
    {
        if (empty($breads)) {
            return;
        }

        $this->breadcrum = array_merge($this->breadcrum, $breads);
    }


    /**
     * Setter for label
     *
     * @param string $str Label.
     *
     * @return void
     */
    public function setLabel(string $str)
    {
        $this->label = $str;
    }


    /**
     * Getter for label
     *
     * @return array Breadcrum.
     */
    public function getLabel()
    {
        return $this->label;
    }


    /**
     * Return units associated to target interval (in seconds).
     *
     * @param integer $interval Target interval.
     *
     * @return integer Unit.
     */
    public function getTimeUnit($interval)
    {
        $units = [
            1,
            60,
            3600,
            86400,
            604800,
            2592000,
            31104000,
        ];

        $size = count($units);
        for ($i = 0; $i < $size; $i++) {
            if ($interval < $units[$i]) {
                if (($i - 1) < 0) {
                    return 1;
                }

                return $units[($i - 1)];
            }
        }

        return $units[-1];
    }


    /**
     * Builder for breadcrum
     *
     * @param array   $urls Array of urls to be stored in breadcrum.
     * @param boolean $add  True if breadcrum should be added
     *                      instead of overwrite it.
     *
     * @return void
     */
    public function prepareBreadcrum(
        array $urls,
        bool $add=false
    ) {
        $bc = [];
        $i = 0;

        foreach ($urls as $url) {
            if ($url['selected'] == 1) {
                $class = 'selected';
            } else {
                $class = '';
            }

            $bc[$i] = '';
            $bc[$i] .= '<span><a class="breadcrumb_link '.$class.'" href="'.$url['link'].'">';
            $bc[$i] .= $url['label'];
            $bc[$i] .= '</a>';
            $bc[$i] .= '</span>';
            $i++;
        }

        if ($add === true) {
            $this->addBreadcrum($bc);
        } else {
            $this->setBreadcrum($bc);
        }
    }


    /**
     * To be overwritten.
     *
     * @return void
     */
    public function run()
    {
        ui_require_css_file('wizard');
        // Check access.
        check_login();

        if (! $this->aclMulticheck()) {
            return;
        }
    }


    /**
     * Check multiple acl perms.
     *
     * @param string $access Access in PM|AR|RR format. Optional.
     *
     * @return boolean Alowed or not.
     */
    public function aclMulticheck($access=null, $id_group=0)
    {
        global $config;

        if (isset($access)) {
            $perms = explode('|', $access);
        } else {
            $perms = explode('|', $this->access);
        }

        $allowed = false;
        foreach ($perms as $perm) {
            $allowed = $allowed || (bool) check_acl(
                $config['id_user'],
                $id_group,
                $perm
            );
        }

        return $allowed;
    }


    /**
     * Checks if environment is ready,
     * returns array
     *   icon: icon to be displayed
     *   label: label to be displayed
     *
     * @return array With data.
     **/
    public function load()
    {
        global $config;
        // Check access.
        check_login();

        if (! $this->aclMulticheck()) {
            return false;
        }

        return [
            'icon'  => $this->icon,
            'label' => $this->label,
            'url'   => $this->url,
        ];
    }


    /**
     * Print breadcrum to follow flow.
     *
     * @return string Breadcrum HTML code.
     */
    public function printBreadcrum()
    {
        return implode(
            '<span class="breadcrumb_link_separator">&nbsp/&nbsp</span>',
            $this->breadcrum
        );
    }


    /**
     * Prints a header for current wizard.
     *
     * @param boolean $return Return HTML or print it.
     *
     * @return string HTML code for header.
     */
    public function printHeader(bool $return=false)
    {
        $output = $this->printBreadcrum();
        if ($return === false) {
            echo $output;
        }

        return $output;
    }


    /**
     * Return formatted html for error handler.
     *
     * @param string $message Error mesage.
     *
     * @return string
     */
    public static function error($message)
    {
        if (is_ajax()) {
            echo json_encode(
                [
                    'error' => ui_print_error_message($message, '', true),
                ]
            );
        } else {
            return ui_print_error_message($message, '', true);
        }
    }


    /**
     * Print input using functions html lib.
     *
     * @param array $data Input definition.
     *
     * @return string HTML code for desired input.
     */
    public static function printInput($data)
    {
        global $config;

        include_once $config['homedir'].'/include/functions_html.php';

        if (is_array($data) === false) {
            return '';
        }

        $input_only = true;
        if (isset($data['input_only']) === true) {
            $input_only = $data['input_only'];
        }

        $input = html_print_input(
            ($data + ['return' => true]),
            'div',
            $input_only
        );
        if ($input === false) {
            return '';
        }

        return $input;
    }


    /**
     * Prints a go back button redirecting to main page.
     *
     * @param string $url Optional target url.
     *
     * @return void
     */
    public static function printGoBackButton($url=null, $return=false)
    {
        if (isset($url) === false) {
            $url = ui_get_full_url(
                'index.php?sec=gservers&sec2=godmode/servers/discovery'
            );
        }

        $form = [
            'form'   => [
                'method' => 'POST',
                'action' => $url,
            ],
            'inputs' => [
                [
                    'class'     => 'w100p',
                    'arguments' => [
                        'name'       => 'submit',
                        'label'      => __('Go back'),
                        'type'       => 'submit',
                        'attributes' => [
                            'icon' => 'back',
                            'mode' => 'secondary',
                        ],
                        'return'     => true,
                    ],
                ],
            ],
        ];

        if ($return === true) {
            return self::printForm($form, $return);
        }

        self::printForm($form, $return);
    }


    /**
     * Print a block of inputs.
     * Example, using direct to 'anidate' inputs directly to wrapper:
     * [
     *     'wrapper'       => 'div',
     *     'block_id'      => 'example_id',
     *     'class'         => 'your class',
     *     'direct'        => 1,
     *     'block_content' => [
     *         [
     *             'arguments' => [
     *                 'label'      => __('Sugesstion'),
     *                 'type'       => 'button',
     *                 'attributes' => 'class="sub ok btn_sug"',
     *                 'name'       => 'option_1',
     *                 'id'         => 'option_1',
     *                 'script'     => 'change_option1()',
     *             ],
     *         ],
     *         [
     *             'arguments' => [
     *                 'label'      => __('Something is not quite right'),
     *                 'type'       => 'button',
     *                 'attributes' => 'class="sub ok btn_something"',
     *                 'name'       => 'option_2',
     *                 'id'         => 'option_2',
     *                 'script'     => 'change_option2()',
     *             ],
     *         ],
     *     ],
     * ].
     *
     * @param array   $input  Definition of target block to be printed.
     * @param boolean $return Return as string or direct output.
     * @param boolean $direct Avoid encapsulation if input print is direct.
     *
     * @return string HTML content.
     */
    public static function printBlock(
        array $input,
        bool $return=false,
        bool $direct=false
    ) {
        global $config;
        $text_color = '';
        if ($config['style'] === 'pandora_black' && !is_metaconsole()) {
            $text_color = 'style="color: white"';
        }

        $output = '';
        if (($input['hidden'] ?? null) == 1) {
            $class = ' hidden';
        } else {
            $class = '';
        }

        if (empty($input['style']) === false) {
            $style_li = $input['style'];
        }

        if (isset($input['class']) === true) {
            $class = $input['class'].$class;
        }

        if (is_array(($input['block_content'] ?? null)) === true) {
            $direct = (bool) ($input['direct'] ?? false);
            $toggle = (bool) ($input['toggle'] ?? false);

            if (isset($input['label']) === true) {
                $output .= '<span '.$text_color.'>'.$input['label'].'</span>';
            }

            // Print independent block of inputs.
            $output .= '<li id="li-'.($input['block_id'] ?? '').'" class="'.$class.'" style="'.$style_li.'">';

            if (isset($input['wrapper']) === true) {
                $output .= '<'.$input['wrapper'].' id="'.($input['block_id'] ?? '').'" class="'.$class.'">';
            }

            if (!$direct) {
                // Avoid encapsulation if input is direct => 1.
                $output .= '<ul class="wizard '.($input['block_class'] ?? '').'">';
            }

            $html = '';

            foreach ($input['block_content'] as $in) {
                $html .= self::printBlock(
                    $in,
                    $return,
                    (bool) $direct
                );
            }

            if ($toggle === true) {
                $output .= ui_print_toggle(
                    [
                        'name'            => (isset($input['toggle_name']) ? $input['toggle_name'] : 'toggle_'.uniqid()),
                        'content'         => $html,
                        'title'           => $input['toggle_title'],
                        'id'              => $input['toggle_id'],
                        'hidden_default'  => $input['toggle_hidden_default'],
                        'return'          => (isset($input['toggle_return']) ? $input['toggle_return'] : true),
                        'toggle_class'    => $input['toggle_toggle_class'],
                        'main_class'      => $input['toggle_main_class'],
                        'container_class' => $input['toggle_container_class'],
                        'img_a'           => $input['toggle_img_a'],
                        'img_b'           => $input['toggle_img_b'],
                        'clean'           => (isset($input['toggle_clean']) ? $input['toggle_clean'] : false),
                    ]
                );
            } else {
                $output .= $html;
            }

            // Close block.
            if (!$direct) {
                $output .= '</ul>';
            }

            if (isset($input['wrapper']) === true) {
                $output .= '</'.$input['wrapper'].'>';
            }

            $output .= '</li>';
        } else {
            if (is_array(($input['arguments'] ?? false)) === true
                && ($input['arguments']['type'] ?? false) != 'hidden'
                && ($input['arguments']['type'] ?? false) != 'hidden_extended'
                && ($input['arguments']['type'] ?? false) != 'datalist'
            ) {
                // Raw content for attach at the start of the input.
                if (isset($input['surround_start']) === true) {
                    $output .= $input['surround_start'];
                }

                if (!$direct) {
                    $output .= '<li id="'.($input['id'] ?? '').'" class="'.$class.'" style="'.$style_li.'">';
                }

                if (isset($input['label']) === true) {
                    $output .= '<label '.$text_color.'>'.$input['label'].'</label>';
                }

                $output .= self::printInput($input['arguments']);
                // Allow dynamic content.
                $output .= ($input['extra'] ?? '');
                if (!$direct) {
                    $output .= '</li>';
                }

                // Raw content for attach at the end of the input.
                if (isset($input['surround_end']) === true) {
                    $output .= $input['surround_end'];
                }
            } else {
                $output .= self::printInput(($input['arguments'] ?? []));
                // Allow dynamic content.
                $output .= ($input['extra'] ?? '');
            }
        }

        if ($return === false) {
            echo $output;
        }

        return $output;
    }


    /**
     * Print a block of inputs with grid format.
     *
     * @param array   $input  Definition of target block to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML content.
     */
    public static function printBlockAsGrid(array $input, bool $return=false)
    {
        $output = '';
        if ($input['hidden'] == 1) {
            $class = ' hidden';
        } else {
            $class = '';
        }

        if (isset($input['class']) === true) {
            $class = $input['class'].$class;
        }

        if (is_array($input['block_content']) === true) {
            if (empty($input['label']) === false) {
                $output .= '<div class="label_select">';
                $output .= $input['label'];
                $output .= '</div>';
            }

            // Print independent block of inputs.
            $output .= '<ul class="wizard '.$input['block_class'].'">';
            $output .= '<li id="'.$input['block_id'].'" class="'.$class.'">';
            foreach ($input['block_content'] as $input) {
                $output .= self::printBlockAsGrid($input, $return);
            }

            $output .= '</ul></li>';
        } else {
            if ($input['arguments']['type'] != 'hidden'
                && $input['arguments']['type'] != 'hidden_extended'
            ) {
                if ($input['arguments']['inline'] != 'true') {
                    $output .= '<div class="edit_discovery_input">';
                } else {
                    $output .= '<div class="flex-row-center flex-nowrap mrgn_btn_15px">';
                    if (!isset($input['extra'])) {
                        $output .= '<div class="w100p">';
                    }

                    if (isset($input['extra'])) {
                        $output .= '<div class="flex mrgn_right_10px">';
                    }
                }

                if ($input['arguments']['inline'] == 'true' && isset($input['extra'])) {
                    $output .= '<div class="mrgn_right_10px">';
                }

                $output .= '<div class="">';
                $output .= $input['label'];
                $output .= '</div>';

                if ($input['arguments']['inline'] == 'true' && isset($input['extra'])) {
                    $output .= '</div>';
                }

                if ($input['arguments']['inline'] == 'true' && !isset($input['extra'])) {
                    $output .= '</div>';
                }

                if ($input['arguments']['type'] == 'text' || $input['arguments']['type'] == 'text_extended') {
                    $output .= '<div class="discovery_text_input">';
                    $output .= self::printInput($input['arguments']);
                    $output .= '</div>';
                } else if ($input['arguments']['inline'] == 'true') {
                    if (isset($input['extra'])) {
                        $output .= '<div  >';
                        $output .= '<div class="float-left">';
                    } else {
                        $output .= '<div class="w50p">';
                        $output .= '<div class="float-right">';
                    }

                    $output .= self::printInput($input['arguments']);
                    $output .= '</div>';
                    $output .= '</div>';

                    if (isset($input['extra'])) {
                        $output .= '</div>';
                    }
                } else {
                    $output .= self::printInput($input['arguments']);
                }

                // Allow dynamic content.
                $output .= $input['extra'];
                $output .= '</div>';
            } else {
                $output .= self::printInput($input['arguments']);
                // Allow dynamic content.
                $output .= $input['extra'];
            }
        }

        if ($return === false) {
            echo $output;
        }

        return $output;
    }


    /**
     * Print a block of inputs as a list element.
     *
     * @param array   $input  Definition of target block to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML content.
     */
    public static function printBlockAsList(array $input, bool $return=false)
    {
        $output = '';
        if ($input['hidden'] == 1) {
            $class = ' hidden';
        } else {
            $class = '';
        }

        if (isset($input['class']) === true) {
            $class = $input['class'].$class;
        }

        if (is_array($input['block_content']) === true) {
            // Print independent block of inputs.
            $output .= '<li id="'.$input['block_id'].'" class="'.$class.'">';
            $output .= '<ul class="wizard list '.$input['block_class'].'">';
            foreach ($input['block_content'] as $input) {
                $output .= self::printBlockAsList($input, $return);
            }

            $output .= '</ul></li>';
        } else {
            if ($input['arguments']['type'] != 'hidden'
                && $input['arguments']['type'] != 'hidden_extended'
            ) {
                $output .= '<li id="'.$input['id'].'" class="'.$class.'">';
                $output .= '<label>'.$input['label'].'</label>';
                if (isset($input['extra-container']) === true && $input['extra-container'] === true) {
                    $output .= '<div class="flex extra-container-input">';
                }

                $output .= self::printInput($input['arguments']);
                // Allow dynamic content.
                $output .= $input['extra'];

                if (isset($input['extra-container']) === true && $input['extra-container'] === true) {
                    $output .= '</div>';
                }

                $output .= '</li>';
            } else {
                $output .= self::printInput($input['arguments']);
                // Allow dynamic content.
                $output .= $input['extra'];
            }
        }

        if ($return === false) {
            echo $output;
        }

        return $output;
    }


    /**
     * Print a form.
     *
     * @param array   $data            Definition of target form to be printed.
     * @param boolean $return          Return as string or direct output.
     * @param boolean $print_white_box Print a white box.
     *
     * @return string HTML code.
     */
    public static function printForm(
        array $data,
        bool $return=false,
        bool $print_white_box=false
    ) {
        $form = ($data['form'] ?? null);
        $inputs = ($data['inputs'] ?? []);
        $blocks = ($data['blocks'] ?? []);
        $rawInputs = ($data['rawInputs'] ?? null);
        $js = ($data['js'] ?? null);
        $rawjs = ($data['js_block'] ?? null);
        $cb_function = ($data['cb_function'] ?? null);
        $cb_args = ($data['cb_args'] ?? null);

        $output_head = '';
        if (empty($data['pre-content']) === false) {
            $output_head .= $data['pre-content'];
        }

        if (isset($data['form']) === true) {
            $output_head .= '<form ';
            if (isset($form['name']) === true) {
                $output_head .= 'name="'.$form['name'].'" ';
            }

            if (isset($form['id']) === true) {
                $output_head .= 'id="'.$form['id'].'" ';
            }

            $output_head .= 'class="max_floating_element_size ';
            if (isset($form['class']) === true) {
                $output_head .= 'discovery '.$form['class'].' ';
            }

            $output_head .= '"';

            if (isset($form['onsubmit']) === true) {
                $output_head .= 'onsubmit="'.$form['onsubmit'].'" ';
            }

            if (isset($form['enctype']) === true) {
                $output_head .= 'enctype="'.$form['enctype'].'" ';
            }

            if (isset($form['action']) === true) {
                $output_head .= 'action="'.$form['action'].'" ';
            }

            if (isset($form['method']) === true) {
                $output_head .= 'method="'.$form['method'].'" ';
            }

            if (isset($form['extra']) === true) {
                $output_head .= $form['extra'];
            }

            $output_head .= '>';
        }

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    array_values((isset($cb_args) === true) ? $cb_args : [])
                );
            }
        } catch (Exception $e) {
            error_log('Error executing wizard callback: ', $e->getMessage());
        }

        $output_submit = '';
        $output = '';

        if (empty($blocks) === false) {
            $output .= '<div class="container-block-column">';
            foreach ($blocks as $valueblock) {
                $output .= '<ul class="wizard">';
                foreach ($inputs[$valueblock] as $input) {
                    if (is_array(($input['arguments'] ?? null)) === true
                        && isset($input['arguments']) === true
                        && isset($input['arguments']['type']) === true
                        && $input['arguments']['type'] === 'submit'
                    ) {
                        $output_submit .= self::printBlock($input, true);
                    } else {
                        $output .= self::printBlock($input, true);
                    }
                }

                $output .= '</ul>';
            }

            $output .= '</div>';
        } else {
            if ($print_white_box === true) {
                $output .= '<div class="white_box pdd_15px">';
            }

            $output .= '<ul class="wizard">';

            foreach ($inputs as $input) {
                if (is_array(($input['arguments'] ?? null)) === true
                    && isset($input['arguments']) === true
                    && isset($input['arguments']['type']) === true
                    && $input['arguments']['type'] === 'submit'
                ) {
                    $output_submit .= self::printBlock($input, true);
                } else {
                    $output .= self::printBlock($input, true);
                }
            }

            $output .= '</ul>';

            // There is possible add raw inputs for this form.
            if (empty($rawInputs) === false) {
                $output .= $rawInputs;
            }

            if ($print_white_box === true) {
                $output .= '</div>';
            }

            $output .= '<ul class="wizard">'.$output_submit.'</ul>';
        }

        $output .= html_print_csrf_hidden(true);
        $output .= '</form>';
        $output .= '<script>'.$js.'</script>';
        if ($rawjs) {
            $output .= $rawjs;
        }

        if ($return === false) {
            echo $output;
        }

        return $output_head.$output;

    }


    /**
     * Print a form as a grid of inputs.
     *
     * @param array   $data   Definition of target form to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML code.
     */
    public static function printFormAsGrid(array $data, bool $return=false)
    {
        $form = $data['form'];

        $rows = $data['rows'];
        $rawInputs = $data['rawInputs'];
        $js = $data['js'];
        $rawjs = $data['js_block'];
        $cb_function = $data['cb_function'];
        $cb_args = $data['cb_args'];

        $output_head = '<form class="discovery '.$form['class'].'" onsubmit="'.$form['onsubmit'].'"  enctype="'.$form['enctype'].'" action="'.$form['action'].'" method="'.$form['method'];
        $output_head .= '" id="'.$form['id'].'" '.$form['extra'].'>';

        if (isset($form['title']) === true && empty($form['title']) === false) {
            $output_head .= '<div class="form_title"">';
            $output_head .= '<span>'.$form['title'].'</span>';
            $output_head .= '</div>';
        }

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    array_values((isset($cb_args) === true) ? $cb_args : [])
                );
            }
        } catch (Exception $e) {
            error_log('Error executing wizard callback: ', $e->getMessage());
        }

        $output_submit = '';
        $output = '';

        $first_block_printed = false;

        if (is_array($rows)) {
            foreach ($rows as $row) {
                if ($row['new_form_block'] == true) {
                    if ($first_block_printed === true) {
                        // If first form block has been placed, then close it before starting a new one.
                        $output .= '</div>';
                        $output .= '<div class="white_box mrgn_top_30px">';
                    } else {
                        $output .= '<div class="white_box">';
                    }

                    $first_block_printed = true;
                }

                $output .= '<div class="edit_discovery_info '.$row['class'].'" style="'.$row['style'].'">';

                foreach ($row['columns'] as $column) {
                    $width = isset($column['width']) ? 'width: '.$column['width'].';' : 'width: 100%;';
                    $padding_left = isset($column['padding-left']) ? 'padding-left: '.$column['padding-left'].';' : 'padding-left: 0;';
                    $padding_right = isset($column['padding-right']) ? 'padding-right: '.$column['padding-right'].';' : 'padding-right: 0;';
                    $extra_styles = isset($column['style']) ? $column['style'] : '';
                    $class = isset($column['class']) ? $column['class'] : '';

                    $output .= '<div class="'.$class.'" ';
                    $output .= ' style="'.$width.$padding_left.$padding_right;
                    $output .= $extra_styles.'">';

                    // Toggle option.
                    foreach ($column['inputs'] as $input) {
                        if (is_array($input)) {
                            if ($input['arguments']['type'] != 'submit') {
                                if ($input['toggle'] === true) {
                                    $output .= ui_print_toggle(
                                        [
                                            'name'            => (isset($input['toggle_name']) ? $input['toggle_name'] : 'toggle_'.uniqid()),
                                            'title'           => $input['toggle_title'],
                                            'id'              => $input['toggle_id'],
                                            'hidden_default'  => $input['toggle_hidden_default'],
                                            'content'         => self::printBlockAsGrid(
                                                $input,
                                                true
                                            ),
                                            'return'          => true,
                                            'name'            => (isset($input['toggle_name']) ? $input['toggle_name'] : 'toggle_'.uniqid()),
                                            'toggle_class'    => $input['toggle_toggle_class'],
                                            'main_class'      => $input['toggle_main_class'],
                                            'container_class' => $input['toggle_container_class'],
                                            'img_a'           => $input['toggle_img_a'],
                                            'img_b'           => $input['toggle_img_b'],
                                            'clean'           => (isset($input['toggle_clean']) ? $input['toggle_clean'] : true),
                                        ]
                                    );
                                } else {
                                    $output .= self::printBlockAsGrid($input, true);
                                }
                            } else {
                                $output_submit .= self::printBlockAsGrid($input, true);
                            }
                        } else {
                            $output .= $input;
                        }
                    }

                    $output .= '</div>';
                }

                $output .= '</div>';
            }
        }

        // There is possible add raw inputs for this form.
        if (empty($rawInputs) === false) {
            $output .= $rawInputs;
        }

        $output .= '</div>';

        $output .= '<ul class="wizard">'.$output_submit.'</ul>';
        $output .= html_print_csrf_hidden(true);
        $output .= '</form>';
        $output .= '<script>'.$js.'</script>';
        if ($rawjs) {
            $output .= $rawjs;
        }

        if ($return === false) {
            echo $output;
        }

        return $output_head.$output;

    }


    /**
     * Print a form as a list.
     *
     * @param array   $data   Definition of target form to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML code.
     */
    public static function printFormAsList(array $data, bool $return=false)
    {
        $form = $data['form'];
        $inputs = $data['inputs'];
        $rawInputs = $data['rawInputs'];
        $js = $data['js'];
        $rawjs = $data['js_block'];
        $cb_function = $data['cb_function'];
        $cb_args = $data['cb_args'];

        $output_head = '<form class="discovery max_floating_element_size" id="'.$form['id'].'" onsubmit="'.$form['onsubmit'].'" enctype="'.$form['enctype'].'" action="'.$form['action'].'" method="'.$form['method'];
        $output_head .= '" '.$form['extra'].'>';

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    array_values((isset($cb_args) === true) ? $cb_args : [])
                );
            }
        } catch (Exception $e) {
            error_log('Error executing wizard callback: ', $e->getMessage());
        }

        $output = '<div class="white_box pdd_15px">';
        $output .= '<ul class="wizard">';

        foreach ($inputs as $input) {
            if ($input['arguments']['type'] != 'submit') {
                $output .= self::printBlockAsList($input, true);
            } else {
                $output_submit = self::printBlockAsList($input, true);
            }
        }

        $output .= '</ul>';
        // There is possible add raw inputs for this form.
        if (empty($rawInputs) === false) {
            $output .= $rawInputs;
        }

        $output .= '</div>';

        $output .= '<ul class="wizard">'.$output_submit.'</ul>';
        $output .= html_print_csrf_hidden(true);
        $output .= '</form>';
        $output .= '<script>'.$js.'</script>';
        if ($rawjs) {
            $output .= $rawjs;
        }

        if ($return === false) {
            echo $output;
        }

        return $output_head.$output;

    }


    /**
     * Print a big button element (huge image, big text and link).
     *
     * @param array $data Element data (link, image...).
     *
     * @return void Only prints the element.
     */
    public static function printBigButtonElement($data)
    {
        if (isset($data['url']) === false) {
            $data['url'] = '#';
        }

        ?>
        <li class="discovery">
            <a href="<?php echo $data['url']; ?>">
                <div class="data_container">
                    <?php html_print_image($data['icon']); ?>
                    <br><label id="text_wizard">
                        <?php echo io_safe_output($data['label']); ?>
                    </label>
                </div>
            </a>
        </li>
        <?php
    }


    /**
     * Print a list of big buttons elements.
     *
     * @param array $list_data Array of data for printBigButtonElement.
     *
     * @return void Print the full list.
     */
    public static function printBigButtonsList($list_data)
    {
        echo '<ul class="bigbuttonlist">';
        array_map('self::printBigButtonElement', $list_data);
        echo '</ul>';
    }


    /**
     * Returns a n-dimensional array (data) into a html tree structure.
     *
     * Advanced documentation:
     *   https://www.jqueryscript.net/other/Checkable-Hierarchical-Tree.html
     *
     * @param string $target   Target DOM id.
     * @param array  $data     N-dimensional array.
     * @param string $onclick  Onclick function.
     * @param string $onchange Onchange function.
     *
     * @return string
     */
    public static function printTree(
        $target,
        $data,
        $onclick='',
        $onchange=''
    ) {
        ui_require_css_file('simTree');
        ui_require_javascript_file('simTree');

        /*
         * SAMPLE SELECT ALL.
         *
         * echo '<div class="subtitle">';
         * echo '<span>';
         * echo __('Please select devices to be monitored');
         * echo '</span><div class="manage">';
         * echo '<button onclick="$(\'.sim-tree li a\').each(function(){simTree_tree.doCheck($(this), false); simTree_tree.clickNode($(this));});">';
         * echo __('select all');
         * echo '</button>';
         * echo '<button onclick="$(\'.sim-tree li a\').each(function(){simTree_tree.doCheck($(this), true); simTree_tree.clickNode($(this));});">';
         * echo __('deselect all');
         * echo '</button>';
         * echo '</div>';
         * echo '</div>';
         *
         * END SAMPLE SELECT ALL.
         */

        $output = '
<script type="text/javascript">
var simTree_'.$target.';
    $(document).ready(function() {
        simTree_'.$target.' = simTree({
            el: $('.$target.'),
            data: '.json_encode($data).',
            onClick: function (item) {'.$onclick.';},
            onChange: function (item) {
                '.$onchange.';
                $("#tree-data-'.$target.'").val(JSON.stringify(this.sels));
            },
            check: true,
            linkParent: true
        });
        $("#'.$target.'").append(
            \'<input type="hidden" id="tree-data-'.$target.'" name="tree-data-'.$target.'">\'
        );

        $("#tree-data-'.$target.'").val(JSON.stringify(simTree_'.$target.'.sels));
    });
</script>';

        return $output;
    }


}
