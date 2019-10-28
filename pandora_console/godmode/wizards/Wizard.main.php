<?php
/**
 * Extension to schedule tasks on Pandora FMS Console
 *
 * @category   Wizard
 * @package    Pandora FMS
 * @subpackage Wizard skel
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2005-2019 Artica Soluciones Tecnologicas
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
  * Global Wizard generic class. Needs to be inherited.
  *
  * Used in Hostdevices class, Applications class and others, is the core of
  * Discovery proyect.
  */
class Wizard
{

    /**
     * Breadcrum
     *
     * @var array.
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
     * @param string $breads Elements to add to breadcrum.
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
    public function aclMulticheck($access=null)
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
                0,
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
            '<span class="breadcrumb_link">&nbsp/&nbsp</span>',
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
     * Print input using functions html lib.
     *
     * @param array $data Input definition.
     *
     * @return string HTML code for desired input.
     */
    public function printInput($data)
    {
        global $config;

        include_once $config['homedir'].'/include/functions_html.php';

        if (is_array($data) === false) {
            return '';
        }

        $input = html_print_input(($data + ['return' => true]), 'div', true);
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
    public function printGoBackButton($url=null)
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
                        'attributes' => 'class="sub cancel"',
                        'return'     => true,
                    ],
                ],
            ],
        ];

        $this->printForm($form);
    }


    /**
     * Print a block of inputs.
     *
     * @param array   $input  Definition of target block to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML content.
     */
    public function printBlock(array $input, bool $return=false)
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
            if ($input['wrapper']) {
                $output .= '<li id="li-'.$input['block_id'].'" class="'.$class.'">';
                $output .= '<'.$input['wrapper'].' id="'.$input['block_id'].'" class="'.$class.'">';
            } else {
                $output .= '<li id="'.$input['block_id'].'" class="'.$class.'">';
            }

            $output .= '<ul class="wizard '.$input['block_class'].'">';
            foreach ($input['block_content'] as $input) {
                $output .= $this->printBlock($input, $return);
            }

            // Close block.
            if ($input['wrapper']) {
                $output .= '</ul></'.$input['wrapper'].'>';
            } else {
                $output .= '</ul></li>';
            }
        } else {
            if ($input['arguments']['type'] != 'hidden') {
                $output .= '<li id="'.$input['id'].'" class="'.$class.'">';
                $output .= '<label>'.$input['label'].'</label>';
                $output .= $this->printInput($input['arguments']);
                // Allow dynamic content.
                $output .= $input['extra'];
                $output .= '</li>';
            } else {
                $output .= $this->printInput($input['arguments']);
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
     * Print a block of inputs with grid format.
     *
     * @param array   $input  Definition of target block to be printed.
     * @param boolean $return Return as string or direct output.
     *
     * @return string HTML content.
     */
    public function printBlockAsGrid(array $input, bool $return=false)
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
            $output .= '<ul class="wizard '.$input['block_class'].'">';
            foreach ($input['block_content'] as $input) {
                $output .= $this->printBlockAsGrid($input, $return);
            }

            $output .= '</ul></li>';
        } else {
            if ($input['arguments']['type'] != 'hidden') {
                if ($input['arguments']['inline'] != 'true') {
                    $output .= '<div class="edit_discovery_input">';
                } else {
                    $output .= '<div style="display: flex; margin-bottom: 25px; flex-wrap: wrap;">';
                    if (!isset($input['extra'])) {
                        $output .= '<div style="width: 50%;">';
                    }

                    if (isset($input['extra'])) {
                        $output .= '<div style="display: flex; margin-right:10px;">';
                    }
                }

                if ($input['arguments']['inline'] == 'true' && isset($input['extra'])) {
                    $output .= '<div style="margin-right:10px;">';
                }

                $output .= '<div class="label_select">';
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
                    $output .= $this->printInput($input['arguments']);
                    $output .= '</div>';
                } else if ($input['arguments']['inline'] == 'true') {
                    if (isset($input['extra'])) {
                        $output .= '<div style="">';
                        $output .= '<div style="float: left;">';
                    } else {
                        $output .= '<div style="width:50%;">';
                        $output .= '<div style="float: right;">';
                    }

                    $output .= $this->printInput($input['arguments']);
                    $output .= '</div>';
                    $output .= '</div>';

                    if (isset($input['extra'])) {
                        $output .= '</div>';
                    }
                } else {
                    $output .= $this->printInput($input['arguments']);
                }

                // Allow dynamic content.
                $output .= $input['extra'];
                $output .= '</div>';
            } else {
                $output .= $this->printInput($input['arguments']);
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
    public function printBlockAsList(array $input, bool $return=false)
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
            $output .= '<ul class="wizard '.$input['block_class'].'">';
            foreach ($input['block_content'] as $input) {
                $output .= $this->printBlockAsList($input, $return);
            }

            $output .= '</ul></li>';
        } else {
            if ($input['arguments']['type'] != 'hidden') {
                $output .= '<li id="'.$input['id'].'" class="'.$class.'">';
                $output .= '<label>'.$input['label'].'</label>';
                $output .= $this->printInput($input['arguments']);
                // Allow dynamic content.
                $output .= $input['extra'];
                $output .= '</li>';
            } else {
                $output .= $this->printInput($input['arguments']);
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
    public function printForm(
        array $data,
        bool $return=false,
        bool $print_white_box=false
    ) {
        $form = $data['form'];
        $inputs = $data['inputs'];
        $js = $data['js'];
        $rawjs = $data['js_block'];
        $cb_function = $data['cb_function'];
        $cb_args = $data['cb_args'];

        $output_head = '<form id="'.$form['id'].'" class="discovery '.$form['class'].'" onsubmit="'.$form['onsubmit'].'" enctype="'.$form['enctype'].'" action="'.$form['action'].'" method="'.$form['method'];
        $output_head .= '" '.$form['extra'].'>';

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    (isset($cb_args) === true) ? $cb_args : []
                );
            }
        } catch (Exception $e) {
            error_log('Error executing wizard callback: ', $e->getMessage());
        }

        $output_submit = '';
        $output = '';

        if ($print_white_box === true) {
            $output .= '<div class="white_box">';
        }

        $output .= '<ul class="wizard">';

        foreach ($inputs as $input) {
            if ($input['arguments']['type'] != 'submit') {
                $output .= $this->printBlock($input, true);
            } else {
                $output_submit .= $this->printBlock($input, true);
            }
        }

        $output .= '</ul>';

        if ($print_white_box === true) {
            $output .= '</div>';
        }

        $output .= '<ul class="wizard">'.$output_submit.'</ul>';
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
    public function printFormAsGrid(array $data, bool $return=false)
    {
        $form = $data['form'];

        $rows = $data['rows'];

        $js = $data['js'];
        $rawjs = $data['js_block'];
        $cb_function = $data['cb_function'];
        $cb_args = $data['cb_args'];

        $output_head = '<form class="discovery" onsubmit="'.$form['onsubmit'].'"  enctype="'.$form['enctype'].'" action="'.$form['action'].'" method="'.$form['method'];
        $output_head .= '" '.$form['extra'].'>';

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    (isset($cb_args) === true) ? $cb_args : []
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
                        $output .= '<div class="white_box" style="margin-top: 30px;">';
                    } else {
                        $output .= '<div class="white_box">';
                    }

                    $first_block_printed = true;
                }

                $output .= '<div class="edit_discovery_info" style="'.$row['style'].'">';

                foreach ($row['columns'] as $column) {
                    $width = isset($column['width']) ? 'width: '.$column['width'].';' : 'width: 100%;';
                    $padding_left = isset($column['padding-left']) ? 'padding-left: '.$column['padding-left'].';' : 'padding-left: 0;';
                    $padding_right = isset($column['padding-right']) ? 'padding-right: '.$column['padding-right'].';' : 'padding-right: 0;';
                    $extra_styles = isset($column['style']) ? $column['style'] : '';

                    $output .= '<div style="'.$width.$padding_left.$padding_right.$extra_styles.'">';

                    foreach ($column['inputs'] as $input) {
                        if (is_array($input)) {
                            if ($input['arguments']['type'] != 'submit') {
                                $output .= $this->printBlockAsGrid($input, true);
                            } else {
                                $output_submit .= $this->printBlockAsGrid($input, true);
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

        $output .= '</div>';

        $output .= '<ul class="wizard">'.$output_submit.'</ul>';
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
    public function printFormAsList(array $data, bool $return=false)
    {
        $form = $data['form'];
        $inputs = $data['inputs'];
        $js = $data['js'];
        $rawjs = $data['js_block'];
        $cb_function = $data['cb_function'];
        $cb_args = $data['cb_args'];

        $output_head = '<form class="discovery" onsubmit="'.$form['onsubmit'].'" enctype="'.$form['enctype'].'" action="'.$form['action'].'" method="'.$form['method'];
        $output_head .= '" '.$form['extra'].'>';

        if ($return === false) {
            echo $output_head;
        }

        try {
            if (isset($cb_function) === true) {
                call_user_func_array(
                    $cb_function,
                    (isset($cb_args) === true) ? $cb_args : []
                );
            }
        } catch (Exception $e) {
            error_log('Error executing wizard callback: ', $e->getMessage());
        }

        $output = '<div class="white_box">';
        $output .= '<ul class="wizard">';

        foreach ($inputs as $input) {
            if ($input['arguments']['type'] != 'submit') {
                $output .= $this->printBlockAsList($input, true);
            } else {
                $output_submit .= $this->printBlockAsList($input, true);
            }
        }

        $output .= '</ul>';
        $output .= '</div>';
        $output .= '<ul class="wizard">'.$output_submit.'</ul>';
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


}
