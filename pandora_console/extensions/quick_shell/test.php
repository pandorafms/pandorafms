<?php

$host = '127.0.0.1';
$port = 8080;

$r = file_get_contents('http://'.$host.':'.$port.'/js/hterm.js');
$r .= file_get_contents('http://'.$host.':'.$port.'/hterms.js');
$gotty = file_get_contents('http://'.$host.':'.$port.'/js/gotty.js');


$url = "var url = (httpsEnabled ? 'wss://' : 'ws://') + window.location.host + window.location.pathname + 'ws';";
$new = "var url = (httpsEnabled ? 'wss://' : 'ws://') + window.location.host + ':8081' + window.location.pathname;";

$gotty = str_replace($url, $new, $gotty);


?>
<!doctype html>
<html>
  <head>
    <title>GoTTY</title>
    <style>body, #terminal {position: absolute; height: 100%; width: 100%; margin: 0px;}</style>
    <link rel="icon" type="image/png" href="favicon.png">
  </head>
  <body>
    <div id="terminal"></div>
    <script type="text/javascript">
    <?php echo $r; ?>
    </script>
    <script type="text/javascript">
    <?php echo $gotty; ?>
    </script>
  </body>
</html>
<?php
/*
    $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    $connect = socket_connect($socket, $host, $port);
    if (!$connect) {
    echo 'failed to open target socket redirection';
    $this->disconnect($user->socket);
    } else {
    echo ">> me he conectado al otro lado \n";
    echo "Enviando petición HTTP HEAD ...\n";

    $out .= 'GET ws://'.$host.':'.$port.'/ws HTTP/1.1'."\r\n";
    $out .= 'Host: '.$host.':'.$port."\r\n";
    $out .= 'Connection: Upgrade'."\r\n";
    $out .= 'Upgrade: websocket'."\r\n";
    //$out .= 'Sec-WebSocket-Key: tqIu95AAeKFFlrLTsixBAA=='."\r\n";
    $out .= 'Sec-WebSocket-Extensions: permessage-deflate; client_max_window_bits'."\r\n";
    $out .= 'Sec-WebSocket-Protocol: gotty'."\r\n";

    $out .= '{"Arguments":"","AuthToken":""}';

    socket_write($socket, $out);
    echo "Leyendo respuesta:\n\n";
    while ($out = socket_read($socket, 2048)) {
        echo '['.$out.']';
    }
    }



    // Disconnect.
    $out = "GET / HTTP/1.1\r\n";
    $out .= 'Host: '.$host."\r\n";
    $out .= "Connection: Close\r\n\r\n";

    socket_write($socket, $out);
    echo ">> Recibiendo respuesta de peticion de cierre:\n";
    while ($out = socket_read($socket, 2048)) {
    echo '['.$out.']';
    }

    socket_close($socket);
*/
