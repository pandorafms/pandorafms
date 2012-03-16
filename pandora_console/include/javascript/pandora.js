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
	open (url, wid,"width=650,height=410,status=no,toolbar=no,menubar=no,scrollbar=no");
	// WARNING !! Internet Explorer DOESNT SUPPORT "-" CHARACTERS IN WINDOW HANDLE VARIABLE
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
		if(this[j] == arguments[0])
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
	if($("#hidden-custom_condition").val() != undefined) {
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
	
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	jQuery.post ('ajax.php', 
		 {"page": "operation/agentes/ver_agente",
		 "get_agent_modules_json_for_multiple_agents": 1,
		 "id_agent[]": idAgents,
		 "all": find_modules,
		 "custom_condition": custom_condition
		 },
		 function (data) {
			 $('#module').empty ();
			 
			 if (isEmptyObject(data)) {
				 var noneText = $("#none_text").html(); //Trick for catch the translate text.
				 
				 if (noneText == null) {
					 noneText = 'None';
				 }
				 
				 $('#module').append ($('<option></option>').html (noneText).attr ("None", "").attr('selected', true));
				 
				 return;
			 }
			 
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
			 $('#module').css ("width", "auto");
			 $('#module').css ("max-width", "");
			 
			 
			 $('#module').removeAttr('disabled');
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
	template = $('#id_alert_template option:selected').attr("value");
	$('#module').attr ('disabled', 1);
	$('#module').empty ();
	$('#module').append ($('<option></option>').html ("Loading...").attr ("value", 0));
	jQuery.post ('ajax.php', 
				 {"page": "operation/agentes/ver_agente",
				 "get_agent_modules_alerts_json_for_multiple_agents": 1,
				 "template": template,
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
	jQuery.post ('ajax.php', 
				 {"page": "operation/agentes/ver_agente",
				 "get_agents_json_for_multiple_modules": 1,
				 "module_name[]": idModules
				 },
				 function (data) {
					 $('#agents').append ($('<option></option>').html ("Loading...").attr ("value", 0));

					 $('#agents').empty ();
					 
					 if (typeof($(document).data('text_for_module')) != 'undefined') {
						 $('#agents').append ($('<option></option>').html ($(document).data('text_for_module')).attr("value", 0).attr('selected', true));
					 }
					 else {
						 if (typeof(data['any_text']) != 'undefined') {
							 $('#agents').append ($('<option></option>').html (data['any_text']).attr ("value", 0).attr('selected', true));
						 }
						 else {
							 var anyText = $("#any_text").html(); //Trick for catch the translate text.
							 
							 if (anyText == null) {
								 anyText = 'Any';
							 }
							 
							 $('#agents').append ($('<option></option>').html (anyText).attr ("value", 0).attr('selected', true));
						 }
					 }
					 jQuery.each (data, function (i, val) {
								  s = js_html_entity_decode(val);
								  $('#agents').append ($('<option></option>').html (s).attr ("value", val));
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
function agent_module_autocomplete (id_agent_name, id_agent_id, id_agent_module_selector, id_server_name, noneValue) {
	//Check exist the field with id in the var id_agent_name.
	if ($(id_agent_name).length == 0)
		return;
	
	$(id_agent_name).autocomplete({
		minLength: 2,
		source: function( request, response ) {
				var term = request.term; //Word to search
				
				var data_params = {"page": "include/ajax/agent",
					"search_agents_2": 1,
					"q": term};
				jQuery.ajax ({
					data: data_params,
					async: false,
					type: 'POST',
					url: action="ajax.php",
					timeout: 10000,
					dataType: 'json',
					success: function (data) {
						response(data);
						return;
					}
				});
				return;
			},
		select: function( event, ui ) {
			var agent_name = ui.item.name;
			var agent_id = ui.item.id;
			var server_name = ui.item.ip;
			
			//Put the name
			$(this).val(agent_name);
			
			//Put the id
			if (typeof(id_agent_id) != "undefined") {
				$(id_agent_id).val(agent_id);
			}
			
			//Put the server
			if (typeof(id_server_name) != "undefined") {
				$(id_server_name).val(server_name);
			}
			
			//Fill the modules select box
			$(id_agent_module_selector).fadeOut ('normal', function () {
				$('#module').empty ();
				
				var data_params = {"page": "operation/agentes/ver_agente",
					"get_agent_modules_json": 1,
					"id_agent": agent_id,
					"server_name": server_name,
					"filter" : 'disabled=0 AND delete_pending=0',
					"fields" : "id_agente_modulo,nombre"};
				jQuery.ajax ({
					data: data_params,
					type: 'POST',
					url: action="ajax.php",
					timeout: 10000,
					dataType: 'json',
					success: function (data) {
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
						$(id_agent_module_selector).fadeIn ('normal');
						$(id_agent_module_selector).removeAttr('disabled');
					}
				});
			});
			
			return false;
		}
	})
	.data( "autocomplete")._renderItem = function( ul, item ) {
		if (item.ip == '') {
			text = "<a>" + item.name + "</a>";
		}
		else {
			text = "<a>" + item.name
				+ "<br><span style='font-size: 70%; font-style: italic;'>IP:" + item.ip + "</span></a>";
		}
		
		return $("<li></li>")
			.data("item.autocomplete", item)
			.append(text)
			.appendTo(ul);
	};
	
	//Force the size of autocomplete
	$(".ui-autocomplete").css("max-height", "100px");
	$(".ui-autocomplete").css("overflow-y", "auto");
	/* prevent horizontal scrollbar */
	$(".ui-autocomplete").css("overflow-x", "hidden");
	/* add padding to account for vertical scrollbar */
	$(".ui-autocomplete").css("padding-right", "20px");
	
	//Force to style of items
	$(".ui-autocomplete").css("text-align", "left");
}

/**
 * Autocomplete Agent box functions.
 * 
 * This function has all the necesary javascript to use the box with to autocomplete
 * an agent name, and store it's id on a hidden field and fill a selector with the 
 * modules from that agent.
 * 
 * @param id_agent_name id of the agent name box
 * @param id_agent_id id of the hidden field to store the agent id
 * @param id_agent_module_selector id of the selector for the modules of the agent.
 */
function agent_autocomplete (id_agent_name, id_server_name, id_agent_id ) {
	//Check exist the field with id in the var id_agent_name.
	if ($(id_agent_name).length == 0)
		return;
	
	$(id_agent_name).autocomplete({
		minLength: 2,
		source: function( request, response ) {
			var term = request.term; //Word to search
			
			var data_params = {"page": "include/ajax/agent",
				"search_agents_2": 1,
				"q": term};
			
			jQuery.ajax ({
				data: data_params,
				async: false,
				type: 'POST',
				url: action="ajax.php",
				timeout: 10000,
				dataType: 'json',
				success: function (data) {
					response(data);
					return;
				}
			});
			return;
		},
		select: function( event, ui ) {
			var agent_id = ui.item.id;
			var server_name = ui.item.ip;
			var agent_name = ui.item.name;
			
			//Put the name
			$(this).val(agent_name);
			
			//Put the id
			if (typeof(id_agent_id) != "undefined") {
				$(id_agent_id).val(agent_id);
			}
			
			//Put the server
			if (typeof(id_server_name) != "undefined") {
				$(id_server_name).val(server_name);
			}
			
			return false;
		}
	})
	.data( "autocomplete")._renderItem = function( ul, item ) {
		if (item.ip == '') {
			text = "<a>" + item.name + "</a>";
		}
		else {
			text = "<a>" + item.name
				+ "<br><span style='font-size: 70%; font-style: italic;'>IP:" + item.ip + "</span></a>";
		}
		
		return $("<li></li>")
			.data("item.autocomplete", item)
			.append(text)
			.appendTo(ul);
	};
		
	//Force the size of autocomplete
	$(".ui-autocomplete").css("max-height", "100px");
	$(".ui-autocomplete").css("overflow-y", "auto");
	/* prevent horizontal scrollbar */
	$(".ui-autocomplete").css("overflow-x", "hidden");
	/* add padding to account for vertical scrollbar */
	$(".ui-autocomplete").css("padding-right", "20px");
	
	//Force to style of items
	$(".ui-autocomplete").css("text-align", "left");
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
	// Manual mode is hidden by default
	$('#'+name+'_manual').hide();
	
	// If the text input is empty, we put on it 5 minutes by default
	if($('#text-'+name+'_text').val() == '') {
		$('#text-'+name+'_text').val(300);
		$('#'+name+'_select option:eq(1)').attr('selected', true);
	}
	
	$('.'+name+'_toggler').click(function() {
		$('#'+name+'_default').toggle();
		$('#'+name+'_manual').toggle();
		$('#text-'+name+'_text').focus();
	});
	
	function adjustTextUnits() {
		var restPrev;
		var unitsSelected = false;
		$('#'+name+'_units option').each(function() {
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
	}
	
	adjustTextUnits();
	
	// When select a default period, is setted in seconds
	$('#'+name+'_select').change(function() {
		var value = $('#'+name+'_select').val();
		
		if(value == 0) {
			value = 300;
		}
		
		$('#hidden-'+name).val(value); 
		$('#text-'+name+'_text').val(value);
		adjustTextUnits();
	});
	
	// When select a custom units, the default period changes to 'custom' and
	// the time in seconds is calculated into hidden input
	$('#'+name+'_units').change(function() {
		$('#'+name+'_select option:eq(0)').attr('selected', 'selected');
		calculateSeconds();
	});
	
	// When write any character into custom input, it check to convert it to 
	// integer and calculate in seconds into hidden input
	$('#text-'+name+'_text').keyup (function () {
		var cleanValue = parseInt($('#text-'+name+'_text').val());
		if(isNaN(cleanValue)) {
			cleanValue = '';
		}
		
		$('#text-'+name+'_text').val(cleanValue);
		
		$('#'+name+'_select option:eq(0)').attr('selected', 'selected');
		calculateSeconds();
	});
	
	// Function to calculate the custom time in seconds into hidden input
	function calculateSeconds() {
		var calculated = $('#text-'+name+'_text').val()*$('#'+name+'_units').val();
		$('#hidden-'+name).val(calculated);
	}
	
}
