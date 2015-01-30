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

var TreeController;
var TreeNodeDetailController;

TreeController = {
	controllers: [],
	getController: function () {
		var controller = {
			index: -1,
			recipient: '',
			tree: [],
			emptyMessage: "Empty",
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
							.prepend('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')+'images/pandora.ico.gif" />');
					}
					// Normal group
					else {
						$group
							.addClass("tree-group")
							.hide();
					}

					container.append($group);
					
					elements.forEach(function(element, index) {
						element.jqObject = _processNode($group, element);
					}, $group);
					
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
										.prop("title", title)
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
					// Load the counters asynchronously
					// else if (typeof element.searchCounters != 'undefined' && element.searchCounters) {
					// 	var $counters = $("<div></div>");
					// 	$counters
					// 		.addClass('tree-node-counters')
					// 		.append(' (')
					// 		.append('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')+'images/spinner.gif" />')
					// 		.append(')');

					// 	$content.append($counters);
					// }
				}

				// Load leaf
				function _processNode (container, element) {
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
								$content.append(element.iconHTML);
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
							$content.append(element.name);
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

							// Graph pop-up
							if (typeof element.moduleGraph != 'undefined') {
								var $graphImage = $('<img src="'+(controller.baseURL.length > 0 ? controller.baseURL : '')
										+'images/chart_curve.png" /> ');
								$graphImage
									.addClass('module-graph')
									.click(function (e) {
										e.preventDefault();

										try {
											winopeng(element.moduleGraph.url, element.moduleGraph.handle);
										}
										catch (error) {
											// console.log(error);
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
												show_module_detail_dialog(element.id, '', serverName, 0, 86400);
										}
										catch (error) {
											// console.log(error);
										}
									});

								$content.append($dataImage);
							}

							// Alerts
							if (typeof element.alertsImageHTML != 'undefined'
									&& element.alertsImageHTML.length > 0) {

								var $alertsImage = $(element.alertsImageHTML);

								$alertsImage
									.addClass("module-alerts")
									.click(function (e) {
										TreeNodeDetailController.getController().init({
											recipient: controller.detailRecipient,
											type: 'alert',
											id: element.id,
											serverID: element.serverID,
											baseURL: controller.baseURL,
											ajaxURL: controller.ajaxURL,
											ajaxPage: controller.ajaxPage
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

					// If exist the detail container, show the data
					if (typeof controller.detailRecipient != 'undefined' && controller.detailRecipient.length > 0) {
						if (element.type == 'agent' || element.type == 'module') {
							$content.click(function (e) {
									TreeNodeDetailController.getController().init({
										recipient: controller.detailRecipient,
										type: element.type,
										id: element.id,
										serverID: element.serverID,
										baseURL: controller.baseURL,
										ajaxURL: controller.ajaxURL,
										ajaxPage: controller.ajaxPage
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
												
												data.tree.forEach(function(element, index) {
													element.jqObject = _processNode($group, element);
												}, $group);

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
				if (typeof data.recipient != 'undefined' && data.recipient.length > 0) {
					this.recipient = data.recipient;
				}
				if (typeof data.detailRecipient != 'undefined' && data.detailRecipient.length > 0) {
					this.detailRecipient = data.detailRecipient;
				}
				if (typeof data.tree != 'undefined') {
					this.tree = data.tree;
				}
				if (typeof data.emptyMessage != 'undefined' && data.emptyMessage.length > 0) {
					this.emptyMessage = data.emptyMessage;
				}
				if (typeof data.errorMessage != 'undefined' && data.errorMessage.length > 0) {
					this.errorMessage = data.errorMessage;
				}
				if (typeof data.baseURL != 'undefined' && data.baseURL.length > 0) {
					this.baseURL = data.baseURL;
				}
				if (typeof data.ajaxURL != 'undefined' && data.ajaxURL.length > 0) {
					this.ajaxURL = data.ajaxURL;
				}
				if (typeof data.ajaxPage != 'undefined' && data.ajaxPage.length > 0) {
					this.ajaxPage = data.ajaxPage;
				}
				if (typeof data.filter != 'undefined') {
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

// The controllers will be inside the 'controllers' object,
// ordered by ['type']['id']
TreeNodeDetailController = {
	controllers: {},
	controllerExist: function (type, id) {
		if (typeof this.controllers[type][id] != 'undefined') {
			return true;
		}
		else {
			return false;
		}
	},
	removeControllers: function () {
		try {
			$.each(TreeNodeDetailController.controllers, function(type, elements) {
				$.each(elements, function(id, element) {
					element.remove();
				});
			});
		}
		catch (error) {
			// console.log(error);
		}
	},
	getController: function () {
		var controller = {
			recipient: '',
			type: 'none',
			id: -1,
			serverID: -1,
			emptyMessage: "Empty",
			errorMessage: "Error",
			baseURL: "",
			ajaxURL: "ajax.php",
			ajaxPage: "include/ajax/tree.ajax",
			container: '',
			reload: function () {
				// Label
				var $label = $("<div></div>");
				$label
					.addClass("tree-element-detail-label")
					.click(function (e) {
						if ($label.hasClass('tree-element-detail-loaded'))
							controller.toggle();
					});

				// Content
				var $content = $("<div></div>");
				$content.addClass("tree-element-detail-content");

				// Container
				this.container = $("<div></div>");
				this.container
					.addClass("tree-element-detail")
					.addClass("tree-element-detail-closed")
					.append($label)
					.data('label', $label)
					.append($content)
					.data('content', $content);

				$label.addClass('tree-element-detail-loading');
				$.ajax({
					url: this.ajaxURL,
					type: 'POST',
					dataType: 'html',
					async: true,
					data: {
						page: this.ajaxPage,
						getDetail: 1,
						type: this.type,
						id: this.id,
						serverID: this.serverID
					},
					complete: function(xhr, textStatus) {
						$label.removeClass('tree-element-detail-loading');
					},
					success: function(data, textStatus, xhr) {
						$label.addClass('tree-element-detail-loaded');
						$content.html(data);

						controller.open();
					},
					error: function(xhr, textStatus, errorThrown) {
						$label.addClass('tree-element-detail-error');
						$content.html(controller.errorMessage);
					}
				});
				
				this.recipient.append(this.container);
			},
			load: function () {
				this.reload();
			},
			toggle: function () {
				if (typeof this.container == 'undefined' || this.container.length <= 0) {
					return false;
				}
				if (this.container.hasClass("tree-element-detail-closed")) {
					this.open();
				}
				else {
					this.close();
				}
			},
			open: function () {
				if (typeof this.container == 'undefined' || this.container.length <= 0) {
					return false;
				}
				if (this.container.hasClass("tree-element-detail-closed")) {
					this.container
						.removeClass("tree-element-detail-closed")
						.data('content').show();
				}
			},
			close: function () {
				if (typeof this.container == 'undefined' || this.container.length <= 0) {
					return false;
				}
				if (!this.container.hasClass("tree-element-detail-closed")) {
					this.container
						.addClass("tree-element-detail-closed")
						.data('content').hide();
				}
			},
			init: function (data) {
				// Remove the other controllers
				TreeNodeDetailController.removeControllers();

				// Required
				if (typeof data.recipient != 'undefined' && data.recipient.length > 0) {
					this.recipient = data.recipient;
				}
				else {
					return false;
				}
				// Required
				if (typeof data.type != 'undefined' && data.type.length > 0) {
					this.type = data.type;
				}
				else {
					return false;
				}
				// Required
				if (typeof data.id != 'undefined' && (data.id.length > 0 || !isNaN(data.id))) {
					this.id = data.id;
				}
				else {
					return false;
				}
				if (typeof data.serverID != 'undefined' && (data.serverID.length > 0 || !isNaN(data.serverID))) {
					this.serverID = data.serverID;
				}
				if (typeof data.emptyMessage != 'undefined' && data.emptyMessage.length > 0) {
					this.emptyMessage = data.emptyMessage;
				}
				if (typeof data.errorMessage != 'undefined' && data.errorMessage.length > 0) {
					this.errorMessage = data.errorMessage;
				}
				if (typeof data.baseURL != 'undefined' && data.baseURL.length > 0) {
					this.baseURL = data.baseURL;
				}
				if (typeof data.ajaxURL != 'undefined' && data.ajaxURL.length > 0) {
					this.ajaxURL = data.ajaxURL;
				}
				if (typeof data.ajaxPage != 'undefined' && data.ajaxPage.length > 0) {
					this.ajaxPage = data.ajaxPage;
				}
				if (typeof data.counterTitles != 'undefined') {
					this.counterTitles = data.counterTitles;
				}
				if (typeof data.shouldHaveCounters != 'undefined') {
					this.shouldHaveCounters = data.shouldHaveCounters;
				}

				if (typeof TreeNodeDetailController.controllers[this.type] == 'undefined')
					TreeNodeDetailController.controllers[this.type] = {};
				TreeNodeDetailController.controllers[this.type][this.id] = this;
				this.load();
			},
			remove: function () {
				if (typeof this.recipient != 'undefined' && this.recipient.length > 0) {
					this.recipient.empty();
				}
				if (this.type != 'none' && this.id > -1) {
					try {
						delete TreeNodeDetailController.controllers[this.type][this.id];
					}
					catch (error) {
						// console.log('Item not deleted');
					}
				}
			},
			closeOther: function () {
				try {
					$.each(TreeNodeDetailController.controllers, function(type, elements) {
						$.each(elements, function(id, element) {
							if (controller.type != type && controller.id != id)
								element.close();
						});
					});
				}
				catch (error) {
					// console.log(error);
				}
			},
			removeOther: function () {
				try {
					TreeNodeDetailController.controllers.forEach(function(elements, type) {
						elements.forEach(function(element, id) {
							if (controller.type != type && controller.id != id)
								element.remove();
						});
					});
				}
				catch (error) {
					// console.log(error);
				}
			}
		}
		return controller;
	}
}