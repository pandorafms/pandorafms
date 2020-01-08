/* global $ */
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

        if (module_type !== NaN) module_types_excluded.push(module_type);
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
      status_module: module_status
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
      jQuery.each(data, function(i, val) {
        var s = js_html_entity_decode(val);

        $("#module").append(
          $("<option></option>")
            .html(s)
            .attr("value", i)
            .attr("title", s)
        );

        $("#module").fadeIn("normal");
      });
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
        s = js_html_entity_decode(val);
        $("#module").append(
          $("<option></option>")
            .html(s)
            .attr("value", val)
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
        s = js_html_entity_decode(val);
        $("#module").append(
          $("<option></option>")
            .html(s)
            .attr("value", val)
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
            .html(s)
            .attr("value", i)
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
      $("#" + name + "_select option[value=" + selected + "]").attr(
        "selected",
        true
      );
      $("#text-" + name + "_text").val("");
    } else {
      $("#" + name + "_select option[value=none]").attr("selected", true);
      $("#" + name + "_default").hide();
      $("#" + name + "_manual").show();
    }
  } else {
    $("#" + name + "_select option[value=none]").attr("selected", true);
  }

  $("#" + name + "_select").change(function() {
    var value = $("#" + name + "_select").val();
    $("#" + name + "_select option[value=" + value + "]").attr(
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

    if (count != 1) {
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
 */
function period_select_init(name) {
  // Manual mode is hidden by default
  $("#" + name + "_manual").hide();
  $("#" + name + "_default").show();

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
  } else if ($("#text-" + name + "_text").val() == 0) {
    $("#" + name + "_units option:last").prop("selected", false);
    $("#" + name + "_manual").show();
    $("#" + name + "_default").hide();
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
    $("#" + name + "_default").css("display", "inline");
  } else {
    $("#" + name + "_default").css("display", "none");
  }

  if ($("#" + name + "_manual").css("display") == "none") {
    $("#" + name + "_manual").css("display", "inline");
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
      $("#" + name + "_units option:eq(" + ($(this).index() - 1) + ")").prop(
        "selected",
        true
      );
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

  var qrcode = new QRCode(where, {
    text: text,
    width: width,
    height: height,
    colorDark: "#343434",
    colorLight: "#ffffff",
    correctLevel: QRCode.CorrectLevel.M
  });
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

function removeTinyMCE(elementID) {
  if (elementID.length > 0 && !isEmptyObject(tinyMCE))
    tinyMCE.EditorManager.execCommand("mceRemoveControl", true, elementID);
}

function addTinyMCE(elementID) {
  if (elementID.length > 0 && !isEmptyObject(tinyMCE))
    tinyMCE.EditorManager.execCommand("mceAddControl", true, elementID);
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

    width_x = 101;
    height_x = 50;

    svg
      .append("g")
      .attr("transform", "translate(100, 150)")
      .call(yAxis);

    //legend Normal text
    svg
      .append("g")
      .attr("width", 300)
      .attr("height", 300)
      .append("text")
      .attr("x", width_x)
      .attr("y", height_x - 20)
      .attr("fill", "black")
      .style("font-family", "arial")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .html(legend_normal)
      .style("text-anchor", "first")
      .attr("width", 300)
      .attr("height", 300);

    //legend Normal rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_normal")
      .attr("x", width_x + 80)
      .attr("y", height_x - 30)
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", "#82B92E");

    //legend Warning text
    svg
      .append("g")
      .append("text")
      .attr("x", width_x + 100)
      .attr("y", height_x - 20)
      .attr("fill", "black")
      .style("font-family", "arial")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .html(legend_warning)
      .style("text-anchor", "first");

    //legend Warning rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_warning")
      .attr("x", width_x + 185)
      .attr("y", height_x - 30)
      .attr("width", 10)
      .attr("height", 10)
      .style("fill", "#ffd731");

    //legend Critical text
    svg
      .append("g")
      .append("text")
      .attr("x", width_x + 205)
      .attr("y", height_x - 20)
      .attr("fill", "black")
      .style("font-family", "arial")
      .style("font-weight", "bold")
      .style("font-size", "8pt")
      .html(legend_critical)
      .style("text-anchor", "first");

    //legend critical rect
    svg
      .append("g")
      .append("rect")
      .attr("id", "legend_critical")
      .attr("x", width_x + 285)
      .attr("y", height_x - 30)
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
      .attr("width", 300)
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
        .attr("width", 300)
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
        .attr("width", 300)
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
        .attr("width", 300)
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
        .attr("width", 300)
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
        .attr("width", 300)
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
        .attr("width", 300)
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
        .style("font-family", "arial")
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
        .style("font-family", "arial")
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

/**
 * Display a dialog with an image
 *
 * @param {string} icon_name The name of the icon you will display
 * @param {string} icon_path The path to the icon
 * @param {Object} incoming_options All options
 * 		grayed: {bool} True to display the background black
 * 		title {string} 'Logo preview' by default
 */
function logo_preview(icon_name, icon_path, incoming_options) {
  // Get the options
  options = {
    grayed: false,
    title: "Logo preview"
  };
  $.extend(options, incoming_options);

  if (icon_name == "") return;

  $dialog = $("<div></div>");
  $image = $('<img src="' + icon_path + '">');
  $image.css("max-width", "500px").css("max-height", "500px");

  try {
    $dialog
      .hide()
      .html($image)
      .dialog({
        title: options.title,
        resizable: true,
        draggable: true,
        modal: true,
        dialogClass: options.grayed ? "dialog-grayed" : "",
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        minHeight: 1,
        width: $image.width,
        close: function() {
          $dialog.empty().remove();
        }
      })
      .show();
  } catch (err) {
    // console.log(err);
  }
}

// Advanced Form control.
function load_modal(settings) {
  var AJAX_RUNNING = 0;
  var data = new FormData();
  if (settings.extradata) {
    settings.extradata.forEach(function(item) {
      if (item.value != undefined) data.append(item.name, item.value);
    });
  }
  data.append("page", settings.onshow.page);
  data.append("method", settings.onshow.method);
  if (settings.onshow.extradata != undefined) {
    data.append("extradata", JSON.stringify(settings.onshow.extradata));
  }

  if (settings.target == undefined) {
    var uniq = uniqId();
    var div = document.createElement("div");
    div.id = "div-modal-" + uniq;
    div.style.display = "none";

    document.getElementById("main").append(div);

    var id_modal_target = "#div-modal-" + uniq;

    settings.target = $(id_modal_target);
  }

  var width = 630;
  if (settings.onshow.width) {
    width = settings.onshow.width;
  }

  if (settings.modal.overlay == undefined) {
    settings.modal.overlay = {
      opacity: 0.5,
      background: "black"
    };
  }

  settings.target.html("Loading modal...");
  settings.target
    .dialog({
      title: "Loading",
      close: false,
      width: 200,
      buttons: []
    })
    .show();
  var required_buttons = [];
  if (settings.modal.cancel != undefined) {
    //The variable contains a function
    // that is responsible for executing the method it receives from settings
    // which confirms the closure of a modal
    var cancelModal = function() {
      settings.target.dialog("close");
      if (AJAX_RUNNING) return;
      AJAX_RUNNING = 1;
      var formdata = new FormData();

      formdata.append("page", settings.oncancel.page);
      formdata.append("method", settings.oncancel.method);

      $.ajax({
        method: "post",
        url: settings.url,
        processData: false,
        contentType: false,
        data: formdata,
        success: function(data) {
          if (typeof settings.oncancel.callback == "function") {
            settings.oncancel.callback(data);
            settings.target.dialog("close");
          }
          AJAX_RUNNING = 0;
        },
        error: function(data) {
          // console.log(data);
          AJAX_RUNNING = 0;
        }
      });
    };

    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
      text: settings.modal.cancel,
      click: function() {
        if (settings.oncancel != undefined) {
          if (typeof settings.oncancel.confirm == "function") {
            //receive function
            settings.oncancel.confirm(cancelModal);
          } else if (settings.oncancel != undefined) {
            cancelModal();
          }
        } else {
          $(this).dialog("close");
        }
      }
    });
  }

  if (settings.modal.ok != undefined) {
    required_buttons.push({
      class:
        "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
      text: settings.modal.ok,
      click: function() {
        if (AJAX_RUNNING) return;

        if (settings.onsubmit != undefined) {
          if (settings.onsubmit.preaction != undefined) {
            settings.onsubmit.preaction();
          }
          AJAX_RUNNING = 1;
          if (settings.onsubmit.dataType == undefined) {
            settings.onsubmit.dataType = "html";
          }

          var formdata = new FormData();
          if (settings.extradata) {
            settings.extradata.forEach(function(item) {
              if (item.value != undefined)
                formdata.append(item.name, item.value);
            });
          }
          formdata.append("page", settings.onsubmit.page);
          formdata.append("method", settings.onsubmit.method);

          var flagError = false;

          $("#" + settings.form + " :input").each(function() {
            if (this.checkValidity() === false) {
              $(this).attr("title", this.validationMessage);
              $(this).tooltip({
                tooltipClass: "uitooltip",
                position: {
                  my: "right bottom",
                  at: "right top",
                  using: function(position, feedback) {
                    $(this).css(position);
                    $("<div>")
                      .addClass("arrow")
                      .addClass(feedback.vertical)
                      .addClass(feedback.horizontal)
                      .appendTo(this);
                  }
                }
              });
              $(this).tooltip("open");

              var element = $(this);
              setTimeout(
                function(element) {
                  element.tooltip("destroy");
                  element.removeAttr("title");
                },
                3000,
                element
              );

              flagError = true;
            }

            if (this.type == "file") {
              if ($(this).prop("files")[0]) {
                formdata.append(this.name, $(this).prop("files")[0]);
              }
            } else {
              if ($(this).attr("type") == "checkbox") {
                if (this.checked) {
                  formdata.append(this.name, "on");
                }
              } else {
                formdata.append(this.name, $(this).val());
              }
            }
          });

          if (flagError === false) {
            $.ajax({
              method: "post",
              url: settings.url,
              processData: false,
              contentType: false,
              data: formdata,
              dataType: settings.onsubmit.dataType,
              success: function(data) {
                if (settings.ajax_callback != undefined) {
                  if (settings.idMsgCallback != undefined) {
                    settings.ajax_callback(data, settings.idMsgCallback);
                  } else {
                    settings.ajax_callback(data);
                  }
                }
                AJAX_RUNNING = 0;
              }
            });
          } else {
            AJAX_RUNNING = 0;
          }
        } else {
          // No onsumbit configured. Directly close.
          $(this).dialog("close");
        }
      },
      error: function(data) {
        // console.log(data);
        AJAX_RUNNING = 0;
      }
    });
  }

  $.ajax({
    method: "post",
    url: settings.url,
    processData: false,
    contentType: false,
    data: data,
    success: function(data) {
      settings.target.html(data);
      if (settings.onload != undefined) {
        settings.onload(data);
      }
      settings.target.dialog({
        resizable: true,
        draggable: true,
        modal: true,
        title: settings.modal.title,
        width: width,
        overlay: settings.modal.overlay,
        buttons: required_buttons,
        closeOnEscape: false,
        open: function() {
          $(".ui-dialog-titlebar-close").hide();
        },
        close: function() {
          if (id_modal_target != undefined) {
            $(id_modal_target).remove();
          }
        }
      });
    },
    error: function(data) {
      // console.log(data);
    }
  });
}

//Function that shows a dialog box to confirm closures of generic manners. The modal id is random
function confirmDialog(settings) {
  var randomStr = uniqId();

  $("body").append(
    '<div id="confirm_' + randomStr + '">' + settings.message + "</div>"
  );
  $("#confirm_" + randomStr);
  $("#confirm_" + randomStr)
    .dialog({
      title: settings.title,
      close: false,
      width: 350,
      modal: true,
      buttons: [
        {
          text: "Cancel",
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub upd submit-cancel",
          click: function() {
            $(this).dialog("close");
            if (typeof settings.onDeny == "function") settings.onDeny();
          }
        },
        {
          text: "Ok",
          class:
            "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
          click: function() {
            $(this).dialog("close");
            if (typeof settings.onAccept == "function") settings.onAccept();
          }
        }
      ]
    })
    .show();
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
 * Function to show modal with message Validation.
 *
 * @param {json} data Json example:
 * $return = [
 *  'error' => 0 or 1,
 *  'title' => [
 *    Failed,
 *    Success,
 *  ],
 *  'text'  => [
 *    Failed,
 *    Success,
 *  ],
 *];
 * @param {string} idMsg ID div charge modal.
 *
 * @return {void}
 */
function generalShowMsg(data, idMsg) {
  var title = data.title[data.error];
  var text = data.text[data.error];
  var failed = !data.error;

  $("#" + idMsg).empty();
  $("#" + idMsg).html(text);
  $("#" + idMsg).dialog({
    width: 450,
    position: {
      my: "center",
      at: "center",
      of: window,
      collision: "fit"
    },
    title: title,
    buttons: [
      {
        class:
          "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
        text: "OK",
        click: function(e) {
          if (!failed) {
            $(".ui-dialog-content").dialog("close");
          } else {
            $(this).dialog("close");
          }
        }
      }
    ]
  });
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
