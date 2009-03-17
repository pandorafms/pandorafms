(function($) {
	var dummyFunc = function () {
		return;
	};
	
	$.extend ({
		pandoraSelectGroup: new function() {
			this.defaults = {
				agentSelect: "select#id_agent",
				loading: "#agent_loading",
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
					
					this.config = $.extend (this.config, $.pandoraSelectGroup.defaults, settings);
					var config = this.config;
					
					$(this).change (function () {
						var $select = $(config.agentSelect).disable ();
						$(config.loading).show ();
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
									$(config.agentSelect).append (option);
								});
								$(config.loading).hide ();
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
		pandoraSelectAgentModule: new function() {
			this.defaults = {
				moduleSelect: "select#id_agent_module",
				loading: "#module_loading",
				callbackBefore: dummyFunc,
				callbackPre: dummyFunc,
				callbackPost: dummyFunc,
				callbackAfter: dummyFunc,
				moduleFilter: 'disabled=0',
				debug: false
			};
			
			/* public methods */
			this.construct = function (settings) {
				return this.each (function() {
					this.config = {};
					
					this.config = $.extend (this.config, $.pandoraSelectAgentModule.defaults, settings);
					
					var config = this.config;

					$(this).change (function () {
						var $select = $(config.moduleSelect).disable ();
						$(config.loading).show ();
						$("option[value!=0]", $select).remove ();
						config.callbackBefore (this);
						
						jQuery.post ('ajax.php', 
							{"page": "operation/agentes/ver_agente",
							"get_agent_modules_json": 1,
							"id_agent": this.value,
							"filter" : config.moduleFilter,
							"fields" : "id_agente_modulo,nombre"
							},
							function (data) {
								jQuery.each (data, function (i, value) {
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", value['id'])
										.html (html_entity_decode (value['template']['name']));
									config.callbackPost (i, value, option);
									$(config.moduleSelect).append (option);
								});
								$(config.loading).hide ();
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
		pandoraSelectAgentAlert: new function() {
			this.defaults = {
				alertSelect: "select#id_agent_module",
				loading: "#alert_loading",
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
					
					this.config = $.extend (this.config, $.pandoraSelectAgentAlert.defaults, settings);
					
					var config = this.config;

					$(this).change (function () {
						var $select = $(config.alertSelect).disable ();
						$(config.loading).show ();
						$("option[value!=0]", $select).remove ();
						config.callbackBefore (this);
						
						jQuery.post ('ajax.php', 
							{"page": "godmode/alerts/alert_list",
							"get_agent_alerts_simple": 1,
							"id_agent": this.value
							},
							function (data) {
								jQuery.each (data, function (i, value) {
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", value['id'])
										.html (html_entity_decode (value['template']['name']))
										.append (" ("+html_entity_decode (value['module_name'])+")");
									config.callbackPost (i, value, option);
									$(config.alertSelect).append (option);
								});
								$(config.loading).hide ();
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
		pandoraSelectAgentModule: $.pandoraSelectAgentModule.construct,
		pandoraSelectAgentAlert: $.pandoraSelectAgentAlert.construct
	});
}) (jQuery);
