<?php
/**
 * LogStorage element for tactical view.
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

use PandoraFMS\TacticalView\Element;

/**
 * LogStorage, this class contain all logic for this section.
 */
class LogStorage extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
        $this->title = __('Log storage');
        $this->ajaxMethods = [
            'getStatus',
            'getTotalSources',
            'getStoredData',
            'getAgeOfStoredData',
        ];
        $this->interval = 300000;
        $this->refreshConfig = [
            'status'       => [
                'id'     => 'status-log-storage',
                'method' => 'getStatus',
            ],
            'total-source' => [
                'id'     => 'total-source-log-storage',
                'method' => 'getTotalSources',
            ],
            'total-lines'  => [
                'id'     => 'total-lines-log-storage',
                'method' => 'getStoredData',
            ],
            'age'          => [
                'id'     => 'age-of-stored',
                'method' => 'getAgeOfStoredData',
            ],
        ];
    }


    /**
     * Check if log storage module exist.
     *
     * @return boolean
     */
    public function isEnabled():bool
    {
        global $config;
        if ((bool) $config['log_collector'] === true) {
            return true;
        } else {
            return false;
        }
    }


     /**
      * Returns the html status of log storage.
      *
      * @return string
      */
    public function getStatus():string
    {
        $classDisabled = '';
        if ($this->isEnabled() === true) {
            $value = $this->valueMonitoring('Log server connection');
            $status = ((int) $value[0]['datos'] === 1) ? true : false;
            if ($status === true) {
                $image_status = html_print_image('images/status_check@svg.svg', true);
                $text = html_print_div(
                    [
                        'content' => __('Everything’s OK!'),
                        'class'   => 'status-text',
                    ],
                    true
                );
            } else {
                $image_status = html_print_image('images/status_error@svg.svg', true);
                $text = html_print_div(
                    [
                        'content' => __('Something’s wrong'),
                        'class'   => 'status-text',
                    ],
                    true
                );
            }
        } else {
            $image_status = html_print_image('images/status_check@svg.svg', true);
            $text = html_print_div(
                [
                    'content' => __('Everything’s OK!'),
                    'class'   => 'status-text',
                ],
                true
            );
            $classDisabled = 'alpha50';
        }

        $output = $image_status.$text;

        return html_print_div(
            [
                'content' => $output,
                'class'   => 'flex_center margin-top-5 '.$classDisabled,
                'style'   => 'margin: 0px 10px 10px 10px;',
                'id'      => 'status-log-storage',
            ],
            true
        );
    }


    /**
     * Returns the html of total sources in log storage.
     *
     * @return string
     */
    public function getTotalSources():string
    {
        if ($this->isEnabled() === true) {
            $data = $this->valueMonitoring('Total sources');
            $value = format_numeric($data[0]['datos']);
        } else {
            $value = __('N/A');
        }

        return html_print_div(
            [
                'content' => $value,
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
                'id'      => 'total-source-log-storage',
            ],
            true
        );
    }


    /**
     * Returns the html of lines in log storage.
     *
     * @return string
     */
    public function getStoredData():string
    {
        if ($this->isEnabled() === true) {
            $data = $this->valueMonitoring('Total documents');
            $value = format_numeric($data[0]['datos']);
        } else {
            $value = __('N/A');
        }

        return html_print_div(
            [
                'content' => $value,
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
                'id'      => 'total-lines-log-storage',
            ],
            true
        );
    }


    /**
     * Returns the html of age of stored data.
     *
     * @return string
     */
    public function getAgeOfStoredData():string
    {
        $data = $this->valueMonitoring('Longest data archived');
        $date = $data[0]['datos'];
        if ($date > 0 && $this->isEnabled() === true) {
            $interval = (time() - strtotime($date));
            $days = format_numeric(($interval / 86400), 0);
        } else {
            $days = 'N/A';
        }

        return html_print_div(
            [
                'content' => $days,
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
                'id'      => 'age-of-stored',
            ],
            true
        );
    }


}
