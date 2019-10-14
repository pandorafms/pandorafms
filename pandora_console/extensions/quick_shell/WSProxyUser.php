<?php

require_once __DIR__.'/WebSocketServer.php';
require_once __DIR__.'/WebSocketUser.php';

use \PandoraFMS\WebSocketServer;
use \PandoraFMS\WebSocketUser;

/**
 * WebSocket proxy user.
 */
class WSProxyUser extends WebSocketUser
{

    /**
     * Redirection socket.
     *
     * @var socket
     */
    public $intSocket;

    /**
     * User identifier.
     *
     * @var string
     */
    public $myId;


    /**
     * Builder.
     *
     * @param string $id        Identifier.
     * @param socket $socket    Socket (origin).
     * @param socket $intSocket Socket (internal).
     */
    public function __construct($id, $socket)
    {
        parent::__construct($id, $socket);
        $this->myId = $id;
    }


}
