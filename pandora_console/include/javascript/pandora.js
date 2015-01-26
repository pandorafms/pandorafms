var ENTERPRISE_DIR = 'enterprise';

/* Function to hide/unhide a specific Div id */
function toggleDiv (divid){
	if (document.getElementById(divid).style.display == 'none') {
		document.getElementById(divid).style.display = 'block';
	}
	else {
		document.getElementById(divid).style.display = 'none';
	}
}

function winopeng (url, wid) {
	open (url, wid,"width=700,height=460,status=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes");
	// WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
	status = wid;
}

function winopeng_var (url, wid, width, height) {
	open (url, wid,"width="+width+",height="+height+",status=no,toolbar=no,menubar=no,scrollbar=yes");
        // WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
        status = wid;
}

function open_help (help_id, home_url) {
	open (home_url+"general/pandora_help.php?id="+help_id, "pandorahelp", "width=650,height=500,status=0,toolbar=0,menubar=0,scrollbars=1,location=0");
}

/**
 * Decode HTML entities into characters. Useful when receiving something from AJAX
 *
 * @param str String to convert
 *
 * @retval str with entities decoded
 */
function js_html_entity_decode (str) {
	if (! str)
		return "";
	
	str2 = str.replace (/</g, "&lt;").
		replace (/>/g,"&gt;").replace(/&lt;/g,'<')
		.replace(/&gt;/g,'>').replace(/&#92;/g,'\\')
		.replace(/&quot;/g,'\"').replace(/&#039;/g,'\'')
		.replace(/&amp;/g,'&').replace(/&#x20;/g,' ')
		.replace(/&#13;/g, '\r').replace(/&#10;/g, '\n');
	
	return str2;
}

/**
 * Function to search an element in an array.
 *
 * Extends the array object to use it like a method in an array object. Example:
 * <code>
 a = Array (4, 7, 9);
 alert (a.in_array (4)); // true
 alert (a.in_array (5)); // false
 */
Array.prototype.in_array = function () {
	for (var j in this) {
		if (this[j] == arguments[0])
			return true;
	}
	
	return false;
};

/**
 * Fill up select box with id "module" with modules after agent has been selected
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed (event, id_agent, selected) {
	if (id_agent == undefined)
		id_agent = this.value;
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	jQuery.post ('ajax.php', 
				{"page": "operation/agentes/ver_agente",
				"get_agent_modules_json": 1,
				"id_agent": id_agent
				},
				function (data) {
					
					$('#module').empty ();
					
					if (typeof($(document).data('text_for_module')) != 'undefined') {
						$('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
					}
					else {
						if (typeof(data['any_text']) != 'undefined') {
							$('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
						}
						else {
							var anyText = $("#any_text").html(); //Trick for catch the translate text.
							
							if (anyText == null) {
								anyText = 'Any';
							}
							
							$('#module').append ($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
						}
					}
					jQuery.each (data, function (i, val) {
								s = js_html_entity_decode (val['nombre']);
								$('#module').append ($('<option></option>').html (s).attr ("value", val['id_agente_modulo']));
								$('#module').fadeIn ('normal');
								});
					if (selected != undefined)
					$('#module').attr ('value', selected);
					$('#module').removeAttr('disabled');
				},
				"json"
				);
}

/**
 * Util for check is empty object
 * 
 * @param obj the object to check
 * @returns {Boolean} True it is empty
 */
function isEmptyObject(obj) {
	for(var prop in obj) {
		if(obj.hasOwnProperty(prop))
			return false;
	}
	
	return true;
}


/**
 * Fill up select box with id "module" with modules after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents (event, id_agent, selected) {
	// Hack to add custom condition
	if ($("#hidden-custom_condition").val() != undefined) {
		custom_condition = $("#hidden-custom_condition").val();
	}
	else {
		custom_condition = '';
	}
	
	var idAgents = Array();
	
	jQuery.each ($("#id_agents option:selected"), function (i, val) {
		//val() because the var is same <option val="NNN"></option>
		idAgents.push($(val).val());
	});
	
	//Hack to find only enabled modules
	//Pass a flag as global var
	find_modules = 'all';
	if (typeof(show_only_enabled_modules) != "undefined") {
		if (show_only_enabled_modules == true) {
			find_modules = 'enabled';
		}
	}
	
	var selection_mode = $('#modules_selection_mode').val();
	if (selection_mode == undefined) {
		selection_mode = 'common';
	}
	
	var serialized = $('#hidden-serialized').val();
	if (serialized == undefined) {
		serialized = '';
	}
	
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>')
		.html ("Loading...").attr ("value", 0));
	
	
	// Check if homedir was received like a JSON
	homedir = '';
	id_server = 0;
	if (typeof(event) == 'undefined') {
		homedir += '.';
	}
	else {
		if (event.data == null)
			homedir += '.';
		else {
			homedir  = event.data.homedir;
		
			if (event.data.metaconsole != null) {
				id_server = $("#" + event.data.id_server).val();
			}
		}
	}
	
	jQuery.post (homedir + '/ajax.php', 
		{
			"page": "operation/agentes/ver_agente",
			"get_agent_modules_json_for_multiple_agents": 1,
			"id_agent[]": idAgents,
			"all": find_modules,
			"custom_condition": custom_condition,
			"selection_mode": selection_mode,
			"serialized": serialized,
			"id_server": id_server
		},
		function (data) {
			$('#module').empty ();
			
			if (isEmptyObject(data)) {
				var noneText = $("#none_text").html(); //Trick for catch the translate text.
				
				if (noneText == null) {
					noneText = 'None';
				}
				
				$('#module')
					.append ($('<option></option>')
						.html(noneText)
						.attr ("None", "")
						.attr('selected', true));
				
				return;
			}
			
			if (typeof($(document).data('text_for_module')) != 'undefined') {
				$('#module')
					.append ($('<option></option>')
						.html ($(document)
						.data('text_for_module'))
						.attr("value", 0)
						.attr('selected', true));
			}
			else {
				if (typeof(data['any_text']) != 'undefined') {
					$('#module')
						.append ($('<option></option>')
							.html (data['any_text'])
							.attr ("value", 0)
							.attr('selected', true));
				}
				else {
					var anyText = $("#any_text").html(); //Trick for catch the translate text.
					
					if (anyText == null) {
						anyText = 'Any';
					}
					
					$('#module')
						.append ($('<option></option>')
							.html (anyText)
							.attr ("value", 0)
							.attr('selected', true));
				}
			}
			jQuery.each (data, function (i, val) {
					s = js_html_entity_decode(val);
					
					$('#module')
						.append ($('<option></option>')
							.html (s)
							.attr ("value", i)
							.attr("title", s));
					
					$('#module').fadeIn ('normal');
				});
			if (selected != undefined)
				$('#module').attr ('value', selected);
			
			$('#module').css ("max-width", "");
			
			
			$('#module').removeAttr('disabled');
			
			if (typeof(function_hook_loaded_module_list) == 'function') {
				function_hook_loaded_module_list();
			}
		},
		"json"
		);
}

/**
 * Fill up select box with id "module" with modules with alerts of one template
 * after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents_with_alerts (event, id_agent, selected) {
	var idAgents = Array();
	
	jQuery.each ($("#id_agents option:selected"), function (i, val) {
		//val() because the var is same <option val="NNN"></option>
		idAgents.push($(val).val());
	});
	
	var selection_mode = $('#modules_selection_mode').val();
	if(selection_mode == undefined) {
		selection_mode = 'common';
	}
	
	template = $('#id_alert_template option:selected').attr("value");
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	jQuery.post ('ajax.php', 
				{"page": "operation/agentes/ver_agente",
				"get_agent_modules_alerts_json_for_multiple_agents": 1,
				"template": template,
				"id_agent[]": idAgents,
				"selection_mode": selection_mode
				},
				function (data) {
					$('#module').empty ();
					
					if (typeof($(document).data('text_for_module')) != 'undefined') {
						$('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
					}
					else {
						if (typeof(data['any_text']) != 'undefined') {
							$('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
						}
						else {
							var anyText = $("#any_text").html(); //Trick for catch the translate text.
							
							if (anyText == null) {
								anyText = 'Any';
							}
							
							$('#module').append ($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
						}
					}
					jQuery.each (data, function (i, val) {
								s = js_html_entity_decode(val);
								$('#module').append ($('<option></option>').html (s).attr ("value", val));
								$('#module').fadeIn ('normal');
								});
					if (selected != undefined)
					$('#module').attr ('value', selected);
					$('#module').removeAttr('disabled');
				},
				"json"
				);
}

/**
 * Fill up select box with id "agent" with agents after module has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_module Module ID that has been selected
 * @param selected Which agent(s) have to be selected
 */
function module_changed_by_multiple_modules (event, id_module, selected) {
	var idModules = Array();
	
	jQuery.each ($("#module_name option:selected"), function (i, val) {
		//val() because the var is same <option val="NNN"></option>
		idModules.push($(val).val());
	});
	
	$('#agents').attr ('disabled', 1);
	$('#agents').empty ();
	$('#agents').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	
	var selection_mode = $('#agents_selection_mode').val();
	if(selection_mode == undefined) {
		selection_mode = 'common';
	}
	
	jQuery.post('ajax.php', 
		{"page": "operation/agentes/ver_agente",
		"get_agents_json_for_multiple_modules": 1,
		"module_name[]": idModules,
		"selection_mode": selection_mode
		},
		function (data) {
			$('#agents')
				.append ($('<option></option>').html ("Loading...").attr ("value", 0));
			
			$('#agents').empty ();
			
			if (isEmptyObject(data)) {
				var noneText = $("#none_text").html(); //Trick for catch the translate text.
				
				if (noneText == null) {
					noneText = 'None';
				}
				
				$('#agents')
					.append($('<option></option>').html (noneText).attr ("None", "").attr('selected', true));
				
				return;
			}
			
			if (typeof($(document).data('text_for_module')) != 'undefined') {
				$('#agents')
					.append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
			}
			else {
				if (typeof(data['any_text']) != 'undefined') {
					$('#agents')
						.append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
				}
				else {
					var anyText = $("#any_text").html(); //Trick for catch the translate text.
					
					if (anyText == null) {
						anyText = 'Any';
					}
					
					$('#agents')
						.append($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
				}
			}
			jQuery.each (data, function (i, val) {
				s = js_html_entity_decode(val);
				$('#agents')
					.append ($('<option></option>').html (s).attr ("value", val));
				$('#agents').fadeIn ('normal');
			});
			
			if (selected != undefined)
				$('#agents').attr ('value', selected);
			$('#agents').removeAttr('disabled');
		},
		"json"
	);
}

/**
 * Fill up select box with id "module" with modules after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents_id (event, id_agent, selected) {
	var idAgents = Array();
	
	jQuery.each ($("#id_agents option:selected"), function (i, val) {
		//val() because the var is same <option val="NNN"></option>
		idAgents.push($(val).val());
	});
	
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	jQuery.post ('ajax.php', 
		{"page": "operation/agentes/ver_agente",
		"get_agent_modules_json_for_multiple_agents_id": 1,
		"id_agent[]": idAgents
		},
		function (data) {
			$('#module').empty ();
			
			if (typeof($(document).data('text_for_module')) != 'undefined') {
				$('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
			}
			else {
				if (typeof(data['any_text']) != 'undefined') {
					$('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
				}
				else {
					var anyText = $("#any_text").html(); //Trick for catch the translate text.
					
					if (anyText == null) {
						anyText = 'Any';
					}
					
					$('#module').append ($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
				}
			}
			
			jQuery.each (data, function (i, val) {
				s = js_html_entity_decode(val['nombre']);
				//$('#module').append ($('<option></option>').html (s).attr ("value", val));
				$('#module').append ($('<option></option>').html (s).attr ("value", val['id_agente_modulo']));
				$('#module').fadeIn ('normal');
				});
			if (selected != undefined)
			$('#module').attr ('value', selected);
			$('#module').removeAttr('disabled');
		},
		"json"
		);
}

/**
 * Init values for html_extended_select_for_time
 * 
 * This function initialize the values of the control
 * 
 * @param name string with the name of the select for time
 */
function period_select_init(name) {
	// Manual mode is hidden by default
	$('#'+name+'_manual').hide();
	$('#'+name+'_default').show();
	
	// If the text input is empty, we put on it 5 minutes by default
	if($('#text-'+name+'_text').val() == '') {
		$('#text-'+name+'_text').val(300);
		// Set the value in the hidden field too
		$('.'+name).val(300);
		if($('#'+name+'_select option:eq(0)').val() == 0) {
			$('#'+name+'_select option:eq(2)').attr('selected', 'selected');
		}
		else {
			$('#'+name+'_select option:eq(1)').attr('selected', 'selected');
		}
	}
	else if($('#text-'+name+'_text').val() == 0) {
		$('#'+name+'_units option:last').removeAttr('selected');
		$('#'+name+'_manual').show();
		$('#'+name+'_default').hide();
	}
}

/**
 * Manage events into html_extended_select_for_time
 * 
 * This function has all the events to manage the extended select
 * for time
 * 
 * @param name string with the name of the select for time
 */
function period_select_events(name) {
	$('.'+name+'_toggler').click(function() {
		toggleBoth(name);
		$('#text-'+name+'_text').focus();
	});
	
	adjustTextUnits(name);
	
	// When select a default period, is setted in seconds
	$('#'+name+'_select').change(function() {
		var value = $('#'+name+'_select').val();
		
		if(value == -1) {
			value = 300;
			toggleBoth(name);
			$('#text-'+name+'_text').focus();
		}
		
		$('.'+name).val(value); 
		$('#text-'+name+'_text').val(value);
		adjustTextUnits(name);
	});
	
	// When select a custom units, the default period changes to 'custom' and
	// the time in seconds is calculated into hidden input
	$('#'+name+'_units').change(function() {
		selectFirst(name);
		calculateSeconds(name);
	});
	
	// When write any character into custom input, it check to convert it to 
	// integer and calculate in seconds into hidden input
	$('#text-'+name+'_text').keyup (function () {
		var cleanValue = parseInt($('#text-'+name+'_text').val());
		if(isNaN(cleanValue)) {
			cleanValue = '';
		}
		
		$('#text-'+name+'_text').val(cleanValue);
		
		selectFirst(name+'_select');
		calculateSeconds(name);
	});
}

/**
 * 
 * Select first option of a select if is not value=0
 * 
 */

function selectFirst(name) {
	if($('#'+name+' option:eq(0)').val() == 0) {
		$('#'+name+' option:eq(1)').attr('selected', 'selected');
	}
	else {
		$('#'+name+' option:eq(0)').attr('selected', 'selected');
	}
}

/**
 * 
 * Toggle default and manual controls of period control
 * It is done with css function because hide and show do not
 * work properly when the divs are into a hiden div
 * 
 */
	
function toggleBoth(name) {
	if($('#'+name+'_default').css('display') == 'none') {
		$('#'+name+'_default').css('display','inline');
	}
	else {
		$('#'+name+'_default').css('display','none');
	}
	
	if($('#'+name+'_manual').css('display') == 'none') {
		$('#'+name+'_manual').css('display','inline');
	}
	else {
		$('#'+name+'_manual').css('display','none');
	}
}

/**
 * 
 * Calculate the custom time in seconds into hidden input
 * 
 */
function calculateSeconds(name) {
	var calculated = $('#text-'+name+'_text').val()*$('#'+name+'_units').val();
	
	$('.'+name).val(calculated);
}

/**
 * 
 * Update via Javascript an advance selec for time
 * 
 */
function period_select_update(name, seconds) {
	$('#text-'+name+'_text').val(seconds);
	adjustTextUnits(name);
	calculateSeconds(name);
	$('#'+name+'_manual').show();	
	$('#'+name+'_default').hide();
}

/**
 * 
 * Adjust units in the advanced select for time
 * 
 */
function adjustTextUnits(name) {
	var restPrev;
	var unitsSelected = false;
	$('#'+name+'_units option').each(function() {
		if($(this).val() < 0) {
			return;
		}
		var rest = $('#text-'+name+'_text').val()/$(this).val();
		var restInt = parseInt(rest).toString();
		
		if(rest != restInt && unitsSelected == false) {
			$('#'+name+'_units option:eq('+($(this).index()-1)+')').attr('selected', true);
			$('#text-'+name+'_text').val(restPrev);
			unitsSelected = true;
		}
		
		restPrev = rest;
	});
	
	if(unitsSelected == false) {
		$('#'+name+'_units option:last').attr('selected', true);
		$('#text-'+name+'_text').val(restPrev);
	}
	
	if($('#text-'+name+'_text').val() == 0) {
		selectFirst(name+'_units');
	}
}


/**
 * Sidebar function
 * params:
 * 	menuW: $params['width']
 * 	icon_width: $params['icon_width']
 *  position: $params['position']
 *  top_dist: $params['top']
 *  autotop: $params['autotop']
 *  icon_closed: $params['icon_closed']
 * 	icon_open: $params['icon_open']
 * 	homeurl: $config['homeurl']
 *
**/
function hidded_sidebar(position, menuW, menuH, icon_width, top_dist, autotop, right_dist, autoright, icon_closed, icon_open, homeurl, vertical_mode) {
	var defSlideTime = 220;
	var visibleMargin = icon_width + 10;
	var hiddenMarginW = menuW - visibleMargin;
	menuH = parseInt(menuH);
	var hiddenMarginH = menuH - visibleMargin;
	var windowWidth = $(window).width();
	var sideClosed = 1;

	if(top_dist == 'auto_over') {
		top_dist = $('#' + autotop).offset().top;
	}
	else if(top_dist == 'auto_below') {
		top_dist = $('#' + autotop).offset().top + $('#' + autotop).height();
		switch(position) {
			case 'bottom':
				if(vertical_mode == 'in') {
					top_dist -= visibleMargin + 10;
				}
		}
	}

	if(right_dist == 'auto_right') {
		right_dist = $('#' + autoright).offset().left + $('#' + autoright).width();
	}
	else if(right_dist == 'auto_left') {
		right_dist = $('#' + autoright).offset().left;
	}
	
	$(document).ready( function() {
		// SET INITIAL POSITION AND SHOW LAYER
		$('#side_layer').css('top', top_dist);
		switch (position) {
			case 'left':
				$('#side_layer').css('left', -hiddenMarginW);
				break;
			case 'right':
				$('#side_layer').css('left', windowWidth - visibleMargin - 1);
				$('#side_layer').css('width', visibleMargin + 'px');
				break;
			case 'bottom':
				$('#side_layer').css('left', right_dist - menuW);
				$('#side_layer').css('height', visibleMargin + 'px');
				break;
		}
		$('#side_layer').show();
		
		$("#graph_menu_arrow").click(function(){
			switch(position) {			
				case 'right':
					if (sideClosed == 0){
						$('#side_layer').animate({"width": "-=" + (hiddenMarginW) + "px", "left": "+=" + (hiddenMarginW) + "px"}, defSlideTime);
						$("#graph_menu_arrow").attr("src", homeurl + icon_closed);
					}
					else {
						$('#side_layer').animate({"width": "+=" + (hiddenMarginW) + "px", "left": "-=" + (hiddenMarginW) + "px"}, defSlideTime);
						$("#graph_menu_arrow").attr("src", homeurl + icon_open);
					}
					break;
				case 'left':
					if (sideClosed == 1){
						$('#side_layer').animate({"left": "+=" + (hiddenMarginW) + "px"}, defSlideTime);
						
						$("#graph_menu_arrow").attr("src", homeurl + icon_closed);
					}
					else {
						$('#side_layer').animate({"left": "-=" + (hiddenMarginW) + "px"}, defSlideTime);
						$("#graph_menu_arrow").attr("src", homeurl + icon_open);
					}
					break;
				case 'bottom':
					if (sideClosed == 0){
						$('#side_layer').animate({"height": "-=" + (hiddenMarginH+10) + "px", "top": "+=" + (hiddenMarginH) + "px"}, defSlideTime);
						$("#graph_menu_arrow").attr("src", homeurl + icon_closed);
					}
					else {
						$('#side_layer').animate({"height": "+=" + (hiddenMarginH-10) + "px", "top": "-=" + (hiddenMarginH) + "px"}, defSlideTime);
						$("#graph_menu_arrow").attr("src", homeurl + icon_open);
					}
					break;
			}
			
			if(sideClosed == 0) {
				//$('#side_top_text').hide();
				//$('#side_body_text').hide();
				//$('#side_bottom_text').hide();
				sideClosed = 1;
			}
			else {
				$('#side_top_text').show();
				$('#side_body_text').show();
				$('#side_bottom_text').show();
				sideClosed = 0;
			}
		});
	});
	
	switch(position) {
		case 'right':
		case 'bottom':
			// Move the right menu if window is resized
			$(window).resize(function() {
				var newWindowWidth = $(window).width();
				var widthVariation = newWindowWidth - windowWidth;
				$('#side_layer').animate({"left": "+=" + (widthVariation) + "px"}, 0);
				
				windowWidth = newWindowWidth;
			});
		break;
	}
}

// Function that recover a previously stored value from php code
function get_php_value(value) {
	return $.parseJSON($('#php_to_js_value_' + value).html());
}

function paint_qrcode(text, where, width, height) {
	if (typeof(text) == 'undefined') {
		text = window.location.href;
	}
	else {
		//null value
		if (isEmptyObject(text)) {
			text = window.location.href;
		}
	}
	
	if (typeof(where) == 'undefined') {
		where = $("#qrcode_container_image").get(0);
	}
	else if (typeof(where) == 'string') {
		where = $(where).get(0);
	}
	
	if (typeof(where) == 'undefined') {
		where = $("#qrcode_container_image").get(0);
	}
	else if (typeof(where) == 'string') {
		where = $(where).get(0);
	}
	
	if (typeof(width) == 'undefined') {
		width = 256;
	}
	else {
		if (typeof(width) == 'object')
			//null value
			if (isEmptyObject(width)) {
				width = 256;
			}
	}
	
	if (typeof(height) == 'undefined') {
		height = 256;
	}
	else {
		if (typeof(height) == 'object')
			//null value
			if (isEmptyObject(height)) {
				height = 256;
			}
	}
	
	$(where).empty();
	
	var qrcode = new QRCode(where, {
		text: text,
		width: width,
		height: height,
		colorDark : "#3B6941",
		colorLight : "#ffffff",
		correctLevel : QRCode.CorrectLevel.M
	});
}

function show_dialog_qrcode(dialog, text, where, width, height) {
	if (typeof(dialog) == 'undefined') {
		dialog = "#qrcode_container";
	}
	else {
		if (typeof(dialog) == 'object')
			//null value
			if (isEmptyObject(dialog)) {
				dialog = "#qrcode_container";
			}
	}
	
	if (typeof(where) == 'undefined') {
		where = $("#qrcode_container_image").get(0);
	}
	else if (typeof(where) == 'string') {
		where = $(where).get(0);
	}
	
	if (typeof(width) == 'undefined') {
		width = 256;
	}
	else {
		if (typeof(width) == 'object')
			//null value
			if (isEmptyObject(width)) {
				width = 256;
			}
	}
	
	if (typeof(height) == 'undefined') {
		height = 256;
	}
	else {
		if (typeof(height) == 'object')
			//null value
			if (isEmptyObject(height)) {
				height = 256;
			}
	}
	
	paint_qrcode(text, where, 256, 256)
	
	$(dialog).dialog( "open" );
}

function openURLTagWindow(url) {
	window.open(url, '','width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no'); 
}