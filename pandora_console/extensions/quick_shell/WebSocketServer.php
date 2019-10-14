<?php
/**
 * PHP WebocketServer from:
 *
 * Copyright (c) 2012, Adam Alexander
 * All rights reserved.
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
namespace PandoraFMS;

use \PandoraFMS\WebSocketUser;

/**
 * Abstract class to be implemented.
 */
abstract class WebSocketServer
{

    /**
     * Bae class to be created.
     *
     * @var string
     */
    protected $userClass = 'WebSocketUser';

    /**
     * Redefine this if you want a custom user class.  The custom user class
     * should inherit from WebSocketUser.
     *
     * @var integer
     */
    protected $maxBufferSize;

    /**
     * Max. concurrent connections.
     *
     * @var integer
     */
    protected $maxConnections = 20;

    /**
     * Undocumented variable
     *
     * @var [type]
     */
    protected $master;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $sockets = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $users = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $heldMessages = [];

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $interactive = true;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $headerOriginRequired = false;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $headerSecWebSocketProtocolRequired = false;

    /**
     * Undocumented variable
     *
     * @var array
     */
    protected $headerSecWebSocketExtensionsRequired = false;

    /**
     * Stored raw headers for rediretion.
     *
     * @var array
     */
    public $rawHeaders = [];


    /**
     * Builder.
     *
     * @param string  $addr           Address where websocketserver will listen.
     * @param integer $port           Port where listen.
     * @param integer $bufferLength   Max buffer length.
     * @param integer $maxConnections Max concurrent connections.
     */
    public function __construct(
        string $addr,
        int $port,
        int $bufferLength=2048,
        int $maxConnections=20
    ) {
        if (isset($this->maxBufferSize)
            && $this->maxBufferSize < $bufferLength
        ) {
            $this->maxBufferSize = $bufferLength;
        }

        if (is_numeric($maxConnections) && $maxConnections > 0) {
            $this->maxConnections = $maxConnections;
        }

        $this->master = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->master || die('Failed: socket_create()');

        $__tmp = socket_set_option($this->master, SOL_SOCKET, SO_REUSEADDR, 1);
        $__tmp || die('Failed: socket_option()');

        $__tmp = socket_bind($this->master, $addr, $port);
        $__tmp || die('Failed: socket_bind()');

        $__tmp = socket_listen($this->master, $this->maxConnections);
        $__tmp || die('Failed: socket_listen()');

        $this->sockets['m'] = $this->master;
        $this->stdout("Server started\nListening on: ".$addr.':'.$port."\n");
        $this->stdout('Master socket: '.$this->master."\n");

    }


    /**
     * Process user message. Implement.
     *
     * @param object $user    User.
     * @param string $message Message.
     *
     * @return void
     */
    abstract protected function process($user, string $message);


    /**
     * Called immediately when the data is recieved.
     *
     * @param object $user User.
     *
     * @return void
     */
    abstract protected function connected($user);


    /**
     * Called after the handshake response is sent to the client.
     *
     * @param object $user User.
     *
     * @return void
     */
    abstract protected function closed($user);


    /**
     * Called after the connection is closed.
     * Override to handle a connecting user, after the instance of the User
     * is created, but before the handshake has completed.
     *
     * @param object $user User.
     *
     * @return void
     */
    protected function connecting($user)
    {
        // Optional implementation.
    }


    /**
     * Send a message to target user.
     *
     * @param object $user    User.
     * @param string $message Message.
     *
     * @return void
     */
    protected function send($user, string $message)
    {
        if ($user->handshake) {
            $message = $this->frame($message, $user);
            $result = socket_write($user->socket, $message, strlen($message));
        } else {
            // User has not yet performed their handshake.
            // Store for sending later.
            $holdingMessage = [
                'user'    => $user,
                'message' => $message,
            ];
            $this->heldMessages[] = $holdingMessage;
        }
    }


    /**
     * Override this for any process that should happen periodically.
     * Will happen at least once per second, but possibly more often.
     *
     * @return void
     */
    protected function tick()
    {
        // Optional implementation.
    }


    /**
     * Internal backend for tick.
     *
     * @return void
     */
    protected function pTick()
    {
        // Core maintenance processes, such as retrying failed messages.
        foreach ($this->heldMessages as $key => $hm) {
            $found = false;
            foreach ($this->users as $currentUser) {
                if ($hm['user']->socket == $currentUser->socket) {
                    $found = true;
                    if ($currentUser->handshake) {
                        unset($this->heldMessages[$key]);
                        $this->send($currentUser, $hm['message']);
                    }
                }
            }

            if (!$found) {
                // If they're no longer in the list of connected users,
                // drop the message.
                unset($this->heldMessages[$key]);
            }
        }
    }


    /**
     * Main processing loop
     *
     * @return void
     */
    public function run()
    {
        while (true) {
            if (empty($this->sockets) === true) {
                $this->sockets['m'] = $this->master;
            }

            $read = $this->sockets;
            $except = null;
            $write = null;
            $this->pTick();
            $this->tick();
            socket_select($read, $write, $except, 1);
            foreach ($read as $socket) {
                if ($socket == $this->master) {
                    $client = socket_accept($socket);
                    if ($client < 0) {
                        $this->stderr('Failed: socket_accept()');
                        continue;
                    } else {
                        $this->connect($client);
                        $this->stdout('Client connected. '.$client);
                    }
                } else {
                    $numBytes = socket_recv(
                        $socket,
                        $buffer,
                        $this->maxBufferSize,
                        0
                    );
                    if ($numBytes === false) {
                        $sockErrNo = socket_last_error($socket);
                        switch ($sockErrNo) {
                            case 102:
                                // ENETRESET
                                // Network dropped connection because of reset.
                            case 103:
                                // ECONNABORTED
                                // Software caused connection abort.
                            case 104:
                                // ECONNRESET
                                // Connection reset by peer.
                            case 108:
                                // ESHUTDOWN
                                // Cannot send after transport endpoint shutdown
                                // Probably more of an error on our side,
                                // if we're trying to write after the socket is
                                // closed.  Probably not a critical error,
                                // though.
                            case 110:
                                // ETIMEDOUT
                                // Connection timed out.
                            case 111:
                                // ECONNREFUSED
                                // Connection refused
                                // We shouldn't see this one, since we're
                                // listening... Still not a critical error.
                            case 112:
                                // EHOSTDOWN
                                // Host is down.
                                // Again, we shouldn't see this, and again,
                                // not critical because it's just one connection
                                // and we still want to listen to/for others.
                            case 113:
                                // EHOSTUNREACH
                                // No route to host.
                            case 121:
                                // EREMOTEIO
                                // Rempte I/O error
                                // Their hard drive just blew up.
                            case 125:
                                // ECANCELED
                                // Operation canceled.
                                $this->stderr(
                                    'Unusual disconnect on socket '.$socket
                                );
                                // Disconnect before clearing error, in case
                                // someone with their own implementation wants
                                // to check for error conditions on the socket.
                                $this->disconnect($socket, true, $sockErrNo);
                            break;

                            default:
                                $this->stderr(
                                    'Socket error: '.socket_strerror($sockErrNo)
                                );
                            break;
                        }
                    } else if ($numBytes == 0) {
                        $this->disconnect($socket);
                        $this->stderr(
                            'Client disconnected. TCP connection lost: '.$socket
                        );
                    } else {
                        $user = $this->getUserBySocket($socket);
                        if (!$user->handshake) {
                            $tmp = str_replace("\r", '', $buffer);
                            if (strpos($tmp, "\n\n") === false) {
                                continue;
                                // If the client has not finished sending the
                                // header, then wait before sending our upgrade
                                // response.
                            }

                            $this->doHandshake($user, $buffer);
                        } else {
                            // Split packet into frame and send it to deframe.
                            $this->splitPacket($numBytes, $buffer, $user);
                        }
                    }
                }
            }
        }
    }


    /**
     * Register user (and its socket) into master.
     *
     * @param Socket $socket Socket.
     *
     * @return void
     */
    protected function connect($socket)
    {
        $user = new $this->userClass(uniqid('u'), $socket);
        $this->users[$user->id] = $user;
        $this->sockets[$user->id] = $socket;
        $this->connecting($user);
    }


    /**
     * Disconnect socket from master.
     *
     * @param Socket  $socket        Socket.
     * @param boolean $triggerClosed Also close.
     * @param integer $sockErrNo     Clear error.
     *
     * @return void
     */
    protected function disconnect(
        $socket,
        bool $triggerClosed=true,
        $sockErrNo=null
    ) {
        $disconnectedUser = $this->getUserBySocket($socket);

        if ($disconnectedUser !== null) {
            unset($this->users[$disconnectedUser->id]);

            if (array_key_exists($disconnectedUser->id, $this->sockets)) {
                unset($this->sockets[$disconnectedUser->id]);
            }

            if ($sockErrNo !== null) {
                socket_clear_error($socket);
            }

            if ($triggerClosed) {
                $this->closed($disconnectedUser);
                $this->stdout(
                    'Client disconnected. '.$disconnectedUser->socket
                );
                socket_close($disconnectedUser->socket);
            } else {
                $message = $this->frame('', $disconnectedUser, 'close');
                socket_write(
                    $disconnectedUser->socket,
                    $message,
                    strlen($message)
                );
            }
        }
    }


    /**
     * Perform a handshake.
     *
     * @param object $user   User.
     * @param string $buffer Buffer.
     *
     * @return void
     */
    protected function doHandshake($user, string $buffer)
    {
        $magicGUID = '258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
        $headers = [];
        $lines = explode("\n", $buffer);
        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                $header = explode(':', $line, 2);
                $headers[strtolower(trim($header[0]))] = trim($header[1]);
                $this->rawHeaders[trim($header[0])] = trim($header[1]);
            } else if (stripos($line, 'get ') !== false) {
                preg_match('/GET (.*) HTTP/i', $buffer, $reqResource);
                $headers['get'] = trim($reqResource[1]);
            }
        }

        if (isset($headers['get'])) {
            $user->requestedResource = $headers['get'];
        } else {
            // TODO: fail the connection.
            $handshakeResponse = "HTTP/1.1 405 Method Not Allowed\r\n\r\n";
        }

        if (!isset($headers['host'])
            || !$this->checkHost($headers['host'])
        ) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['upgrade'])
            || strtolower($headers['upgrade']) != 'websocket'
        ) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['connection'])
            || strpos(strtolower($headers['connection']), 'upgrade') === false
        ) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['sec-websocket-key'])) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        if (!isset($headers['sec-websocket-version'])
            || strtolower($headers['sec-websocket-version']) != 13
        ) {
            $handshakeResponse = "HTTP/1.1 426 Upgrade Required\r\nSec-WebSocketVersion: 13";
        }

        if (($this->headerOriginRequired
            && !isset($headers['origin']) )
            || ($this->headerOriginRequired
            && !$this->checkOrigin($headers['origin']))
        ) {
            $handshakeResponse = 'HTTP/1.1 403 Forbidden';
        }

        if (($this->headerSecWebSocketProtocolRequired
            && !isset($headers['sec-websocket-protocol']))
            || ($this->headerSecWebSocketProtocolRequired
            && !$this->checkWebsocProtocol(
                $headers['sec-websocket-protocol']
            ))
        ) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        if (($this->headerSecWebSocketExtensionsRequired
            && !isset($headers['sec-websocket-extensions']))
            || ($this->headerSecWebSocketExtensionsRequired
            && !$this->checkWebsocExtensions(
                $headers['sec-websocket-extensions']
            ))
        ) {
            $handshakeResponse = 'HTTP/1.1 400 Bad Request';
        }

        // Done verifying the _required_ headers and optionally required headers.
        if (isset($handshakeResponse)) {
            socket_write(
                $user->socket,
                $handshakeResponse,
                strlen($handshakeResponse)
            );
            $this->disconnect($user->socket);
            return;
        }

        $user->headers = $headers;
        $user->handshake = $buffer;

        $webSocketKeyHash = sha1($headers['sec-websocket-key'].$magicGUID);

        $rawToken = '';
        for ($i = 0; $i < 20; $i++) {
            $rawToken .= chr(hexdec(substr($webSocketKeyHash, ($i * 2), 2)));
        }

        $handshakeToken = base64_encode($rawToken)."\r\n";

        $subProtocol = '';
        if (isset($headers['sec-websocket-protocol'])) {
            $subProtocol = $this->processProtocol(
                $headers['sec-websocket-protocol']
            );
        }

        $extensions = '';
        if (isset($headers['sec-websocket-extensions'])) {
            $extensions = $this->processExtensions(
                $headers['sec-websocket-extensions']
            );
        }

        $handshakeResponse = "HTTP/1.1 101 Switching Protocols\r\n";
        $handshakeResponse .= "Upgrade: websocket\r\nConnection: Upgrade\r\n";
        $handshakeResponse .= 'Sec-WebSocket-Accept: ';
        $handshakeResponse .= $handshakeToken.$subProtocol.$extensions."\r\n";
        socket_write(
            $user->socket,
            $handshakeResponse,
            strlen($handshakeResponse)
        );
        $this->connected($user);
    }


    /**
     * Check target host.
     *
     * @param string $hostName Target hostname to be checked.
     *
     * @return boolean Ok or not.
     */
    protected function checkHost($hostName): bool
    {
        // Override and return false if host is not one that you would expect.
        // Ex: You only want to accept hosts from the my-domain.com domain,
        // but you receive a host from malicious-site.com instead.
        return true;
    }


    /**
     * Check origin.
     *
     * @param string $origin Origin of connections.
     *
     * @return boolean Allowed or not.
     */
    protected function checkOrigin($origin): bool
    {
        // Override and return false if origin is not one that you would expect.
        return true;
    }


    /**
     * Check websocket protocol.
     *
     * @param string $protocol Protocol received.
     *
     * @return boolean Expected or not.
     */
    protected function checkWebsocProtocol($protocol): bool
    {
        // Override and return false if a protocol is not found that you
        // would expect.
        return true;
    }


    /**
     * Check websocket extension.
     *
     * @param string $extensions Extension.
     *
     * @return boolean Allowed or not.
     */
    protected function checkWebsocExtensions($extensions): bool
    {
        // Override and return false if an extension is not found that you
        // would expect.
        return true;
    }


    /**
     * Return either
     * * "Sec-WebSocket-Protocol: SelectedProtocolFromClientList\r\n"
     * or return an empty string.
     *
     * The carriage return/newline combo must appear at the end of a non-empty
     * string, and must not appear at the beginning of the string nor in an
     * otherwise empty string, or it will be considered part of the response
     * body, which will trigger an error in the client as it will not be
     * formatted correctly.
     *
     * @param string $protocol Selected protocol.
     *
     * @return string
     */
    protected function processProtocol(string $protocol): string
    {
        return '';
    }


    /**
     * Return either
     * * "Sec-WebSocket-Extensions: SelectedExtensions\r\n"
     * or return an empty string.
     *
     * @param string $extensions Selected extensions.
     *
     * @return string
     */
    protected function processExtensions(string $extensions): string
    {
        return '';
    }


    /**
     * Return user associated to target socket.
     *
     * @param Socket $socket Socket.
     *
     * @return object
     */
    protected function getUserBySocket($socket)
    {
        foreach ($this->users as $user) {
            if ($user->socket == $socket) {
                return $user;
            }
        }

        return null;
    }


    /**
     * Dump to stdout.
     *
     * @param string $message Message.
     *
     * @return void
     */
    public function stdout($message=null)
    {
        if ((bool) $this->interactive === true) {
            echo $message."\n";
        }
    }


    /**
     * Dump to stderr.
     *
     * @param string $message Message.
     *
     * @return void
     */
    public function stderr(string $message=null)
    {
        if ($this->interactive) {
            echo $message."\n";
        }
    }


    /**
     * Process a frame message.
     *
     * @param string  $message          Message.
     * @param object  $user             User.
     * @param string  $messageType      MessageType.
     * @param boolean $messageContinues MessageContinues.
     *
     * @return string Framed message.
     */
    protected function frame(
        string $message,
        $user,
        string $messageType='text',
        bool $messageContinues=false
    ) {
        switch ($messageType) {
            case 'continuous':
                $b1 = 0;
            break;

            case 'text':
                $b1 = ($user->sendingContinuous) ? 0 : 1;
            break;

            case 'binary':
                $b1 = ($user->sendingContinuous) ? 0 : 2;
            break;

            case 'close':
                $b1 = 8;
            break;

            case 'ping':
                $b1 = 9;
            break;

            case 'pong':
                $b1 = 10;
            break;

            default:
                // Ignore.
            break;
        }

        if ($messageContinues) {
            $user->sendingContinuous = true;
        } else {
            $b1 += 128;
            $user->sendingContinuous = false;
        }

        $length = strlen($message);
        $lengthField = '';
        if ($length < 126) {
            $b2 = $length;
        } else if ($length < 65536) {
            $b2 = 126;
            $hexLength = dechex($length);
            // $this->stdout("Hex Length: $hexLength");
            if ((strlen($hexLength) % 2) == 1) {
                $hexLength = '0'.$hexLength;
            }

            $n = (strlen($hexLength) - 2);

            for ($i = $n; $i >= 0; $i = ($i - 2)) {
                $lengthField = chr(
                    hexdec(substr($hexLength, $i, 2))
                ).$lengthField;
            }

            $len = strlen($lengthField);
            while ($len < 2) {
                $lengthField = chr(0).$lengthField;
                $len = strlen($lengthField);
            }
        } else {
            $b2 = 127;
            $hexLength = dechex($length);
            if ((strlen($hexLength) % 2) == 1) {
                $hexLength = '0'.$hexLength;
            }

            $n = (strlen($hexLength) - 2);

            for ($i = $n; $i >= 0; $i = ($i - 2)) {
                $lengthField = chr(
                    hexdec(substr($hexLength, $i, 2))
                ).$lengthField;
            }

            $len = strlen($lengthField);
            while ($length < 8) {
                $lengthField = chr(0).$lengthField;
                $len = strlen($lengthField);
            }
        }

        return chr($b1).chr($b2).$lengthField.$message;
    }


    /**
     * Check packet if he have more than one frame and process each frame
     * individually.
     *
     * @param integer $length Length.
     * @param string  $packet Packet.
     * @param object  $user   User.
     *
     * @return void
     */
    protected function splitPacket(
        int $length,
        string $packet,
        $user
    ) {
        // Add PartialPacket and calculate the new $length.
        if ($user->handlingPartialPacket) {
            $packet = $user->partialBuffer.$packet;
            $user->handlingPartialPacket = false;
            $length = strlen($packet);
        }

        $fullpacket = $packet;
        $frame_pos = 0;
        $frame_id = 1;

        while ($frame_pos < $length) {
            $headers = $this->extractHeaders($packet);
            $headers_size = $this->calcOffset($headers);
            $framesize = ($headers['length'] + $headers_size);

            // Split frame from packet and process it.
            $frame = substr($fullpacket, $frame_pos, $framesize);

            $message = $this->deframe($frame, $user, $headers);

            if ($message !== false) {
                if ($user->hasSentClose) {
                    $this->disconnect($user->socket);
                } else {
                    if ((preg_match('//u', $message))
                        || ($headers['opcode'] == 2)
                    ) {
                        /*
                         * Debug purposes.
                         * $this->stdout("Text msg encoded UTF-8 or Binary msg\n".$message);
                         */

                        $this->process($user, $message);
                    } else {
                        $this->stderr("not UTF-8\n");
                    }
                }
            }

            // Get the new position also modify packet data.
            $frame_pos += $framesize;
            $packet = substr($fullpacket, $frame_pos);
            $frame_id++;
        }
    }


    /**
     * Calculate offset.
     *
     * @param array $headers Headers received.
     *
     * @return integer Calculated offset.
     */
    protected function calcOffset(array $headers): int
    {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }

        if ($headers['length'] > 65535) {
            $offset += 8;
        } else if ($headers['length'] > 125) {
            $offset += 2;
        }

        return $offset;
    }


    /**
     * Parse frame.
     *
     * @param string $message Message received.
     * @param object $user    Origin.
     *
     * @return boolean Process ok or not.
     */
    protected function deframe(
        string $message,
        &$user
    ) {
        /*
         * Debug purposes.
         * echo $this->strtohex($message);
         */

        $headers = $this->extractHeaders($message);
        $pongReply = false;
        $willClose = false;

        switch ($headers['opcode']) {
            case 0:
            case 1:
            case 2:
            case 10:
                $willClose = false;
            break;

            case 8:
                // TODO: close the connection.
                $user->hasSentClose = true;
            return '';

            case 9:
                $pongReply = true;
            break;

            default:
                /*
                 * TODO: fail connection.
                 * $this->disconnect($user);
                 */

                $willClose = true;
            break;
        }

        /*
         * Deal by splitPacket() as now deframe() do only one frame at a time.
         * if ($user->handlingPartialPacket) {
         *     $message = $user->partialBuffer . $message;
         *     $user->handlingPartialPacket = false;
         *     return $this->deframe($message, $user);
         * }
         */

        if ($this->checkRSVBits($headers, $user)) {
            return false;
        }

        if ($willClose) {
            // TODO: fail the connection.
            return false;
        }

        $payload = $user->partialMessage.$this->extractPayload(
            $message,
            $headers
        );

        if ($pongReply) {
            $reply = $this->frame($payload, $user, 'pong');
            socket_write($user->socket, $reply, strlen($reply));
            return false;
        }

        if ($headers['length'] > strlen($this->applyMask($headers, $payload))) {
            $user->handlingPartialPacket = true;
            $user->partialBuffer = $message;
            return false;
        }

        $payload = $this->applyMask($headers, $payload);

        if ($headers['fin']) {
            $user->partialMessage = '';
            return $payload;
        }

        $user->partialMessage = $payload;

        return false;
    }


    /**
     * Extract headers from message.
     *
     * @param string $message Message.
     *
     * @return array Headers.
     */
    protected function extractHeaders(string $message): array
    {
        $header = [
            'fin'     => ($message[0] & chr(128)),
            'rsv1'    => ($message[0] & chr(64)),
            'rsv2'    => ($message[0] & chr(32)),
            'rsv3'    => ($message[0] & chr(16)),
            'opcode'  => (ord($message[0]) & 15),
            'hasmask' => ($message[1] & chr(128)),
            'length'  => 0,
            'mask'    => '',
        ];

        $header['length'] = ord($message[1]);
        if (ord($message[1]) >= 128) {
            $header['length'] = (ord($message[1]) - 128);
        }

        if ($header['length'] == 126) {
            if ($header['hasmask']) {
                $header['mask'] = $message[4].$message[5];
                $header['mask'] .= $message[6].$message[7];
            }

            $header['length'] = (ord($message[2]) * 256 + ord($message[3]));
        } else if ($header['length'] == 127) {
            if ($header['hasmask']) {
                $header['mask'] = $message[10].$message[11];
                $header['mask'] .= $message[12].$message[13];
            }

            $header['length']  = (ord($message[2]) * 65536 * 65536 * 65536 * 256);
            $header['length'] += (ord($message[3]) * 65536 * 65536 * 65536);
            $header['length'] += (ord($message[4]) * 65536 * 65536 * 256);
            $header['length'] += (ord($message[5]) * 65536 * 65536);
            $header['length'] += (ord($message[6]) * 65536 * 256);
            $header['length'] += (ord($message[7]) * 65536);
            $header['length'] += (ord($message[8]) * 256);
            $header['length'] += ord($message[9]);
        } else if ($header['hasmask']) {
            $header['mask'] = $message[2].$message[3].$message[4].$message[5];
        }

        /*
         * Debug purposes.
         *  echo $this->strtohex($message);
         *
         *  $this->printHeaders($header);
         */

        return $header;
    }


    /**
     * Get payload from message using headers.
     *
     * @param string $message Message.
     * @param array  $headers Headers.
     *
     * @return string
     */
    protected function extractPayload(
        string $message,
        array $headers
    ) {
        $offset = 2;
        if ($headers['hasmask']) {
            $offset += 4;
        }

        if ($headers['length'] > 65535) {
            $offset += 8;
        } else if ($headers['length'] > 125) {
            $offset += 2;
        }

        return substr($message, $offset);
    }


    /**
     * Apply mask.
     *
     * @param array  $headers Headers.
     * @param string $payload Payload.
     *
     * @return string Xor.
     */
    protected function applyMask(
        array $headers,
        string $payload
    ) {
        $effectiveMask = '';
        if ($headers['hasmask']) {
            $mask = $headers['mask'];
        } else {
            return $payload;
        }

        $len_mask = strlen($effectiveMask);
        $len_payload = strlen($payload);

        // Enlarge.
        while ($len_mask < $len_payload) {
            $effectiveMask .= $mask;
            $len_mask = strlen($effectiveMask);
            $len_payload = strlen($payload);
        }

        // Decrease.
        while ($len_mask > $len_payload) {
            $effectiveMask = substr($effectiveMask, 0, -1);
            $len_mask = strlen($effectiveMask);
            $len_payload = strlen($payload);
        }

        return ($effectiveMask ^ $payload);
    }


    /**
     * Check RSV bits.
     * Override this method if you are using an extension where RSV bits are
     * being used.
     *
     * @param array  $headers Headers.
     * @param object $user    User.
     *
     * @return boolean OK or not.
     */
    protected function checkRSVBits(
        array $headers,
        $user
    ): bool {
        $len = ord($headers['rsv1']);
        $len += ord($headers['rsv2']);
        $len += ord($headers['rsv3']);
        if ($len > 0) {
            /*
             * TODO: fail connection.
             * $this->disconnect($user);
             */

            return true;
        }

        return false;
    }


    /**
     * Transforms string into HEX string.
     *
     * @param string $str String.
     *
     * @return string HEX string.
     */
    protected function strtohex(
        string $str=''
    ): string {
        $strout = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if (ord($str[$i]) < 16) {
                $strout .= '0'.dechex(ord($str[$i]));
            } else {
                $strout .= dechex(ord($str[$i]));
            }

            $strout .= ' ';
            if (($i % 32) == 7) {
                $strout .= ': ';
            }

            if (($i % 32) == 15) {
                $strout .= ': ';
            }

            if (($i % 32) == 23) {
                $strout .= ': ';
            }

            if (($i % 32) == 31) {
                $strout .= "\n";
            }
        }

        return $strout."\n";
    }


    /**
     * Debug purposes. Print headers.
     *
     * @param array $headers Headers.
     *
     * @return void
     */
    protected function printHeaders($headers)
    {
        echo "Array\n(\n";
        foreach ($headers as $key => $value) {
            if ($key == 'length' || $key == 'opcode') {
                echo "\t[".$key.'] => '.$value."\n\n";
            } else {
                echo "\t[".$key.'] => '.$this->strtohex($value)."\n";
            }
        }

        echo ")\n";
    }


}
