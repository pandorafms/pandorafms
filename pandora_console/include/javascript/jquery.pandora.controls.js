(function($) {
	var dummyFunc = function () {
		return true;
	};

	var _pandoraSelectGroupAgent = function (disabled) {
		var that = this;

		this.defaults = {
			agentSelect: "select#id_agent",
			recursion: 0,
			filter_agents_json: "",
			loading: "#agent_loading",
			callbackBefore: dummyFunc,
			callbackPre: dummyFunc,
			callbackPost: dummyFunc,
			callbackAfter: dummyFunc,
			debug: false,
			disabled: disabled || false,
		};

		/* public methods */
		this.construct = function (settings) {
			return this.each (function() {
				this.config = {};

				this.config = $.extend (this.config, that.defaults, settings);
				var config = this.config;

				$(this).change (function () {
					var $select = $(config.agentSelect).disable ();
					$(config.loading).show ();
					$("option[value!=0]", $select).remove ();
					if (! config.callbackBefore (this))
						return;

					var opts = {
						"page" : "godmode/groups/group_list",
						"get_group_agents" : 1,
						"id_group" : this.value,
						"recursion" : config.recursion,
						"filter_agents_json" : config.filter_agents_json,
						"disabled" : config.disabled ? 1 : 0,
					};

					jQuery.post ("ajax.php",
						opts,
						function (data, status) {
							jQuery.each (data, function (id, value) {
								if (id !== 'keycount'){
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", id)
										.html (value);
									config.callbackPost (id, value, option);
									$(config.agentSelect).append (option);
								}
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
	};
	
	$.extend ({
		pandoraSelectGroupAgent: new _pandoraSelectGroupAgent(),
		pandoraSelectGroupAgentDisabled: new _pandoraSelectGroupAgent(true),
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
				moduleFilter: 'disabled=0 AND delete_pending=0',
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
						if (! config.callbackBefore (this))
							return;
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
									// Get the selected item from hidden field
									selected = $('#hidden-'+config.moduleSelect.attr('id')+'_selected').val();
									if(selected == i) {
										option = $("<option></option>")
											.attr ("selected", "selected")
											.attr ("value", value['id_agente_modulo'])
											.html (js_html_entity_decode (value['nombre']));
									}
									else {
										option = $("<option></option>")
											.attr ("value", value['id_agente_modulo'])
											.html (js_html_entity_decode (value['nombre']));
									}
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
						if (! config.callbackBefore (this))
							return;
						
						jQuery.post ('ajax.php', 
							{"page": "include/ajax/alert_list.ajax",
							"get_agent_alerts_simple": 1,
							"id_agent": this.value
							},
							function (data) {
								jQuery.each (data, function (i, value) {
									config.callbackPre ();
									option = $("<option></option>")
										.attr ("value", value['id'])
										.html (js_html_entity_decode (value['template']['name']))
										.append (" ("+js_html_entity_decode (value['module_name'])+")");
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
	
	$.extend ({
		pandoraSelectOS: new function() {
			this.defaults = {
				alertSelect: "select#id_os",
				spanPreview: "#os_preview",
				debug: false
			};
			
			/* public methods */
			this.construct = function (settings) {
				return this.each (function() {
					this.config = {};
					this.config = $.extend (this.config, $.pandoraSelectOS.defaults, settings);
					
					var config = this.config;

					$(this).change (function () {
						var id_os = this.value;
						
						$(config.spanPreview).fadeOut ('fast', function () {
							$("img", config.spanPreview).remove ();
							jQuery.post ('ajax.php', 
								{"page": "godmode/setup/setup",
								"get_os_icon": 1,
								"id_os": id_os
								},
								function (data) {
									$(config.spanPreview)
										.append (data)
										.fadeIn ('fast');
								},
								"html"
							);
						});
						
					});
				});
			};
		}
	});
	
	$.extend ({
		pandoraSelectGroupIcon: new function() {
			this.defaults = {
				alertSelect: "select#id_group",
				spanPreview: "#group_preview",
				debug: false
			};
			
			/* public methods */
			this.construct = function (settings) {
				return this.each (function() {
					this.config = {};
					this.config = $.extend (this.config, $.pandoraSelectGroupIcon.defaults, settings);
					
					var config = this.config;

					$(this).change (function () {
						var id_group = this.value;
						
						$(config.spanPreview).fadeOut ('fast', function () {
							$("img", config.spanPreview).remove ();
							jQuery.post ('ajax.php',
								{"page": "godmode/groups/group_list",
								"get_group_json": 1,
								"id_group": id_group
								},
								function (data) {
									img = $("<img />").attr ("src", "images/groups_small/"+data["icon"]+".png");
									$(config.spanPreview)
										.append (img)
										.fadeIn ('fast');
								},
								"json"
							);
						});
						
					});
				});
			};
		}
	});
	
	$.fn.extend({
		pandoraSelectGroupAgent: $.pandoraSelectGroupAgent.construct,
		pandoraSelectGroupAgentDisabled: $.pandoraSelectGroupAgentDisabled.construct,
		pandoraSelectAgentModule: $.pandoraSelectAgentModule.construct,
		pandoraSelectAgentAlert: $.pandoraSelectAgentAlert.construct,
		pandoraSelectOS: $.pandoraSelectOS.construct,
		pandoraSelectGroupIcon: $.pandoraSelectGroupIcon.construct
	});
}) (jQuery);
