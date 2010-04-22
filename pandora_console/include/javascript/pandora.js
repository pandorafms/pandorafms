var ENTERPRISE_DIR = 'enterprise';

/* Function to hide/unhide a specific Div id */
function toggleDiv (divid){
	if (document.getElementById(divid).style.display == 'none') {
		document.getElementById(divid).style.display = 'block';
	} else {
		document.getElementById(divid).style.display = 'none';
	}
}

function winopeng (url, wid) {
	open (url, wid,"width=590,height=310,status=no,toolbar=no,menubar=no,scrollbar=no");
	// WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
	status =wid;
}

function open_help (help_id) {
	open ("general/pandora_help.php?id="+help_id, "pandorahelp", "width=650,height=500,status=0,toolbar=0,menubar=0,scrollbars=1,location=0");
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
	replace (/>/g,"&gt;").replace(/&lt;/g,'<').replace(/&gt;/g,'>')
	.replace(/&#92;/g,'\\').replace(/&quot;/g,'\"').replace(/&#039;/g,'\'')
	.replace(/&amp;/g,'&')
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
		if(this[j] == arguments[0])
			return true;
	}
	return false;
}

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
						 $('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
					 }
					 jQuery.each (data, function (i, val) {
								  s = js_html_entity_decode (val['nombre']);
								  $('#module').append ($('<option></option>').html (s).attr ("value", val['id_agente_modulo']));
								  $('#module').fadeIn ('normal');
								  });
					 if (selected != undefined)
					 $('#module').attr ('value', selected);
					 $('#module').attr ('disabled', 0);
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
function agent_changed_by_multiple_agents (event, id_agent, selected) {
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
				 "get_agent_modules_json_for_multiple_agents": 1,
				 "id_agent[]": idAgents
				 },
				 function (data) {
					 $('#module').empty ();
					 
					 if (typeof($(document).data('text_for_module')) != 'undefined') {
						 $('#module').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
					 }
					 else { 
						 $('#module').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
					 }
					 jQuery.each (data, function (i, val) {
								  s = js_html_entity_decode(val);
								  $('#module').append ($('<option></option>').html (s).attr ("value", val));
								  $('#module').fadeIn ('normal');
								  });
					 if (selected != undefined)
					 $('#module').attr ('value', selected);
					 $('#module').attr ('disabled', 0);
				 },
				 "json"
				 );
}


/**
 * Autocomplete Agent box and module selector functions.
 * 
 * This function has all the necesary javascript to use the box with to autocomplete
 * an agent name, and store it's id on a hidden field and fill a selector with the 
 * modules from that agent.
 * 
 * @param id_agent_name id of the agent name box
 * @param id_agent_id id of the hidden field to store the agent id
 * @param id_agent_module_selector id of the selector for the modules of the agent.
 */
function agent_module_autocomplete (id_agent_name, id_agent_id, id_agent_module_selector, noneValue) {
		$(id_agent_name).autocomplete(
			"ajax.php",
			{
				minChars: 2,
				scroll:true,
				extraParams: {
					page: "include/ajax/agent",
					search_agents: 1,
					id_group: 1
				},
				formatItem: function (data, i, total) {
					if (total == 0)
						$(id_agent_name).css ('background-color', '#cc0000');
					else
						$(id_agent_name).css ('background-color', '');
					if (data == "")
						return false;
					return data[0]+'<br><span class="ac_extra_field"><?php echo __("IP") ?>: '+data[2]+'</span>';
				},
				delay: 200
			}
		);
		// Callback from the autocomplete
		$(id_agent_name).result (
			function (e, data, formatted) {
				$(id_agent_module_selector).attr('disabled', false);
				agent_id = data[1];
				$(id_agent_id).val(agent_id);
				jQuery.post ('ajax.php', 
							{"page": "operation/agentes/ver_agente",
									"get_agent_modules_json": 1,
									"id_agent": agent_id,
									"filter" : 'disabled=0 AND delete_pending=0',
									"fields" : "id_agente_modulo,nombre"
									},
									function (data) {
										$(id_agent_module_selector).empty();
										if (typeof(noneValue) != "undefined") {
											if (noneValue == true) {
												option = $("<option></option>")
												.attr ("value", 0)
												.html ("--");
												$(id_agent_module_selector).append (option);
											}
										}
										jQuery.each (data, function (i, value) {
											option = $("<option></option>")
												.attr ("value", value['id_agente_modulo'])
												.html (js_html_entity_decode (value['nombre']));
											$(id_agent_module_selector).append (option);
										});
									},
									"json"
								);
			}
		);
}
