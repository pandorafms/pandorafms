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

//Set character encoding to UTF-8 - fixes a lot of multibyte character
//headaches
if (function_exists ('mb_internal_encoding')) {
	mb_internal_encoding ("UTF-8");
}

$develop_bypass = 1;

require_once("include/ui.class.php");
require_once("include/system.class.php");
require_once("include/db.class.php");
require_once("include/user.class.php");

require_once('operation/home.php');
require_once('operation/tactical.php');
require_once('operation/groups.php');
require_once('operation/events.php');
require_once('operation/alerts.php');
require_once('operation/agents.php');
require_once('operation/modules.php');
require_once('operation/module_graph.php');
require_once('operation/agent.php');
$enterpriseHook = enterprise_include('mobile/include/enterprise.class.php');

$system = System::getInstance();

$user = User::getInstance();
$user->hackInjectConfig();

$action = $system->getRequest('action');
if (!$user->isLogged()) {
	$action = 'login';
}

switch ($action) {
	case 'ajax':
		$parameter1 = $system->getRequest('parameter1', false);
		$parameter2 = $system->getRequest('parameter2', false);
		
		switch ($parameter1) {
			case 'events':
				$events = new Events();
				$events->ajax($parameter2);
				break;
			case 'agents':
				$agents = new Agents();
				$agents->ajax($parameter2);
				break;
			case 'modules':
				$modules = new Modules();
				$modules->ajax($parameter2);
				break;
			case 'module_graph':
				$module_graph = new ModuleGraph();
				$module_graph->ajax($parameter2);
				break;
		}
		return;
		break;
	case 'login':
		if (!$user->checkLogin()) {
			$user->showLogin();
		}
		else {
			if ($user->isLogged()) {
				$home = new Home();
				$home->show();
			}
			else {
				$user->showLoginFail();
			}
		}
		break;
	case 'logout':
		$user->logout();
		$user->showLogin();
		break;
	default:
		$page = $system->getRequest('page', 'home');
		switch ($page) {
			case 'home':
			default:
				$home = new Home();
				$home->show();
				break;
			case 'tactical':
				$tactical = new Tactical();
				$tactical->show();
				break;
			case 'groups':
				$groups = new Groups();
				$groups->show();
				break;
			case 'events':
				$events = new Events();
				$events->show();
				break;
			case 'alerts':
				$alerts = new Alerts();
				$alerts->show();
				break;
			case 'agents':
				$agents = new Agents();
				$agents->show();
				break;
			case 'modules':
				$modules = new Modules();
				$modules->show();
				break;
			case 'module_graph':
				$module_graph = new ModuleGraph();
				$module_graph->show();
				break;
			case 'agent':
				$agent = new Agent();
				$agent->show();
				break;
		}
		break;
}
?>
