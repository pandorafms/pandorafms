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
    }


     /**
      * Returns the html status of log storage.
      *
      * @return string
      */
    public function getStatus():string
    {
        // TODO connect to automonitorization.
        $status = true;

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

        $output = $image_status.$text;

        return html_print_div(
            [
                'content' => $output,
                'class'   => 'flex_center margin-top-5',
                'style'   => 'margin: 0px 10px 10px 10px;',
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
        // TODO connect to automonitorization.
        return html_print_div(
            [
                'content' => '9.999.999',
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
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
        // TODO connect to automonitorization.
        return html_print_div(
            [
                'content' => '9.999.999',
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
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
        // TODO connect to automonitorization.
        return html_print_div(
            [
                'content' => '9.999.999',
                'class'   => 'text-l',
                'style'   => 'margin: 0px 10px 0px 10px;',
            ],
            true
        );
    }


}
