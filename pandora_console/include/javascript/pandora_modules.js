/* Modules ids to check types */
var id_modules_icmp = Array (6, 7);
var id_modules_tcp = Array (8, 9, 10, 11);
var id_modules_snmp = Array (15, 16, 17, 18);

function configure_modules_form () {
	$("#id_module_type").change (function () {
		if (id_modules_icmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2, tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").attr ("disabled", "1");
		} else if (id_modules_snmp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").show ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").hide ();
			$("#text-tcp_port").removeAttr ("disabled");
		} else if (id_modules_tcp.in_array (this.value)) {
			$("tr#simple-snmp_1, tr#simple-snmp_2").hide ();
			$("tr#advanced-tcp_send, tr#advanced-tcp_receive").show ();
			$("#text-tcp_port").removeAttr ("disabled");
		}
	});
	
	$("#local_component_group").change (function () {
	
		var $select = $("#local_component").hide ();
		$("#component").hide ();
		if (this.value == 0)
			return;
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
					$("span#no_component").show ();
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
	
	
	$("#local_component").change (function () {
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_local_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				configuration_data = data['data']
					.replace(/&lt;/g,'<').replace(/&gt;/g,'>')
					.replace(/&#92;/g,'\\').replace(/&quot;/g,'\"').replace(/&#039;/g,'\'').replace(/&amp;/g,'&');
				
				$("#text-name").attr ("value", html_entity_decode (data["name"]));
				$("#textarea_description").attr ("value", html_entity_decode (data["description"]));
				$("#textarea_configuration_data").attr ("value", configuration_data);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
			},
			"json"
		);
	});
	
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
					$("span#no_component").show ();
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
	
	$("#network_component").change (function () {
		if (this.value == 0)
			return;
		$("#component_loading").show ();
		$(".error").hide ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"get_module_component" : 1,
			"id_module_component" : this.value
			},
			function (data, status) {
				$("#text-name").attr ("value", html_entity_decode (data["name"]));
				$("#textarea_description").attr ("value", html_entity_decode (data["description"]));
				$("#id_module_type option[value="+data["type"]+"]").select (1);
				$("#text-max").attr ("value", data["max"]);
				$("#text-min").attr ("value", data["min"]);
				$("#text-module_interval").attr ("value", data["module_interval"]);
				$("#text-tcp_port").attr ("value", data["tcp_port"]);
				$("#textarea_tcp_send").attr ("value", html_entity_decode (data["tcp_send"]));
				$("#textarea_tcp_rcv").attr ("value", html_entity_decode (data["tcp_rcv"]));
				$("#text-snmp_community").attr ("value", html_entity_decode (data["snmp_community"]));
				$("#text-snmp_oid").attr ("value", html_entity_decode (data["snmp_oid"])).show ();
				$("#oid, img#edit_oid").hide ();
				$("#id_module_group option["+data["id_group"]+"]").select (1);
				$("#max_timeout").attr ("value", data["max_timeout"]);
				$("#id_plugin option[value="+data["id_plugin"]+"]").select (1);
				$("#text-plugin_user").attr ("value", html_entity_decode (data["plugin_user"]));
				$("#password-plugin_pass").attr ("value", html_entity_decode (data["plugin_pass"]));
				$("#text-plugin_parameter").attr ("value", html_entity_decode (data["plugin_parameter"]));
				if (data["history_data"])
					$("#checkbox-history_data").check ();
				else
					$("#checkbox-history_data").uncheck ();
				$("#text-min_warning").attr ("value", (data["min_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-max_warning").attr ("value", (data["max_warning"] == 0) ? 0 : data["min_warning"]);
				$("#text-min_critical").attr ("value", (data["min_critical"] == 0) ? 0 : data["min_critical"]);
				$("#text-max_critical").attr ("value", (data["max_critical"] == 0) ? 0 : data["max_critical"]);
				$("#text-ff_threshold").attr ("value", (data["min_ff_event"] == 0) ? 0 : data["min_ff_event"]);
				$("#component_loading").hide ();
				$("#id_module_type").change ();
			},
			"json"
		);
	});
	
	$("#text-ip_target").keyup (function () {
		if (this.value != '') {
			$("#button-snmp_walk").enable ();
		} else {
			$("#button-snmp_walk").disable ();
		}
	});
	
	$("#button-snmp_walk").click (function () {
		$(this).disable ();
		$("#oid_loading").show ();
		$("span.error").hide ();
		$("#select_snmp_oid").empty ().hide ();
		$("#text-snmp_oid").hide ().attr ("value", "");
		$("span#oid").show ();
		jQuery.post ("ajax.php",
			{"page" : "godmode/agentes/module_manager_editor",
			"snmp_walk" : 1,
			"ip_target" : $("#text-ip_target").fieldValue (),
			"snmp_community" : $("#text-snmp_community").fieldValue ()
			},
			function (data, status) {
				if (data == false) {
					$("span#no_snmp").show ();
					$("#oid_loading").hide ();
					$("#edit_oid").hide ();
					return false;
				}
				jQuery.each (data, function (id, value) {
					opt = $("<option></option>").attr ("value", id).html (value);
					$("#select_snmp_oid").append (opt);
				});
				$("#select_snmp_oid").show ();
				$("#oid_loading").hide ();
				$("#button-snmp_walk").enable ();
				$("#edit_oid").show ();
				$("#button-snmp_walk").enable ();
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
	
	$("#prediction_id_agent").pandoraSelectAgentModule ({
		moduleSelect: "select#prediction_module"
	});
}
