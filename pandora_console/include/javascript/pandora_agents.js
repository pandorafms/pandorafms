/* globals $ */
// eslint-disable-next-line no-unused-vars
function agent_manager_add_secondary_groups(
  event,
  id_agent,
  extra_id,
  id_form,
  dictionary
) {
  event.preventDefault();
  var primary_value = $("#grupo").val();
  dictionary = JSON.parse(atob(dictionary));
  // The selected primary value cannot be selected like secondary.
  if (
    $(
      "#secondary_groups" +
        extra_id +
        " option:selected[value=" +
        primary_value +
        "]"
    ).length > 0
  ) {
    alert(dictionary.primary_group);
    return;
  }

  // On agent creation PHP will update the secondary groups table (not via AJAX).
  if (id_agent == 0) {
    agent_manager_add_secondary_groups_ui(extra_id);
    agent_manager_update_hidden_input_secondary(id_form, extra_id);
    return;
  }

  var selected_items = new Array();
  $("#secondary_groups" + extra_id + " option:selected").each(function() {
    selected_items.push($(this).val());
  });

  var data = {
    page: "godmode/agentes/agent_manager",
    id_agent: id_agent,
    groups: selected_items,
    add_secondary_groups: 1
  };

  // Make the AJAX call to update the secondary groups.
  $.ajax({
    type: "POST",
    url: "ajax.php",
    dataType: "html",
    data: data,
    success: function(data) {
      if (data == 1) {
        agent_manager_add_secondary_groups_ui(extra_id);
      } else {
        console.error("Error in AJAX call to add secondary groups");
      }
    },
    error: function(data) {
      console.error(
        "Fatal error in AJAX call to add secondary groups: " + data
      );
    }
  });
}

// eslint-disable-next-line no-unused-vars
function agent_manager_remove_secondary_groups(
  event,
  id_agent,
  extra_id,
  id_form,
  dictionary
) {
  event.preventDefault();

  dictionary = JSON.parse(atob(dictionary));
  // On agent creation PHP will update the secondary groups table (not via AJAX).
  if (id_agent == 0) {
    agent_manager_remove_secondary_groups_ui(dictionary.strNone, extra_id);
    agent_manager_update_hidden_input_secondary(id_form, extra_id);
    return;
  }

  var selected_items = new Array();
  $("#secondary_groups_selected" + extra_id + " option:selected").each(
    function() {
      selected_items.push($(this).val());
    }
  );

  var data = {
    page: "godmode/agentes/agent_manager",
    id_agent: id_agent,
    groups: selected_items,
    remove_secondary_groups: 1
  };

  // Make the AJAX call to update the secondary groups.
  $.ajax({
    type: "POST",
    url: "ajax.php",
    dataType: "html",
    data: data,
    success: function(data) {
      if (data == 1) {
        agent_manager_remove_secondary_groups_ui(dictionary.strNone, extra_id);
      } else {
        console.error("Error in AJAX call to add secondary groups");
      }
    },
    error: function(data) {
      console.error(
        "Fatal error in AJAX call to add secondary groups: " + data
      );
    }
  });
}

// Move from left input to right input.
function agent_manager_add_secondary_groups_ui(extra_id) {
  $("#secondary_groups_selected" + extra_id + " option[value=0]").remove();
  $("#secondary_groups" + extra_id + " option:selected").each(function() {
    $(this)
      .remove()
      .appendTo("#secondary_groups_selected" + extra_id);
  });
}

// Move from right input to left input.
function agent_manager_remove_secondary_groups_ui(strNone, extra_id) {
  // Remove the groups selected if success.
  $("#secondary_groups_selected" + extra_id + " option:selected").each(
    function() {
      $(this)
        .remove()
        .appendTo("#secondary_groups" + extra_id);
    }
  );

  // Add none if empty select.
  if ($("#secondary_groups_selected" + extra_id + " option").length == 0) {
    $("#secondary_groups_selected" + extra_id).append(
      $("<option>", {
        value: 0,
        text: strNone
      })
    );
  }
}

function agent_manager_update_hidden_input_secondary(id_form, extra_id) {
  var groups = [];
  if (!$("#" + id_form + " #secondary_hidden" + extra_id).length) {
    $("#" + id_form).append(
      '<input name="secondary_hidden' +
        extra_id +
        '" type="hidden" id="secondary_hidden' +
        extra_id +
        '">'
    );
  }

  $("#secondary_groups_selected" + extra_id + " option").each(function() {
    groups.push($(this).val());
  });

  $("#secondary_hidden" + extra_id).val(groups.join(","));
}
