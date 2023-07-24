/* globals $,jQuery */
// Load the SNMP tree via AJAX
function snmpBrowse() {
  // Empty the SNMP tree
  $("#snmp_browser").html("");

  // Hide the data div
  hideOIDData();

  $("#button-srcbutton")
    .find("div")
    .addClass("rotation");

  // Reset previous searches
  $("#search_results").css("display", "none");
  $("#hidden-search_count").val(-1);

  // Show the spinner
  $("#spinner").css("display", "");

  // Read the target IP and community
  var target_ip = $("#text-target_ip").val();
  var target_port = $("#target_port").val();
  var community = $("#text-community").val();
  var starting_oid = $("#text-starting_oid").val();
  var snmp_version = $("#snmp_browser_version").val();
  var server_to_exec = $("#server_to_exec").val();
  var snmp3_auth_user = $("#text-snmp3_browser_auth_user").val();
  var snmp3_security_level = $("#snmp3_browser_security_level").val();
  var snmp3_auth_method = $("#snmp3_browser_auth_method").val();
  var snmp3_auth_pass = $("#password-snmp3_browser_auth_pass").val();
  var snmp3_privacy_method = $("#snmp3_browser_privacy_method").val();
  var snmp3_privacy_pass = $("#password-snmp3_browser_privacy_pass").val();
  var ajax_url = $("#hidden-ajax_url").val();

  // Prepare the AJAX call

  var params = {};
  params["target_ip"] = target_ip;
  params["target_port"] = target_port;
  params["community"] = community;
  params["starting_oid"] = starting_oid;
  params["snmp_browser_version"] = snmp_version;
  params["server_to_exec"] = server_to_exec;
  params["snmp3_browser_auth_user"] = snmp3_auth_user;
  params["snmp3_browser_security_level"] = snmp3_security_level;
  params["snmp3_browser_auth_method"] = snmp3_auth_method;
  params["snmp3_browser_auth_pass"] = snmp3_auth_pass;
  params["snmp3_browser_privacy_method"] = snmp3_privacy_method;
  params["snmp3_browser_privacy_pass"] = snmp3_privacy_pass;
  params["server_to_exec"] = server_to_exec;
  params["action"] = "snmptree";
  params["page"] = "include/ajax/snmp_browser.ajax";

  // Browse!
  jQuery.ajax({
    data: params,
    type: "POST",
    url: (action = ajax_url),
    async: true,
    success: function(data) {
      // Hide the spinner
      $("#spinner").css("display", "none");

      // Load the SNMP tree
      $("#snmp_tree_container").show();
      $("#search_options").show();
      $("#button-srcbutton")
        .find("div")
        .removeClass("rotation");
      $("#snmp_browser").html(data);

      // Manage click and select events.
      snmp_browser_events_manage();
    },
    error: function(XMLHttpRequest, textStatus, errorThrown) {
      $("#spinner").css("display", "none");
      var htmlError = "";

      if (XMLHttpRequest.responseText) {
        htmlError =
          "<p><b>Status:</b> " +
          textStatus +
          "</p><p>" +
          XMLHttpRequest.responseText +
          "</p>";
      } else {
        htmlError =
          "<p><b>Status:</b> " +
          textStatus +
          "</p><p>" +
          "<b>Error:</b> " +
          errorThrown +
          "</p>";
      }
      $("#snmp_tree_container").show();
      $("#search_options").show();
      $("#button-srcbutton")
        .find("div")
        .removeClass("rotation");
      $("#snmp_browser").html(htmlError);
    }
  });
}

function snmp_browser_events_manage() {
  // Hide create buttons.
  $("#snmp_create_buttons").hide();

  $("input[id^=checkbox-create]").change(function() {
    if ($(this).is(":checked")) {
      $("#snmp_create_buttons").show();
      var id_input = $(this).attr("id");
      id_input = id_input.match("checkbox-create_([0-9]+)");
      var checks = $("#ul_" + id_input[1])
        .find("input")
        .map(function() {
          if (this.id.indexOf("checkbox-create_") != -1) {
            return this.id;
          }
        })
        .get();

      checks.forEach(function(product, index) {
        $("#" + product).prop("checked", "true");
      });
    } else {
      var id_input = $(this).attr("id");

      id_input = id_input.match("checkbox-create_([0-9]+)");
      var checks = $("#ul_" + id_input[1])
        .find("input")
        .map(function() {
          if (this.id.indexOf("checkbox-create_") != -1) {
            return this.id;
          }
        })
        .get();

      checks.forEach(function(product, index) {
        $("#" + product).prop("checked", false);
      });
    }

    // Hide buttons if no ckbox is checked.
    var checked = false;
    $("input[id^=checkbox-create]").each(function() {
      checked = $(this).is(":checked");
      if (checked == true) {
        return false;
      }
    });

    if (checked == false) {
      $("#snmp_create_buttons").hide();
    }
  });
}

// Expand or collapse an SNMP tree node
function toggleTreeNode(node) {
  var display = $("#ul_" + node).css("display");
  var src = $("#anchor_" + node)
    .children("img")
    .attr("src");

  // Show the expanded or collapsed square
  if (display == "none") {
    src = src.replace("closed", "expanded");
  } else {
    src = src.replace("expanded", "closed");
  }
  $("#anchor_" + node)
    .children("img")
    .attr("src", src);

  // Hide or show leaves
  $("#ul_" + node).toggle();
}

// Expand an SNMP tree node
function expandTreeNode(node) {
  if (node == 0) {
    return;
  }

  // Show the expanded square
  var src = $("#anchor_" + node)
    .children("img")
    .attr("src");
  src = src.replace("closed", "expanded");
  $("#anchor_" + node)
    .children("img")
    .attr("src", src);

  // Show leaves
  $("#ul_" + node).css("display", "block");
}

// Expand an SNMP tree node
function collapseTreeNode(node) {
  if (node == 0) {
    return;
  }

  // Show the collapsed square
  var src = $("#anchor_" + node)
    .children("img")
    .attr("src");
  src = src.replace("expanded", "closed");
  $("#anchor_" + node)
    .children("img")
    .attr("src", src);

  // Hide leaves
  $("#ul_" + node).css("display", "none");
}

// Expand all tree nodes
function expandAll(node) {
  $("#snmp_browser")
    .find("ul")
    .each(function() {
      var id = $(this)
        .attr("id")
        .substr(3);
      expandTreeNode(id);
    });
}

// Collapse all tree nodes
function collapseAll(node) {
  // Reset previous searches
  $("#search_results").css("display", "none");
  $("#hidden-search_count").val(-1);

  $("#snmp_browser")
    .find("ul")
    .each(function() {
      var id = $(this)
        .attr("id")
        .substr(3);
      collapseTreeNode(id);
    });
}

// Perform an SNMP get request via AJAX
function snmpGet(oid) {
  // Empty previous OID data
  $("#snmp_data").empty();

  // Read the target IP and community
  var target_ip = $("#text-target_ip").val();
  var community = $("#text-community").val();
  var snmp_version = $("#snmp_browser_version").val();
  var snmp3_auth_user = $("#text-snmp3_browser_auth_user").val();
  var snmp3_security_level = $("#snmp3_browser_security_level").val();
  var snmp3_auth_method = $("#snmp3_browser_auth_method").val();
  var snmp3_auth_pass = $("#password-snmp3_browser_auth_pass").val();
  var snmp3_privacy_method = $("#snmp3_browser_privacy_method").val();
  var snmp3_privacy_pass = $("#password-snmp3_browser_privacy_pass").val();
  var ajax_url = $("#hidden-ajax_url").val();
  var server_to_exec = $("#server_to_exec").val();
  var target_port = $("#target_port").val();
  var is_policy_or_agent = $("#is_policy_agent").val();

  // Check for a custom action
  var custom_action = $("#hidden-custom_action").val();
  if (custom_action == undefined) {
    custom_action = "";
  }

  var params = {};

  params["target_ip"] = target_ip;
  params["community"] = community;
  params["oid"] = oid;
  params["snmp_browser_version"] = snmp_version;
  params["snmp3_browser_auth_user"] = snmp3_auth_user;
  params["snmp3_browser_security_level"] = snmp3_security_level;
  params["snmp3_browser_auth_method"] = snmp3_auth_method;
  params["snmp3_browser_auth_pass"] = snmp3_auth_pass;
  params["snmp3_browser_privacy_method"] = snmp3_privacy_method;
  params["snmp3_browser_privacy_pass"] = snmp3_privacy_pass;
  params["server_to_exec"] = server_to_exec;
  params["action"] = "snmpget";
  params["custom_action"] = custom_action;
  params["page"] = "include/ajax/snmp_browser.ajax";
  params["target_port"] = target_port;
  if (typeof is_policy_or_agent !== "undefined") {
    params["print_copy_oid"] = 1;
  } else {
    params["print_create_agent_module"] = 1;
  }

  // SNMP get!
  jQuery.ajax({
    data: params,
    type: "POST",
    url: (action = ajax_url),
    async: true,
    timeout: 60000,
    success: function(data) {
      $("#snmp_data").html(data);
      forced_title_callback();
    }
  });

  // Show the data div
  showOIDData();
}

// Show the div that displays OID data
function showOIDData() {
  $("#snmp_data").css("display", "");
}

// Hide the div that displays OID data
function hideOIDData() {
  // Empty previous OID data
  $("#snmp_data").empty();

  $("#snmp_data").css("display", "none");
  $(".forced_title_layer").css("display", "none");
}

// Search the SNMP tree for a matching string
function searchText() {
  var text = $("#text-search_text").val();
  var regexp = new RegExp(text, "i");
  var search_matches_translation = $(
    "#hidden-search_matches_translation"
  ).val();

  // Hide previous search result count
  $("#search_results").css("display", "");

  // Show the spinner
  $("#spinner").css("display", "");

  // Collapse previously searched nodes
  $(".expanded").each(function() {
    $(this).removeClass("expanded");

    // Remove the leading ul_
    var node_id = $(this)
      .attr("id")
      .substr(3);

    collapseTreeNode(node_id);
  });

  // Un-highlight previously searched nodes
  $("match").removeClass("match");
  $("span").removeClass("group_view_warn");

  // Hide values
  $("span.value").css("display", "none");

  // Disable empty searches
  var count = 0;
  if (text != "") {
    count = searchTreeNode($("#snmp_browser"), regexp);
  }

  // Hide the spinner
  $("#spinner").css("display", "none");

  // Show and save the search result count
  $("#hidden-search_count").val(count);
  $("#search_results").text(search_matches_translation + ": " + count);
  $("#search_results").css("display", "");

  // Reset the search index
  $("#hidden-search_index").val(-1);

  // Focus the first match
  searchNextMatch();
}

// Recursively search an SNMP tree node trying to match the given regexp
function searchTreeNode(obj, regexp) {
  // For each node tree
  var count = 0;
  $(obj)
    .children("ul")
    .each(function() {
      var ul_node = this;

      // Expand if regexp matches one of its children
      $(ul_node).addClass("expand");

      // Search children for matches
      $(ul_node)
        .children("li")
        .each(function() {
          var li_node = this;
          var text = $(li_node).text();

          // Match!
          if (regexp.test(text) == true) {
            count++;

            // Highlight in yellow
            $(li_node)
              .children("span")
              .addClass("group_view_warn");
            $(li_node).addClass("match");

            // Show the value
            $(li_node)
              .children("span.value")
              .css("display", "");

            // Expand all nodes that lead to this one
            $(".expand").each(function() {
              $(this).addClass("expanded");

              // Remove the leading ul_
              var node_id = $(this)
                .attr("id")
                .substr(3);

              expandTreeNode(node_id);
            });
          }
        });

      // Search sub nodes
      count += searchTreeNode(ul_node, regexp);

      // Do not expand this node if it has not been expanded already
      $(ul_node).removeClass("expand");
    });

  return count;
}

// Focus the next search match
function searchNextMatch() {
  var search_index = $("#hidden-search_index").val();
  var search_count = $("#hidden-search_count").val();

  // Update the search index
  search_index++;
  if (search_index >= search_count) {
    search_index = 0;
  }

  // Get the id of the next element
  var id = $(".match:eq(" + search_index + ")").attr("id");

  // Scroll
  var body = $("html, body");
  $("#snmp_browser").animate(
    {
      scrollTop:
        $("#snmp_browser").scrollTop() +
        $("#" + id).offset().top -
        $("#snmp_browser").offset().top
    },
    1000,
    function() {
      // Blink.
      $("#" + id)
        .fadeOut(100)
        .fadeIn(100)
        .fadeOut(100)
        .fadeIn(100)
        .focus();
    }
  );

  // Save the search index
  $("#hidden-search_index").val(search_index);
}

// Focus the previous search match
function searchPrevMatch() {
  var search_index = $("#hidden-search_index").val();
  var search_count = $("#hidden-search_count").val();

  // Update the search index
  search_index--;
  if (search_index < 0) {
    search_index = search_count - 1;
  }

  // Get the id of the next element
  var id = $(".match:eq(" + search_index + ")").attr("id");

  // Scroll
  $("#snmp_browser").animate(
    {
      scrollTop:
        $("#snmp_browser").scrollTop() +
        $("#" + id).offset().top -
        $("#snmp_browser").offset().top
    },
    1000,
    function() {
      // Blink.
      $("#" + id)
        .fadeOut(100)
        .fadeIn(100)
        .fadeOut(100)
        .fadeIn(100)
        .focus();
    }
  );

  // Save the search index
  $("#hidden-search_index").val(search_index);
}

// Focus the first search match
function searchFirstMatch() {
  // Reset the search index
  $("#hidden-search_index").val(-1);

  // Focus the first match
  searchNextMatch();
}

// Focus the last search match
function searchLastMatch() {
  // Reset the search index
  $("#hidden-search_index").val(-1);

  // Focus the last match
  searchPrevMatch();
}

// Hide or show SNMP v3 options
function checkSNMPVersion() {
  if ($("#snmp_browser_version").val() == "3") {
    $("#snmp3_browser_options").css("display", "");
  } else {
    $("#snmp3_browser_options").css("display", "none");
  }
}

// Show the SNMP browser window
function snmpBrowserWindow(id_agente = 0) {
  // Keep elements in the form and the SNMP browser synced
  $("#text-target_ip").val($("#text-ip_target").val());
  $("#target_port").val($("#text-tcp_port").val());
  $("#text-community").val($("#text-snmp_community").val());
  $("#snmp_browser_version").val($("#snmp_version").val());
  $("#text-snmp3_browser_auth_user").val($("#text-snmp3_auth_user").val());
  $("#snmp3_browser_security_level").val($("#snmp3_security_level").val());
  $("#snmp3_browser_auth_method").val($("#snmp3_auth_method").val());
  $("#password-snmp3_browser_auth_pass").val(
    $("#password-snmp3_auth_pass").val()
  );
  $("#snmp3_browser_privacy_method").val($("#snmp3_privacy_method").val());
  $("#password-snmp3_browser_privacy_pass").val(
    $("#password-snmp3_privacy_pass").val()
  );
  // Realation agente module.
  $("#id_agent_module").val(id_agente);

  checkSNMPVersion();

  $("#snmp_browser_container")
    .show()
    .dialog({
      title: "",
      resizable: true,
      draggable: true,
      modal: true,
      overlay: {
        opacity: 0.5,
        background: "black"
      },
      width: 1000,
      height: 800
    });
}

// Set the form OID to the value selected in the SNMP browser
function setOID() {
  if ($("#snmp_browser_version").val() == "3") {
    $("#text-snmp_oid").val($("#table1-0-1").text());
  } else {
    $("#text-snmp_oid").val($("#snmp_selected_oid").text());
  }

  // Close the SNMP browser
  $(".ui-dialog-titlebar-close").trigger("click");
}

/**
 * Create module on selected module_target (agent, network component or policy).
 *
 * @param string module_target Target to create module.
 * @param return_values Return snmp values.
 */
function snmp_browser_create_modules(module_target, return_post = true) {
  var id_check = $("#ul_0")
    .find("input")
    .map(function() {
      if (this.id.indexOf("checkbox-create_") != -1) {
        if ($(this).is(":checked")) {
          return this.id;
        }
      }
    })
    .get();

  var target_ip = $("#text-target_ip").val();
  var target_port = $("#target_port").val();
  var community = $("#text-community").val();
  var snmp_version = $("#snmp_browser_version").val();
  var snmp3_auth_user = $("#text-snmp3_browser_auth_user").val();
  var snmp3_security_level = $("#snmp3_browser_security_level").val();
  var snmp3_auth_method = $("#snmp3_browser_auth_method").val();
  var snmp3_auth_pass = $("#password-snmp3_browser_auth_pass").val();
  var snmp3_privacy_method = $("#snmp3_browser_privacy_method").val();
  var snmp3_privacy_pass = $("#password-snmp3_browser_privacy_pass").val();

  var custom_action = $("#hidden-custom_action").val();
  if (custom_action == undefined) {
    custom_action = "";
  }

  var oids = [];
  id_check.forEach(function(product, index) {
    var oid = $("#" + product)
      .parent()
      .parent()
      .find("a")
      .attr("href");
    if (oid.indexOf('javascript: snmpGet("') != -1) {
      oid = oid.replace('javascript: snmpGet("', "");
      oid = oid.replace('");', "");
      oids.push(oid);
    }
  });

  var snmp_conf = {};

  snmp_conf["target_ip"] = target_ip;
  snmp_conf["target_port"] = target_port;
  snmp_conf["community"] = community;
  snmp_conf["oids"] = oids;
  snmp_conf["snmp_browser_version"] = snmp_version;
  snmp_conf["snmp3_browser_auth_user"] = snmp3_auth_user;
  snmp_conf["snmp3_browser_security_level"] = snmp3_security_level;
  snmp_conf["snmp3_browser_auth_method"] = snmp3_auth_method;
  snmp_conf["snmp3_browser_auth_pass"] = snmp3_auth_pass;
  snmp_conf["snmp3_browser_privacy_method"] = snmp3_privacy_method;
  snmp_conf["snmp3_browser_privacy_pass"] = snmp3_privacy_pass;
  snmp_conf["module_target"] = module_target;
  snmp_conf["custom_action"] = custom_action;

  var snmp_data = [];

  for (var snmp_data_name in snmp_conf) {
    snmp_data.push({
      name: snmp_data_name,
      value: snmp_conf[snmp_data_name]
    });
  }

  if (return_post) {
    return snmp_data;
  } else {
    var params = {};

    params["method"] = "snmp_browser_create_modules";
    params["module_target"] = module_target;
    params["page"] = "include/ajax/snmp_browser.ajax";
    params["snmp_extradata"] = snmp_data;

    $("button[name=create_modules_" + module_target + "]").removeClass(
      "sub add"
    );
    $("button[name=create_modules_" + module_target + "]").addClass(
      "sub spinn"
    );

    $("#dialog_error").on("dialogclose", function(event) {
      $("button[name=create_modules_" + module_target + "]").removeClass(
        "sub spinn"
      );
      $("button[name=create_modules_" + module_target + "]").addClass(
        "sub add"
      );
    });

    $("#dialog_success").on("dialogclose", function(event) {
      $("button[name=create_modules_" + module_target + "]").removeClass(
        "sub spinn"
      );
      $("button[name=create_modules_" + module_target + "]").addClass(
        "sub add"
      );
    });

    $.ajax({
      method: "post",
      url: "ajax.php",
      data: params,
      dataType: "html",
      success: function(data) {
        snmp_show_result_message(data);
      }
    });
  }
}
