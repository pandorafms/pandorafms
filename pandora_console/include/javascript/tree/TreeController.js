// Pandora FMS - http://pandorafms.com
// ==================================================
// Copyright (c) 2005-2021 Artica Soluciones Tecnologicas
// Please see http://pandorafms.org for full contribution list

// This program is free software; you can redistribute it and/or
// modify it under the terms of the  GNU Lesser General Public License
// as published by the Free Software Foundation; version 2

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.

/*global $, _*/

var TreeController = {
  controllers: [],
  getController: function() {
    var controller = {
      index: -1,
      recipient: "",
      tree: [],
      emptyMessage: "No data found.",
      foundMessage: "Groups found",
      errorMessage: "Error",
      baseURL: "",
      ajaxURL: "ajax.php",
      ajaxPage: "include/ajax/tree.ajax",
      detailRecipient: "",
      filter: {},
      counterTitles: {},
      shouldHaveCounters: true,
      reload: function() {
        // Bad recipient
        if (
          typeof this.recipient == "undefined" ||
          this.recipient.length == 0
        ) {
          return;
        }

        function _recursiveGroupsCount(elements, childGroupsLength) {
          if (typeof childGroupsLength === "undefined") {
            childGroupsLength = 0;
          }

          _.each(elements, function(element) {
            if (typeof element.children !== "undefined") {
              childGroupsLength = _recursiveGroupsCount(
                element.children,
                childGroupsLength
              );
              childGroupsLength += element.children.length;
            }
          });
          return childGroupsLength;
        }

        // Load branch
        function _processGroup(container, elements, rootGroup) {
          var $group = $("<ul></ul>");
          var childGroupsLength = _recursiveGroupsCount(elements);

          // First group.
          if (typeof rootGroup != "undefined" && rootGroup == true) {
            var messageLength = controller.tree.length;
            if (childGroupsLength > 0) {
              messageLength = childGroupsLength + controller.tree.length;
            }

            group_message = controller.foundMessage + ": " + messageLength;
            if (controller.foundMessage == "") {
              group_message = "";
            }
            $group
              .addClass("tree-root")
              .hide()
              .prepend(
                '<div class="tree-node tree-node-header">' +
                  '<img src="' +
                  (controller.baseURL.length > 0 ? controller.baseURL : "") +
                  'images/pandora.png" />' +
                  "<span class='margin-left-1'>" +
                  (controller.tree.length > 0 ? group_message : "") +
                  "</div>"
              );
          } else {
            // Normal group.
            $group.addClass("tree-group").hide();
          }

          container.append($group);

          _.each(elements, function(element) {
            element.jqObject = _processNode($group, element);
          });

          return $group;
        }

        // Load leaf counters
        function _processNodeCounters(container, counters, type) {
          var hasCounters = false;

          if (typeof counters != "undefined") {
            function _processNodeCounterTitle(
              container,
              elementType,
              counterType
            ) {
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
              };

              var serviceCounterTitles = {
                total_services: {
                  totals: "Services"
                },
                total_agents: {
                  totals: "Agents"
                },
                total_modules: {
                  totals: "Modules"
                }
              };

              var IPAMSupernetCounterTitles = {
                total_networks: {
                  totals: "Networks"
                }
              };

              var IPAMNetworkCounterTitles = {
                alive_ips: {
                  totals: "Alive IPs"
                },
                total_ips: {
                  totals: "Total IPs"
                }
              };

              try {
                var title = "";

                switch (elementType) {
                  case "group":
                    if (
                      typeof controller.counterTitles != "undefined" &&
                      typeof controller.counterTitles[counterType] !=
                        "undefined" &&
                      typeof controller.counterTitles[counterType].agents !=
                        "undefined"
                    ) {
                      title = controller.counterTitles[counterType].agents;
                    } else {
                      title = defaultCounterTitles[counterType].agents;
                    }
                    break;
                  case "agent":
                    if (
                      typeof controller.counterTitles != "undefined" &&
                      typeof controller.counterTitles[counterType] !=
                        "undefined" &&
                      typeof controller.counterTitles[counterType].modules !=
                        "undefined"
                    ) {
                      title = controller.counterTitles[counterType].modules;
                    } else {
                      title = defaultCounterTitles[counterType].modules;
                    }
                    break;
                  case "services":
                    title = serviceCounterTitles[counterType].totals;
                    break;
                  case "IPAM_supernets":
                    title = IPAMSupernetCounterTitles[counterType].totals;
                    break;
                  case "IPAM_networks":
                    title = IPAMNetworkCounterTitles[counterType].totals;
                    break;
                  default:
                    if (
                      typeof controller.counterTitles != "undefined" &&
                      typeof controller.counterTitles[counterType] !=
                        "undefined" &&
                      typeof controller.counterTitles[counterType].none !=
                        "undefined"
                    ) {
                      title = controller.counterTitles[counterType].none;
                    } else {
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
              } catch (error) {
                // console.log(error);
              }
            }

            if (type == "services") {
              var $counters = $("<span></span>");
              $counters
                .addClass("tree-node-counters")
                .addClass("tree-node-service-counters");

              if (
                counters.total_services +
                  counters.total_agents +
                  counters.total_modules >
                0
              ) {
                // Open the parentheses
                $counters.append(" (");

                if (
                  typeof counters.total_services != "undefined" &&
                  counters.total_services >= 0
                ) {
                  var $servicesCounter = $("<div></div>");
                  $servicesCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html(counters.total_services);

                  _processNodeCounterTitle(
                    $servicesCounter,
                    type,
                    "total_services"
                  );

                  $counters.append($servicesCounter);
                } else {
                  var $servicesCounter = $("<div></div>");
                  $servicesCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html("0");

                  _processNodeCounterTitle(
                    $servicesCounter,
                    type,
                    "total_services"
                  );

                  $counters.append($servicesCounter);
                }

                if (
                  typeof counters.total_agents != "undefined" &&
                  counters.total_agents > 0
                ) {
                  var $agentsCounter = $("<div></div>");
                  $agentsCounter
                    .addClass("tree-node-counter")
                    .html(counters.total_agents);

                  _processNodeCounterTitle(
                    $agentsCounter,
                    type,
                    "total_agents"
                  );

                  $counters.append(" : ").append($agentsCounter);
                } else {
                  var $agentsCounter = $("<div></div>");
                  $agentsCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html("0");

                  _processNodeCounterTitle(
                    $agentsCounter,
                    type,
                    "total_agents"
                  );

                  $counters.append(" : ").append($agentsCounter);
                }

                if (
                  typeof counters.total_modules != "undefined" &&
                  counters.total_modules > 0
                ) {
                  var $modulesCounter = $("<div></div>");
                  $modulesCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html(counters.total_modules);

                  _processNodeCounterTitle(
                    $modulesCounter,
                    type,
                    "total_modules"
                  );

                  $counters.append(" : ").append($modulesCounter);
                } else {
                  var $modulesCounter = $("<div></div>");
                  $modulesCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html("0");

                  _processNodeCounterTitle(
                    $modulesCounter,
                    type,
                    "total_modules"
                  );

                  $counters.append(" : ").append($modulesCounter);
                }

                // Close the parentheses
                $counters.append(")");

                hasCounters = true;
              }
            } else if (type == "IPAM_supernets") {
              var $counters = $("<div></div>");
              $counters.addClass("tree-node-counters");

              if (counters.total_networks > 0) {
                // Open the parentheses
                $counters.append(" (");

                if (
                  typeof counters.total_networks !== "undefined" &&
                  counters.total_networks >= 0
                ) {
                  var $networksCounter = $("<div></div>");
                  $networksCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html(counters.total_networks);

                  _processNodeCounterTitle(
                    $networksCounter,
                    type,
                    "total_networks"
                  );

                  $counters.append($networksCounter);
                } else {
                  var $networksCounter = $("<div></div>");
                  $networksCounter
                    .addClass("tree-node-counter")
                    .addClass("total")
                    .html("0");

                  _processNodeCounterTitle(
                    $networksCounter,
                    type,
                    "total_networks"
                  );

                  $counters.append($networksCounter);
                }

                // Close the parentheses
                $counters.append(")");

                hasCounters = true;
              }
            } else if (type == "IPAM_networks") {
              var $counters = $("<div></div>");
              $counters.addClass("tree-node-counters");

              // Open the parentheses
              $counters.append(" (");

              if (
                typeof counters.alive_ips !== "undefined" &&
                counters.alive_ips >= 0
              ) {
                var $aliveCounter = $("<div></div>");
                $aliveCounter
                  .addClass("tree-node-counter")
                  .addClass("total")
                  .html(counters.alive_ips);

                _processNodeCounterTitle($aliveCounter, type, "alive_ips");

                $counters.append($aliveCounter);
              } else {
                var $aliveCounter = $("<div></div>");
                $aliveCounter
                  .addClass("tree-node-counter")
                  .addClass("total")
                  .html("0");

                _processNodeCounterTitle($aliveCounter, type, "alive_ips");

                $counters.append($aliveCounter);
              }

              if (
                typeof counters.total_ips !== "undefined" &&
                counters.total_ips >= 0
              ) {
                var $totalCounter = $("<div></div>");
                $totalCounter
                  .addClass("tree-node-counter")
                  .addClass("total")
                  .html(counters.total_ips);

                _processNodeCounterTitle($totalCounter, type, "total_ips");

                $counters.append(" : ").append($totalCounter);
              } else {
                var $totalCounter = $("<div></div>");
                $totalCounter
                  .addClass("tree-node-counter")
                  .addClass("total")
                  .html("0");

                _processNodeCounterTitle($totalCounter, type, "total_ips");

                $counters.append(" : ").append($totalCounter);
              }

              // Close the parentheses
              $counters.append(")");

              hasCounters = true;
            } else {
              var $counters = $("<div></div>");
              $counters.addClass("tree-node-counters");

              if (typeof counters.total != "undefined" && counters.total >= 0) {
                var $totalCounter = $("<div></div>");
                $totalCounter
                  .addClass("tree-node-counter")
                  .addClass("total")
                  .html(counters.total);

                _processNodeCounterTitle($totalCounter, type, "total");

                // Open the parentheses
                $counters.append("&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; [ ");

                $counters.append($totalCounter);

                if (
                  typeof counters.alerts != "undefined" &&
                  counters.alerts > 0
                ) {
                  var $firedCounter = $("<div></div>");
                  $firedCounter
                    .addClass("tree-node-counter")
                    .addClass("alerts")
                    .addClass("orange")
                    .html(counters.alerts);

                  _processNodeCounterTitle($firedCounter, type, "alerts");

                  $counters.append(" : ").append($firedCounter);
                }
                if (
                  typeof counters.critical != "undefined" &&
                  counters.critical > 0
                ) {
                  var $criticalCounter = $("<div></div>");
                  $criticalCounter
                    .addClass("tree-node-counter")
                    .addClass("critical")
                    .addClass("red")
                    .html(counters.critical);

                  _processNodeCounterTitle($criticalCounter, type, "critical");

                  $counters.append(" : ").append($criticalCounter);
                }
                if (
                  typeof counters.warning != "undefined" &&
                  counters.warning > 0
                ) {
                  var $warningCounter = $("<div></div>");
                  $warningCounter
                    .addClass("tree-node-counter")
                    .addClass("warning")
                    .addClass("yellow")
                    .html(counters.warning);

                  _processNodeCounterTitle($warningCounter, type, "warning");

                  $counters.append(" : ").append($warningCounter);
                }
                if (
                  typeof counters.unknown != "undefined" &&
                  counters.unknown > 0
                ) {
                  var $unknownCounter = $("<div></div>");
                  $unknownCounter
                    .addClass("tree-node-counter")
                    .addClass("unknown")
                    .addClass("grey")
                    .html(counters.unknown);

                  _processNodeCounterTitle($unknownCounter, type, "unknown");

                  $counters.append(" : ").append($unknownCounter);
                }
                if (
                  typeof counters.not_init != "undefined" &&
                  counters.not_init > 0
                ) {
                  var $notInitCounter = $("<div></div>");
                  $notInitCounter
                    .addClass("tree-node-counter")
                    .addClass("not_init")
                    .addClass("blue")
                    .html(counters.not_init);

                  _processNodeCounterTitle($notInitCounter, type, "not_init");

                  $counters.append(" : ").append($notInitCounter);
                }
                if (typeof counters.ok != "undefined" && counters.ok > 0) {
                  var $okCounter = $("<div></div>");
                  $okCounter
                    .addClass("tree-node-counter")
                    .addClass("ok")
                    .addClass("green")
                    .html(counters.ok);

                  _processNodeCounterTitle($okCounter, type, "ok");

                  $counters.append(" : ").append($okCounter);
                }
              }

              // Close the parentheses
              $counters.append(" ]");

              hasCounters = true;
            }

            // Add the counters html to the container
            container.append($counters);
          }

          return hasCounters;
        }

        // Load leaf
        function _processNode(container, element) {
          // type, [id], [serverID], callback
          function _getTreeDetailData(type, id, serverID, callback) {
            var lastParam = arguments[arguments.length - 1];
            var callback;
            if (typeof lastParam === "function") callback = lastParam;

            var serverID;
            if (arguments.length >= 4) serverID = arguments[2];
            var id;
            if (arguments.length >= 3) id = arguments[1];
            var type;
            if (arguments.length >= 2) type = arguments[0];

            if (typeof type === "undefined")
              throw new TypeError("Type required");
            if (typeof callback === "undefined")
              throw new TypeError("Callback required");

            var postData = {
              page: controller.ajaxPage,
              getDetail: 1,
              type: type,
              auth_class: controller.auth_class,
              id_user: controller.id_user,
              auth_hash: controller.auth_hash
            };

            if (typeof id !== "undefined") postData.id = id;
            if (typeof serverID !== "undefined") postData.serverID = serverID;

            $.ajax({
              url: controller.ajaxURL,
              type: "POST",
              dataType: "html",
              data: postData,
              success: function(data, textStatus, xhr) {
                callback(null, data);
                $("#fixed-bottom-box-head-title").html(
                  $("#fixedBottomHeadTitle").html()
                );
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
          var disabled = false;
          if (element.disabled == true) {
            disabled = true;
            $content.addClass("disabled");
          }
          switch (element.type) {
            case "group":
              if (
                typeof element.icon != "undefined" &&
                element.icon.length > 0
              ) {
                var extension = element.icon.split(".").pop();
                $content.append(
                  '<div class="node-icon"><div class="node-icon-container"><img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    (controller.baseURL.indexOf("meta") !== -1
                      ? "../../images/"
                      : "images/") +
                    (extension === "png" || extension === element.icon
                      ? "groups_small/"
                      : "") +
                    (extension === element.icon
                      ? element.icon + ".png"
                      : element.icon) +
                    '" class="invert_filter"/></div></div>'
                );
              } else if (
                typeof element.iconHTML != "undefined" &&
                element.iconHTML.length > 0
              ) {
                $content.append(element.iconHTML + " ");
              }
              $content.append(
                '<div class="module-name module-name-parent">' +
                  element.name +
                  "</div>"
              );

              if (typeof element.delete != "undefined") {
                var url_delete =
                  controller.baseURL +
                  "index.php?sec=gagente&sec2=godmode/groups/group_list&tab=tree&delete_group=1&id_group=" +
                  element.id;
                var $deleteBtn = $(
                  '<a style="float: right; margin-top: 5px;"><img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    (controller.meta != undefined && controller.meta == 1
                      ? "../../images/"
                      : "images/") +
                    'delete.svg" class="main_menu_icon invert_filter" style="width:18px; padding: 0 5px;"/></a>'
                );
                $deleteBtn.click(function(event) {
                  var ok_function = function() {
                    window.location.replace(url_delete);
                  };
                  display_confirm_dialog(
                    element.delete.messages.messg,
                    element.delete.messages.confirm,
                    element.delete.messages.cancel,
                    ok_function
                  );
                });
                $content.append($deleteBtn);
              }

              if (typeof element.edit != "undefined") {
                var url_edit =
                  controller.baseURL +
                  "index.php?sec=gagente&sec2=godmode/groups/configure_group&tab=tree&id_group=" +
                  element.id;
                var $updateicon = $(
                  '<img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    (controller.meta != undefined && controller.meta == 1
                      ? "../../images/"
                      : "images/") +
                    'edit.svg" class="main_menu_icon invert_filter" style="width:18px; padding: 0 5px;"/>'
                );
                var $updatebtn = $(
                  '<a style="float: right; margin-top: 5px;" href = "' +
                    url_edit +
                    '"></a>'
                ).append($updateicon);
                $content.append($updatebtn);
              }

              if (typeof element.alerts != "undefined") {
                $content.append(element.alerts);
              }

              break;
            case "agent":
              // Is quiet

              if (
                typeof element.quietImageHTML != "undefined" &&
                element.quietImageHTML.length > 0
              ) {
                var $quietImage = $(element.quietImageHTML);
                $quietImage.addClass("agent-quiet");

                $content.append($quietImage);
              }
              // Status image
              if (
                typeof element.statusImageHTML != "undefined" &&
                element.statusImageHTML.length > 0
              ) {
                var $statusImage = $(element.statusImageHTML);
                $statusImage.addClass("node-icon");
                $statusImage.addClass("node-status");

                $content.append($statusImage);
              }

              // Events by agent
              if (element.showEventsBtn == 1) {
                if (typeof element.eventAgent != "undefined") {
                  $content.append(
                    '<form method="post" id="hiddenAgentsEventsForm-' +
                      element.eventAgent +
                      '" style="display: none;" action="index.php?sec=eventos&sec2=operation/events/events&refr=0&pure=&section=list&history=0"><input type="hidden" name="id_agent" value="' +
                      element.eventAgent +
                      '"></form>'
                  );
                  var $eventImage = $(
                    '<img src="' +
                      (controller.baseURL.length > 0
                        ? controller.baseURL
                        : "") +
                      'images/event.svg" /> '
                  );
                  $eventImage.addClass("agent-alerts-fired invert_filter");
                  $eventImage
                    .click(function(e) {
                      e.preventDefault();

                      document
                        .getElementById(
                          "hiddenAgentsEventsForm-" + element.eventAgent
                        )
                        .submit();
                    })
                    .css("cursor", "pointer");

                  $content.append($eventImage);
                }
              }

              $content.append(
                '<span class="module-name module-name-alias">' +
                  element.alias +
                  "</span>"
              );
              break;
            case "IPAM_supernets":
              var IPAMSupernetDetailImage = $(
                '<img class="invert_filter" src="' +
                  (controller.baseURL.length > 0 ? controller.baseURL : "") +
                  'images/server-transactions@svg.svg" /> '
              );

              if (typeof element.id !== "undefined") {
                IPAMSupernetDetailImage.click(function(e) {
                  e.preventDefault();

                  var postData = {
                    page: "enterprise/include/ajax/ipam.ajax",
                    show_networkmap_statistics: 1,
                    "node_data[id_net]": element.id,
                    "node_data[type_net]": "supernet"
                  };

                  $.ajax({
                    url: controller.ajaxURL,
                    type: "POST",
                    dataType: "html",
                    data: postData,
                    success: function(data, textStatus, xhr) {
                      controller.detailRecipient
                        .render("IPAMsupernets", data)
                        .open();
                    }
                  });
                }).css("cursor", "pointer");

                $content.append(IPAMSupernetDetailImage);
              }

              if (element.name !== null) {
                $content.append("&nbsp;&nbsp;&nbsp;" + element.name);
              }

              break;
            case "IPAM_networks":
              $content.addClass("ipam-network");

              var IPAMNetworkDetailImage = $(
                '<img class="invert_filter" src="' +
                  (controller.baseURL.length > 0 ? controller.baseURL : "") +
                  'images/logs@svg.svg" /> '
              );

              if (typeof element.id !== "undefined") {
                IPAMNetworkDetailImage.click(function(e) {
                  e.preventDefault();

                  //window.location.href = element.IPAMNetworkDetail;
                  var postData = {
                    page: "enterprise/include/ajax/ipam.ajax",
                    show_networkmap_statistics: 1,
                    "node_data[id_net]": element.id,
                    "node_data[type_net]": "network"
                  };

                  $.ajax({
                    url: controller.ajaxURL,
                    type: "POST",
                    dataType: "html",
                    data: postData,
                    success: function(data, textStatus, xhr) {
                      controller.detailRecipient
                        .render("IPAMnetwork", data)
                        .open();
                    }
                  });
                }).css("cursor", "pointer");

                $content.append(IPAMNetworkDetailImage);
              }

              if (element.name !== null) {
                $content.append("&nbsp;&nbsp;&nbsp;" + element.name);
              }

              break;
            case "services":
              $content.addClass("node-service");
              if (
                typeof element.statusImageHTML != "undefined" &&
                element.statusImageHTML.length > 0
              ) {
                var $statusImage = $(element.statusImageHTML);
                $statusImage.addClass("agent-status");

                $content.append($statusImage);
              }
              var image_tooltip =
                '<span class="reinvert_filter"><img class="invert_filter forced_title" data-title="' +
                (element.title ? element.title : element.name) +
                '" data-use_title_for_force_title="1" src="' +
                (controller.baseURL.length > 0 ? controller.baseURL : "") +
                'images/info@svg.svg" style="width: 16px" class="img_help" ' +
                ' alt="' +
                element.name +
                '"/></span> ';

              var $serviceDetailImage = $(
                '<img class="invert_filter" src="' +
                  (controller.baseURL.length > 0 ? controller.baseURL : "") +
                  'images/snmp-trap@svg.svg" /> '
              );

              if (
                typeof element.serviceDetail != "undefined" &&
                element.name != null
              ) {
                $serviceDetailImage
                  .click(function(e) {
                    e.preventDefault();

                    window.location.href = element.serviceDetail;
                  })
                  .css("cursor", "pointer");
                $content.append($serviceDetailImage);
                $content.append(" " + image_tooltip);

                if (
                  typeof element.elementDescription !== "undefined" &&
                  element.elementDescription != "" &&
                  element.elementDescription != null
                ) {
                  $content.append(
                    '<span class="node-service-name" style="">' +
                      element.elementDescription +
                      "</span>"
                  );
                } else if (
                  typeof element.description !== "undefined" &&
                  element.description != ""
                ) {
                  $content.append(
                    '<span class="node-service-name" style="flex: 1 1 50%;">' +
                      element.description +
                      "</span>"
                  );
                } else {
                  $content.append(
                    '<span class="node-service-name" style="flex: 1 1 50%;">' +
                      element.name +
                      "</span>"
                  );
                }
              } else {
                $content.remove($node);
              }

              break;
            case "modules":
              if (
                typeof element.statusImageHTML != "undefined" &&
                element.statusImageHTML.length > 0
              ) {
                var $statusImage = $(element.statusImageHTML);
                $statusImage.addClass("agent-status");

                $content.append($statusImage);
              }

              // Events by module
              if (element.showEventsBtn == 1) {
                if (typeof element.eventModule != "undefined") {
                  $content.append(
                    '<form method="post" id="hiddenModulesEventsForm-' +
                      element.eventModule +
                      '" style="display: none;" action="index.php?sec=eventos&sec2=operation/events/events&refr=0&pure=&section=list&history=0"><input type="hidden" name="module_search_hidden" value="' +
                      element.eventModule +
                      '"></form>'
                  );
                  var $moduleImage = $(
                    '<img src="' +
                      (controller.baseURL.length > 0
                        ? controller.baseURL
                        : "") +
                      'images/event.svg" /> '
                  );
                  $moduleImage
                    .click(function(e) {
                      e.preventDefault();

                      document
                        .getElementById(
                          "hiddenModulesEventsForm-" + element.eventModule
                        )
                        .submit();
                    })
                    .css("cursor", "pointer");

                  $content.append($moduleImage);
                }
              }

              $content.append('<span class="">' + element.name + "</span>");
              break;
            case "module":
              $content.addClass("module");

              // Status image
              if (
                typeof element.statusImageHTML != "undefined" &&
                element.statusImageHTML.length > 0
              ) {
                var $statusImage = $(element.statusImageHTML);
                $statusImage.addClass("node-icon").addClass("node-status");

                $content.append($statusImage);
              } else {
                $content.addClass("module-only-caption");
              }

              element.name = htmlDecode(element.name);
              // Name max 42 chars.
              $content.append(
                '<span title="' +
                  element.name +
                  '" class="module-name">' +
                  element.name.substring(0, 42) +
                  (element.name.length > 42 ? "..." : "") +
                  "</span>"
              );

              // Avoiding 'undefined' text.
              if (typeof element.value === "undefined") {
                element.value = "";
              }

              // Value.
              $content.append(
                '<span class="module-value">' + element.value + "</span>"
              );

              var actionButtons = $("<div></div>");
              actionButtons.addClass("module-action-buttons");

              if (
                typeof element.showGraphs != "undefined" &&
                element.showGraphs != 0
              ) {
                // Graph histogram pop-up
                if (typeof element.histogramGraph != "undefined") {
                  var graphImageHistogram = $(
                    '<img src="' +
                      (controller.baseURL.length > 0
                        ? controller.baseURL
                        : "") +
                      'images/event-history.svg" /> '
                  );

                  graphImageHistogram
                    .addClass("module-graph")
                    .addClass("module-button")
                    .click(function(e) {
                      e.stopPropagation();
                      try {
                        winopeng_var(
                          element.histogramGraph.url,
                          element.histogramGraph.handle,
                          800,
                          480
                        );
                      } catch (error) {
                        // console.log(error);
                      }
                    });

                  actionButtons.append(graphImageHistogram);
                }

                // Graph pop-up
                if (typeof element.moduleGraph != "undefined") {
                  if (element.statusImageHTML.indexOf("data:image") != -1) {
                    var $graphImage = $(
                      '<img src="' +
                        (controller.baseURL.length > 0
                          ? controller.baseURL
                          : "") +
                        'images/item-icon.svg" /> '
                    );
                  } else {
                    var $graphImage = $(
                      '<img src="' +
                        (controller.baseURL.length > 0
                          ? controller.baseURL
                          : "") +
                        (controller.baseURL.indexOf("meta") !== -1
                          ? "../../images/"
                          : "images/") +
                        'module-graph.svg" /> '
                    );
                  }

                  $graphImage
                    .addClass("module-graph")
                    .addClass("module-button")
                    .click(function(e) {
                      e.stopPropagation();
                      if (element.statusImageHTML.indexOf("data:image") != -1) {
                        try {
                          winopeng_var(
                            decodeURI(element.snapshot[0]),
                            element.snapshot[1],
                            element.snapshot[2],
                            element.snapshot[3]
                          );
                        } catch (error) {
                          // console.log(error);
                        }
                      } else {
                        try {
                          winopeng_var(
                            element.moduleGraph.url,
                            element.moduleGraph.handle,
                            800,
                            480
                          );
                        } catch (error) {
                          // console.log(error);
                        }
                      }
                    });

                  actionButtons.append($graphImage);
                }

                // Data pop-up
                if (typeof element.id != "undefined" && !isNaN(element.id)) {
                  if (isNaN(element.metaID)) {
                    var $dataImage = $(
                      '<img src="' +
                        (controller.baseURL.length > 0
                          ? controller.baseURL
                          : "") +
                        (controller.baseURL.indexOf("meta") !== -1
                          ? "../../images/"
                          : "images/") +
                        'simple-value.svg" /> '
                    );
                    $dataImage
                      .addClass("module-data")
                      .addClass("module-button")
                      .click(function(e) {
                        e.stopPropagation();

                        try {
                          var serverName =
                            element.serverName.length > 0
                              ? element.serverName
                              : "";
                          if ($("#module_details_window").length > 0)
                            show_module_detail_dialog(
                              element.id,
                              "",
                              serverName,
                              0,
                              86400,
                              element.name.replace(/&#x20;/g, " ")
                            );
                        } catch (error) {
                          // console.log(error);
                        }
                      });

                    actionButtons.append($dataImage);
                  }
                }
              }

              // Alerts
              if (
                typeof element.alertsImageHTML != "undefined" &&
                element.alertsImageHTML.length > 0
              ) {
                var $alertsImage = $(element.alertsImageHTML);

                $alertsImage
                  .addClass("module-alerts")
                  .click(function(e) {
                    _getTreeDetailData(
                      "alert",
                      element.id,
                      element.serverID,
                      function(error, data) {
                        if (error) {
                          // console.error(error);
                        } else {
                          controller.detailRecipient
                            .render(element.name, data)
                            .open();
                        }
                      }
                    );

                    // Avoid the execution of the module detail event
                    e.stopPropagation();
                  })
                  .css("cursor", "pointer");

                actionButtons.append($alertsImage);
              }

              $content.append(actionButtons);

              break;
            case "os":
              if (
                typeof element.icon != "undefined" &&
                element.icon.length > 0
              ) {
                $content.append(
                  '<div class="node-icon"><div class="node-icon-container"><img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    "images/" +
                    element.icon +
                    '" /></div>'
                );
              }
              $content.append(
                '<span class="module-name module-name-parent">' +
                  element.name +
                  "</span>"
              );
              break;
            case "tag":
              if (
                typeof element.icon != "undefined" &&
                element.icon.length > 0
              ) {
                $content.append(
                  '<img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    "images/" +
                    element.icon +
                    '" /> '
                );
              } else {
                $content.append(
                  '<img src="' +
                    (controller.baseURL.length > 0 ? controller.baseURL : "") +
                    'images/tag_red.png" /> '
                );
              }
              $content.append(
                '<span class="module-name module-name-parent">' +
                  element.name +
                  "</span>"
              );
              break;
            case "services":
              // Status image
              if (
                typeof element.statusImageHTML != "undefined" &&
                element.statusImageHTML.length > 0
              ) {
                var $statusImage = $(element.statusImageHTML);
                $statusImage.addClass("agent-status");

                $content.append($statusImage);
              }
              $content.append(
                '<span class="module-name module-name-parent">' +
                  element.name +
                  "</span>"
              );
              break;
            default:
              $content.append(
                '<span class="module-name module-name-parent module-only-caption">' +
                  element.name +
                  "</span>"
              );
              break;
          }

          // Load the status counters
          var hasCounters = _processNodeCounters(
            $content,
            element.counters,
            element.type
          );
          //Don't show empty groups
          if (element.type == "agent") {
            if (!hasCounters) {
              return;
            }
          }
          // If detail container exists, show the data.
          if (
            typeof controller.detailRecipient !== "undefined" ||
            disabled == false
          ) {
            if (element.type == "agent" || element.type == "module") {
              if (typeof element.noAcl === "undefined") {
                $content
                  .click(function(e) {
                    _getTreeDetailData(
                      element.type,
                      element.id,
                      element.serverID,
                      function(error, data) {
                        if (error) {
                          // console.error(error);
                        } else {
                          controller.detailRecipient
                            .render(element.name, data)
                            .open();
                        }
                      }
                    );
                  })
                  .css("cursor", "pointer");
              }
            }
          }

          $node
            .addClass("tree-node")
            .append($leafIcon)
            .append($content);

          container.append($node);

          $node.addClass("leaf-empty");

          if (
            (typeof element.children != "undefined" &&
              element.children.length > 0) ||
            element.disabled == false
          ) {
            // Add children
            var $children = _processGroup($node, element.children);
            $node.data("children", $children);
            /*
            if (
              typeof element.searchChildren == "undefined" ||
              !element.searchChildren
            ) {
              $leafIcon.click(function(e) {
                console.log(e);
                e.preventDefault();
                return;
                if ($node.hasClass("leaf-open")) {
                  $node
                    .removeClass("leaf-open")
                    .addClass("leaf-closed")
                    .data("children")
                    .slideUp();
                } else {
                  $node
                    .removeClass("leaf-closed")
                    .addClass("leaf-open")
                    .data("children")
                    .slideDown();
                }
              });
            }
            */
          }

          if (
            typeof element.searchChildren != "undefined" &&
            element.searchChildren
          ) {
            if (
              element.rootType == "group_edition" &&
              typeof element.children == "undefined"
            ) {
              $node.addClass("leaf-empty");
            } else {
              $node.removeClass("leaf-empty").addClass("leaf-closed");
              $leafIcon.click(function(e) {
                e.preventDefault();

                if (
                  !$node.hasClass("leaf-loading") &&
                  !$node.hasClass("children-loaded") &&
                  !$node.hasClass("leaf-empty")
                ) {
                  $node
                    .removeClass("leaf-closed")
                    .removeClass("leaf-error")
                    .addClass("leaf-loading");
                  $.ajax({
                    url: controller.ajaxURL,
                    type: "POST",
                    dataType: "json",
                    data: {
                      page: controller.ajaxPage,
                      getChildren: 1,
                      id: element.id,
                      type: element.type,
                      rootID: element.rootID,
                      serverID: element.serverID,
                      rootType: element.rootType,
                      metaID: element.metaID,
                      title: element.title,
                      filter: controller.filter,
                      auth_class: controller.auth_class,
                      id_user: controller.id_user,
                      auth_hash: controller.auth_hash
                    },
                    complete: function(xhr, textStatus) {
                      $node.removeClass("leaf-loading");
                      $node.addClass("children-loaded");
                    },
                    success: function(data, textStatus, xhr) {
                      if (data.success) {
                        var $group = $node.children("ul.tree-group");
                        if (
                          (typeof data.tree != "undefined" &&
                            data.tree.length > 0) ||
                          $group.length > 0
                        ) {
                          if (controller.filter.statusModule === "fired") {
                            var newData = { success: data.success, tree: [] };

                            data.tree.forEach(element => {
                              // Agents.
                              if (
                                typeof element.counters !== "undefined" &&
                                element.counters.alerts > 0
                              ) {
                                var treeTmp = element;

                                treeTmp.counters.critical = 0;
                                treeTmp.counters.not_init = 0;
                                treeTmp.counters.ok = 0;
                                treeTmp.counters.unknown = 0;
                                treeTmp.counters.warning = 0;
                                treeTmp.counters.total =
                                  element.counters.alerts;

                                treeTmp.critical_count = 0;
                                treeTmp.normal_count = 0;
                                treeTmp.notinit_count = 0;
                                treeTmp.unknown_count = 0;
                                treeTmp.warning_count = 0;
                                treeTmp.total_count = element.fired_count;

                                treeTmp.state_critical = 0;
                                treeTmp.state_normal = 0;
                                treeTmp.state_notinit = 0;
                                treeTmp.state_unknown = 0;
                                treeTmp.state_warning = 0;
                                treeTmp.state_total = element.fired_count;

                                newData.tree.push(treeTmp);
                                data = newData;
                              }

                              // Modules.
                              if (element.alerts > 0) {
                                var treeTmp = element;

                                newData.tree.push(treeTmp);
                                data = newData;
                              }
                            });
                          }

                          $node.addClass("leaf-open");

                          if ($group.length <= 0) {
                            $group = $("<ul></ul>");
                            $group.addClass("tree-group").hide();
                            $node.append($group);
                          }

                          // Get the main values of the tree.
                          var rawTree = Object.values(data.tree);
                          // Sorting tree by description (services.treeview_services.php).
                          rawTree.sort(function(a, b) {
                            // Only the services are ordered since only they have the elementDescription property.
                            if (a.elementDescription && b.elementDescription) {
                              var x = a.elementDescription.toLowerCase();
                              var y = b.elementDescription.toLowerCase();
                              if (x < y) {
                                return -1;
                              }
                              if (x > y) {
                                return 1;
                              }
                            }
                            return 0;
                          });

                          _.each(rawTree, function(element) {
                            element.jqObject = _processNode($group, element);
                          });

                          $group.slideDown();

                          $node.data("children", $group);

                          // Add again the hover event to the 'force_callback' elements
                          forced_title_callback();
                        } else {
                          $node.addClass("leaf-empty");
                        }
                      } else {
                        $node.addClass("leaf-error");
                      }
                    },
                    error: function(xhr, textStatus, errorThrown) {
                      $node.addClass("leaf-error");
                    }
                  });
                } else if (!$node.hasClass("leaf-empty")) {
                  if ($node.hasClass("leaf-open")) {
                    $node
                      .removeClass("leaf-open")
                      .addClass("leaf-closed")
                      .data("children")
                      .slideUp();
                  } else {
                    $node
                      .removeClass("leaf-closed")
                      .addClass("leaf-open")
                      .data("children")
                      .slideDown();
                  }
                }
              });
            }
          }

          if (typeof treeViewControlModuleValues === "function") {
            treeViewControlModuleValues();
          }

          return $node;
        }

        if (controller.recipient.length == 0) {
          return;
        } else if (controller.tree.length == 0) {
          controller.recipient.empty();
          controller.recipient.html(
            "<div>" + controller.emptyMessage + "</div>"
          );
          return;
        }

        controller.recipient.empty();
        var $children = _processGroup(this.recipient, this.tree, true);
        $children.show();

        controller.recipient.data("children", $children);

        // Add again the hover event to the 'force_callback' elements
        forced_title_callback();
      },
      load: function() {
        this.reload();
      },
      changeTree: function(tree) {
        this.tree = tree;
        this.reload();
      },
      init: function(data) {
        if (data.filter.statusModule === "fired") {
          const newData = {
            ajaxUrl: data.ajaxURL,
            baseURL: data.baseURL,
            counterTitle: data.counterTitle,
            detailRecipient: data.detailRecipient,
            emptyMessage: data.emptyMessage,
            filter: data.filter,
            foundMessage: data.foundMessage,
            page: data.page,
            recipient: data.recipient,
            tree: []
          };
          data.tree.forEach(element => {
            if (element.counters.alerts > 0) {
              element.counters.critical = 0;
              element.counters.not_init = 0;
              element.counters.ok = 0;
              element.counters.unknown = 0;
              element.counters.warning = 0;
              element.counters.total = element.counters.alerts;

              newData.tree.push(element);
            }
          });

          data = newData;
        }
        if (
          typeof data.recipient !== "undefined" &&
          data.recipient.length > 0
        ) {
          this.recipient = data.recipient;
        }
        if (typeof data.detailRecipient !== "undefined") {
          this.detailRecipient = data.detailRecipient;
        }
        if (typeof data.tree !== "undefined") {
          this.tree = data.tree;
        }
        if (
          typeof data.emptyMessage !== "undefined" &&
          data.emptyMessage.length > 0
        ) {
          this.emptyMessage = data.emptyMessage;
        }
        if (
          typeof data.foundMessage !== "undefined" &&
          data.foundMessage.length > 0
        ) {
          this.foundMessage = data.foundMessage;
        }
        if (
          typeof data.errorMessage !== "undefined" &&
          data.errorMessage.length > 0
        ) {
          this.errorMessage = data.errorMessage;
        }
        if (typeof data.baseURL !== "undefined" && data.baseURL.length > 0) {
          this.baseURL = data.baseURL;
        }
        if (typeof data.ajaxURL !== "undefined" && data.ajaxURL.length > 0) {
          this.ajaxURL = data.ajaxURL;
        }
        if (typeof data.ajaxPage !== "undefined" && data.ajaxPage.length > 0) {
          this.ajaxPage = data.ajaxPage;
        }
        if (typeof data.filter !== "undefined") {
          this.filter = data.filter;
        }

        if (typeof data.auth_class !== "undefined") {
          this.auth_class = data.auth_class;
        }
        if (typeof data.id_user !== "undefined") {
          this.id_user = data.id_user;
        }
        if (typeof data.auth_hash !== "undefined") {
          this.auth_hash = data.auth_hash;
        }
        if (
          typeof data.tree !== "undefined" &&
          Array.isArray(data.tree) &&
          data.tree.length > 0 &&
          data.tree[0]["rootType"] == "services"
        ) {
          this.foundMessage = "";
        }
        this.load();
      },
      remove: function() {
        if (typeof this.recipient != "undefined" && this.recipient.length > 0) {
          this.recipient.empty();
        }

        if (this.index > -1) {
          TreeController.controllers.splice(this.index, 1);
        }
      }
    };
    controller.index = TreeController.controllers.push(controller) - 1;

    return controller;
  }
};
