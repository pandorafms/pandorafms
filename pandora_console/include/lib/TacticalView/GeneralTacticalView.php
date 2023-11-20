<?php
/**
 * General tactical view
 *
 * @category   General
 * @package    Pandora FMS
 * @subpackage TacticalView
 * @version    1.0.0
 * @license    See below
 *
 *    ______                 ___                    _______ _______ ________
 *   |   __ \.-----.--.--.--|  |.-----.----.-----. |    ___|   |   |     __|
 *  |    __/|  _  |     |  _  ||  _  |   _|  _  | |    ___|       |__     |
 * |___|   |___._|__|__|_____||_____|__| |___._| |___|   |__|_|__|_______|
 *
 * ============================================================================
 * Copyright (c) 2007-2023 Artica Soluciones Tecnologicas, http://www.artica.es
 * This code is NOT free software. This code is NOT licenced under GPL2 licence
 * You cannnot redistribute it without written permission of copyright holder.
 * ============================================================================
 */

namespace PandoraFMS\TacticalView;

use Exception;
use PandoraFMS\View;

/**
 * General tactical view
 */
class GeneralTacticalView
{

    /**
     * List elements instanced for show in view.
     *
     * @var array
     */
    protected $elements;


    /**
     * Constructor
     */
    public function __construct()
    {
        ui_require_css_file('general_tactical_view');
        ui_require_javascript_file('general_tactical_view');
        $this->elements = $this->instanceElements();
    }


    /**
     * Returns whether general statistics are disabled.
     *
     * @return boolean
     */
    public function disableGeneralStatistics():bool
    {
        global $config;
        if (users_is_admin($config['id_user']) === true) {
            return false;
        } else {
            return (bool) $config['disable_general_statistics'];
        }
    }


    /**
     * Instantiate all the elements that will build the dashboard
     *
     * @return array
     */
    public function instanceElements():array
    {
        global $config;
        $dir = $config['homedir'].'/include/lib/TacticalView/elements/';

        $handle = opendir($dir);
        if ($handle === false) {
            return [];
        }

        $ignores = [
            '.',
            '..',
        ];

        $elements = [];
        $elements['welcome'] = $this->getWelcomeMessage();
        while (false !== ($file = readdir($handle))) {
            try {
                if (in_array($file, $ignores) === true) {
                    continue;
                }

                $filepath = realpath($dir.'/'.$file);
                if (is_readable($filepath) === false
                    || is_dir($filepath) === true
                    || preg_match('/.*\.php$/', $filepath) === false
                ) {
                    continue;
                }

                $className = preg_replace('/.php/', '', $file);
                include_once $filepath;
                if (class_exists($className) === true) {
                    $instance = new $className();
                    $elements[$className] = $instance;
                }
            } catch (Exception $e) {
            }
        }

        return $elements;
    }


    /**
     * Render funcion for print the html.
     *
     * @return void
     */
    public function render():void
    {
        $data = [];
        $data['javascript'] = $this->javascript();
        $data['disableGeneralStatistics'] = $this->disableGeneralStatistics();
        $data = array_merge($data, $this->elements);
        View::render(
            'tacticalView/view',
            $data
        );
    }


    /**
     * Function for print js embedded in html.
     *
     * @return string
     */
    public function javascript():string
    {
        $js = '<script>';
        foreach ($this->elements as $key => $element) {
            if ($element->interval > 0) {
                foreach ($element->refreshConfig as $key => $conf) {
                    $js .= 'autoRefresh('.$element->interval.',"'.$conf['id'].'", "'.$conf['method'].'", "'.$element->nameClass().'");';
                }
            }
        }

        $js .= '</script>';
        return $js;
    }


    /**
     * Return the welcome message.
     *
     * @return string
     */
    private function getWelcomeMessage():string
    {
        global $config;
        $user = users_get_user_by_id($config['id_user']);
        if (is_array($user) === true && count($user) > 0) {
            $name = $user['fullname'];
        } else {
            $name = '';
        }

        if (empty($name) === true) {
            $message = __('Welcome back! 👋');
        } else {
            $message = __('Welcome back %s! 👋', $name);
        }

        return html_print_div(
            [
                'content' => $message,
                'class'   => 'message-welcome',
            ],
            true
        );
    }


}
