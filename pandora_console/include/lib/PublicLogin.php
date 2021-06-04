<?php
/**
 * Public access interface to provide access using hash and id_user.
 *
 * @category   Interfaces
 * @package    Pandora FMS
 * @subpackage Login
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
namespace PandoraFMS;

interface PublicLogin
{


    /**
     * Generates a hash to authenticate in public views.
     *
     * @param string|null $other_secret If you need to authenticate using a
     * varable string, use this 'other_secret' to customize the hash.
     *
     * @return string Returns a hash with the authenticaction.
     */
    public static function generatePublicHash(?string $other_secret=''):string;


    /**
     * Validates a hash to authenticate in public view.
     *
     * @param string $hash         Hash to be checked.
     * @param string $other_secret Any custom string needed for you.
     *
     * @return boolean Returns true if hash is valid.
     */
    public static function validatePublicHash(
        string $hash,
        string $other_secret=''
    ):bool;


}
