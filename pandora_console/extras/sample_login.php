<?php

// Password estatica, se define en ambos extremos
$pwd = 'sistemas';

$user = 'admin';
$data = $user.$pwd;
$data = md5($data);

echo "DEBUG md5sum $data user $user Pass $pwd<br>";
echo '<form name=test method=post action="http://192.168.61.41/pandora_console/index.php?loginhash=auto&sec=estado&sec2=operation/agentes/estado_agente&refr=60">';
echo '<input type="hidden" name="loginhash_data" value="'.$data.'">';
echo '<input type="hidden" name="loginhash_user" value="'.str_rot13($user).'">';
echo '<input type="submit">';
echo '</form>';
