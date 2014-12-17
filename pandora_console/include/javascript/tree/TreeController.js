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
			ajaxPage: "include/ajax/tree.ajax.php",
			reload: function () {
				if (typeof this.recipient == 'undefined' || this.recipient.length == 0) {
					return;
				}
				
				function _processGroup (container, elements, baseURL, rootGroup) {
					var $group = $("<ul></ul>");
					
					if (typeof rootGroup != 'undefinded' && rootGroup == true) {
						$group
							.addClass("tree-root")
							.hide()
							.prepend('<img src="'+(baseURL.length > 0 ? baseURL + '/' : '')+'images/pandora.ico.gif" />');
					}
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
					
					if (typeof element.children != 'undefined' && element.children.length > 0) {
						$node.addClass("leaf-closed");
						
						// Add children
						var $children = _processGroup($node, element.children, this.baseURL);
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
						
						$leafIcon.click(function () {
							if (! $node.hasClass("children-loaded")) {
								$node
									.removeClass("leaf-closed")
									.removeClass("leaf-error")
									.addClass("leaf-loading");
								
								$.ajax({
									url: this.ajaxURL,
									type: 'POST',
									dataType: 'json',
									data: {
										page: this.ajaxPage,
										getChildren: 1,
										id: element.id,
										type: element.type
									},
									complete: function(xhr, textStatus) {
										$node.removeClass("leaf-loading");
									},
									success: function(data, textStatus, xhr) {
										if (data.success) {
											$node.addClass("leaf-open");
											
											var $children = _processGroup($node, data.elements, this.baseURL);
											$children.slideDown();
											
											$node.data('children', $children);
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
							else {
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
				
				if (this.recipient.length == 0) {
					return;
				}
				else if (this.tree.length == 0) {
					this.recipient.html("<div>" + this.emptyMessage + "</div>");
					return;
				}
				
				this.recipient.empty();
				var $children = _processGroup(this.recipient, this.tree, this.baseURL, true);
				$children.show();
				
				this.recipient.data('children', $children);
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
				
				this.index = TreeController.controllers.push(this) - 1;
				this.load();
			},
			remove: function () {
				if (typeof this.recipient == 'undefined' || this.recipient.length > 0) {
					return;
				}
				
				this.recipient.empty();
				if (this.index > -1) {
					TreeController.controllers.splice(this.index, 1);
				}
			}
		}
		return controller;
	}
}