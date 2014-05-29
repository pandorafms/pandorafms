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
require_once('operation/networkmaps.php');
require_once('operation/networkmap.php');
require_once('operation/visualmaps.php');
require_once('operation/visualmap.php');
$enterpriseHook = enterprise_include('mobile/include/enterprise.class.php');

$system = System::getInstance();

$user = User::getInstance();
$user->hackInjectConfig();

$action = $system->getRequest('action');
if (!$user->isLogged()) {
	$action = 'login';
}

if ($action != "ajax") {
	$user_language = get_user_language ($system->getConfig('id_user'));
	if (file_exists ('../include/languages/'.$user_language.'.mo')) {
		$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
		$l10n->load_tables();
	}
}

// Disable mobile console if ALC enterprise is enabled
if ($system->getConfig('enterprise_installed') == 1 && $system->getConfig('acl_enterprise') == 1) {
		$ui = Ui::getInstance();
		$system = System::getInstance();		
		$ui->createPage();
		$ui->createHeader(__('Mobile console disabled'));
		$ui->showFooter(false);
		$ui->beginContent();
		$ui->contentAddHtml(ui_print_info_message(array('title' => '', 'message' => __('Mobile console is not available with ACL enterprise enabled. Please contact with the administrator.'), 'no_close' => true, 'force_style' => 'margin: 0 auto;'), '', true));
		$ui->endContent();
		$ui->showPage();
		return;
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
			case 'agent':
				$agent = new Agent();
				$agent->ajax($parameter2);
				break;
			case 'modules':
				$modules = new Modules();
				$modules->ajax($parameter2);
				break;
			case 'module_graph':
				$module_graph = new ModuleGraph();
				$module_graph->ajax($parameter2);
				break;
			case 'visualmap':
				$visualmap = new Visualmap();
				$visualmap->ajax($parameter2);
			case 'tactical':
				$tactical = new Tactical();
				$tactical->ajax($parameter2);
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
				$user_language = get_user_language ($system->getConfig('id_user'));
				if (file_exists ('../include/languages/'.$user_language.'.mo')) {
					$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
					$l10n->load_tables();
				}
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
			case 'networkmaps':
				$networkmaps = new Networkmaps();
				$networkmaps->show();
				break;
			case 'networkmap':
				$networkmap = new Networkmap();
				$networkmap->show();
				break;
			case 'visualmaps':
				$visualmaps = new Visualmaps();
				$visualmaps->show();
				break;
			case 'visualmap':
				$visualmap = new Visualmap();
				$visualmap->show();
				break;
		}
		break;
}
?>
