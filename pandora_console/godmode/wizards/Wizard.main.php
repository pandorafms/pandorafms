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
global $config;

require_once $config['homedir'].'/vendor/autoload.php';
require_once $config['homedir'].'/include/class/HTML.class.php';

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
     * Return formatted html for error handler.
     *
     * @param string $message Error mesage.
     *
     * @return string
     */
    public function error($message)
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
    public function printGoBackButton($url=null, $return=false)
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
            return $this->printForm($form, $return);
        }

        $this->printForm($form, $return);
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
        return HTML::printForm($data, $return, $print_white_box);
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
        return HTML::printFormAsGrid($data, $return);
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
        return HTML::printFormAsList($data, $return);
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
