<?php
// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation for version 2.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

ob_start ();

require_once("include/system.class.php");
require_once("include/user.class.php");
require_once("include/functions_web.php");
require_once('operation/agents/view_agents.php');
require_once('operation/servers/view_servers.php');

$system = new System();

$user = $system->getSession('user', null);
if ($user == null) {
	$user = new User();
}
$user->hackinjectConfig();
?>
<html>
	<head>
		<title>XXX</title>
		<link rel="stylesheet" href="include/style/main.css" type="text/css" />
		<link rel="stylesheet" href="../include/styles/tip.css" type="text/css" />
		<script type="text/javascript" src="../include/javascript/jquery.js"></script>
	</head>
	<body>
		<!--<div style="width: 100%; height: 100%; border: 2px solid red; overflow: hidden;">-->
		<!--<div style="width: 240px; height: 320px; border: 2px solid red; overflow: hidden;">-->
		<div style="width: 240px; height: 640px; border: 2px solid red; overflow: hidden;">
		<?php
		$action = $system->getRequest('action');
		switch ($action) {
			case 'login':
				if (!$user->checkLogin()) {
					$user->login();
				}
				menu();
				break;
			case 'logout':
				$user->logout();
				break;
			default:
				if (!$user->isLogged()) {
					$user->login();
				}
				else {
					menu();
					$page = $system->getRequest('page', 'dashboard');
					switch ($page) {
						default:
						case 'dashboard':
							break;
						case 'agents':
							$viewAgents = new ViewAgents();
							$viewAgents->show();
							break;
						case 'agent':
							$viewAgent = new ViewAgent();
							$viewAgent->show();
							break;
						case 'servers':
							$viewServers = new ViewServers();
							$viewServers->show();
							break;
					}
				}
				break;
		}
		?>
		</div>
	</body>
</html>
<?php
$system->setSession('user', $user);
//$system->sessionDestroy();
ob_end_flush();
?>