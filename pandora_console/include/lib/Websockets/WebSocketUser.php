<?php
/**
 * PHP WebSocketUser from:
 *
 * Copyright (c) 2012, Adam Alexander
 * All rights reserved.
 *
 * Adapted to PandoraFMS by Fco de Borja Sanchez <fborja.sanchez@artica.es>
 * Compatible with PHP >= 7.0
 *
 * @category   External library
 * @package    Pandora FMS
 * @subpackage WebSocketUser
 * @version    1.0.0
 * @license    See below
 * @filesource https://github.com/ghedipunk/PHP-Websockets
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 *
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * - Neither the name of PHP WebSockets nor the names of its contributors may
 *   be used to endorse or promote products derived from this software without
 *   specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

// Begin.
namespace PandoraFMS\Websockets;


/**
 * Parent class for WebSocket User.
 */
class WebSocketUser
{

    /**
     * Socket.
     *
     * @var Socket
     */
    public $socket;

    /**
     * Id.
     *
     * @var string
     */
    public $id;

    /**
     * Headers.
     *
     * @var array
     */
    public $headers = [];

    /**
     * Handshake.
     *
     * @var boolean
     */
    public $handshake = false;

    /**
     * HandlingPartialPacket.
     *
     * @var boolean
     */
    public $handlingPartialPacket = false;

    /**
     * PartialBuffer.
     *
     * @var string
     */
    public $partialBuffer = '';

    /**
     * SendingContinuous.
     *
     * @var boolean
     */
    public $sendingContinuous = false;

    /**
     * PartialMessage.
     *
     * @var string
     */
    public $partialMessage = '';

    /**
     * HasSentClose.
     *
     * @var boolean
     */
    public $hasSentClose = false;

    /**
     * Raw packet for redirection.
     *
     * @var string
     */
    public $lastRawPacket;

    /**
     * Pair resend packages.
     *
     * @var WebSocketUser
     */
    public $redirect;

    /**
     * Pandora FMS user account.
     *
     * @var User
     */
    public $account;

    /**
     * Remote address.
     *
     * @var string
     */
    public $address;


    /**
     * Initializes a websocket user.
     *
     * @param string  $id     Id of the new user.
     * @param \Socket $socket Socket where communication is stablished.
     */
    public function __construct($id, $socket)
    {
        socket_getpeername($socket, $this->address);
        $this->id = $id;
        $this->socket = $socket;
    }


}
