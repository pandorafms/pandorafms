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
	open (url, wid,"width=570,height=310,status=no,toolbar=no,menubar=no,scrollbar=no");
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
function html_entity_decode (str) {
	if (! str)
		return "";
	var ta = document.createElement ("textarea");
	ta.innerHTML = str.replace (/</g, "&lt;").replace (/>/g,"&gt;");
	return ta.value;
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
				 $('#module').append ($('<option></option>').html ("<?php echo __('Any')?>").attr ("value", 0));
				 jQuery.each (data, function (i, val) {
							  s = html_entity_decode (val['nombre']);
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
