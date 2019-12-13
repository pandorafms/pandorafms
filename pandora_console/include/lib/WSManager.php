<?php
/**
 * PHP WebSocketServer Manager.
 *
 * Adapted to PandoraFMS by Fco de Borja Sanchez <fborja.sanchez@artica.es>
 * Compatible with PHP >= 7.0
 *
 * @category   External library
 * @package    Pandora FMS
 * @subpackage WebSocketServer
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
namespace PandoraFMS\WebSockets;

use \PandoraFMS\Websockets\WebSocketServer;
use \PandoraFMS\Websockets\WebSocketUser;
use \PandoraFMS\User;


require_once __DIR__.'/../functions.php';

/**
 * Redirects ws communication between two endpoints.
 */
class WSManager extends WebSocketServer
{

    /**
     * Target host.
     *
     * @var string
     */
    private $intHost = '127.0.0.1';

    /**
     * Target port
     *
     * @var integer
     */
    private $intPort = 8080;

    /**
     * Internal URL.
     *
     * @var string
     */
    private $intUrl = '/ws';

    /**
     * 1MB... overkill for an echo server, but potentially plausible for other
     * applications.
     *
     * @var integer
     */
    public $maxBufferSize = 1048576;

    /**
     * Interactive mode.
     *
     * @var boolean
     */
    public $interative = true;

    /**
     * Use a timeout of 100 milliseconds to search for messages..
     *
     * @var integer
     */
    public $timeout = 250;

    /**
     * Handlers for connected step:
     *   'protocol' => 'function';
     *
     * @var array
     */
    public $handlerConnected = [];

    /**
     * Handlers for process step:
     *   'protocol' => 'function';
     *
     * @var array
     */
    public $handlerProcess = [];

    /**
     * Handlers for processRaw step:
     *   'protocol' => 'function';
     *
     * @var array
     */
    public $handlerProcessRaw = [];

    /**
     * Handlers for tick step:
     *   'protocol' => 'function';
     *
     * @var array
     */
    public $handlerTick = [];

    /**
     * Allow only one connection per user session.
     *
     * @var boolean
     */
    public $socketPerSession = false;


    /**
     * Builder.
     *
     * @param string  $listen_addr  Target address (external).
     * @param integer $listen_port  Target port (external).
     * @param array   $connected    Handlers for <connected> step.
     * @param array   $process      Handlers for <process> step.
     * @param array   $processRaw   Handlers for <processRaw> step.
     * @param array   $tick         Handlers for <tick> step.
     * @param integer $bufferLength Max buffer size.
     * @param boolean $debug        Enable traces.
     */
    public function __construct(
        $listen_addr,
        int $listen_port,
        $connected=[],
        $process=[],
        $processRaw=[],
        $tick=[],
        $bufferLength=1048576,
        $debug=false
    ) {
        $this->maxBufferSize = $bufferLength;
        $this->debug = $debug;

        // Configure handlers.
        $this->handlerConnected = $connected;
        $this->handlerProcess = $process;
        $this->handlerProcessRaw = $processRaw;
        $this->handlerTick = $tick;

        $this->userClass = '\\PandoraFMS\\Websockets\\WebSocketUser';
        parent::__construct($listen_addr, $listen_port, $bufferLength);
    }


    /**
     * Call a target handler function.
     *
     * @param User  $user      User.
     * @param array $handler   Internal handler.
     * @param array $arguments Arguments for handler function.
     *
     * @return mixed handler return or null.
     */
    public function callHandler($user, $handler, $arguments)
    {
        if (isset($user->headers['sec-websocket-protocol'])) {
            $proto = $user->headers['sec-websocket-protocol'];
            if (isset($handler[$proto])
                && function_exists($handler[$proto])
            ) {
                // Launch configured handler.
                $this->stderr('Calling '.$handler[$proto]);
                return call_user_func_array(
                    $handler[$proto],
                    $arguments
                );
            }
        }

        return null;
    }


    /**
     * Read from user's socket.
     *
     * @param object $user Target user connection.
     *
     * @return string Buffer.
     */
    public function readSocket($user)
    {
        $buffer;

        $numBytes = socket_recv(
            $user->socket,
            $buffer,
            $this->maxBufferSize,
            0
        );
        if ($numBytes === false) {
            // Failed. Disconnect.
            $this->handleSocketError($user->socket);
            return false;
        } else if ($numBytes == 0) {
            $this->disconnect($user->socket);
            $this->stderr(
                'Client disconnected. TCP connection lost: '.$user->socket
            );
            return false;
        }

        $user->lastRawPacket = $buffer;
        return $buffer;
    }


    /**
     * Write to socket.
     *
     * @param object $user    Target user connection.
     * @param string $message Target message to be sent.
     *
     * @return void
     */
    public function writeSocket($user, $message)
    {
        if (is_resource($user->socket)) {
            if (!socket_write($user->socket, $message)) {
                $this->disconnect($user->socket);
            }
        } else {
            // Failed. Disconnect all.
            $this->disconnect($user->socket);
            $this->disconnect($user->redirect->socket);
        }

    }


    /**
     * User already connected.
     *
     * @param object $user User.
     *
     * @return void
     */
    public function connected($user)
    {
        global $config;

        $match;
        $php_session_id = '';
        \preg_match(
            '/PHPSESSID=(.*)/',
            $user->headers['cookie'],
            $match
        );

        if (is_array($match) === true) {
                $php_session_id = $match[1];
        }

        $php_session_id = \preg_replace('/;.*$/', '', $php_session_id);

        // If being redirected from proxy.
        if (isset($user->headers['x-forwarded-for']) === true) {
            $user->address = $user->headers['x-forwarded-for'];
        }

        $user->account = new User(['phpsessionid' => $php_session_id]);
        $_SERVER['REMOTE_ADDR'] = $user->address;

        // Ensure user is allowed to connect.
        if (\check_login(false) === false) {
            $this->disconnect($user->socket);
            \db_pandora_audit(
                'WebSockets engine',
                'Trying to access websockets engine without a valid session',
                'N/A'
            );
            return;
        }

        // User exists, and session is valid.
        \db_pandora_audit(
            'WebSockets engine',
            'WebSocket connection started',
            $user->account->idUser
        );
        $this->stderr('ONLINE '.$user->address.'('.$user->account->idUser.')');

        if ($this->socketPerSession === true) {
            // Disconnect previous sessions.
            $this->cleanupSocketByCookie($user);
        }

        // Launch registered handler.
        $this->callHandler(
            $user,
            $this->handlerConnected,
            [
                $this,
                $user,
            ]
        );
    }


    /**
     * Protocol.
     *
     * @param string $protocol Protocol.
     *
     * @return string
     */
    public function processProtocol($protocol): string
    {
        return 'Sec-Websocket-Protocol: '.$protocol."\r\n";
    }


    /**
     * Process programattic function
     *
     * @return void
     */
    public function tick()
    {
        foreach ($this->users as $user) {
            // Launch registered handler.
            $this->callHandler(
                $user,
                $this->handlerTick,
                [
                    $this,
                    $user,
                ]
            );
        }

    }


    /**
     * Process undecoded user message.
     *
     * @param object $user   User.
     * @param string $buffer Message.
     *
     * @return boolean
     */
    public function processRaw($user, $buffer)
    {
        // Launch registered handler.
        return $this->callHandler(
            $user,
            $this->handlerProcessRaw,
            [
                $this,
                $user,
                $buffer,
            ]
        );
    }


    /**
     * Process user message. Implement.
     *
     * @param object  $user        User.
     * @param string  $message     Message.
     * @param boolean $str_message String message or not.
     *
     * @return void
     */
    public function process($user, $message, $str_message)
    {
        if ($str_message === true) {
            $remmitent = $user->address.'('.$user->account->idUser.')';
            $this->stderr($remmitent.': '.$message);
        }

        // Launch registered handler.
        $this->callHandler(
            $user,
            $this->handlerProcess,
            [
                $this,
                $user,
                $message,
                $str_message,
            ]
        );
    }


    /**
     * Also close internal socket.
     *
     * @param object $user User.
     *
     * @return void
     */
    public function closed($user)
    {
        if ($user->account) {
            $_SERVER['REMOTE_ADDR'] = $user->address;
            \db_pandora_audit(
                'WebSockets engine',
                'WebSocket connection finished',
                $user->account->idUser
            );

            $this->stderr('OFFLINE '.$user->address.'('.$user->account->idUser.')');
        }

        // Ensure both sockets are disconnected.
        $this->disconnect($user->socket);
        if ($user->redirect) {
            $this->disconnect($user->redirect->socket);
        }
    }


}
