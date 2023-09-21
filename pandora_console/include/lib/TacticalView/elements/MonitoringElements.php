<?php
/**
 * MonitoringElements element for tactical view.
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
 * MonitoringElements, this class contain all logic for this section.
 */
class MonitoringElements extends Element
{


    /**
     * Constructor
     */
    public function __construct()
    {
        $this->title = __('Monitoring elements');
    }


}
