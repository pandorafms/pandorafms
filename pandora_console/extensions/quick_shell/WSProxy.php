<?php

require_once __DIR__.'/WebSocketServer.php';
require_once __DIR__.'/WSProxyUser.php';

use \PandoraFMS\WebSocketServer;


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
    protected $interative = false;


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
        $this->interactive = $debug;
        $this->userClass = 'WSProxyUser';
        parent::__construct($listen_addr, $listen_port, $bufferLength);
    }


    /**
     * Read from socket
     *
     * @param socket $socket Target connection.
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
            $this->disconnect($user->socket);
            return false;
        }

        return $this->splitPacket($numBytes, $buffer, $user);
    }


    /**
     * Write to socket.
     *
     * @param socket $socket  Target socket.
     * @param string $message Target message to be sent.
     *
     * @return void
     */
    protected function writeSocket($user, $message)
    {
        if ($user->socket) {
            socket_write(
                $user->socket,
                $message
            );
        } else {
            // Failed. Disconnect.
            $this->disconnect($user->socket);
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
        echo '** CONNECTED'."\n";

        /*
         * $user->intSocket is connected to internal.
         * $user->socket is connected to external.
         */

        // Create a new socket connection (internal).
        $intUser = $this->connectInt($this->rawHeaders);

        // Map user.
        $user->intUser = $intUser;
        // And socket.
        $user->intSocket = $intUser->socket;

        $response = $this->readSocket($intUser);

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
     * From client to socket (internal);
     *
     * @param object $user    Caller.
     * @param string $message Message.
     *
     * @return void
     */
    protected function process($user, $message)
    {
        echo '>> ['.$user->id.'] => ['.$user->intUser->id."]\n";
        echo $this->dump($this->rawPacket);
        $this->writeSocket(
            $user->intUser,
            $this->rawPacket
        );

        echo '<< ['.$user->intUser->id.'] => ['.$user->id."]\n";
        $response = $this->readSocket($user->intUser);
        $this->send($user, $response);
        echo $this->dump($this->rawPacket);
        print "\n********************************************\n";
        print "\n\n\n";
        print "\n\n\n";
        print "\n\n\n";
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
        $response = 'GET '.$this->intUrl." HTTP/1.1\r\n";
        $response .= 'Host: '.$this->intHost."\r\n";
        $response .= "Connection: Close\r\n\r\n";
        $this->writeSocket($user->intUser, $response);
        $response = $this->readSocket($user->intUser);

        $this->disconnect($user->intSocket);
        $this->disconnect($user->socket);

    }


    public function out($ss, $str)
    {
        echo $ss."\n";
        echo $this->dump($str);
    }


}
