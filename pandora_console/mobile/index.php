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
$enterpriseHook = enterprise_include('mobile/operation/home.php');

if (!empty ($config["https"]) && empty ($_SERVER['HTTPS'])) {
	$query = '';
	if (sizeof ($_REQUEST))
		//Some (old) browsers don't like the ?&key=var
		$query .= 'mobile/index.php?1=1';
	
	//We don't clean these variables up as they're only being passed along
	foreach ($_GET as $key => $value) {
		if ($key == 1)
			continue;
		$query .= '&'.$key.'='.$value;
	}
	foreach ($_POST as $key => $value) {
		$query .= '&'.$key.'='.$value;
	}
	$url = ui_get_full_url($query);
	
	// Prevent HTTP response splitting attacks
	// http://en.wikipedia.org/wiki/HTTP_response_splitting
	$url = str_replace ("\n", "", $url);
	header ('Location: '.$url);
	exit; //Always exit after sending location headers
}

$system = System::getInstance();

//~ In this moment doesn't work the version mobile when have metaconsole version.
//~ In the future versions of pandora maybe is added a mobile version of PandoraFMS Metaconsole version.
if ($system->getConfig('metaconsole'))
	header ("Location: " . $system->getConfig('homeurl') . "enterprise/meta");


require_once($system->getConfig('homedir').'/include/constants.php');

$user = User::getInstance();

if (!is_object($user) && gettype($user) == 'object') {
	$user = unserialize (serialize ($user));
}

$user->saveLogin();

$page = $system->getRequest('page', 'home');
$action = $system->getRequest('action');

// The logout action has priority
if ($action != 'logout') {
	if (!$user->isLogged()) {
		$action = 'login';
	}
	else if ($user->isWaitingDoubleAuth()) {
		$dauth_period = SECONDS_2MINUTES;
		$now = time();
		$dauth_time = $user->getLoginTime();

		if ($now - $dauth_period < $dauth_time) {
			$action = 'double_auth';
		}
		// Expired login
		else {
			$action = 'logout';
		}
	}
}

if ($action != "ajax") {
	$user_language = get_user_language ($system->getConfig('id_user'));
	if (file_exists ('../include/languages/'.$user_language.'.mo')) {
		$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
		$l10n->load_tables();
	}
}

switch ($action) {
	case 'ajax':
		$parameter1 = $system->getRequest('parameter1', false);
		$parameter2 = $system->getRequest('parameter2', false);

		if (class_exists("Enterprise")) {
			$enterprise = Enterprise::getInstance();

			$permission = $enterprise->checkEnterpriseACL($parameter1);

			if (!$permission) {
				return false;
			}
		}
		
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
		if ($user->login() && $user->isLogged()) {
			if ($user->isWaitingDoubleAuth()) {
				if ($user->validateDoubleAuthCode()) {
					$user_language = get_user_language ($system->getConfig('id_user'));
					if (file_exists ('../include/languages/'.$user_language.'.mo')) {
						$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
						$l10n->load_tables();
					}
					if (class_exists("HomeEnterprise"))
						$home = new HomeEnterprise();
					else
						$home = new Home();
					$home->show();
				}
				else {
					$user->showDoubleAuthPage();
				}
			}
			else {
				$user_language = get_user_language ($system->getConfig('id_user'));
				if (file_exists ('../include/languages/'.$user_language.'.mo')) {
					$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
					$l10n->load_tables();
				}
				if (class_exists("HomeEnterprise"))
					$home = new HomeEnterprise();
				else
					$home = new Home();
				$home->show();
			}

		}
		else {
			$user->showLoginPage();
		}
		break;
	case 'double_auth':
		if ($user->isLogged()) {
			if ($user->validateDoubleAuthCode()) {
				$user_language = get_user_language ($system->getConfig('id_user'));
				if (file_exists ('../include/languages/'.$user_language.'.mo')) {
					$l10n = new gettext_reader (new CachedFileReader('../include/languages/'.$user_language.'.mo'));
					$l10n->load_tables();
				}
				if (class_exists("HomeEnterprise"))
					$home = new HomeEnterprise();
				else
					$home = new Home();
				$home->show();
			}
			else {
				$user->showDoubleAuthPage();
			}
		}
		else {
			$user->showLoginPage();
		}
		break;
	case 'logout':
		$user->logout();
		$user->showLoginPage();
		break;
	default:
		if (class_exists("Enterprise")) {
			$enterprise = Enterprise::getInstance();

			if ($page != "home") {
				$permission = $enterprise->checkEnterpriseACL($page);

				if (!$permission) {
					$error['type'] = 'onStart';
					$error['title_text'] = __('You don\'t have access to this page');
					$error['content_text'] = __('Access to this page is restricted to authorized users only, please contact system administrator if you need assistance. <br><br>Please know that all attempts to access this page are recorded in security logs of Pandora System Database');
					if (class_exists("HomeEnterprise"))
						$home = new HomeEnterprise();
					else
						$home = new Home();
					$home->show($error);

					return;
				}
			}
		}

		switch ($page) {
			case 'home':
			default:
				if (class_exists("HomeEnterprise"))
					$home = new HomeEnterprise();
				else
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
