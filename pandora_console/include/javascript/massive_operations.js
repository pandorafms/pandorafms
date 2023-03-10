/* global $, confirmDialog, showSpinner, hideSpinner */
function massiveOperationValidation(contents, totalCount, limit, thisForm) {
  var output = false;

  // If the amount of changes exceed the limit, the operation stops.
  if (totalCount > limit) {
    showMassiveModal(contents);

    return false;
  } else {
    confirmDialog({
      title: contents.title,
      message: contents.question,
      ok: contents.ok,
      cancel: contents.cancel,
      onAccept: function() {
        showSpinner();
        output = true;
        $("#" + thisForm).submit();
      },
      onDeny: function() {
        hideSpinner();
        return false;
      }
    });
  }

  return output;
}

function showMassiveModal(contents) {
  $("#massive_modal")
    .empty()
    .html(contents.html);
  // Set the title.
  $("#massive_modal").prop("title", contents.title);
  // Build the dialog for show the mesage.
  $("#massive_modal").dialog({
    resizable: true,
    draggable: true,
    modal: true,
    width: 800,
    buttons: [
      {
        text: "OK",
        click: function() {
          hideSpinner();
          $(this).dialog("close");
          return false;
        }
      }
    ],
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: false,
    open: function(event, ui) {
      $(".ui-dialog-titlebar-close").hide();
    }
  });
}

/*
function showMassiveOperationMessage(message) {
  $("#massive_modal")
    .empty()
    .html(message);

  $("#massive_modal").prop("title", "Massive operations");

  $("#massive_modal").dialog({
    resizable: true,
    draggable: true,
    modal: true,
    width: 800,
    buttons: [
      {
        text: "OK",
        click: function() {
          $(this).dialog("close");
          hideSpinner();
        }
      }
    ],
    overlay: {
      opacity: 0.5,
      background: "black"
    },
    closeOnEscape: false,
    open: function(event, ui) {
      $(".ui-dialog-titlebar-close").hide();
    }
  });
}
*/

// eslint-disable-next-line no-unused-vars
function form_controls_massive_operations_agents(metaconsole) {
  // Listeners.
  var recursion;
  $("#checkbox-recursion").click(function() {
    recursion = this.checked ? 1 : 0;
    $("#id_group").trigger("change");
  });

  var disabled;
  $("#disabled").change(function() {
    disabled = this.value;
    $("#id_group").trigger("change");
  });

  if (metaconsole == 1) {
    $("#nodes").change(function() {
      disabled = $("#disabled").val();
    });

    $("#id_group").change(function() {
      disabled = $("#disabled").val();
    });
  }

  var nodes;
  $("#nodes").change(function() {
    nodes = $("#nodes").val();
    $("#id_group").trigger("change");
  });

  $("#status_agents").change(function() {
    $("#id_group").trigger("change");
  });

  // Build data.
  var data = {
    status_agents: function() {
      return $("#status_agents").val();
    },
    agentSelect: "select#id_agents",
    privilege: "AW",
    recursion: function() {
      return recursion;
    },
    disabled: function() {
      return disabled;
    }
  };

  if (metaconsole == 1) {
    data.serialized = true;
    data.serialized_separator = "|";
    data.nodes = function() {
      return nodes;
    };
  }

  // Change agents.
  $("#id_group").pandoraSelectGroupAgent(data);
}
