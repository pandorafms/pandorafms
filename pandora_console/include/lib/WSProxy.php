<?php
/**
 * PHP WebSocketServer Proxy.
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
class WSProxy extends WebSocketServer
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
    protected $maxBufferSize = 1048576;

    /**
     * Interactive mode.
     *
     * @var boolean
     */
    protected $interative = true;

    /**
     * Use a timeout of 100 milliseconds to search for messages..
     *
     * @var integer
     */
    public $timeout = 250;


    /**
     * Builder.
     *
     * @param string  $listen_addr  Target address (external).
     * @param integer $listen_port  Target port (external).
     * @param string  $to_addr      Target address (internal).
     * @param integer $to_port      Target port (internal).
     * @param integer $to_url       Target url (internal).
     * @param integer $bufferLength Max buffer size.
     * @param boolean $debug        Enable traces.
     */
    public function __construct(
        $listen_addr,
        $listen_port,
        $to_addr,
        $to_port,
        $to_url='/ws',
        $bufferLength=1048576,
        $debug=false
    ) {
        $this->intHost = $to_addr;
        $this->intPort = $to_port;
        $this->intUrl = $to_url;
        $this->maxBufferSize = $bufferLength;
        $this->debug = $debug;
        $this->userClass = '\\PandoraFMS\\Websockets\\WebSocketUser';
        parent::__construct($listen_addr, $listen_port, $bufferLength);
    }


    /**
     * Read from user's socket.
     *
     * @param object $user Target user connection.
     *
     * @return string Buffer.
     */
    protected function readSocket($user)
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
    protected function writeSocket($user, $message)
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
     * Connects to internal socket.
     *
     * @param array $headers Communication headers.
     *
     * @return socket Active socket or null.
     */
    protected function connectInt($headers)
    {
        $intSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connect = socket_connect(
            $intSocket,
            $this->intHost,
            $this->intPort
        );
        if (!$connect) {
            return null;
        }

        $c_str = 'GET '.$this->intUrl." HTTP/1.1\r\n";
        $c_str .= 'Host: '.$this->intHost."\r\n";
        $c_str .= "Upgrade: websocket\r\n";
        $c_str .= "Connection: Upgrade\r\n";
        $c_str .= 'Origin: http://'.$this->intHost."\r\n";
        $c_str .= 'Sec-WebSocket-Key: '.$headers['Sec-WebSocket-Key']."\r\n";
        $c_str .= 'Sec-WebSocket-Version: '.$headers['Sec-WebSocket-Version']."\r\n";
        if (isset($headers['Sec-WebSocket-Protocol'])) {
            $c_str .= 'Sec-WebSocket-Protocol: '.$headers['Sec-WebSocket-Protocol']."\r\n";
        }

        $c_str .= "\r\n";

        // Send.
        // Register user - internal.
        $intUser = new $this->userClass('INTERNAL-'.uniqid('u'), $intSocket);

        $intUser->headers = [
            'get'    => $this->intUrl.' HTTP/1.1',
            'host'   => $this->intHost,
            'origin' => $this->intHost,
        ];
        $this->writeSocket($intUser, $c_str);

        return $intUser;
    }


    /**
     * User already connected.
     *
     * @param object $user User.
     *
     * @return void
     */
    protected function connected($user)
    {
        global $config;

        $php_session_id = \str_replace(
            'PHPSESSID=',
            '',
            $user->headers['cookie']
        );

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

        // Disconnect previous sessions.
        $this->cleanupSocketByCookie($user);

        /*
         * $user->intSocket is connected to internal.
         * $user->socket is connected to external.
         */

        // Create a new socket connection (internal).
        $intUser = $this->connectInt($this->rawHeaders);
        if ($intUser === null) {
            $this->disconnect($user->socket);
            return;
        }

        // Map user.
        $user->intUser = $intUser;
        // And socket.
        $user->intSocket = $intUser->socket;
        $user->redirect = $intUser;
        $intUser->redirect = $user;

        // Keep an eye on changes.
        $this->remoteSockets[$intUser->id] = $intUser->socket;
        $this->remoteUsers[$intUser->id] = $intUser;

        // Ignore. Cleanup socket.
        $response = $this->readSocket($user->intUser);
    }


    /**
     * Protocol.
     *
     * @param string $protocol Protocol.
     *
     * @return string
     */
    protected function processProtocol($protocol): string
    {
        return 'Sec-Websocket-Protocol: '.$protocol."\r\n";
    }


    /**
     * Process undecoded user message.
     *
     * @param object $user   User.
     * @param string $buffer Message.
     *
     * @return boolean
     */
    protected function processRaw($user, $buffer)
    {
        if (!isset($user->redirect)) {
            $this->disconnect($user->socket);
            return false;
        }

        $this->stderr($user->id.' >> '.$user->redirect->id);
        $this->stderr($this->dump($buffer));
        $this->writeSocket($user->redirect, $buffer);

        return true;
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
    protected function process($user, $message, $str_message)
    {
        if ($str_message === true) {
            $remmitent = $user->address.'('.$user->account->idUser.')';
            $this->stderr($remmitent.': '.$message);
        }
    }


    /**
     * Also close internal socket.
     *
     * @param object $user User.
     *
     * @return void
     */
    protected function closed($user)
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
