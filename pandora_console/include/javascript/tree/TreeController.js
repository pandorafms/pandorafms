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
			ajaxURL: "ajax.php",
			ajaxPage: "include/ajax/tree.ajax.php",
			reload: function () {
				if (typeof this.recipient == 'undefined' || this.recipient.length == 0) {
					return;
				}

				function _processGroup (container, elements) {
					var $group = $("<ul></ul>");
					$group
						.addClass("tree-group")
						.hide();

					container.append($group);

					var last;
					elements.forEach(function(element, index) {
						last = index == elements.length - 1 ? true : false;
						element.jqObject = _processNode($group, element, last);
					}, $group);

					return $group;
				}
				function _processNode (container, element, last) {
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

					if (typeof last != 'undefinded' && last == true) {
						$node.addClass("tree-last");
					}

					container.append($node);

					if (typeof element.children != 'undefined' && element.children.length > 0) {
						$node.addClass("leaf-closed");

						// Add children
						var $children = _processGroup($node, element.children);
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

											var $children = _processGroup($node, data.elements);
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

				var $loadingImage = $('<img src="images/spinner.gif" />');

				var $children = _processGroup(this.recipient, this.tree);
				$children.show();

				this.recipient.data('children', $children);

				// $.ajax({
				// 	url: this.ajaxURL,
				// 	type: 'POST',
				// 	dataType: 'json',
				// 	data: {
				// 		page: this.ajaxPage,
				// 		getChildren: 1,
				// 		type: element.type
				// 	},
				// 	complete: function(xhr, textStatus) {
				// 		$loadingImage.remove();
				// 	},
				// 	success: function(data, textStatus, xhr) {
				// 		if (data.success) {
				// 			var $children = _processGroup(this.recipient, data.elements);
				// 			$children.show();

				// 			this.recipient.data('children', $children);
				// 		}
				// 		else {
				// 			$loadingImage.remove();
				// 			this.recipient.html("<div>" + this.errorMessage + "</div>");
				// 		}
				// 	},
				// 	error: function(xhr, textStatus, errorThrown) {
				// 		this.recipient.html("<div>" + this.errorMessage + "</div>");
				// 	}
				// });
			},
			load: function () {
				this.reload();
			},
			changeTree: function (tree) {
				this.tree = tree;
				this.reload();
			},
			addLeaf: function (leaf) {
				// this.tree.unshift(leaf);
				// this.reload();
			},
			removeLeaf: function (leafID) {
				if (leafID != 0 && this.tree.length > 0) {
					this.tree.splice(leafID, 1);
					this.reload();
				}
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