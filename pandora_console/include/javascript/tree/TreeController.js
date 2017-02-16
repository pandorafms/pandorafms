// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2010 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

var TreeController = {
	controllers: [],
	getController: function () {
		var controller = {
			index: -1,
			recipient: '',
			tree: [],
			emptyMessage: "No data found.",
			errorMessage: "Error",
			baseURL: "",
			ajaxURL: "ajax.php",
			ajaxPage: "include/ajax/tree.ajax",
			detailRecipient: '',
			filter: {},
			counterTitles: {},
			shouldHaveCounters: true,
			reload: function () {
				// Bad recipient
				if (typeof this.recipient == 'undefined' || this.recipient.length == 0) {
					return;
				}

				// Load branch
				function _processGroup (container, elements, rootGroup) {
					var $group = $("<ul></ul>");
					
					// First group
					if (typeof rootGroup != 'undefinded' && rootGroup == true) {
						$group
							.addClass("tree-root")
							.hide()
							.prepend('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')+'images/pandora.png" />');
					}
					// Normal group
					else {
						$group
							.addClass("tree-group")
							.hide();
					}

					container.append($group);
					
					_.each(elements, function(element) {
						element.jqObject = _processNode($group, element);
					});
					
					return $group;
				}

				// Load leaf counters
				function _processNodeCounters (container, counters, type) {
					var hasCounters = false;

					if (typeof counters != 'undefined') {
						
						function _processNodeCounterTitle (container, elementType, counterType) {
							var defaultCounterTitles = {
								total: {
									agents: "Total agents",
									modules: "Total modules",
									none: "Total"
								},
								alerts: {
									agents: "Alerts fired",
									modules: "Alerts fired",
									none: "Alerts fired"
								},
								critical: {
									agents: "Critical agents",
									modules: "Critical modules",
									none: "Critical"
								},
								warning: {
									agents: "Warning agents",
									modules: "Warning modules",
									none: "Warning"
								},
								unknown: {
									agents: "Unknown agents",
									modules: "Unknown modules",
									none: "Unknown"
								},
								not_init: {
									agents: "Not init agents",
									modules: "Not init modules",
									none: "Not init"
								},
								ok: {
									agents: "Normal agents",
									modules: "Normal modules",
									none: "Normal"
								}
							}

							try {
								var title = '';
								switch (elementType) {
									case "group":
										if (typeof controller.counterTitles != 'undefined'
												&& typeof controller.counterTitles[counterType] != 'undefined'
												&& typeof controller.counterTitles[counterType].agents != 'undefined') {
											title = controller.counterTitles[counterType].agents;
										}
										else {
											title = defaultCounterTitles[counterType].agents;
										}
										break;
									case "agent":
										if (typeof controller.counterTitles != 'undefined'
												&& typeof controller.counterTitles[counterType] != 'undefined'
												&& typeof controller.counterTitles[counterType].modules != 'undefined') {
											title = controller.counterTitles[counterType].modules;
										}
										else {
											title = defaultCounterTitles[counterType].modules;
										}
										break;
									default:
										if (typeof controller.counterTitles != 'undefined'
												&& typeof controller.counterTitles[counterType] != 'undefined'
												&& typeof controller.counterTitles[counterType].none != 'undefined') {
											title = controller.counterTitles[counterType].none;
										}
										else {
											title = defaultCounterTitles[counterType].none;
										}
										break;
								}
								if (title.length > 0) {
									container
										.data("title", title)
										.addClass("forced_title")
										.data("use_title_for_force_title", 1); // Trick to make easier the 'force title' output
								}
							}
							catch (error) {
								// console.log(error);
							}
						}

						var $counters = $("<div></div>");
						$counters.addClass('tree-node-counters');

						if (typeof counters.total != 'undefined'
								&& counters.total > 0) {
							var $totalCounter = $("<div></div>");
							$totalCounter
								.addClass('tree-node-counter')
								.addClass('total')
								.html(counters.total);
							
							_processNodeCounterTitle($totalCounter, type, "total");
							
							// Open the parentheses
							$counters.append(" (");

							$counters.append($totalCounter);

							if (typeof counters.alerts != 'undefined'
									&& counters.alerts > 0) {
								var $firedCounter = $("<div></div>");
								$firedCounter
									.addClass('tree-node-counter')
									.addClass('alerts')
									.addClass('orange')
									.html(counters.alerts);

								_processNodeCounterTitle($firedCounter, type, "alerts");

								$counters
									.append(" : ")
									.append($firedCounter);
							}
							if (typeof counters.critical != 'undefined'
									&& counters.critical > 0) {
								var $criticalCounter = $("<div></div>");
								$criticalCounter
									.addClass('tree-node-counter')
									.addClass('critical')
									.addClass('red')
									.html(counters.critical);

								_processNodeCounterTitle($criticalCounter, type, "critical");
								
								$counters
									.append(" : ")
									.append($criticalCounter);
							}
							if (typeof counters.warning != 'undefined'
									&& counters.warning > 0) {
								var $warningCounter = $("<div></div>");
								$warningCounter
									.addClass('tree-node-counter')
									.addClass('warning')
									.addClass('yellow')
									.html(counters.warning);

								_processNodeCounterTitle($warningCounter, type, "warning");
								
								$counters
									.append(" : ")
									.append($warningCounter);
							}
							if (typeof counters.unknown != 'undefined'
									&& counters.unknown > 0) {
								var $unknownCounter = $("<div></div>");
								$unknownCounter
									.addClass('tree-node-counter')
									.addClass('unknown')
									.addClass('grey')
									.html(counters.unknown);

								_processNodeCounterTitle($unknownCounter, type, "unknown");
								
								$counters
									.append(" : ")
									.append($unknownCounter);
							}
							if (typeof counters.not_init != 'undefined'
									&& counters.not_init > 0) {
								var $notInitCounter = $("<div></div>");
								$notInitCounter
									.addClass('tree-node-counter')
									.addClass('not_init')
									.addClass('blue')
									.html(counters.not_init);

								_processNodeCounterTitle($notInitCounter, type, "not_init");
								
								$counters
									.append(" : ")
									.append($notInitCounter);
							}
							if (typeof counters.ok != 'undefined'
									&& counters.ok > 0) {
								var $okCounter = $("<div></div>");
								$okCounter
									.addClass('tree-node-counter')
									.addClass('ok')
									.addClass('green')
									.html(counters.ok);

								_processNodeCounterTitle($okCounter, type, "ok");
								
								$counters
									.append(" : ")
									.append($okCounter);
							}

							// Close the parentheses
							$counters.append(")");

							hasCounters = true;
						}

						// Add the counters html to the container
						container.append($counters);
					}

					return hasCounters;
				}

				// Load leaf
				function _processNode (container, element) {
					
					// type, [id], [serverID], callback
					function _getTreeDetailData (type, id, serverID, callback) {
						var lastParam = arguments[arguments.length - 1];
						var callback;
						if (typeof lastParam === 'function')
							callback = lastParam;
						
						var serverID;
						if (arguments.length >= 4)
							serverID = arguments[2];
						var id;
						if (arguments.length >= 3)
							id = arguments[1];
						var type;
						if (arguments.length >= 2)
							type = arguments[0];
						
						if (typeof type === 'undefined')
							throw new TypeError('Type required');
						if (typeof callback === 'undefined')
							throw new TypeError('Callback required');
						
						var postData = {
							page: controller.ajaxPage,
							getDetail: 1,
							type: type
						}
						
						if (typeof id !== 'undefined')
							postData.id = id;
						if (typeof serverID !== 'undefined')
							postData.serverID = serverID;
						
						$.ajax({
							url: controller.ajaxURL,
							type: 'POST',
							dataType: 'html',
							data: postData,
							success: function(data, textStatus, xhr) {
								callback(null, data);
							},
							error: function(xhr, textStatus, errorThrown) {
								callback(errorThrown);
							}
						});
					}

					var $node = $("<li></li>");
					var $leafIcon = $("<div></div>");
					var $content = $("<div></div>");

					// Leaf icon
					$leafIcon.addClass("leaf-icon");

					// Content
					$content.addClass("node-content");
					switch (element.type) {
						case 'group':
							if (typeof element.icon != 'undefined' && element.icon.length > 0) {
								$content.append('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
									+'images/groups_small/'+element.icon+'" /> ');
							}
							else if (typeof element.iconHTML != 'undefined' && element.iconHTML.length > 0) {
								$content.append(element.iconHTML + " ");
							}
							$content.append(element.name);
							break;
						case 'agent':
							// Is quiet
							if (typeof element.quietImageHTML != 'undefined'
									&& element.quietImageHTML.length > 0) {
								var $quietImage = $(element.quietImageHTML);
								$quietImage.addClass("agent-quiet");

								$content.append($quietImage);
							}
							// Status image
							if (typeof element.statusImageHTML != 'undefined'
									&& element.statusImageHTML.length > 0) {
								var $statusImage = $(element.statusImageHTML);
								$statusImage.addClass("agent-status");

								$content.append($statusImage);
							}
							// Alerts fired image
							if (typeof element.alertImageHTML != 'undefined'
									&& element.alertImageHTML.length > 0) {
								var $alertImage = $(element.alertImageHTML);
								$alertImage.addClass("agent-alerts-fired");

								$content.append($alertImage);
							}
							$content.append(element.alias);
							break;
						case 'module':
							// Status image
							if (typeof element.statusImageHTML != 'undefined'
									&& element.statusImageHTML.length > 0) {
								var $statusImage = $(element.statusImageHTML);
								$statusImage.addClass("module-status");

								$content.append($statusImage);
							}
							// Server type
							if (typeof element.serverTypeHTML != 'undefined'
									&& element.serverTypeHTML.length > 0
									&& element.serverTypeHTML != '--') {
								var $serverTypeImage = $(element.serverTypeHTML);
								$serverTypeImage.addClass("module-server-type");

								$content.append($serverTypeImage);
							}
							
							if (typeof element.showGraphs != 'undefined' && element.showGraphs != 0) {
								// Graph pop-up
								if (typeof element.moduleGraph != 'undefined') {
									
									if(element.statusImageHTML.indexOf('data:image')!=-1){
									var $graphImage = $('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
											+'images/photo.png" /> ');
									}
									else{
									
									var $graphImage = $('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
											+'images/chart_curve.png" /> ');	
									}
									
									$graphImage
										.addClass('module-graph')
										.click(function (e) {
											e.preventDefault();
	if(element.statusImageHTML.indexOf('data:image')!=-1){
											try {
												winopeng('operation/agentes/snapshot_view.php?id='+element.id+'&refr=&label='+element.name);
											}
											catch (error) {
												// console.log(error);
											}
										}
										else{
											
											try {
												
													winopeng(element.moduleGraph.url, element.moduleGraph.handle);
											}
											catch (error) {
												// console.log(error);
											}
											
											
										}
										});

									$content.append($graphImage);
								}
								
								// Data pop-up
								if (typeof element.id != 'undefined' && !isNaN(element.id)) {

									var $dataImage = $('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
											+'images/binary.png" /> ');
									$dataImage
										.addClass('module-data')
										.click(function (e) {
											e.preventDefault();

											try {
												var serverName = element.serverName.length > 0 ? element.serverName : '';
												if ($("#module_details_window").length > 0)
													show_module_detail_dialog(element.id, '', serverName, 0, 86400, element.name.replace(/&#x20;/g , " ") );
											}
											catch (error) {
												// console.log(error);
											}
										});

									$content.append($dataImage);
								}
							}

							// Alerts
							if (typeof element.alertsImageHTML != 'undefined'
									&& element.alertsImageHTML.length > 0) {

								var $alertsImage = $(element.alertsImageHTML);

								$alertsImage
									.addClass("module-alerts")
									.click(function (e) {
										_getTreeDetailData('alert', element.id, element.serverID, function (error, data) {
											if (error) {
												// console.error(error);
											}
											else {
												controller.detailRecipient.render(element.name, data).open();
											}
										});

										// Avoid the execution of the module detail event
										e.stopPropagation();
									})
									.css('cursor', 'pointer');

								$content.append($alertsImage);
							}

							$content.append(element.name);
							break;
						case 'os':
							if (typeof element.icon != 'undefined' && element.icon.length > 0) {
								$content.append('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
									+'images/os_icons/'+element.icon+'" /> ');
							}
							$content.append(element.name);
							break;
						case 'tag':
							if (typeof element.icon != 'undefined' && element.icon.length > 0) {
								$content.append('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
									+'images/os_icons/'+element.icon+'" /> ');
							}
							else {
								$content.append('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
									+'images/tag_red.png" /> ');
							}
							$content.append(element.name);
							break;
						default:
							$content.append(element.name);
							break;
					}

					// Load the status counters
					var hasCounters = _processNodeCounters($content, element.counters, element.type);
					//Don't show empty groups
					if (element.type == 'agent') {
						if (!hasCounters) {
							return;
						}
					}
					// If exist the detail container, show the data
					if (typeof controller.detailRecipient !== 'undefined') {
						if (element.type == 'agent' || element.type == 'module') {
							$content.click(function (e) {
									_getTreeDetailData(element.type, element.id, element.serverID, function (error, data) {
										if (error) {
											// console.error(error);
										}
										else {
											controller.detailRecipient.render(element.name, data).open();
										}
									});
								})
								.css('cursor', 'pointer');
						}
					}

					$node
						.addClass("tree-node")
						.append($leafIcon)
						.append($content);

					container.append($node);

					$node.addClass("leaf-empty");

					if (typeof element.children != 'undefined' && element.children.length > 0) {
						$node
							.removeClass("leaf-empty")
							.addClass("leaf-closed");

						// Add children
						var $children = _processGroup($node, element.children);
						$node.data('children', $children);

						if (typeof element.searchChildren == 'undefined' || !element.searchChildren) {
							$leafIcon.click(function (e) {
								e.preventDefault();

								if ($node.hasClass("leaf-open")) {
									$node
										.removeClass("leaf-open")
										.addClass("leaf-closed")
										.data('children')
											.slideUp();
								}
								else {
									$node
										.removeClass("leaf-closed")
										.addClass("leaf-open")
										.data('children')
											.slideDown();
								}
							});
						}
					}
					if (typeof element.searchChildren != 'undefined' && element.searchChildren) {
						$node
							.removeClass("leaf-empty")
							.addClass("leaf-closed");

						$leafIcon.click(function (e) {
							e.preventDefault();

							if (!$node.hasClass("leaf-loading") && !$node.hasClass("children-loaded") && !$node.hasClass("leaf-empty")) {
								$node
									.removeClass("leaf-closed")
									.removeClass("leaf-error")
									.addClass("leaf-loading");

								$.ajax({
									url: controller.ajaxURL,
									type: 'POST',
									dataType: 'json',
									data: {
										page: controller.ajaxPage,
										getChildren: 1,
										id: element.id,
										type: element.type,
										rootID: element.rootID,
										serverID: element.serverID,
										rootType: element.rootType,
										filter: controller.filter
									},
									complete: function(xhr, textStatus) {
										$node.removeClass("leaf-loading");
										$node.addClass("children-loaded");
									},
									success: function(data, textStatus, xhr) {
										if (data.success) {
											var $group = $node.children("ul.tree-group");

											if ((typeof data.tree != 'undefined' && data.tree.length > 0) || $group.length > 0) {
												$node.addClass("leaf-open");

												if ($group.length <= 0) {
													$group = $("<ul></ul>");
													$group
														.addClass("tree-group")
														.hide();
													$node.append($group);
												}
												
												_.each(data.tree, function(element) {
													element.jqObject = _processNode($group, element);
												});

												$group.slideDown();

												$node.data('children', $group);
												
												// Add again the hover event to the 'force_callback' elements
												forced_title_callback();
											}
											else {
												$node.addClass("leaf-empty");
											}
										}
										else {
											$node.addClass("leaf-error");
										}
									},
									error: function(xhr, textStatus, errorThrown) {
										$node.addClass("leaf-error");
									}
								});
							}
							else if (! $node.hasClass("leaf-empty")) {
								if ($node.hasClass("leaf-open")) {
									$node
										.removeClass("leaf-open")
										.addClass("leaf-closed")
										.data('children')
											.slideUp();
								}
								else {
									$node
										.removeClass("leaf-closed")
										.addClass("leaf-open")
										.data('children')
											.slideDown();
								}
							}
						});
					}

					return $node;
				}

				if (controller.recipient.length == 0) {
					return;
				}
				else if (controller.tree.length == 0) {
					controller.recipient.empty();
					controller.recipient.html("<div>" + controller.emptyMessage + "</div>");
					return;
				}

				controller.recipient.empty();

				var $children = _processGroup(this.recipient, this.tree, true);
				$children.show();

				controller.recipient.data('children', $children);

				// Add again the hover event to the 'force_callback' elements
				forced_title_callback();
			},
			load: function () {
				this.reload();
			},
			changeTree: function (tree) {
				this.tree = tree;
				this.reload();
			},
			init: function (data) {
				if (typeof data.recipient !== 'undefined' && data.recipient.length > 0) {
					this.recipient = data.recipient;
				}
				if (typeof data.detailRecipient !== 'undefined') {
					this.detailRecipient = data.detailRecipient;
				}
				if (typeof data.tree !== 'undefined') {
					this.tree = data.tree;
				}
				if (typeof data.emptyMessage !== 'undefined' && data.emptyMessage.length > 0) {
					this.emptyMessage = data.emptyMessage;
				}
				if (typeof data.errorMessage !== 'undefined' && data.errorMessage.length > 0) {
					this.errorMessage = data.errorMessage;
				}
				if (typeof data.baseURL !== 'undefined' && data.baseURL.length > 0) {
					this.baseURL = data.baseURL;
				}
				if (typeof data.ajaxURL !== 'undefined' && data.ajaxURL.length > 0) {
					this.ajaxURL = data.ajaxURL;
				}
				if (typeof data.ajaxPage !== 'undefined' && data.ajaxPage.length > 0) {
					this.ajaxPage = data.ajaxPage;
				}
				if (typeof data.filter !== 'undefined') {
					this.filter = data.filter;
				}
				
				this.load();
			},
			remove: function () {
				if (typeof this.recipient != 'undefined' && this.recipient.length > 0) {
					this.recipient.empty();
				}
				
				if (this.index > -1) {
					TreeController.controllers.splice(this.index, 1);
				}
			}
		}
		controller.index = TreeController.controllers.push(controller) - 1;

		return controller;
	}
}
