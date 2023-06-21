/* global $ jQuery */
/* exported load_modal */

var ENTERPRISE_DIR = "enterprise";

/* Function to hide/unhide a specific Div id */
function toggleDiv(divid) {
  if (document.getElementById(divid).style.display == "none") {
    document.getElementById(divid).style.display = "block";
  } else {
    document.getElementById(divid).style.display = "none";
  }
}

function winopeng(url, wid) {
  open(
    url,
    wid,
    "width=1000,height=550,status=no,toolbar=no,menubar=no,scrollbars=yes,resizable=yes"
  );
  // WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
  status = wid;
}

function winopeng_var(url, wid, width, height) {
  open(
    url,
    wid,
    "width=" +
      width +
      ",height=" +
      height +
      ",status=no,toolbar=no,menubar=no,scrollbar=yes"
  );
  // WARNING !! Internet Explorer DOESNT SUPPORT "-" CARACTERS IN WINDOW HANDLE VARIABLE
  status = wid;
}

function newTabjs(content) {
  content = atob(content);
  var printWindow = window.open("");
  printWindow.document.body.innerHTML += "<div>" + content + "</div>";
}

function open_help(url) {
  if (!navigator.onLine) {
    alert(
      "The help system could not be started. Please, check your network connection."
    );
    return;
  }
  if (url == "") {
    alert(
      "The help system is currently under maintenance. Sorry for the inconvenience."
    );
    return;
  }
  open(
    url,
    "pandorahelp",
    "width=650,height=500,status=0,toolbar=0,menubar=0,scrollbars=1,location=0"
  );
}

/**
 * Decode HTML entities into characters. Useful when receiving something from AJAX
 *
 * @param str String to convert
 *
 * @retval str with entities decoded
 */
function js_html_entity_decode(str) {
  if (!str) return "";

  str2 = str
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/&lt;/g, "<")
    .replace(/&gt;/g, ">")
    .replace(/&#92;/g, "\\")
    .replace(/&quot;/g, '"')
    .replace(/&#039;/g, "'")
    .replace(/&amp;/g, "&")
    .replace(/&#x20;/g, " ")
    .replace(/&#13;/g, "\r")
    .replace(/&#10;/g, "\n");

  return str2;
}

function truncate_string(str, str_length, separator) {
  if (str.length <= str_length) {
    return str;
  }

  separator = separator || "...";

  var separator_length = separator.length,
    chars_to_show = str_length - separator_length,
    front_chars = Math.ceil(chars_to_show / 2),
    tail_chars = Math.floor(chars_to_show / 2);

  return (
    str.substr(0, front_chars) + separator + str.substr(str.length - tail_chars)
  );
}

/**
 * Function to search an element in an array.
 *
 * Extends the array object to use it like a method in an array object. Example:
 * <code>
 a = Array (4, 7, 9);
 alert (a.in_array (4)); // true
 alert (a.in_array (5)); // false
 */
Array.prototype.in_array = function() {
  for (var j in this) {
    if (this[j] == arguments[0]) return true;
  }

  return false;
};

/**
 * Util for check is empty object
 *
 * @param obj the object to check
 * @returns {Boolean} True it is empty
 */
function isEmptyObject(obj) {
  for (var prop in obj) {
    if (obj.hasOwnProperty(prop)) return false;
  }

  return true;
}

/**
 * Fill up select box with id "module" with modules after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents(event, id_agent, selected) {
  // Hack to avoid certain module types
  var module_types_excluded = [];
  if (typeof $("input.module_types_excluded") !== "undefined") {
    try {
      $("input.module_types_excluded").each(function(index, el) {
        var module_type = parseInt($(el).val());

        if (isNaN(module_type) == false)
          module_types_excluded.push(module_type);
      });
    } catch (error) {}
  }

  var module_status = -1;
  if (typeof $("#status_module") !== "undefined") {
    try {
      module_status = $("#status_module").val();
    } catch (error) {}
  }

  // Module name
  var module_name = $("#text-module_filter").val();

  var idAgents = Array();

  jQuery.each($("#id_agents option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    idAgents.push($(val).val());
  });

  var tags_to_search = $("#tags1").val();

  //Hack to find only enabled modules
  //Pass a flag as global var
  find_modules = "all";
  if (
    typeof show_only_enabled_modules !== "undefined" &&
    show_only_enabled_modules
  ) {
    find_modules = "enabled";
  }

  var selection_mode = $("#modules_selection_mode").val();
  if (typeof selection_mode === "undefined") {
    selection_mode = "common";
  }

  var serialized = $("#hidden-serialized").val();
  if (typeof serialized === "undefined") {
    serialized = "";
  }

  var id_group = null;
  if (typeof $("#filter_group") !== "undefined") {
    try {
      id_group = $("#filter_group").val();
    } catch (error) {}
  }

  $("#module")
    .prop("disabled", true)
    .empty()
    .append(
      $("<option></option>")
        .html("Loading...")
        .attr("value", 0)
    );

  // Check if homedir was received like a JSON
  var homedir = ".";
  var id_server = 0;
  if (typeof event !== "undefined" && typeof event.data !== "undefined") {
    if (event.data != null) {
      if (typeof event.data !== "undefined") {
        if (typeof event.data.homedir !== "undefined") {
          homedir = event.data.homedir;
        }

        if (
          typeof event.data.metaconsole !== "undefined" &&
          event.data.metaconsole
        ) {
          id_server = $("#" + event.data.id_server).val();
        }
      }
    }
  }

  jQuery.post(
    homedir + "/ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agent_modules_json_for_multiple_agents: 1,
      "id_agent[]": idAgents,
      "tags[]": tags_to_search,
      all: find_modules,
      "module_types_excluded[]": module_types_excluded,
      name: module_name,
      selection_mode: selection_mode,
      serialized: serialized,
      id_server: id_server,
      status_module: module_status,
      id_group: id_group,
      pendingdelete:
        event.target != undefined ? event.target.dataset.pendingdelete : 0 // Get pendingdelete attribute from target
    },
    function(data) {
      $("#module").empty();

      if (isEmptyObject(data)) {
        //Trick for catch the translate text.
        var noneText =
          $("#id_agents").val() === null
            ? $("#select_agent_first_text").html()
            : $("#none_text").html();
        if (noneText == null) {
          noneText = "None";
        }

        $("#module").append(
          $("<option></option>")
            .html(noneText)
            .attr("None", "")
            .prop("selected", true)
        );

        return;
      }

      if (typeof $(document).data("text_for_module") != "undefined") {
        $("#module").append(
          $("<option></option>")
            .html($(document).data("text_for_module"))
            .attr("value", 0)
            .prop("selected", true)
        );
      } else {
        if (typeof data["any_text"] != "undefined") {
          $("#module").append(
            $("<option></option>")
              .html(data["any_text"])
              .attr("value", 0)
              .prop("selected", true)
          );
        } else {
          var anyText = $("#any_text").html(); //Trick for catch the translate text.

          if (anyText == null) {
            anyText = "Any";
          }

          $("#module").append(
            $("<option></option>")
              .html(anyText)
              .attr("value", 0)
              .prop("selected", true)
          );
        }
      }

      var all_common_modules = [];

      $.each(data, function(i, val) {
        var s = js_html_entity_decode(val);

        s = s.replace(/"/g, "&quot;").replace(/'/g, "&apos;");
        i = i.replace(/"/g, "&quot;").replace(/'/g, "&apos;");

        $("#module").append(
          $('<option value="' + i + '" title="' + s + '"></option>').text(val)
        );

        all_common_modules.push(i);
        $("#module").fadeIn("normal");
      });

      $("#hidden-all_common_modules").val(all_common_modules.toString());

      if (typeof selected !== "undefined") $("#module").attr("value", selected);

      $("#module")
        .css("max-width", "")
        .prop("disabled", false);

      if (typeof function_hook_loaded_module_list == "function") {
        function_hook_loaded_module_list();
      }
    },
    "json"
  );
}

/**
 * Fill up select box with id "module" with modules with alerts of one template
 * after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents_with_alerts(
  event,
  id_agent,
  selected
) {
  var idAgents = Array();

  jQuery.each($("#id_agents option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    idAgents.push($(val).val());
  });

  var selection_mode = $("#modules_selection_mode").val();
  if (selection_mode == undefined) {
    selection_mode = "common";
  }

  template = $("#id_alert_template option:selected").val();

  $("#module").attr("disabled", 1);
  $("#module").empty();
  $("#module").append(
    $("<option></option>")
      .html("Loading...")
      .attr("value", 0)
  );
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agent_modules_multiple_alerts_json_for_multiple_agents: 1,
      template: template,
      "id_agent[]": idAgents,
      selection_mode: selection_mode
    },
    function(data) {
      $("#module").empty();

      if (typeof $(document).data("text_for_module") != "undefined") {
        $("#module").append(
          $("<option></option>")
            .html($(document).data("text_for_module"))
            .attr("value", 0)
            .prop("selected", true)
        );
      } else {
        if (typeof data["any_text"] != "undefined") {
          $("#module").append(
            $("<option></option>")
              .html(data["any_text"])
              .attr("value", 0)
              .prop("selected", true)
          );
        } else {
          var anyText = $("#any_text").html(); //Trick for catch the translate text.

          if (anyText == null) {
            anyText = "Any";
          }

          $("#module").append(
            $("<option></option>")
              .html(anyText)
              .attr("value", 0)
              .prop("selected", true)
          );
        }
      }
      jQuery.each(data, function(i, val) {
        var s = js_html_entity_decode(val);

        s = s.replace(/"/g, "&quot;").replace(/'/g, "&apos;");

        $("#module").append($('<option value="' + s + '"></option>').text(val));
        $("#module").fadeIn("normal");
      });
      if (selected != undefined) $("#module").attr("value", selected);
      $("#module").removeAttr("disabled");
    },
    "json"
  );
}

/**
 * Fill up select box with id "module" with modules with alerts of one or more templates
 * before agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function alert_templates_changed_by_multiple_agents_with_alerts(
  event,
  id_agent,
  selected
) {
  var idAgents = Array();

  jQuery.each($("#id_agents option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    idAgents.push($(val).val());
  });

  var selection_mode = $("#modules_selection_mode").val();
  if (selection_mode == undefined) {
    selection_mode = "common";
  }

  templates = Array();
  jQuery.each($("#id_alert_templates option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    templates.push($(val).val());
  });

  $("#module").attr("disabled", 1);
  $("#module").empty();
  $("#module").append(
    $("<option></option>")
      .html("Loading...")
      .attr("value", 0)
  );
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agent_modules_alerts_json_for_multiple_agents: 1,
      "templates[]": templates,
      "id_agent[]": idAgents,
      selection_mode: selection_mode
    },
    function(data) {
      $("#module").empty();

      if (typeof $(document).data("text_for_module") != "undefined") {
        $("#module").append(
          $("<option></option>")
            .html($(document).data("text_for_module"))
            .attr("value", 0)
            .prop("selected", true)
        );
      } else {
        if (typeof data["any_text"] != "undefined") {
          $("#module").append(
            $("<option></option>")
              .html(data["any_text"])
              .attr("value", 0)
              .prop("selected", true)
          );
        } else {
          var anyText = $("#any_text").html(); //Trick for catch the translate text.

          if (anyText == null) {
            anyText = "Any";
          }

          $("#module").append(
            $("<option></option>")
              .html(anyText)
              .attr("value", 0)
              .prop("selected", true)
          );
        }
      }
      jQuery.each(data, function(i, val) {
        var decoded_val = js_html_entity_decode(val);

        decoded_val = decoded_val
          .replace(/"/g, "&quot;")
          .replace(/'/g, "&apos;");

        $("#module").append(
          $(
            '<option value="' +
              decoded_val +
              '" title="' +
              decoded_val +
              '"></option>'
          ).text(val)
        );

        $("#module").fadeIn("normal");
      });
      if (selected != undefined) $("#module").attr("value", selected);
      $("#module").removeAttr("disabled");
    },
    "json"
  );
}

/**
 * Fill up select box with id "agent" with agents after module has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_module Module ID that has been selected
 * @param selected Which agent(s) have to be selected
 */
function module_changed_by_multiple_modules(event, id_module, selected) {
  var idModules = Array();

  jQuery.each($("#module_name option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    idModules.push($(val).val());
  });

  $("#agents").attr("disabled", 1);
  $("#agents").empty();
  $("#agents").append(
    $("<option></option>")
      .html("Loading...")
      .attr("value", 0)
  );

  var status_module = -1;
  if (typeof $("#status_module") !== "undefined") {
    try {
      status_module = $("#status_module").val();
    } catch (error) {}
  }

  var selection_mode = $("#agents_selection_mode").val();
  if (selection_mode == undefined) {
    selection_mode = "common";
  }

  var tags_selected = [];

  var tags_to_search = $("#tags").val();
  if (tags_to_search != null) {
    if (tags_to_search[0] != -1) {
      tags_selected = tags_to_search;
    }
  }
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agents_json_for_multiple_modules: 1,
      truncate_agent_names: 1,
      status_module: status_module,
      "module_name[]": idModules,
      selection_mode: selection_mode,
      tags: tags_selected
    },
    function(data) {
      $("#agents").append(
        $("<option></option>")
          .html("Loading...")
          .attr("value", 0)
      );

      $("#agents").empty();

      if (isEmptyObject(data)) {
        var noneText = $("#none_text").html(); //Trick for catch the translate text.

        if (noneText == null) {
          noneText = "None";
        }

        $("#agents").append(
          $("<option></option>")
            .html(noneText)
            .attr("None", "")
            .prop("selected", true)
        );

        return;
      }

      if (typeof $(document).data("text_for_module") != "undefined") {
        $("#agents").append(
          $("<option></option>")
            .html($(document).data("text_for_module"))
            .attr("value", 0)
            .prop("selected", true)
        );
      } else {
        if (typeof data["any_text"] != "undefined") {
          $("#agents").append(
            $("<option></option>")
              .html(data["any_text"])
              .attr("value", 0)
              .prop("selected", true)
          );
        } else {
          var anyText = $("#any_text").html(); //Trick for catch the translate text.

          if (anyText == null) {
            anyText = "Any";
          }

          $("#agents").append(
            $("<option></option>")
              .html(anyText)
              .attr("value", 0)
              .prop("selected", true)
          );
        }
      }
      jQuery.each(data, function(i, val) {
        s = js_html_entity_decode(val);
        $("#agents").append(
          $("<option></option>")
            .html(truncate_string(s, 30, "..."))
            .attr({ value: i, title: s })
        );
        $("#agents").fadeIn("normal");
      });

      if (selected != undefined) $("#agents").attr("value", selected);
      $("#agents").removeAttr("disabled");
    },
    "json"
  );
}

/**
 * Fill up select box with id "module" with modules after agent has been selected, but this not empty the select box.s
 *
 * @param event that has been triggered
 * @param id_agent Agent ID that has been selected
 * @param selected Which module(s) have to be selected
 */
function agent_changed_by_multiple_agents_id(event, id_agent, selected) {
  var idAgents = Array();

  jQuery.each($("#id_agents option:selected"), function(i, val) {
    //val() because the var is same <option val="NNN"></option>
    idAgents.push($(val).val());
  });

  $("#module").attr("disabled", 1);
  $("#module").empty();
  $("#module").append(
    $("<option></option>")
      .html("Loading...")
      .attr("value", 0)
  );
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_agent_modules_json_for_multiple_agents_id: 1,
      "id_agent[]": idAgents
    },
    function(data) {
      $("#module").empty();

      if (typeof $(document).data("text_for_module") != "undefined") {
        $("#module").append(
          $("<option></option>")
            .html($(document).data("text_for_module"))
            .attr("value", 0)
            .prop("selected", true)
        );
      } else {
        if (typeof data["any_text"] != "undefined") {
          $("#module").append(
            $("<option></option>")
              .html(data["any_text"])
              .attr("value", 0)
              .prop("selected", true)
          );
        } else {
          var anyText = $("#any_text").html(); //Trick for catch the translate text.

          if (anyText == null) {
            anyText = "Any";
          }

          $("#module").append(
            $("<option></option>")
              .html(anyText)
              .attr("value", 0)
              .prop("selected", true)
          );
        }
      }

      jQuery.each(data, function(i, val) {
        s = js_html_entity_decode(val["nombre"]);
        //$('#module').append ($('<option></option>').html (s).attr ("value", val));
        $("#module").append(
          $("<option></option>")
            .html(s)
            .attr("value", val["id_agente_modulo"])
        );
        $("#module").fadeIn("normal");
      });
      if (selected != undefined) $("#module").attr("value", selected);
      $("#module").removeAttr("disabled");
    },
    "json"
  );
}

function post_process_select_init(name) {
  // Manual mode is hidden by default

  $("#" + name + "_manual").hide();
  $("#" + name + "_default").show();
}

function post_process_select_init_inv(name) {
  $("#" + name + "_manual").show();
  $("#" + name + "_default").hide();
}

function post_process_select_init_unit(name, selected) {
  // Manual mode is hidden by default

  $("#" + name + "_manual").hide();
  $("#" + name + "_default").show();

  if (selected != "") {
    var select_or_text = false;
    $("#" + name + "_select option").each(function(i, item) {
      if ($(item).val() == selected) {
        select_or_text = true;
        return false;
      }
    });

    if (select_or_text) {
      $("#" + name + "_select option[value='" + selected + "']").attr(
        "selected",
        true
      );
      $("#text-" + name + "_text").val("");
    } else {
      $("#" + name + "_select option[value=0]").attr("selected", true);
      $("#" + name + "_default").hide();
      $("#" + name + "_manual").show();
    }
  } else {
    $("#" + name + "_select option[value=0]").attr("selected", true);
    $("#" + name + "_default").hide();
    $("#" + name + "_manual").show();
  }

  $("#" + name + "_select").change(function() {
    var value = $("#" + name + "_select").val();
    $("#" + name + "_select option[value='" + value + "']").attr(
      "selected",
      true
    );
  });
}

function post_process_select_events_unit(name, selected) {
  $("." + name + "_toggler").click(function() {
    var value = $("#text-" + name + "_text").val();

    var count = $("#" + name + "_select option").filter(function(i, item) {
      if ($(item).val() == value) return true;
      else return false;
    }).length;

    if (count != 1) {
      $("#" + name + "_select").append(
        $("<option>")
          .val(value)
          .text(value)
      );
    }

    $("#" + name + "_select option")
      .filter(function(i, item) {
        if ($(item).val() == value) return true;
        else return false;
      })
      .prop("selected", true);

    toggleBoth(name);
    $("#text-" + name + "_text").focus();
  });

  // When select a default period, is setted in seconds
  $("#" + name + "_select").change(function() {
    var value = $("#" + name + "_select").val();

    $("." + name).val(value);
    $("#text-" + name + "_text").val(value);
  });

  $("#text-" + name + "_text").keyup(function() {
    var value = $("#text-" + name + "_text").val();
    $("." + name).val(value);
  });
}

function post_process_select_events(name) {
  $("." + name + "_toggler").click(function() {
    var value = $("#text-" + name + "_text").val();
    var count = $("#" + name + "_select option").filter(function(i, item) {
      if (Number($(item).val()) == Number(value)) return true;
      else return false;
    }).length;

    if (count < 1) {
      $("#" + name + "_select").append(
        $("<option>")
          .val(value)
          .text(value)
      );
    }

    $("#" + name + "_select option")
      .filter(function(i, item) {
        if (Number($(item).val()) == Number(value)) return true;
        else return false;
      })
      .prop("selected", true);

    //~ $('#' + name + '_select').val(value);

    toggleBoth(name);
    $("#text-" + name + "_text").focus();
  });

  // When select a default period, is setted in seconds
  $("#" + name + "_select").change(function() {
    var value = $("#" + name + "_select").val();

    $("." + name).val(value);
    $("#text-" + name + "_text").val(value);
  });

  $("#text-" + name + "_text").keyup(function() {
    var value = $("#text-" + name + "_text").val();

    if (isNaN(value)) {
      value = 0;
      $("#text-" + name + "_text").val(value);
    } else {
      $("." + name).val(value);
    }
  });
}

/**
 * Init values for html_extended_select_for_time
 *
 * This function initialize the values of the control
 *
 * @param name string with the name of the select for time
 * @param allow_zero bool Allow the use of the value zero
 */
function period_select_init(name, allow_zero) {
  // Manual mode is hidden by default
  $("#" + name + "_manual").css("display", "none");
  $("#" + name + "_default").css("display", "flex");
  // If the text input is empty, we put on it 5 minutes by default
  if ($("#text-" + name + "_text").val() == "") {
    $("#text-" + name + "_text").val(300);
    // Set the value in the hidden field too
    $("." + name).val(300);
    if ($("#" + name + "_select option:eq(0)").val() == 0) {
      $("#" + name + "_select option:eq(2)").prop("selected", true);
    } else {
      $("#" + name + "_select option:eq(1)").prop("selected", true);
    }
  } else if ($("#text-" + name + "_text").val() == 0 && allow_zero == 1) {
    $("#" + name + "_units option:last").prop("selected", false);
    $("#" + name + "_manual").css("display", "flex");
    $("#" + name + "_default").css("display", "none");
  }
}

/**
 * Manage events into html_extended_select_for_time
 *
 * This function has all the events to manage the extended select
 * for time
 *
 * @param name string with the name of the select for time
 */
function period_select_events(name) {
  $("." + name + "_toggler").click(function() {
    toggleBoth(name);
    $("#text-" + name + "_text").focus();
  });

  adjustTextUnits(name);

  // When select a default period, is setted in seconds
  $("#" + name + "_select").change(function() {
    var value = $("#" + name + "_select").val();

    if (value == -1) {
      value = 300;
      toggleBoth(name);
      $("#text-" + name + "_text").focus();
    }

    $("." + name).val(value);
    $("#text-" + name + "_text").val(value);
    adjustTextUnits(name);
  });

  // When select a custom units, the default period changes to
  // 'custom' and the time in seconds is calculated into hidden input
  $("#" + name + "_units").change(function() {
    selectFirst(name);
    calculateSeconds(name);
  });

  // When write any character into custom input, it check to convert
  // it to integer and calculate in seconds into hidden input
  $("#text-" + name + "_text").keyup(function() {
    var cleanValue = parseInt($("#text-" + name + "_text").val());
    if (isNaN(cleanValue)) {
      cleanValue = "";
    }

    $("#text-" + name + "_text").val(cleanValue);

    selectFirst(name + "_select");
    calculateSeconds(name);
  });
}

function period_set_value(name, value) {
  $("#text-" + name + "_text").val(value);
  adjustTextUnits(name);
  calculateSeconds(name);
  selectFirst(name + "_select");
  $("#" + name + "_manual").hide();
  $("#" + name + "_default").show();
}

/**
 *
 * Select first option of a select if is not value=0
 *
 */
function selectFirst(name) {
  if ($("#" + name + " option:eq(0)").val() == 0) {
    $("#" + name + " option:eq(1)").prop("selected", true);
  } else {
    $("#" + name + " option:eq(0)").prop("selected", true);
  }
}

/**
 *
 * Toggle default and manual controls of period control
 * It is done with css function because hide and show do not
 * work properly when the divs are into a hiden div
 *
 */
function toggleBoth(name) {
  if ($("#" + name + "_default").css("display") == "none") {
    $("#" + name + "_default").css("display", "flex");
  } else {
    $("#" + name + "_default").css("display", "none");
  }

  if ($("#" + name + "_manual").css("display") == "none") {
    $("#" + name + "_manual").css("display", "flex");
  } else {
    $("#" + name + "_manual").css("display", "none");
  }
}

/**
 *
 * Calculate the custom time in seconds into hidden input
 *
 */
function calculateSeconds(name) {
  var calculated =
    $("#text-" + name + "_text").val() * $("#" + name + "_units").val();

  $("." + name).val(calculated);
}

/**
 *
 * Update via Javascript an advance selec for time
 *
 */
function period_select_update(name, seconds) {
  $("#text-" + name + "_text").val(seconds);
  adjustTextUnits(name);
  calculateSeconds(name);
  $("#" + name + "_manual").show();
  $("#" + name + "_default").hide();
}

/**
 *
 * Adjust units in the advanced select for time
 *
 */
function adjustTextUnits(name) {
  var restPrev;
  var unitsSelected = false;
  $("#" + name + "_units option").each(function() {
    if ($(this).val() < 0) {
      return;
    }
    var rest = $("#text-" + name + "_text").val() / $(this).val();
    var restInt = parseInt(rest).toString();

    if (rest != restInt && unitsSelected == false) {
      var value_selected = $(
        "#" + name + "_units option:eq(" + ($(this).index() - 1) + ")"
      ).val();
      $("#" + name + "_units").val(value_selected);

      $("#text-" + name + "_text").val(restPrev);
      unitsSelected = true;
    }

    restPrev = rest;
  });

  if (unitsSelected == false) {
    $("#" + name + "_units option:last").prop("selected", true);
    $("#text-" + name + "_text").val(restPrev);
  }

  if ($("#text-" + name + "_text").val() == 0) {
    selectFirst(name + "_units");
  }
}

/**
 * Sidebar function
 * params:
 * 	menuW: $params['width']
 * 	icon_width: $params['icon_width']
 *  position: $params['position']
 *  top_dist: $params['top']
 *  autotop: $params['autotop']
 *  icon_closed: $params['icon_closed']
 * 	icon_open: $params['icon_open']
 * 	homeurl: $config['homeurl']
 *
 **/
function hidded_sidebar(
  position,
  menuW,
  menuH,
  icon_width,
  top_dist,
  autotop,
  right_dist,
  autoright,
  icon_closed,
  icon_open,
  homeurl,
  vertical_mode
) {
  var defSlideTime = 220;
  var visibleMargin = icon_width + 10;
  var hiddenMarginW = menuW - visibleMargin;
  menuH = parseInt(menuH);
  var hiddenMarginH = menuH - visibleMargin;
  var windowWidth = $(window).width();
  var sideClosed = 1;

  if (top_dist == "auto_over") {
    top_dist = $("#" + autotop).offset().top;
  } else if (top_dist == "auto_below") {
    top_dist = $("#" + autotop).offset().top + $("#" + autotop).height();
    switch (position) {
      case "bottom":
        if (vertical_mode == "in") {
          top_dist -= visibleMargin + 10;
        }
    }
  }

  if (right_dist == "auto_right") {
    right_dist = $("#" + autoright).offset().left + $("#" + autoright).width();
  } else if (right_dist == "auto_left") {
    right_dist = $("#" + autoright).offset().left;
  }

  $(document).ready(function() {
    // SET INITIAL POSITION AND SHOW LAYER
    $("#side_layer").css("top", top_dist);
    switch (position) {
      case "left":
        $("#side_layer").css("left", -hiddenMarginW);
        break;
      case "right":
        $("#side_layer").css("left", windowWidth - visibleMargin - 1);
        $("#side_layer").css("width", visibleMargin + "px");
        break;
      case "bottom":
        $("#side_layer").css("left", right_dist - menuW);
        $("#side_layer").css("height", visibleMargin + "px");
        break;
    }
    $("#side_layer").show();

    $("#graph_menu_arrow").click(function() {
      switch (position) {
        case "right":
          if (sideClosed == 0) {
            $("#side_layer").animate(
              {
                width: "-=" + hiddenMarginW + "px",
                left: "+=" + hiddenMarginW + "px"
              },
              defSlideTime
            );
            $("#graph_menu_arrow").attr("src", homeurl + icon_closed);
          } else {
            $("#side_layer").animate(
              {
                width: "+=" + hiddenMarginW + "px",
                left: "-=" + hiddenMarginW + "px"
              },
              defSlideTime
            );
            $("#graph_menu_arrow").attr("src", homeurl + icon_open);
          }
          break;
        case "left":
          if (sideClosed == 1) {
            $("#side_layer").animate(
              { left: "+=" + hiddenMarginW + "px" },
              defSlideTime
            );

            $("#graph_menu_arrow").attr("src", homeurl + icon_closed);
          } else {
            $("#side_layer").animate(
              { left: "-=" + hiddenMarginW + "px" },
              defSlideTime
            );
            $("#graph_menu_arrow").attr("src", homeurl + icon_open);
          }
          break;
        case "bottom":
          if (sideClosed == 0) {
            $("#side_layer").animate(
              {
                height: "-=" + (hiddenMarginH + 10) + "px",
                top: "+=" + hiddenMarginH + "px"
              },
              defSlideTime
            );
            $("#graph_menu_arrow").attr("src", homeurl + icon_closed);
          } else {
            $("#side_layer").animate(
              {
                height: "+=" + (hiddenMarginH - 10) + "px",
                top: "-=" + hiddenMarginH + "px"
              },
              defSlideTime
            );
            $("#graph_menu_arrow").attr("src", homeurl + icon_open);
          }
          break;
      }

      if (sideClosed == 0) {
        //$('#side_top_text').hide();
        //$('#side_body_text').hide();
        //$('#side_bottom_text').hide();
        sideClosed = 1;
      } else {
        $("#side_top_text").show();
        $("#side_body_text").show();
        $("#side_bottom_text").show();
        sideClosed = 0;
      }
    });
  });

  switch (position) {
    case "right":
    case "bottom":
      // Move the right menu if window is resized
      $(window).resize(function() {
        var newWindowWidth = $(window).width();
        var widthVariation = newWindowWidth - windowWidth;
        $("#side_layer").animate({ left: "+=" + widthVariation + "px" }, 0);

        windowWidth = newWindowWidth;
      });
      break;
  }
}

// Function that recover a previously stored value from php code
function get_php_value(value) {
  return $.parseJSON($("#php_to_js_value_" + value).html());
}

function paint_qrcode(text, where, width, height) {
  if (typeof text == "undefined") {
    text = window.location.href;
  } else {
    //null value
    if (isEmptyObject(text)) {
      text = window.location.href;
    }
  }

  if (typeof where == "undefined") {
    where = $("#qrcode_container_image").get(0);
  } else if (typeof where == "string") {
    where = $(where).get(0);
  }

  if (typeof where == "undefined") {
    where = $("#qrcode_container_image").get(0);
  } else if (typeof where == "string") {
    where = $(where).get(0);
  }

  if (typeof width == "undefined") {
    width = 256;
  } else {
    if (typeof width == "object")
      if (isEmptyObject(width)) {
        //null value
        width = 256;
      }
  }

  if (typeof height == "undefined") {
    height = 256;
  } else {
    if (typeof height == "object")
      if (isEmptyObject(height)) {
        //null value
        height = 256;
      }
  }

  $(where).empty();

  var qrcode = qrCode.createQr({
    typeElement: "createImg",
    data: text,
    typeNumber: 5,
    cellSize: 5
  });

  $(where).append(qrcode);
}

function paint_vcard(text, where) {
  if (typeof text == "undefined") {
    text = window.location.href;
  } else {
    //null value
    if (isEmptyObject(text)) {
      text = window.location.href;
    }
  }

  if (typeof where == "undefined") {
    where = $("#qrcode_container_image").get(0);
  } else if (typeof where == "string") {
    where = $(where).get(0);
  }

  if (typeof where == "undefined") {
    where = $("#qrcode_container_image").get(0);
  } else if (typeof where == "string") {
    where = $(where).get(0);
  }

  // version: "3.0",
  // lastName: "Нижинский",
  // middleName: "D",
  // firstName: "Костя",
  // nameSuffix: "JR",
  // namePrefix: "MR",
  // nickname: "Test User",
  // gender: "M",
  // organization: "ACME Corporation",
  // workPhone: "312-555-1212444",
  // homePhone: "312-555-1313333",
  // cellPhone: "312-555-1414111",
  // pagerPhone: "312-555-1515222",
  // homeFax: "312-555-1616",
  // workFax: "312-555-1717",
  // birthday: "20140112",
  // anniversary: "20140112",
  // title: "Crash Test Dummy",
  // role: "Crash Testing",
  // email: "john.doe@testmail",
  // workEmail: "john.doe@workmail",
  // url: "http://johndoe",
  // workUrl: "http://acemecompany/johndoe",
  // homeAddress: {
  //   label: "Home Address",
  //   street: "123 Main Street",
  //   city: "Chicago",
  //   stateProvince: "IL",
  //   postalCode: "12345",
  //   countryRegion: "United States of America"
  // },

  // workAddress: {
  //   label: "Work Address",
  //   street: "123 Corporate Loop\nSuite 500",
  //   city: "Los Angeles",
  //   stateProvince: "CA",
  //   postalCode: "54321",
  //   countryRegion: "California Republic"
  // },

  // source: "http://sourceurl",
  // note: "dddddd",
  // socialUrls: {
  //   facebook: "johndoe",
  //   linkedIn: "johndoe",
  //   twitter: "johndoe",
  //   flickr: "johndoe",
  //   skype: "test_skype",
  //   custom: "johndoe"
  // }

  $(where).empty();

  var qrcode = qrCode.createVCardQr(text, { typeNumber: 30, cellSize: 2 });

  $(where).append(qrcode);
}

function show_dialog_qrcode(dialog, text, where, width, height) {
  if (typeof dialog == "undefined") {
    dialog = "#qrcode_container";
  } else {
    if (typeof dialog == "object")
      if (isEmptyObject(dialog)) {
        //null value
        dialog = "#qrcode_container";
      }
  }

  if (typeof where == "undefined") {
    where = $("#qrcode_container_image").get(0);
  } else if (typeof where == "string") {
    where = $(where).get(0);
  }

  if (typeof width == "undefined") {
    width = 256;
  } else {
    if (typeof width == "object")
      if (isEmptyObject(width)) {
        //null value
        width = 256;
      }
  }

  if (typeof height == "undefined") {
    height = 256;
  } else {
    if (typeof height == "object")
      if (isEmptyObject(height)) {
        //null value
        height = 256;
      }
  }

  paint_qrcode(text, where, 256, 256);

  $(dialog)
    .dialog({ autoOpen: false, modal: true })
    .dialog("open");
}

function openURLTagWindow(url) {
  window.open(
    url,
    "",
    "width=300, height=300, toolbar=no, location=no, directories=no, status=no, menubar=no"
  );
}

/**
 *
 * Inicialize tinyMCE with customized parameters
 *
 * @param added_config  Associative Array. Config to add adding default.
 */

function defineTinyMCE(selector) {
  tinymce.init({
    selector: selector,
    plugins: "preview, searchreplace, table, nonbreaking, link, image",
    promotion: false,
    branding: false
  });
}

function UndefineTinyMCE(textarea_id) {
  tinyMCE.remove(textarea_id);
  $(textarea_id).show("");
}

function toggle_full_value(id) {
  $("#hidden_value_module_" + id).dialog({
    resizable: true,
    draggable: true,
    modal: true,
    height: 200,
    width: 400,
    overlay: {
      opacity: 0.5,
      background: "black"
    }
  });
}

function autoclick_profile_users(actual_level, firts_level, second_level) {
  if ($("#checkbox-" + actual_level).is(":checked")) {
    if (typeof firts_level !== "undefined") {
      var is_checked_firts = $("#checkbox-" + firts_level).is(":checked");
      if (!is_checked_firts) {
        $("#checkbox-" + firts_level).prop("checked", true);
      }
      if (second_level !== false) {
        if (!$("#checkbox-" + second_level).is(":checked")) {
          $("#checkbox-" + second_level).prop("checked", true);
        }
      }
    }
  }
}
/**
 * Auto hides an element and shows it
 * when the user moves the mouse over the body.
 *
 * @param element [Element object] Element object to hide.
 * @param hideTime [integer] ms of the hide timeout.
 *
 * @retval void
 */
var autoHideElement = function(element, hideTime) {
  hideTime = hideTime || 3000;
  var timerRef;
  var isHoverElement = false;

  var showElement = function() {
    $(element).show();
  };
  var hideElement = function() {
    $(element).fadeOut();
  };
  var startHideTimeout = function(msec) {
    showElement();
    timerRef = setTimeout(hideElement, msec);
  };
  var cancelHideTimeout = function() {
    clearTimeout(timerRef);
    timerRef = null;
  };

  var handleBodyMove = function(event) {
    if (isHoverElement) return;
    if (timerRef) cancelHideTimeout();
    startHideTimeout(hideTime);
  };
  var handleElementEnter = function(event) {
    isHoverElement = true;
    cancelHideTimeout();
  };
  var handleElementLeave = function(event) {
    isHoverElement = false;
    startHideTimeout(hideTime);
  };

  // Bind event handlers
  $(element)
    .mouseenter(handleElementEnter)
    .mouseleave(handleElementLeave);
  $("body").mousemove(handleBodyMove);

  // Start hide
  startHideTimeout(hideTime);
};

function htmlEncode(value) {
  // Create a in-memory div, set its inner text (which jQuery automatically encodes)
  // Then grab the encoded contents back out. The div never exists on the page.
  return $("<div/>")
    .text(value)
    .html();
}

function htmlDecode(value) {
  return $("<div/>")
    .html(value)
    .text();
}

function pagination_show_more(params, message) {
  //value input hidden for save limit
  var value_offset = $("#hidden-offset").val();
  //For each execution offset + limit
  var offset = parseInt(value_offset) + params["limit"];
  //save new value innput hidden
  $("#hidden-offset").val(offset);
  //add array value offset
  params["offset"] = offset;

  $.ajax({
    type: "POST",
    url: "ajax.php",
    data: params,
    success: function(data) {
      if (data == "") {
        $("#container_error").empty();
        $("#container_error").append("<h4>" + message + "</h4>");
      } else {
        $("#container_pag").append(data);
      }
    },
    datatype: "html"
  });
}

/*
 *function use d3.js for paint graph
 */
function paint_graph_status(
  min_w,
  max_w,
  min_c,
  max_c,
  inverse_w,
  inverse_c,
  error_w,
  error_c,
  legend_normal,
  legend_warning,
  legend_critical,
  message_error_warning,
  message_error_critical
) {
  //Check if they are numbers
  if (isNaN(min_w)) {
    min_w = 0;
  }
  if (isNaN(max_w)) {
    max_w = 0;
  }
  if (isNaN(min_c)) {
    min_c = 0;
  }
  if (isNaN(max_c)) {
    max_c = 0;
  }

  //if haven't errors
  if (error_w == 0 && error_c == 0) {
    //parse element
    min_w = parseFloat(min_w);
    min_c = parseFloat(min_c);
    max_w = parseFloat(max_w);
    max_c = parseFloat(max_c);

    //inicialize var
    var range_min = 0;
    var range_max = 0;
    var range_max_min = 0;
    var range_max_min = 0;

    //Find the lowest possible value
    if (min_w < 0 || min_c < 0) {
      if (min_w < min_c) {
        range_min = min_w - 100;
      } else {
        range_min = min_c - 100;
      }
    } else if (min_w > 0 || min_c > 0) {
      if (min_w > min_c) {
        range_max_min = min_w;
      } else {
        range_max_min = min_c;
      }
    } else {
      if (min_w < min_c) {
        range_min = min_w - 100;
      } else {
        range_min = min_c - 100;
      }
    }

    //Find the maximum possible value
    if (max_w > max_c) {
      range_max = max_w + 100 + range_max_min;
    } else {
      range_max = max_c + 100 + range_max_min;
    }

    //Controls whether the maximum = 0 is infinite
    if ((max_w == 0 || max_w == 0.0) && min_w != 0) {
      max_w = range_max;
    }
    if ((max_c == 0 || max_c == 0.0) && min_c != 0) {
      max_c = range_max;
    }

    //Scale according to the position
    position = 200 / (range_max - range_min);

    //axes
    var yScale = d3.scale
      .linear()
      .domain([range_min, range_max])
      .range([100, -100]);

    var yAxis = d3.svg
      .axis()
      .scale(yScale)
      .orient("left");

    //create svg
    var svg = d3.select("#svg_dinamic");
    //delete elements
    svg.selectAll("g").remove();

    var width_x = 101;
    var height_x = 50;
    var legend_width_x = 135;
    var legend_height_x = 80;

    svg
      .append("g")
      .attr("transform", "translate(100, 150)")
      .attr("class", "invert_filter")
      .call(yAxis);

    //legend Normal text
    svg
      .append("g")
      .attr("width", 300)
      .attr("height", 300)
      .append("text")
      .attr("x", legend_width_x + 15)
      .attr("y", legend_height_x - 20)
      .attr("fill", "black")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .attr("class", "invert_filter")
      .html(legend_normal)
      .style("text-anchor", "first")
      .attr("width", 300)
      .attr("height", 300);

    //legend Normal rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_normal")
      .attr("x", legend_width_x)
      .attr("y", legend_height_x - 30)
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", "#82B92E");

    //legend Warning text
    svg
      .append("g")
      .append("text")
      .attr("x", legend_width_x + 15)
      .attr("y", legend_height_x + 5)
      .attr("fill", "black")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .attr("class", "invert_filter")
      .html(legend_warning)
      .style("text-anchor", "first");

    //legend Warning rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_warning")
      .attr("x", legend_width_x)
      .attr("y", legend_height_x - 5)
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", "#ffd731");

    //legend Critical text
    svg
      .append("g")
      .append("text")
      .attr("x", legend_width_x + 15)
      .attr("y", legend_height_x + 30)
      .attr("fill", "black")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .attr("class", "invert_filter")
      .html(legend_critical)
      .style("text-anchor", "first");

    //legend critical rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_critical")
      .attr("x", legend_width_x)
      .attr("y", legend_height_x + 20)
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", "#e63c52");

    //styles for number and axes
    svg
      .selectAll("g .domain")
      .style("stroke-width", 2)
      .style("fill", "none")
      .style("stroke", "black");

    svg
      .selectAll("g .tick text")
      .style("font-size", "9pt")
      .style("font-weight", "initial");

    //estatus normal
    svg
      .append("g")
      .append("rect")
      .attr("id", "status_rect")
      .attr("x", width_x)
      .attr("y", height_x)
      .attr("width", 20)
      .attr("height", 200)
      .style("fill", "#82B92E");

    //controls the inverse warning
    if (inverse_w == 0) {
      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "warning_rect")
        .attr("x", width_x)
        .attr(
          "y",
          height_x + (range_max - min_w) * position - (max_w - min_w) * position
        )
        .attr("width", 20)
        .attr("height", (max_w - min_w) * position)
        .style("fill", "#ffd731");
    } else {
      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "warning_rect")
        .attr("x", width_x)
        .attr("y", height_x + 200 - (min_w - range_min) * position)
        .attr("width", 20)
        .attr("height", (min_w - range_min) * position)
        .style("fill", "#ffd731");

      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "warning_inverse_rect")
        .attr("x", width_x)
        .attr("y", height_x)
        .attr("width", 20)
        .attr(
          "height",
          (range_max - min_w) * position - (max_w - min_w) * position
        )
        .style("fill", "#ffd731");
    }
    //controls the inverse critical
    if (inverse_c == 0) {
      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "critical_rect")
        .attr("x", width_x)
        .attr(
          "y",
          height_x + (range_max - min_c) * position - (max_c - min_c) * position
        )
        .attr("width", 20)
        .attr("height", (max_c - min_c) * position)
        .style("fill", "#e63c52");
    } else {
      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "critical_rect")
        .attr("x", width_x)
        .attr("y", height_x + 200 - (min_c - range_min) * position)
        .attr("width", 20)
        .attr("height", (min_c - range_min) * position)
        .style("fill", "#e63c52");
      svg
        .append("g")
        .append("rect")
        .transition()
        .duration(600)
        .attr("id", "critical_inverse_rect")
        .attr("x", width_x)
        .attr("y", height_x)
        .attr("width", 20)
        .attr(
          "height",
          (range_max - min_c) * position - (max_c - min_c) * position
        )
        .style("fill", "#e63c52");
    }
  } else {
    d3.select("#svg_dinamic rect").remove();
    //create svg
    var svg = d3.select("#svg_dinamic");
    svg.selectAll("g").remove();

    width_x = 10;
    height_x = 50;

    //message error warning
    if (error_w == 1) {
      $("#text-max_warning").addClass("input_error");
      svg
        .append("g")
        .append("text")
        .attr("x", width_x)
        .attr("y", height_x)
        .attr("fill", "black")
        .style("font-weight", "bold")
        .style("font-size", 14)
        .style("fill", "red")
        .html(message_error_warning)
        .style("text-anchor", "first");
    }
    //message error critical
    if (error_c == 1) {
      $("#text-max_critical").addClass("input_error");
      svg
        .append("g")
        .append("text")
        .attr("x", width_x)
        .attr("y", height_x)
        .attr("fill", "black")
        .style("font-weight", "bold")
        .style("font-size", 14)
        .style("fill", "red")
        .html(message_error_critical)
        .style("text-anchor", "first");
    }
  }
}

function round_with_decimals(value, multiplier) {
  // Default values
  if (typeof multiplier === "undefined") multiplier = 1;

  // Return non numeric types without modification
  if (typeof value !== "number" || isNaN(value)) {
    return value;
  }

  if (value * multiplier == 0) return 0;
  if (Math.abs(value) * multiplier >= 1) {
    return Math.round(value * multiplier) / multiplier;
  }
  return round_with_decimals(value, multiplier * 10);
}

/**
 * Display a confirm dialog box
 *
 * @param string Text to display
 * @param string Ok button text
 * @param string Cancel button text
 * @param function Callback to action when ok button is pressed
 */
function display_confirm_dialog(message, ok_text, cancel_text, ok_function) {
  // Clean function to close the dialog
  var clean_function = function() {
    $("#pandora_confirm_dialog_text").hide();
    $("#pandora_confirm_dialog_text").remove();
  };

  // Modify the ok function to close the dialog too
  var ok_function_clean = function() {
    ok_function();
    clean_function();
  };

  var buttons_obj = {};
  buttons_obj[cancel_text] = clean_function;
  buttons_obj[ok_text] = ok_function_clean;

  // Display the dialog
  $("body").append(
    '<div id="pandora_confirm_dialog_text"><h3>' + message + "</h3></div>"
  );
  $("#pandora_confirm_dialog_text").dialog({
    resizable: false,
    draggable: true,
    modal: true,
    dialogClass: "pandora_confirm_dialog",
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: true,
    modal: true,
    buttons: buttons_obj
  });
}

function ellipsize(str, max, ellipse) {
  if (max == null) max = 140;
  if (ellipse == null) ellipse = "…";

  return str.trim().length > max ? str.substr(0, max).trim() + ellipse : str;
}

function uniqId() {
  var randomStr =
    Math.random()
      .toString(36)
      .substring(2, 15) +
    Math.random()
      .toString(36)
      .substring(2, 15);

  return randomStr;
}

/**
 * Function for AJAX request.
 *
 * @param {string} id Id container append data.
 * @param {json} settings Json with settings.
 *
 * @return {void}
 */
function ajaxRequest(id, settings) {
  $.ajax({
    type: settings.type,
    dataType: settings.html,
    url: settings.url,
    data: settings.data,
    success: function(data) {
      $("#" + id).append(data);
    }
  });
}

function progressBarSvg(option) {
  var svgNS = "http://www.w3.org/2000/svg";
  // SVG container.
  var svg = document.createElementNS(svgNS, "svg");

  var backgroundRect = document.createElementNS(svgNS, "rect");
  backgroundRect.setAttribute("fill", option.color);
  backgroundRect.setAttribute("fill-opacity", "0.5");
  backgroundRect.setAttribute("width", "100%");
  backgroundRect.setAttribute("height", "100%");
  backgroundRect.setAttribute("rx", "5");
  backgroundRect.setAttribute("ry", "5");
  var progressRect = document.createElementNS(svgNS, "rect");
  progressRect.setAttribute("fill", option.colorfill);
  progressRect.setAttribute("fill-opacity", "1");
  progressRect.setAttribute("width", option.start + "%");
  progressRect.setAttribute("height", "100%");
  progressRect.setAttribute("rx", "5");
  progressRect.setAttribute("ry", "5");
  var text = document.createElementNS(svgNS, "text");
  text.setAttribute("text-anchor", "middle");
  text.setAttribute("alignment-baseline", "middle");
  text.setAttribute("font-size", "15");
  text.setAttribute("font-family", "arial");
  text.setAttribute("font-weight", "bold");
  text.setAttribute("transform", `translate(10, 17.5)`);
  text.setAttribute("fill", "green");

  //if (this.props.valueType === "value") {
  //    text.style.fontSize = "6pt";
  //
  //    text.textContent = this.props.unit
  //    ? `${formatValue} ${this.props.unit}`
  //    : `${formatValue}`;
  //} else {
  //    text.textContent = `${progress}%`;
  //}

  svg.setAttribute("width", "100%");
  svg.setAttribute("height", "100%");
  svg.append(backgroundRect, progressRect, text);

  return svg;
}

// eslint-disable-next-line no-unused-vars
function inArray(needle, haystack) {
  var length = haystack.length;
  for (var i = 0; i < length; i++) {
    if (haystack[i] == needle) return true;
  }
  return false;
}

// eslint-disable-next-line no-unused-vars
function agent_multiple_change(e, info) {
  info = JSON.parse(atob(info));
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_modules_group_json: 1,
      selection: $("#" + info.selectionModulesNameId).val(),
      id_agents: $("#" + info.agent_name.replace("[]", "")).val(),
      select_mode: 1
    },
    function(data) {
      var name = info.modules_name.replace("[]", "");
      $("#" + name).html("");
      $("#checkbox-" + name + "-check-all").prop("checked", false);
      if (data) {
        jQuery.each(data, function(id, value) {
          var option = $("<option></option>")
            .attr("value", id)
            .html(value);
          $("#" + name).append(option);
        });
      }
    },
    "json"
  );
}

// eslint-disable-next-line no-unused-vars
function selection_multiple_change(info) {
  info = JSON.parse(atob(info));
  jQuery.post(
    "ajax.php",
    {
      page: "operation/agentes/ver_agente",
      get_modules_group_json: 1,
      id_agents: $("#" + info.agent_name.replace("[]", "")).val(),
      selection: $("#" + info.selectionModulesNameId).val(),
      select_mode: 1
    },
    function(data) {
      var name = info.modules_name.replace("[]", "");
      $("#" + name).html("");
      // Check module all.
      $("#checkbox-" + name + "-check-all").prop("checked", false);
      if (data) {
        jQuery.each(data, function(id, value) {
          var option = $("<option></option>")
            .attr("value", id)
            .html(value);
          $("#" + name).append(option);
        });
      }
    },
    "json"
  );
}

/*
 *  Creates a progressbar.
 *  @param id the id of the div we want to transform in a progressbar.
 *  @param duration the duration of the timer example: '10s'.
 *  @param iteration.
 *  @param callback, optional function which is called when the progressbar reaches 0.
 */
function createProgressTimeBar(id, duration, iteration, callback) {
  // We select the div that we want to turn into a progressbar
  var progressbar = document.getElementById(id);
  progressbar.className = "progressbar";

  // We create the div that changes width to show progress
  var progressbarinner = document.createElement("div");
  progressbarinner.className = "inner";

  // Now we set the animation parameters
  progressbarinner.style.animationDuration = duration;

  progressbarinner.style.animationIterationCount = iteration;

  // Eventually couple a callback
  if (typeof callback === "function") {
    if (iteration === "infinite") {
      progressbarinner.addEventListener("animationiteration", callback);
    } else {
      progressbarinner.addEventListener("animationend", callback);
    }
  }

  // Append the progressbar to the main progressbardiv
  progressbar.appendChild(progressbarinner);

  // When everything is set up we start the animation
  progressbarinner.style.animationPlayState = "running";

  return progressbarinner;
}

function progressTimeBar(id, interval, iteration, callback) {
  var progress = createProgressTimeBar(id, interval + "s", iteration, callback);

  var controls = {
    start: function() {
      progress.style.animationPlayState = "running";
    },
    paused: function() {
      progress.style.animationPlayState = "paused";
    }
  };

  return controls;
}

/**
 * Filter selector item by text based on a text input.
 *
 * @param {string} textbox Text input.
 *
 * @return {void}
 */
$.fn.filterByText = function(textbox) {
  var select = this;

  $(textbox).bind("change keyup", function() {
    var search = $.trim($(textbox).val());
    var regex = new RegExp(search, "gi");

    $(select)
      .find("option")
      .each(function() {
        if (
          $(this)
            .text()
            .match(regex) !== null
        ) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
  });
};

/**
 * Confirm Dialog for API token renewal request.
 *
 * @param {string} title Title for show.
 * @param {string} message Message for show.
 * @param {string} form Form to attach renewAPIToken element.
 */
function renewAPIToken(title, message, form) {
  confirmDialog({
    title: title,
    message: message,
    onAccept: function() {
      $("#" + form)
        .append("<input type='hidden' name='renewAPIToken' value='1'>")
        .submit();
    }
  });
}

/**
 * Show Dialog for view the API token.
 *
 * @param {string} title Title for show.
 * @param {string} message Base64 encoded message for show.
 */
function showAPIToken(title, message) {
  confirmDialog({
    title: title,
    message: atob(message),
    hideCancelButton: true
  });
}
function loadPasswordConfig(id, value) {
  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/config.ajax",
      token_name: `${value}`,
      no_boolean: 1
    },
    type: "GET",
    dataType: "json",
    success: function(data) {
      $(`#${id}`).val(data);
    }
  });
}

var formatterDataLabelPie = function(value, ctx) {
  let datasets = ctx.chart.data.datasets;
  if (datasets.indexOf(ctx.dataset) === datasets.length - 1) {
    let sum = datasets[0].data.reduce((a, b) => parseInt(a) + parseInt(b), 0);
    let percentage = ((value * 100) / sum).toFixed(1) + "%";
    return percentage;
  }
};

var formatterDataHorizontalBar = function(value, ctx) {
  let datasets = ctx.chart.data.datasets;
  if (datasets.indexOf(ctx.dataset) === datasets.length - 1) {
    let sum = datasets[0].data.reduce(
      (a, b) => {
        if (a != undefined && b != undefined) {
          return { x: parseInt(a.x) + parseInt(b.x) };
        }
      },
      { x: 0 }
    );
    let percentage = ((value.x * 100) / sum.x).toFixed(1) + "%";
    return percentage;
  }
};

var formatterDataVerticalBar = function(value, ctx) {
  let datasets = ctx.chart.data.datasets;
  if (datasets.indexOf(ctx.dataset) === datasets.length - 1) {
    let sum = datasets[0].data.reduce(
      (a, b) => {
        if (a != undefined && b != undefined) {
          return { y: parseInt(a.y) + parseInt(b.y) };
        }
      },
      { y: 0 }
    );
    let percentage = ((value.y * 100) / sum.y).toFixed(1) + "%";
    return percentage;
  }
};

// Show about section
$(document).ready(function() {
  $("#icon_about").click(function() {
    $("#icon_about").addClass("selected");
    // Hidden  tips modal.
    $(".window").css("display", "none");

    jQuery.post(
      "ajax.php",
      {
        page: "include/functions_menu",
        about: "true"
      },
      function(data) {
        $("div.ui-dialog").remove();
        $("#about-div").html("");
        if (data) {
          $("#about-div").html(data);
          openAbout();
        }
      },
      "html"
    );
  });

  function openAbout() {
    $("#about-tabs").dialog({
      // title: "About",
      resizable: false,
      draggable: false,
      modal: true,
      show: {
        effect: "fade",
        duration: 200
      },
      hide: {
        effect: "fade",
        duration: 200
      },
      closeOnEscape: true,
      width: 700,
      height: 450,

      create: function() {
        $("#about-tabs").tabs({});
        $(".ui-dialog-titlebar").remove();

        $("#about-close").click(function() {
          $("#about-tabs").dialog("close");
          $("div.ui-dialog").remove();
          $("#icon_about").removeClass("selected");
        });
      }
    });
  }
});

function close_info_box(id) {
  $("#" + id).fadeOut("slow", function() {
    $("#" + id).remove();
  });
}

function autoclose_info_box(id, autoCloseTime) {
  setTimeout(() => {
    close_info_box(id);
  }, autoCloseTime);
}

function show_hide_password(e, url) {
  let inputPass = e.target.previousElementSibling;

  if (inputPass.type === "password") {
    inputPass.type = "text";
    inputPass.style.backgroundImage = "url(" + url + "/images/disable.svg)";
  } else {
    inputPass.type = "password";
    inputPass.style.backgroundImage = "url(" + url + "/images/enable.svg)";
  }
}

// Add observer to clear value when type attribute changes.
function observerInputPassword(name) {
  const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
      if (mutation.type === "attributes" && mutation.attributeName === "type") {
        mutation.target.value = "";
      }
    });
  });

  Array.from($("input[type=password]")).forEach(function(input) {
    observer.observe(input, { attributes: true });
  });
}

function scrollFunction() {
  if (
    document.body.scrollTop > 400 ||
    document.documentElement.scrollTop > 400
  ) {
    if (document.getElementById("top_btn")) {
      document.getElementById("top_btn").style.display = "block";
    }
  } else {
    if (document.getElementById("top_btn")) {
      document.getElementById("top_btn").style.display = "none";
    }
  }
}

// When the user clicks on the button, scroll to the top of the document.
function topFunction() {
  /*
   * Safari.
   * document.body.scrollTop = 0;
   * For Chrome, Firefox, IE and Opera.
   * document.documentElement.scrollTop = 0;
   */

  $("HTML, BODY").animate(
    {
      scrollTop: 0
    },
    500
  );
}

function menuActionButtonResizing() {
  $("#principal_action_buttons").attr(
    "style",
    "width: calc(100% - " + $("#menu_full").width() + "px);"
  );
}
