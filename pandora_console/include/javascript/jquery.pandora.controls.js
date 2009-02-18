$(document).ready (function () {
	var dummyFunc = function () {
		return;
	};
	
	$.extend ({
		pandoraSelectGroup: new function() {
			this.defaults = {
				agentSelectId: "id_agent",
				loadingId: "agent_loading",
				callbackBefore: dummyFunc,
				callbackPre: dummyFunc,
				callbackPost: dummyFunc,
				callbackAfter: dummyFunc,
				debug: false
			};
			
			/* public methods */
			this.construct = function (settings) {
				return this.each (function() {
					this.config = {};
					
					var config = $.extend (this.config, $.pandoraSelectGroup.defaults, settings);
					
					$(this).change (function () {
						var $select = $("select#"+config.agentSelectId).disable ();
						$("#"+config.loadingId).show ();
						$("option[value!=0]", $select).remove ();
						config.callbackBefore (this);
						
						jQuery.post ("ajax.php",
							{"page" : "godmode/groups/group_list",
							"get_group_agents" : 1,
							"id_group" : this.value
							},
							function (data, status) {
								jQuery.each (data, function (id, value) {
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", id)
										.html (value);
									config.callbackPost (id, value, option);
									$("#"+config.agentSelectId).append (option);
								});
								$("#"+config.loadingId).hide ();
								$select.enable ();
								config.callbackAfter ();
							},
							"json"
						);
					});
				});
			};
		}
	});
	
	$.extend ({
		pandoraSelectAgent: new function() {
			this.defaults = {
				moduleSelectId: "id_agent_module",
				loadingId: "module_loading",
				callbackBefore: dummyFunc,
				callbackPre: dummyFunc,
				callbackPost: dummyFunc,
				callbackAfter: dummyFunc,
				debug: false
			};
			
			/* public methods */
			this.construct = function (settings) {
				return this.each (function() {
					this.config = {};
					
					var config = $.extend (this.config, $.pandoraSelectAgent.defaults, settings);
					
					$(this).change (function () {
						var $select = $("select#"+config.moduleSelectId).disable ();
						$("#"+config.loadingId).show ();
						$("option[value!=0]", $select).remove ();
						config.callbackBefore (this);
						
						jQuery.post ('ajax.php', 
							{"page": "operation/agentes/ver_agente",
							"get_agent_modules_json": 1,
							"id_agent": this.value
							},
							function (data) {
								jQuery.each (data, function (i, value) {
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", value['id_agente_modulo'])
										.html (html_entity_decode (value['nombre']));
									config.callbackPost (i, value, option);
									$("#"+config.moduleSelectId).append (option);
								});
								$("#"+config.loadingId).hide ();
								$select.enable ();
								config.callbackAfter ();
							},
							"json"
						);
					});
				});
			};
		}
	});
	$.fn.extend({
		pandoraSelectGroup: $.pandoraSelectGroup.construct,
		pandoraSelectAgent: $.pandoraSelectAgent.construct,
	});
});
