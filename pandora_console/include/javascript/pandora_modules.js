/* Modules ids to check types */
var id_modules_icmp = Array (6, 7);
var id_modules_tcp = Array (8, 9, 10, 11);
var id_modules_snmp = Array (15, 16, 17, 18);

function configure_modules_form () {
	$("#id_module_type").change (function () {
		if (id_modules_icmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2, tr#simple-tcp_send, tr#simple-tcp_receive").hide ();
			$("#text-tcp_port").attr ("disabled", "1");
		}
		else if (id_modules_snmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").show ();
			$("tr#simple-tcp_send, tr#simple-tcp_receive").hide ();
			$("#text-tcp_port").removeAttr ("disabled");
		}
		else if (id_modules_tcp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").hide ();
			$("tr#simple-tcp_send, tr#simple-tcp_receive").show ();
			$("#text-tcp_port").removeAttr ("disabled");
		}
	});
	
	$("#id_module_type").trigger("change");
	
	$("#local_component_group").change (function () {
		
		var $select = $("#local_component").hide ();
		$("#component").hide ();
		if (this.value == 0) {
			reset_data_module_form();
			return;
		}
		$("#component_loading").show ();
		$(".error, #no_component").hide ();
		$("option[value!=0]", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_local_components" : 1,
			"id_module_component_group" : this.value,
			"id_module_component_type" : $("#hidden-id_module_component_type").attr ("value")
			},
			function (data, status) {
				if (data == false) {
					$("#component_loading").hide ();
					$("#no_component").show ();
					return;
				}
				jQuery.each (data, function (i, val) {
					option = $("<option></option>")
						.attr ("value", val['id'])
						.append (val['name']);
					$select.append (option);
				});
				$("#component_loading").hide ();
				$select.show ();
				$("#component").show ();
			},
			"json"
		);
		}
	);
	
	function reset_data_module_form() {
			// Delete macro fields
			$('.macro_field').remove();
			
			// Hide show/hide configuration data switch
			$('#simple-show_configuration_data').hide();
			$('#simple-hide_configuration_data').hide();
			$('#configuration_data_legend').hide();
			
			$("#textarea_configuration_data").val('');
			$('#simple-configuration_data').show();
			
			$("#text-name").val('');
			$("#textarea_description").val('');
			$("#checkbox-history_data").check ();
			$("#text-max").attr ("value", "");
			$("#text-min").attr ("value", "");
			$("#text-min_warning").attr ("value", 0);
			$("#text-max_warning").attr ("value", 0);
			$("#text-str_warning").attr ("value", '');
			$("#text-min_critical").attr ("value", 0);
			$("#text-max_critical").attr ("value", 0);
			$("#text-str_critical").attr ("value", '');
			$("#text-ff_event").attr ("value", 0);
			$("#text-post_process").attr("value", 0);
			$("#text-unit").attr("value", '');
			$("#text-critical_inverse").attr ("value", 0);
			$("#text-warning_inverse").attr ("value", 0);
			$("#textarea_critical_instructions").attr ("value", '');
			$("#textarea_warning_instructions").attr ("value", '');
			$("#textarea_unknown_instructions").attr ("value", '');
			
	}
	
	$("#local_component").change (function () {
		if (this.value == 0) {
			reset_data_module_form();
			return;
		}
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php", {
			"page" : "godmode/agentes/module_manager_editor",
			"get_module_local_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				configuration_data = js_html_entity_decode (data['data']);
				$("#text-name").attr ("value",
					js_html_entity_decode (data["name"]));
				$("#textarea_description").attr ("value",
					js_html_entity_decode (data["description"]));
				$("#textarea_configuration_data").val(configuration_data);
				$("#component_loading").hide ();
				$("#id_module_type").val(data["type"]);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				// Workaround to update the advanced select control from html and ajax
				if (typeof 'period_select_module_interval_update' == 'function') {
					period_select_module_interval_update(data["module_interval"]);
				}
				else {
					period_select_update('module_interval',
						data["module_interval"]);
				}
				
				$("#id_module_group").val (data["id_module_group"]);
				
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				
				$("#text-min_warning").attr ("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning").attr ("value", (data["max_warning"] == 0) ? 0 : data["max_warning"]);
				$("#text-str_warning").attr ("value", data["str_warning"]);
				$("#text-min_critical").attr ("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical").attr ("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-str_critical").attr ("value", data["str_critical"]);
				$("#text-ff_event").attr ("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#text-post_process").attr("value", (data["post_process"] == 0) ? 0 : data["post_process"]);
				$("#text-unit").attr("value", (data["unit"] == '') ? '' : data["unit"])
				$("#text-critical_inverse").attr ("value", (data["critical_inverse"] == 0) ? 0 : data["critical_inverse"]);
				$("#text-warning_inverse").attr ("value", (data["warning_inverse"] == 0) ? 0 : data["warning_inverse"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
				if ($("#id_category").is("select")) {
					$("#id_category").val (data["id_category"]);
				}
				else {
					$("#hidden-id_category").val(data["id_category"]);
				}
				
				var tags = data["tags"];
				
				// Reset the selection of tags (put all of them into available box)
				$("#id_tag_selected option").each(function(){
					if ($(this).attr('value') != '') {
						$("#id_tag_selected").find("option[value='" + $(this).attr('value') + "']").remove();
						$("select[name='id_tag_available[]']").append($("<option></option>").val($(this).attr('value')).html($(this).text()));
					}
				});
				if ($("#id_tag_available option").length > 1) {
					$("#id_tag_available").find("option[value='']").remove();
				}
				if ($("#id_tag_selected option").length == 0) {
					$("select[name='id_tag_selected[]']").append($("<option></option>").val('').html('<i>None</i>'));
				}
				
				if (tags != '') {
					tags = tags.split(',');
					
					// Fill the selected tags box with select ones
					for (i = 0; i < tags.length; i++){
						$("#id_tag_available option").each(function(){
							if (tags[i] == $(this).text()) {
								$("#id_tag_available").find("option[value='" + $(this).attr('value') + "']").remove();
								$("select[name='id_tag_selected[]']").append($("<option></option>").val($(this).attr('value')).html($(this).text()));
								$("#id_tag_selected").find("option[value='']").remove();
							}
						});
						
						if ($("#id_tag_available option").length == 0) {
							$("select[name='id_tag_available[]']").append($("<option></option>").val('').html('<i>None</i>'));
						}
					}
				}
				
				if (data["throw_unknown_events"])
					$("input[name='throw_unknown_events']").check();
				else
					$("input[name='throw_unknown_events']").uncheck();
				
				// Delete macro fields
				$('.macro_field').remove();
				
				$('#hidden-macros').val('');
				
				var legend = '';
				// If exist macros, load the fields
				if (data["macros"] != '' && data["macros"] != null) {
					$('#hidden-macros').val(Base64.encode(data["macros"]));
					
					var obj = jQuery.parseJSON(data["macros"]);
					$.each(obj, function(k,macro) {
						add_macro_field(macro, 'simple-macro');
						legend += macro['macro']+" = "+ macro['desc']+"<br>";
					});
					$('#configuration_data_legend').html(legend);
					
					$('#simple-show_configuration_data').show();
					$('#simple-hide_configuration_data').hide();
					$('#configuration_data_legend').show();
					$('#simple-configuration_data').hide();
				}
				else {
					$('#simple-show_configuration_data').hide();
					$('#simple-hide_configuration_data').hide();
					$('#configuration_data_legend').hide();
					$('#simple-configuration_data').show();
				}
			},
			"json"
		);
	});
	
	network_component_group_change_event();
	
	flag_load_plugin_component = false;
	$("#network_component").change (function () {
		
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{
				"page" : "godmode/agentes/module_manager_editor",
				"get_module_component" : 1,
				"id_module_component" : this.value
			},
			function (data, status) {
				flag_load_plugin_component = true;
				
				$("#text-name").attr("value",
					js_html_entity_decode (data["name"]));
				$("#textarea_description").attr("value",
					js_html_entity_decode (data["description"]));
				$("#id_module_type").val(data["type"]);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				// Workaround to update the advanced select control from html and ajax
				if (typeof 'period_select_module_interval_update' == 'function') {
					period_select_module_interval_update(
						data["module_interval"]);
				}
				else {
					period_select_update('module_interval',
						data["module_interval"]);
				}
				$("#text-tcp_port").attr ("value", data["tcp_port"]);
				$("#textarea_tcp_send")
					.attr("value", js_html_entity_decode (data["tcp_send"]));
				$("#textarea_tcp_rcv")
					.attr("value", js_html_entity_decode (data["tcp_rcv"]));
				$("#text-snmp_community")
					.attr("value", js_html_entity_decode (data["snmp_community"]));
				$("#text-snmp_oid")
					.val( js_html_entity_decode (data["snmp_oid"]));
				$("#oid, img#edit_oid").hide ();
				$("#id_module_group").val (data["id_module_group"]);
				$("#max_timeout").attr ("value", data["max_timeout"]);
				$("#max_retries").attr ("value", data["max_retries"]);
				if (data["id_plugin"] != undefined) {
					$("#id_plugin").val(data["id_plugin"]);
				}
				$("#id_plugin").trigger('change');
				$("#text-plugin_user")
					.attr("value", js_html_entity_decode (data["plugin_user"]));
				$("#password-plugin_pass")
					.attr("value", js_html_entity_decode (data["plugin_pass"]));
				$("#text-plugin_parameter")
					.attr("value", js_html_entity_decode (data["plugin_parameter"]));
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				$("#text-min_warning")
					.attr("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning")
					.attr("value", (data["max_warning"] == 0) ? 0 : data["max_warning"]);
				$("#text-str_warning")
					.attr("value", data["str_warning"]);
				$("#text-min_critical")
					.attr("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical")
					.attr("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-str_critical")
					.attr("value", data["str_critical"]);
				$("#text-ff_event")
					.attr("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#text-post_process")
					.attr("value", (data["post_process"] == 0) ? 0 : data["post_process"]);
				$("#text-unit")
					.attr("value", (data["unit"] == '') ? '' : data["unit"]);
				$("#text-critical_inverse")
					.attr ("value", (data["critical_inverse"] == 0) ? 0 : data["critical_inverse"]);
				$("#text-warning_inverse")
					.attr ("value", (data["warning_inverse"] == 0) ? 0 : data["warning_inverse"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
				if ($("#id_category").is("select")) {
					$("#id_category").val (data["id_category"]);
				}
				else {
					$("#hidden-id_category").val(data["id_category"]);
				}
				
				var tags = data["tags"];
				
				// Reset the selection of tags (put all of them into available box)
				$("#id_tag_selected option").each(function() {
					if ($(this).attr('value') != '') {
						$("#id_tag_selected")
							.find("option[value='" + $(this).attr('value') + "']").remove();
						$("select[name='id_tag_available[]']")
							.append($("<option></option>").val($(this).attr('value')).html($(this).text()));
					}
				});
				if ($("#id_tag_available option").length > 1) {
					$("#id_tag_available").find("option[value='']").remove();
				}
				if ($("#id_tag_selected option").length == 0) {
					$("select[name='id_tag_selected[]']")
						.append(
							$("<option></option>")
							.val('').html('<i>None</i>')
						);
				}
				
				if (tags != '') {
					tags = tags.split(',');
					
					// Fill the selected tags box with select ones
					for (i = 0; i < tags.length; i++){
						$("#id_tag_available option").each(function(){
							if (tags[i] == $(this).text()) {
								$("#id_tag_available")
									.find("option[value='" + $(this).attr('value') + "']").remove();
								$("select[name='id_tag_selected[]']")
									.append($("<option></option>").val($(this).attr('value')).html($(this).text()));
								$("#id_tag_selected")
									.find("option[value='']").remove();
							}
						});
						
						if ($("#id_tag_available option").length == 0) {
							$("select[name='id_tag_available[]']")
								.append(
									$("<option></option>").val('')
										.html('<i>None</i>')
								);
						}
					}
				}
				
				// Delete macro fields
				$('.macro_field').remove();
				$('#hidden-macros').val('');
				
				
				// If exist macros, load the fields
				if (data["macros"] != '' && data["macros"] != null) {
					$('#hidden-macros').val(Base64.encode(data["macros"]));
					
					var obj = jQuery.parseJSON(data["macros"]);
					$.each(obj, function(k,macro) {
						add_macro_field(macro, 'simple-macro');
					});
				}
				
				if (data["type"] >= 15 && data["type"] <= 18) {
					$("#snmp_version").val(data["tcp_send"]);
					$("#text-snmp3_auth_user").val(data["plugin_user"]);
					$("#text-snmp3_auth_pass").val(data["plugin_pass"]);
					$("#snmp3_auth_method").val(data["plugin_parameter"]);
					$("#snmp3_privacy_method").val(data["custom_string_1"]);
					$("#text-snmp3_privacy_pass").val(data["custom_string_2"]);
					$("#snmp3_security_level").val(data["custom_string_3"]);
					
					if (data["tcp_send"] == "3") {
						$("#simple-field_snmpv3_row1").attr("style", "");
						$("#simple-field_snmpv3_row2").attr("style", "");
						$("#simple-field_snmpv3_row3").attr("style", "");
						$("input[name=active_snmp_v3]").val(1);
					}
				}
				
				if (data["throw_unknown_events"])
					$("input[name='throw_unknown_events']").check();
				else
					$("input[name='throw_unknown_events']").uncheck();
				
				
				if (data["id_plugin"] != undefined) {
					$("#id_plugin").trigger("change");
				}
			},
			"json"
		);
	});
	
	$("img#edit_oid").click (function () {
		$("#oid").hide ();
		$("#text-snmp_oid").show ()
			.attr ("value", $("#select_snmp_oid").fieldValue ());
		$(this).hide ();
	});
	
	$("form#module_form").submit (function () {
		if ($("#text-name").val () == "") {
			$("#text-name").focus ();
			$("#message").showMessage (no_name_lang);
			return false;
		}
		
		if ($("#id_plugin").attr ("value") == 0) {
			$("#id_plugin").focus ();
			$("#message").showMessage (no_plugin_lang);
			return false;
		}
		
		moduletype = $("#hidden-moduletype").val ();
		if (moduletype == 5) {
			if ($("#prediction_module").val () == null) {
				$("#prediction_module").focus ();
				$("#message").showMessage (no_prediction_module_lang);
				return false;
			}
		}
		
		module = $("#id_module_type").attr ("value");
		
		if (id_modules_icmp.in_array (module) || id_modules_tcp.in_array (module) || id_modules_snmp.in_array (module)) {
			/* Network module */
			if ($("#text-ip_target").val () == "") {
				$("#text-ip_target").focus ();
				$("#message").showMessage (no_target_lang);
				return false;
			}
		}
		
		if (id_modules_snmp.in_array (module)) {
			if ($("#text-snmp_oid").attr ("value") == "") {
				if ($("#select_snmp_oid").attr ("value") == "") {
					$("#message").showMessage (no_oid_lang);
					return false;
				}
			}
		}
		
		$("#message").hide ();
		return true;
	});
	
	if (typeof $("#prediction_id_group").pandoraSelectGroupAgent == 'function') {
		$("#prediction_id_group").pandoraSelectGroupAgent ({
			agentSelect: "select#prediction_id_agent",
			callbackBefore: function () {
				$("#module_loading").show ();
				$("#prediction_module option").remove ();
				return true;
			},
			callbackAfter: function (e) {
				if ($("#prediction_id_agent").children ().length == 0) {
					$("#module_loading").hide ();
					return;
				}
				$("#prediction_id_agent").change ();
			}
		});
	}
	
	if (typeof $("#prediction_id_agent").pandoraSelectAgentModule == 'function') {
		$("#prediction_id_agent").pandoraSelectAgentModule ({
			moduleSelect: "select#prediction_module"
		});
	}
}


// Functions to add and remove dynamic fields for macros
function delete_macro(prefix) {
	var next_number = parseInt($('#next_macro').html());
	// Is not possible delete first macro
	if (next_number == 3) {
		$('#delete_macro_button').hide();
	}
	var next_row = parseInt($('#next_row').html());
	$('#next_macro').html(next_number-1);
	$('#next_row').html(next_row-3);
	
	var nrow1 = next_row - 3;
	var nrow2 = next_row - 2;
	var nrow3 = next_row - 1;
	
	var $row1 = $('#'+prefix+nrow1).remove();
	var $row2 = $('#'+prefix+nrow2).remove();
	var $row3 = $('#'+prefix+nrow3).remove();
}

// The callback parameter is for a callback function
// that will receive the 3 rows (function(row1, row2, row3))
// to edit them before the new_macro function ends.
function new_macro(prefix, callback) {
	$('#delete_macro_button').show();
	
	var next_row = parseInt($('#next_row').html());
	
	$('#next_row').html(next_row + 3);
	var nrow1 = next_row - 3;
	var nrow2 = next_row - 2;
	var nrow3 = next_row - 1;
	var nrow4 = next_row;
	var nrow5 = next_row + 1;
	var nrow6 = next_row + 2;
	
	var next_number = parseInt($('#next_macro').html());
	$('#next_macro').html(next_number + 1);
	var current_number = next_number - 1;
	
	// Clone two last rows
	var $row1 = $('#' + prefix + nrow1).clone(true);
	var $row2 = $('#' + prefix + nrow2).clone(true);
	var $row3 = $('#' + prefix + nrow3).clone(true);
	
	// Change the tr ID
	$row1.attr('id',prefix + (nrow4));
	$row2.attr('id',prefix + (nrow5));
	$row3.attr('id',prefix + (nrow6));
	// Change the td ID
	$row1.find('td').attr('id', changeTdId);
	$row2.find('td').attr('id', changeTdId);
	$row3.find('td').attr('id', changeTdId);
	
	// Insert after last field
	$row3.insertAfter('#' + prefix + nrow3);
	$row2.insertAfter('#' + prefix + nrow3);
	$row1.insertAfter('#' + prefix + nrow3);
	
	// Change labels
	for(i = 0; i <= 3; i++) {
		var label1 = $("#" + prefix + nrow4 + "-" + i).html();
		var exp_reg = new RegExp('field' + current_number, 'g'); 
		label1 = label1.replace(exp_reg,'field' + next_number);
		$("#" + prefix + nrow4 + "-" + i).html(label1);
	}
	
	for(i = 0; i <= 1; i++) {
		var label2 = $("#" + prefix + nrow5 + "-" + i).html();
		var exp_reg = new RegExp('field' + current_number, 'g'); 
		label2 = label2.replace(exp_reg,'field' + next_number);
		$("#" + prefix + nrow5 + "-" + i).html(label2);
	}
	
	for(i = 0; i <= 1; i++) {
		var label3 = $("#" + prefix + nrow6 + "-" + i).html();
		var exp_reg = new RegExp('field' + current_number, 'g'); 
		label3 = label3.replace(exp_reg,'field' + next_number);
		$("#" + prefix + nrow6 + "-" + i).html(label3);
	}
	
	// Empty the text inputs
	$('#text-field' + next_number + '_desc').val('');
	$('#text-field' + next_number + '_help').val('');
	$('#text-field' + next_number + '_value').val('');
	$('#radio-field' + next_number + '_hide').val(0);
	
	if (typeof callback === 'function')
		callback($row1, $row2, $row3);
	
	function changeTdId() {
		switch(this.id) {
			case prefix + (nrow1) + '-0':
				return prefix + (nrow4) + '-0';
				break;
			case prefix + (nrow1) + '-1':
				return prefix + (nrow4) + '-1';
				break;
			case prefix + (nrow1) + '-2':
				return prefix + (nrow4) + '-2';
				break;
			case prefix + (nrow1) + '-3':
				return prefix + (nrow4) + '-3';
				break;
			case prefix + (nrow2) + '-0':
				return prefix + (nrow5) + '-0';
				break;
			case prefix + (nrow2) + '-1':
				return prefix + (nrow5) + '-1';
				break;
			case prefix + (nrow2) + '-2':
				return prefix + (nrow5) + '-2';
				break;
			case prefix + (nrow2) + '-3':
				return prefix + (nrow5) + '-3';
				break;
			case prefix + (nrow3) + '-0':
				return prefix + (nrow6) + '-0';
				break;
			case prefix + (nrow3) + '-1':
				return prefix + (nrow6) + '-1';
				break;
			case prefix + (nrow3) + '-2':
				return prefix + (nrow6) + '-2';
				break;
			case prefix + (nrow3) + '-3':
				return prefix + (nrow6) + '-3';
				break;
		}
	}
}

function add_macro_field(macro, row_model_id) {
	var macro_desc = macro['desc'];
	// Change the carriage returns by html returns <br> in help
	var macro_help = macro['help'].replace(/&#x0d;/g, "<br>");
	var macro_macro = macro['macro'];
	var macro_value =  $('<div />').html(macro['value']).text();
	var macro_hide = macro['hide'];
	
	macro_value.type = 'password';
	
	var row_id = row_model_id + macro_macro;
	
	var $macro_field = $('#'+ row_model_id +'_field').clone(true);
	
	// Change attributes to be unique and with identificable class
	$macro_field.attr('id',row_id);
	$macro_field.attr('class','macro_field');
	
	// Get the number of fields already printed
	var fields = $('.macro_field').size();
	
	// If is the first, we insert it after model row
	if (fields == 0) {
		$macro_field.insertAfter('#'+ row_model_id +'_field');
	}
	// If there are more fields, we insert it after the last one
	else {
		$macro_field.insertAfter('#'+ $('.macro_field').eq(fields-1).attr('id'));
	}
	
	// Change the label
	if (macro_help == '') {
		$('#'+row_id).children().eq(0).html(macro_desc);
	}
	else {
		var field_desc = $('#'+row_id).children().eq(0).html();
		
		field_desc = field_desc.replace('macro_desc',macro_desc);
		field_desc = field_desc.replace('macro_help',macro_help);
		
		$('#'+row_id).children().eq(0).html(field_desc);
	}
	
	// Change the text box id and value
	$('#'+row_id).children().eq(1).children().attr('id','text-'+macro_macro);
	$('#'+row_id).children().eq(1).children().attr('name',macro_macro);
	
	macro_field_hide = false;
	if (typeof(macro['hide']) == "string") {
		if (macro['hide'].length == 0) {
			macro_field_hide = false;
		}
		else {
			if (parseInt(macro['hide'])) {
				macro_field_hide = true;
			}
			else {
				macro_field_hide = false;
			}
		}
	}
	
	if (macro_field_hide) {
		$('#'+row_id).children().eq(1).children().attr('type','password');
	}
	$('#'+row_id).children().eq(1).children().val(macro_value);
	
	
	$('#'+row_id).show();
}

function load_plugin_macros_fields(row_model_id) {
	// Get plugin macros when selected and load macros fields
	var id_plugin = $('#id_plugin').val();
	
	var params = [];
	params.push("page=include/ajax/module");
	params.push("get_plugin_macros=1");
	params.push("id_plugin=" + id_plugin);
	
	jQuery.ajax ({
		data: params.join ("&"),
		type: 'POST',
		url: action = get_php_value('absolute_homeurl') + "ajax.php",
		async: false,
		timeout: 10000,
		dataType: 'json',
		success: function (data) {
			// Delete all the macro fields
			$('.macro_field').remove();
			
			if (data['array'] != null) {
				$('#hidden-macros').val(data['base64']);
				jQuery.each (data['array'], function (i, macro) {
					if (macro['desc'] != '') {
						add_macro_field(macro, row_model_id);
					}
				});
				//Plugin text can be larger
				$(".macro_field").find(":input").attr("maxlength", 1023);
				// Add again the hover event to the 'force_callback' elements
				forced_title_callback();
			}
		}
	});
}

function load_plugin_description(id_plugin) {
	jQuery.post ("ajax.php",
		{
			"page" : "godmode/servers/plugin",
			"get_plugin_description" : 1,
			"id_plugin" : id_plugin
		},
		function (data, status) {
			$("#plugin_description").html(data);
		}
	);
}

// Show the modal window of a module
function show_module_detail_dialog(module_id, id_agente) {
	
	$.ajax({
		type: "POST",
		url: "<?php echo ui_get_full_url('ajax.php', false, false, false); ?>",
		data: "page=include/ajax/module&get_module_detail=1&id_agente="+id_agente+"&id_module=" + module_id,
		dataType: "json",
		success: function(data){
			$("#module_details_window").hide ()
				.empty ()
				.append (data)
				.dialog ({
					resizable: true,
					draggable: true,
					modal: true,
					overlay: {
						opacity: 0.5,
						background: "black"
					},
					width: 620,
					height: 500
				})
				.show ();
		}
	});
}

function network_component_group_change_event() {
	$("#network_component_group").change (function () {
		var $select = $("#network_component").hide ();
		$("#component").hide ();
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error, #no_component").hide ();
		$("option[value!=0]", $select).remove ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_components" : 1,
			"id_module_component_group" : this.value,
			"id_module_component_type" : $("#hidden-id_module_component_type").attr ("value")
			},
			function (data, status) {
				if (data == false) {
					$("#component_loading").hide ();
					$("#no_component").show ();
					return;
				}
				jQuery.each (data, function (i, val) {
					option = $("<option></option>")
						.attr ("value", val['id_nc'])
						.append (val['name']);
					$select.append (option);
				});
				$("#component_loading").hide ();
				$select.show ();
				$("#component").show ();
			},
			"json"
		);
	});
}

function new_macro_local_component(prefix) {
	$('#delete_macro_button').show();
	
	var next_row = parseInt($('#next_row').html());
	
	$('#next_row').html(next_row + 2);
	var nrow1 = next_row - 2;
	var nrow2 = next_row - 1;
	var nrow3 = next_row;
	var nrow4 = next_row + 1;
	
	var next_number = parseInt($('#next_macro').html());
	$('#next_macro').html(next_number + 1);
	var current_number = next_number - 1;
	
	// Clone two last rows
	var $row1 = $('#' + prefix + nrow1).clone(true);
	var $row2 = $('#' + prefix + nrow2).clone(true);
	
	// Change the tr ID
	$row1.attr('id',prefix + (nrow3));
	$row2.attr('id',prefix + (nrow4));
	// Change the td ID
	$row1.find('td').attr('id', changeTdId);
	$row2.find('td').attr('id', changeTdId);
	
	// Insert after last field
	$row2.insertAfter('#' + prefix + nrow2);
	$row1.insertAfter('#' + prefix + nrow2);
	
	// Change labels
	for(i = 0; i <= 3; i++) {
		var label1 = $("#" + prefix + nrow3 + "-" + i).html();
		var exp_reg = new RegExp('field' + current_number, 'g'); 
		label1 = label1.replace(exp_reg,'field' + next_number);
		$("#" + prefix + nrow3 + "-" + i).html(label1);
	}
	
	for(i = 0; i <= 1; i++) {
		var label2 = $("#" + prefix + nrow4 + "-" + i).html();
		var exp_reg = new RegExp('field' + current_number, 'g'); 
		label2 = label2.replace(exp_reg,'field' + next_number);
		$("#" + prefix + nrow4 + "-" + i).html(label2);
	}
	
	// Empty the text inputs
	$('#text-field' + next_number + '_desc').val('');
	$('#text-field' + next_number + '_help').val('');
	$('#text-field' + next_number + '_value').val('');
	
	function changeTdId() {
		switch(this.id) {
			case prefix + (nrow1) + '-0':
				return prefix + (nrow3) + '-0';
				break;
			case prefix + (nrow1) + '-1':
				return prefix + (nrow3) + '-1';
				break;
			case prefix + (nrow1) + '-2':
				return prefix + (nrow3) + '-2';
				break;
			case prefix + (nrow1) + '-3':
				return prefix + (nrow3) + '-3';
				break;
			case prefix + (nrow2) + '-0':
				return prefix + (nrow4) + '-0';
				break;
			case prefix + (nrow2) + '-1':
				return prefix + (nrow4) + '-1';
				break;
			case prefix + (nrow2) + '-2':
				return prefix + (nrow4) + '-2';
				break;
			case prefix + (nrow2) + '-3':
				return prefix + (nrow4) + '-3';
				break;
		}
	}
}

function delete_macro_local_component(prefix) {
	var next_number = parseInt($('#next_macro').html());
	// Is not possible delete first macro
	if (next_number == 3) {
		$('#delete_macro_button').hide();
	}
	var next_row = parseInt($('#next_row').html());
	$('#next_macro').html(next_number-1);
	$('#next_row').html(next_row-2);
	
	var nrow1 = next_row - 2;
	var nrow2 = next_row - 1;
	
	var $row1 = $('#'+prefix+nrow1).remove();
	var $row2 = $('#'+prefix+nrow2).remove();
}

