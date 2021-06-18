<?php

// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list
// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/**
 * @package    Include
 * @subpackage Clippy
 */


function clippy_start_page_homepage()
{
    global $config;

    $clippy_is_annoying = (int) get_cookie('clippy_is_annoying', 0);
    $nagios = (int) get_cookie('nagios', -1);

    $easter_egg_toy = ($nagios % 6);
    if (($easter_egg_toy == 5)
        || ($easter_egg_toy == -1)
    ) {
        $image = 'images/clippy/clippy.png';
    } else {
        $image = 'images/clippy/easter_egg_0'.$easter_egg_toy.'.png';
    }

    if ($image != 'easter_egg_04.png') {
        $style = 'display: block; position: absolute; left: -112px; top: -80px;';
    } else {
        $style = 'display: block; position: absolute; left: -200px; top: -80px;';
    }

    clippy_clean_help();

    $pandorin_img = html_print_image(
        $image,
        true,
        [
            'id'      => 'clippy_toy',
            'onclick' => 'easter_egg_clippy(1);',
        ]
    );

    $pandorin_chkb = html_print_checkbox_extended(
        'clippy_is_annoying',
        1,
        $clippy_is_annoying,
        false,
        'set_clippy_annoying()',
        '',
        true
    );

    $return_tours = [];
    $return_tours['first_step_by_default'] = true;
    $return_tours['tours'] = [];

    // ==================================================================
    // Help tour with the some task for to help the user.
    // ------------------------------------------------------------------
    $return_tours['tours']['homepage'] = [];
    $return_tours['tours']['homepage']['steps'] = [];
    $return_tours['tours']['homepage']['steps'][] = [
        'element' => '#clippy',
        'intro'   => '<div class="clippy_body left pdd_l_20px pdd_r_20px">'.__('Hi, can I help you?').'<br/><br/>'.__('Let me introduce my self: I am Pandorin, the annoying assistant of %s. You can follow my steps to do basic tasks in %s or you can close me and never see me again.', get_product_name(), get_product_name()).'<br /> <br /> <div class="clippy_body font_7pt">'.$pandorin_chkb.__('Close this wizard and don\'t open it again.').'</div></div><div class="relative"><div id="pandorin" style="'.$style.'">'.$pandorin_img.'</div></div>',
    ];
    $return_tours['tours']['homepage']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('Which task would you like to do first?').'<br/><br/><ul class="left mrgn_lft_10px list-type-disc"><li>'."<a href='javascript: clippy_go_link_show_help(\"index.php?sec=gagente&sec2=godmode/agentes/modificar_agente\", \"monitoring_server_step_1\");'>".__('Ping a Linux or Windows server using a %s agent.', get_product_name()).'</a></li><li>'."<a href='javascript: clippy_go_link_show_help(\"index.php\", \"email_alert_module_step_1\");'>".__('Create a alert by email in a critical module.').'</a></li></ul>',
    ];
    $return_tours['tours']['homepage']['conf'] = [];
    $return_tours['tours']['homepage']['conf']['show_bullets'] = 0;
    $return_tours['tours']['homepage']['conf']['show_step_numbers'] = 0;
    $return_tours['tours']['homepage']['conf']['name_obj_js_tour'] = 'intro_homepage';
    $return_tours['tours']['homepage']['conf']['other_js'] = "
		var started = 0;
		
		function show_clippy() {
			if (intro_homepage.started()) {
				started = 1;
			}
			else {
				started = 0;
			}
			
			if (started == 0)
				intro_homepage.start();
		}
		
		var nagios = -1;
		function easter_egg_clippy(click) {
			if (readCookie('nagios')) {
				nagios = readCookie('nagios');
			}
			
			if (click)
				nagios++;
			
			if (nagios > 5) {
				easter_egg_toy = nagios % 6;
				
				if ((easter_egg_toy == 5) ||
					(easter_egg_toy == -1)) {
					image = 'images/clippy/clippy.png';
				}
				else {
					image = 'images/clippy/easter_egg_0' + easter_egg_toy + '.png';
				}
				
				$('#clippy_toy').attr('src', image);
				if (easter_egg_toy == 4) {
					$('#pandorin').css('left', '-200px');
				}
				else {
					$('#pandorin').css('left', '-112px');
				}
				
				document.cookie = 'nagios=' + nagios;
			}
		}
		
		function set_clippy_annoying() {
			var now = new Date();
			var time = now.getTime();
			var expireTime = time + 3600000 * 24 * 360 * 20;
			now.setTime(expireTime);
			
			checked = $('input[name=\'clippy_is_annoying\']')
				.is(':checked');
			//intro_homepage.exit();
			
			if (checked) {
				document.cookie = 'clippy_is_annoying=1;expires=' +
					now.toGMTString() + ';';
			}
			else {
				document.cookie = 'clippy_is_annoying=0;expires=' +
					now.toGMTString() + ';';
			}
		}
		
		function readCookie(name) {
			var nameEQ = name + '=';
			var ca = document.cookie.split(';');
			
			for(var i=0;i < ca.length;i++) {
				var c = ca[i];
				
				while (c.charAt(0)==' ')
					c = c.substring(1,c.length);
				if (c.indexOf(nameEQ) == 0)
					return c.substring(nameEQ.length,c.length);
			}
			return null;
		}
		
		";
    if ($config['logged']) {
        $return_tours['tours']['homepage']['conf']['autostart'] = true;
    } else {
        $return_tours['tours']['homepage']['conf']['autostart'] = false;
    }

    if ($config['tutorial_mode'] == 'on_demand') {
        $return_tours['tours']['homepage']['conf']['autostart'] = false;
    }

    if ($clippy_is_annoying === 1) {
        $return_tours['tours']['homepage']['conf']['autostart'] = false;
    }

    // ==================================================================
    // ==================================================================
    // Help tour about the email alert module (step 1)
    // ------------------------------------------------------------------
    $return_tours['tours']['email_alert_module_step_1'] = [];
    $return_tours['tours']['email_alert_module_step_1']['steps'] = [];
    $return_tours['tours']['email_alert_module_step_1']['steps'][] = [
        'element' => '#clippy',
        'intro'   => __('The first thing you have to do is to setup the e-mail config on the %s Server.', get_product_name()).'<br />'.ui_print_help_icon('context_pandora_server_email', true, '', 'images/help.png').'<br />'.__('If you have it already configured you can go to the next step.'),
    ];
    $return_tours['tours']['email_alert_module_step_1']['steps'][] = [
        'element'  => '#icon_god-alerts',
        'position' => 'top',
        'intro'    => __('Now, pull down the Manage alerts menu and click on Actions. '),
    ];
    $return_tours['tours']['email_alert_module_step_1']['conf'] = [];
    $return_tours['tours']['email_alert_module_step_1']['conf']['show_bullets'] = 0;
    $return_tours['tours']['email_alert_module_step_1']['conf']['show_step_numbers'] = 0;

    $return_tours['tours']['email_alert_module_step_1']['conf']['complete_js'] = '
		;
		';
    $return_tours['tours']['email_alert_module_step_1']['conf']['exit_js'] = '
		location.reload();
		';
    $return_tours['tours']['email_alert_module_step_1']['conf']['next_help'] = 'email_alert_module_step_2';
    // ==================================================================
    return $return_tours;
}
