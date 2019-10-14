#!/usr/bin/env php
<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__.'/WebSocketServer.php';
require_once __DIR__.'/WebSocketUser.php';

use \PandoraFMS\WebSocketServer;
use \PandoraFMS\WebSocketUser;

class MyUser extends WebSocketUser
{

    public $myId;


    function __construct($id, $socket)
    {
        parent::__construct($id, $socket);
        $this->myId = $id;
    }


}

class echoServer extends WebSocketServer
{

    private $redirectedSocket;

    private $redirectedHost = '127.0.0.1';


    function __construct($addr, $port, $bufferLength=2048)
    {
        parent::__construct($addr, $port, $bufferLength);
        $this->userClass = 'MyUser';
    }


    /**
     * 1MB... overkill for an echo server, but potentially plausible for other
     * applications.
     *
     * @var integer
     */
    protected $maxBufferSize = 1048576;


    protected function readSocket()
    {
        $buffer;

        $numBytes = socket_recv(
            $this->redirectedSocket,
            $buffer,
            $this->maxBufferSize,
            0
        );
        if ($numBytes === false) {
            return false;
        }

        return $buffer;
    }


    protected function parseGottyHeaders($response)
    {
        $headers = [];
        $lines = explode('\n', $response);
        foreach ($lines as $l) {
            $c = explode(':', $l);

            $headers[trim($c[0])] = trim($c[1]);
        }

        return $headers;
    }


    protected function translateGottyHeaders($headers)
    {
        // Redirect.
        $h = $headers;
        /*
            $h['Sec-Websocket-Key'] = base64_encode($headers['sec-websocket-key']);
            $h['Sec-Websocket-Extensions'] = $headers['sec-websocket-extensions'];
            $h['Sec-Websocket-Protocol'] = $headers['sec-websocket-protocol'];
            $h['Sec-Websocket-Version'] = $headers['sec-websocket-version'];

            $h['Connection'] = $headers['connection'];
            if ($headers['upgrade']) {
            $h['Upgrade'] = $headers['upgrade'];
            }
        */

        $h['Host'] = '127.0.0.1:8081';
        $h['Origin'] = 'http://127.0.0.1:8081';

        // Cleanup.
        unset($h['get']);
        unset($h['user-agent']);
        unset($h['cache-control']);
        unset($h['pragma']);

        // Build.
        $out = "GET /ws HTTP/1.1\r\n";
        foreach ($h as $key => $value) {
            $out .= ucfirst($key).': '.$value."\r\n";
        }

        $out .= "\r\n";
        return $out;
    }


    protected function process($user, $message)
    {
        // What to do with received message.
        echo 'Received from client> ['.$message."]\n";

        socket_write($this->redirectedSocket, $message);
        $out = $this->readSocket();

        $this->send($user, $out);
    }


    protected function processProtocol(string $protocol): string
    {
        return 'Sec-Websocket-Protocol: '.$protocol;
    }


    protected function connected($user)
    {
        $this->redirectedSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $connect = socket_connect($this->redirectedSocket, $this->redirectedHost, 8080);
        if (!$connect) {
            echo 'failed to open target socket redirection';
            $this->disconnect($user->socket);
        } else {
            var_dump($this->rawHeaders);
            $out = $this->translateGottyHeaders($this->rawHeaders);
            echo '????';
            socket_write($this->redirectedSocket, $out);

            $response = '';
            $response = $this->readSocket();

            // Upgrade $user headers.
            $new_headers = $this->parseGottyHeaders($response);
            $user->headers += $new_headers;

            print ">> Reenviando [gotty] [$response]\n";

            // $this->send($user, $response);
        }

        // Do nothing: This is just an echo server, there's no need to track the user.
        // However, if we did care about the users, we would probably have a cookie to
        // parse at this step, would be looking them up in permanent storage, etc.
    }


    protected function closed($user)
    {
        $out = "GET /ws HTTP/1.1\r\n";
        $out .= 'Host: '.$this->redirectedHost."\r\n";
        $out .= "Connection: Close\r\n\r\n";
        socket_write($this->redirectedSocket, $out);
        echo ">> Recibiendo respuesta de peticion de cierre:\n";
        $out = $this->readSocket();

        socket_close($this->redirectedSocket);
        // Do nothing: This is where cleanup would go, in case the user had any sort of
        // open files or other objects associated with them.  This runs after the socket
        // has been closed, so there is no need to clean up the socket itself here.
    }


}

$echo = new echoServer('0.0.0.0', '8081');

try {
    $echo->run();
} catch (Exception $e) {
    $echo->stdout($e->getMessage());
}
