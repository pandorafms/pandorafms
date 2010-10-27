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
require_once('operation/agents/tactical.php');
require_once('operation/agents/group_view.php');
require_once('operation/agents/view_alerts.php');
require_once('operation/events/events.php');
require_once('operation/agents/monitor_status.php');
$enterpriseHook = enterprise_include('mobile/include/enterprise.class.php');

$system = new System();

$user = $system->getSession('user', null);
if ($user == null) {
	$user = new User();
}
$user->hackinjectConfig();
?>
<html>
	<head>
		<title>Pandora FMS - <?php echo __('the Flexible Monitoring System (mobile version)'); ?></title>
		<link rel="stylesheet" href="include/style/main.css" type="text/css" />
		<link rel="stylesheet" href="../include/styles/tip.css" type="text/css" />
	</head>
	<body>
		<div>
		<!--<div style="width: 240px; height: 320px; border: 2px solid red; overflow: hidden;">-->
		<!--<div style="width: 240px; height: 640px; border: 2px solid red; overflow: hidden;">-->
		<?php
		$action = $system->getRequest('action');
		switch ($action) {
			case 'login':
				if (!$user->checkLogin()) {
					$user->showLogin();
				}
				else {
					if ($user->isLogged()) {
						$user->hackinjectConfig();
						menu();
						
						if (! give_acl($system->getConfig('id_user'), 0, "AR")) {
							pandora_audit("ACL Violation",
								"Trying to access Agent Data view");
							require ("../general/noaccess.php");
							return;
						}
						
						$tactical = new Tactical();
						$tactical->show();
					}
					else {
						$user->showLogin();
					}
				}
				break;
			case 'logout':
				$user->logout();
				$user->showLogin('<span style="color: red; font-weight: bolder; float: right;">' . __('LOGOUT') . '</span>');
				break;
			default:
				if (!$user->isLogged()) {
					$user->showLogin();
				}
				else {
					menu();
					$page = $system->getRequest('page', 'tactical');
					switch ($page) {
						case 'reports':
								if ($enterpriseHook !== ENTERPRISE_NOT_HOOK) {
									$enterprise = new Enterprise($page);
									$enterprise->show(); 
								}
								break;
						default:
						case 'tactical':
							if (! give_acl($system->getConfig('id_user'), 0, "AR")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$tactical = new Tactical();
							$tactical->show();
							break;
						case 'agents':
							if (! give_acl($system->getConfig('id_user'), 0, "AR")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$viewAgents = new ViewAgents();
							$viewAgents->show();
							break;
						case 'agent':
							$action = $system->getRequest('action', 'view_agent');
							switch ($action) {
								case 'view_module_graph':
									$idAgentModule = $system->getRequest('id', 0);
									$viewGraph = new viewGraph($idAgentModule);
									$viewGraph->show();
									break;
								default:
								case 'view_agent':
									$viewAgent = new ViewAgent();
									$viewAgent->show();
									break;
							}
							break;
						case 'servers':
							if (! give_acl($system->getConfig('id_user'), 0, "PM")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$viewServers = new ViewServers();
							$viewServers->show();
							break;
						case 'alerts':
							if (! give_acl($system->getConfig('id_user'), 0, "PM")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$viewAlerts = new ViewAlerts();
							$viewAlerts->show();
							break;
						case 'groups':
							if (! give_acl($system->getConfig('id_user'), 0, "PM")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$groupView = new GroupView();
							$groupView->show();
							break;
						case 'events':
							if (! give_acl($system->getConfig('id_user'), 0, "IR")) {
								pandora_audit("ACL Violation",
									"Trying to access event viewer");
								require ("general/noaccess.php");
								return;
							}
							
							$eventsView = new EventsView();
							$eventsView->show();
							break;
						case 'monitor':
							if (! give_acl($system->getConfig('id_user'), 0, "AR")) {
								pandora_audit("ACL Violation",
									"Trying to access Agent Data view");
								require ("../general/noaccess.php");
								return;
							}
							
							$monitorStatus = new MonitorStatus($user);
							$monitorStatus->show();
							break;
					}
				}
				break;
		}
		?>
		</div>
		<?php
		if ($user->isLogged()) {
			footer();
		}
		?>
	</body>
</html>
<?php
$system->setSession('user', $user);
//$system->sessionDestroy();
ob_end_flush();
?>