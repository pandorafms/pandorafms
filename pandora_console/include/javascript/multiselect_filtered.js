/* global $, jQuery*/

/**
 * Custom selector for case instensitive contains.
 */
jQuery.expr[":"].iContains = jQuery.expr.createPseudo(function(arg) {
  return function(elem) {
    return (
      jQuery(elem)
        .text()
        .toUpperCase()
        .indexOf(arg.toUpperCase()) >= 0
    );
  };
});

/**
 * Add modules from available to selected.
 */
// eslint-disable-next-line no-unused-vars
function addItems(id, noneStr) {
  $("#available-select-" + id + " :selected")
    .toArray()
    .forEach(function(item) {
      $("#selected-select-" + id).append(item);
    });

  keepSelectClean("available-select-" + id, noneStr);
  keepSelectClean("selected-select-" + id, noneStr);
}

/**
 * Mark all options for given id.
 */
// eslint-disable-next-line no-unused-vars
function markAll(id) {
  $("#" + id + " option").prop("selected", true);
}

/**
 * Remove modules from selected back to available.
 */
// eslint-disable-next-line no-unused-vars
function removeItems(id, noneStr) {
  $("#selected-select-" + id + " :selected")
    .toArray()
    .forEach(function(item) {
      $("#available-select-" + id).append(item);
    });

  keepSelectClean("available-select-" + id, noneStr);
  keepSelectClean("selected-select-" + id, noneStr);
}

/**
 * 'None' option, if needed.
 */
function keepSelectClean(id, noneStr) {
  $("#" + id + " option[value=0]").remove();

  if ($("#" + id + " option").length == 0) {
    $("#" + id).append(new Option(noneStr, 0));
  }

  $("#" + id + " option").each(function() {
    $(this).prop("selected", false);
  });
}

function filterItems(id, str) {
  // Remove option 0 - None.
  $("#" + id + " option[value=0]").remove();

  // Move not matching elements filtered to tmp-id.
  var tmp = $("#" + id + " option:not(:iContains(" + str + "))").toArray();
  tmp.forEach(function(item) {
    $("#tmp-" + id).append(item);
    $(this).remove();
  });

  // Move matching filter back to id.
  tmp = $("#tmp-" + id + " option:iContains(" + str + ")").toArray();
  tmp.forEach(function(item) {
    $("#" + id).append(item);
    $(this).remove();
  });
}

// eslint-disable-next-line no-unused-vars
function filterAvailableItems(txt, id, noneStr) {
  filterItems("available-select-" + id, txt);
  keepSelectClean("available-select-" + id, noneStr);
}

// eslint-disable-next-line no-unused-vars
function filterSelectedItems(txt, id, noneStr) {
  filterItems("selected-select-" + id, txt);
  keepSelectClean("selected-select-" + id, noneStr);
}

// eslint-disable-next-line no-unused-vars
function disableFilters(id) {
  $("#id-group-selected-select-" + id).prop("disabled", true);
  $("#checkbox-id-group-recursion-selected-select-" + id).prop(
    "disabled",
    true
  );
}

// eslint-disable-next-line no-unused-vars
function reloadContent(id, url, options, side, noneStr) {
  var current;
  var opposite;

  if (side == "right") {
    current = "selected-select-" + id;
    opposite = "available-select-" + id;
  } else if (side == "left") {
    current = "available-select-" + id;
    opposite = "selected-select-" + id;
  } else {
    console.error("reloadContent bad usage.");
    return;
  }

  var data = JSON.parse(atob(options));
  data.side = side;
  data.group_recursion = $("#checkbox-id-group-recursion-" + current).prop(
    "checked"
  )
    ? 1
    : 0;
  data.group_id = $("#id-group-" + current).val();

  $.ajax({
    method: "post",
    url: url,
    dataType: "json",
    data: data,
    success: function(data) {
      // Cleanup previous content.
      $("#" + current).empty();

      let items = Object.entries(data).sort(function(a, b) {
        if (a[1] == b[1]) return 0;

        var int_a = parseInt(a[1]);
        var int_b = parseInt(b[1]);

        if (!isNaN(int_a) && !isNaN(int_b)) {
          return int_a > int_b ? 1 : -1;
        }
        return a[1] > b[1] ? 1 : -1;
      });

      for (var [value, label] of items) {
        if (
          $("#" + opposite + " option[value='" + value + "']").length == 0 &&
          $("#tmp-" + current + " option[value='" + value + "']").length == 0
        ) {
          // Does not exist in opposite box nor is filtered.
          $("#" + current).append(new Option(label, value));
        }
      }

      keepSelectClean(current, noneStr);
    },
    error: function(data) {
      console.error(data.responseText);
    }
  });
}

$(document).submit(function() {
  // Force select all 'selected' items to send them on submit.
  $("[id*=text-filter-item-selected-")
    .val("")
    .keyup();
  $("[id^=selected-select-] option").prop("selected", true);
});

// eslint-disable-next-line no-unused-vars
function fmAgentChange(uniqId) {
  var idGroup = $("#filtered-module-group-" + uniqId).val();
  var recursion = $("#filtered-module-recursion-" + uniqId).is(":checked");
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agents_group_json: 1,
      id_group: idGroup,
      privilege: "AW",
      keys_prefix: "_",
      recursion: recursion
    },
    function(data) {
      $("#filtered-module-agents-" + uniqId).html("");
      $("#filtered-module-modules-" + uniqId).html("");
      jQuery.each(data, function(id, value) {
        // Remove keys_prefix from the index
        id = id.substring(1);

        var option = $("<option></option>")
          .attr("value", value["id_agente"])
          .html(value["alias"]);
        $("#filtered-module-agents-" + uniqId).append(option);
      });
    },
    "json"
  );
}

// eslint-disable-next-line no-unused-vars
function fmModuleChange(uniqId, isMeta) {
  var idModuleGroup = $("#filtered-module-module-group-" + uniqId).val();
  var idAgents = $("#filtered-module-agents-" + uniqId).val();
  var commonSelectorType = $(
    "#filtered-module-show-common-modules-" + uniqId
  ).attr("type");

  var showCommonModules = +(
    $("#filtered-module-show-common-modules-" + uniqId).prop("checked") == false
  );

  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_modules_group_json: 1,
      id_module_group: idModuleGroup,
      id_agents: idAgents,
      selection: showCommonModules
    },
    function(data) {
      $("#filtered-module-modules-" + uniqId).html("");
      if (data) {
        jQuery.each(data, function(id, value) {
          var option = $("<option></option>");
          if (isMeta === 1) {
            if (value["id_node"] == null || value["id_node"] == "") {
              option.attr("value", id).html(value);
            }
            option
              .attr(
                "value",
                value["id_node"]
                  ? value["id_node"] + "|" + value["id_agente_modulo"]
                  : value["id_agente_modulo"]
              )
              .html(value["nombre"]);
          } else {
            option.attr("value", id).html(value);
          }

          $("#filtered-module-modules-" + uniqId).append(option);
        });
      }
    },
    "json"
  );
}

// Function to search in agents select.
function searchAgent(uniqId) {
  // Declare variables
  var agents = $("#filtered-module-agents-" + uniqId + " option");

  // Loop through all list items, and hide those who don't match the search query
  agents.each(function() {
    var filter = $("#text-agent-searchBar-modules")
      .val()
      .toUpperCase();

    if (
      $(this)
        .text()
        .toUpperCase()
        .indexOf(filter) > -1
    ) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}

// Function to search in modules select.
function searchModule(uniqId) {
  // Declare variables
  var modules = $("#filtered-module-modules-" + uniqId + " option");

  // Loop through all list items, and hide those who don't match the search query
  modules.each(function() {
    var filter = $("#text-module-searchBar-modules")
      .val()
      .toUpperCase();
    if (
      $(this)
        .text()
        .toUpperCase()
        .indexOf(filter) > -1
    ) {
      $(this).show();
    } else {
      $(this).hide();
    }
  });
}
