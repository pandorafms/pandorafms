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
						rootGroup = false;
						$group
							.addClass("tree-group")
							.hide();
					}

					container.append($group);

					var lastNode;
					var firstNode;
					elements.forEach(function(element, index) {
						lastNode = index == elements.length - 1 ? true : false;
						firstNode = rootGroup && index == 0 ? true : false;
						element.jqObject = _processNode($group, element, lastNode, firstNode);
					}, $group);

					return $group;
				}
				// Load leaf
				function _processNode (container, element, lastNode, firstNode) {
					var $node = $("<li></li>");
					var $leafIcon = $("<div></div>");
					var $content = $("<div></div>");

					// Leaf icon
					$leafIcon.addClass("leaf-icon");

					// Content
					$content.addClass("node-content");
					switch (element.type) {
						case 'group':
							$content.append(element.name);
							break;
						case 'agent':
							$content.append(element.name);
							break;
						default:
							$content.append(element.name);
							break;
					}
					// If exist the detail container, show the data
					if (typeof controller.detailRecipient != 'undefined' && controller.detailRecipient.length > 0) {
						$content.click(function (e) {
							TreeNodeDetailController.getController().init({
								recipient: controller.detailRecipient,
								type: element.type,
								id: element.id,
								baseURL: controller.baseURL,
								ajaxURL: controller.ajaxURL,
								ajaxPage: controller.ajaxPage
							});
						});
					}

					$node
						.addClass("tree-node")
						.append($leafIcon)
						.append($content);

					if (typeof lastNode != 'undefinded' && lastNode == true) {
						$node.addClass("tree-last");
					}
					if (typeof firstNode != 'undefinded' && firstNode == true) {
						$node.addClass("tree-first");
					}

					container.append($node);

					if (typeof element.tree != 'undefined' && element.tree.length > 0) {
						$node.addClass("leaf-closed");

						// Add children
						var $children = _processGroup($node, element.tree);
						$node.data('children', $children);

						$leafIcon.click(function () {
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
					else if (typeof element.searchChildren != 'undefined' && element.searchChildren) {
						$node.addClass("leaf-closed");

						$leafIcon.click(function (e) {
							e.preventDefault();

							if (! $node.hasClass("children-loaded") && ! $node.hasClass("leaf-empty")) {
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
										filter: controller.filter
									},
									complete: function(xhr, textStatus) {
										$node.removeClass("leaf-loading");
										$node.addClass("children-loaded")
									},
									success: function(data, textStatus, xhr) {
										if (data.success) {

											if (typeof data.tree != 'undefined' && data.tree.length > 0) {
												$node.addClass("leaf-open");
												
												var $children = _processGroup($node, data.tree);
												$children.slideDown();

												$node.data('children', $children);
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
					else {
						$node.addClass("leaf-empty");
					}

					return $node;
				}

				if (controller.recipient.length == 0) {
					return;
				}
				else if (controller.tree.length == 0) {
					controller.recipient.html("<div>" + controller.emptyMessage + "</div>");
					return;
				}

				controller.recipient.empty();

				var $children = _processGroup(this.recipient, this.tree, true);
				$children.show();

				controller.recipient.data('children', $children);
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
				if (typeof data.tree != 'undefined' && data.tree.length > 0) {
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
		TreeNodeDetailController.controllers.forEach(function(elements, type) {
			elements.forEach(function(element, id) {
				element.remove();
			});
		});
	},
	getController: function () {
		var controller = {
			recipient: '',
			type: 'none',
			id: -1,
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

				$label.addClass('tree-element-detail-loading');
				$.ajax({
					url: this.ajaxURL,
					type: 'POST',
					dataType: 'json',
					data: {
						page: this.ajaxPage,
						getDetail: 1,
						type: this.type,
						id: this.id
					},
					complete: function(xhr, textStatus) {
						$label.removeClass('tree-element-detail-loading');
					},
					success: function(data, textStatus, xhr) {
						if (data.success) {
							$label.addClass('tree-element-detail-loaded');
							$content.append(data.html);
							controller.open();
						}
						else {
							$label.addClass('tree-element-detail-error');
							$content.html(controller.errorMessage);
						}
					},
					error: function(xhr, textStatus, errorThrown) {
						$label.addClass('tree-element-detail-error');
						$content.html(controller.errorMessage);
					}
				});
				

				// Container
				this.container = $("<div></div>");
				this.container
					.addClass("tree-element-detail")
					.append($label)
					.data('label', $label)
					.append($content)
					.data('content', $content)
					.hide();

				this.recipient.append(this.container);
				this.open();
			},
			load: function () {
				this.reload();
			},
			toggle: function () {
				if (typeof this.container != 'undefined' && this.container.length > 0) {
					return false;
				}
				if (this.container.isClosed) {
					this.open();
				}
				else {
					this.close();
				}
			},
			open: function () {
				if (typeof this.container != 'undefined' && this.container.length > 0) {
					return false;
				}
				if (this.container.isClosed) {
					this.container.data('content').slideLeft();
					this.container.isClosed = false;
				}
			},
			close: function () {
				if (typeof this.container != 'undefined' && this.container.length > 0) {
					return false;
				}
				if (!this.container.isClosed) {
					this.container.data('content').slideRight();
					this.container.isClosed = true;
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
				if (typeof data.id != 'undefined' && data.id.length > 0) {
					this.id = data.id;
				}
				else {
					return false;
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
					delete TreeNodeDetailController.controllers[this.type][this.id];
				}
			},
			closeOther: function () {
				TreeNodeDetailController.controllers.forEach(function(elements, type) {
					elements.forEach(function(element, id) {
						if (this.type != type && this.id != id)
							element.close();
					}, this);
				}, this);
			},
			removeOther: function () {
				TreeNodeDetailController.controllers.forEach(function(elements, type) {
					elements.forEach(function(element, id) {
						if (this.type != type && this.id != id)
							element.remove();
					}, this);
				}, this);
			}
		}
		return controller;
	}
}