/* globals $ confirmDialog uniqId showMsg*/
function parse_alert_command(command, classs) {
  if (classs == "recovery") {
    classs = "fields_recovery";
  } else {
    classs = "fields";
  }

  var nfield = 1;
  $("." + classs).each(function() {
    // Only render values different from ''
    var field = "_field" + nfield + "_";
    var regex = new RegExp(field, "gi");
    if ($(this).val() == "") {
      if (
        classs == "fields_recovery" &&
        $($(".fields")[nfield - 1]).val() != ""
      ) {
        command = command.replace(
          regex,
          "[RECOVER]" + $($(".fields")[nfield - 1]).val()
        );
      }
    } else if (
      $(this).css("-webkit-text-security") == "disc" ||
      $(this).css("font-family") == "text-security-disc"
    ) {
      var hidden_character = "*";
      var hidden_string = hidden_character.repeat($(this).val().length);

      command = command.replace(regex, hidden_string);
    } else {
      command = command.replace(regex, $(this).val());
    }
    nfield++;
  });

  return command;
}

// eslint-disable-next-line no-unused-vars
function render_command_preview(original_command) {
  $("#textarea_command_preview").html(
    parse_alert_command(original_command, "")
  );
}

// eslint-disable-next-line no-unused-vars
function render_command_recovery_preview(original_command) {
  $("#textarea_command_recovery_preview").html(
    parse_alert_command(original_command, "recovery")
  );
}

// eslint-disable-next-line no-unused-vars
function render_command_description(command_description) {
  if (command_description != "") {
    command_description = "<br>" + command_description;
  }
  $("#command_description").html(command_description);
}

// eslint-disable-next-line no-unused-vars
function load_templates_alerts_special_days(settings) {
  confirmDialog({
    title: settings.title,
    message: function() {
      var id = "div-" + uniqId();
      $.ajax({
        method: "post",
        url: settings.url,
        data: {
          page: settings.page,
          method: "drawAlertTemplates",
          date: settings.date,
          id_group: settings.id_group,
          day_code: settings.day_code,
          id_calendar: settings.id_calendar
        },
        datatype: "html",
        success: function(data) {
          $("#" + id)
            .empty()
            .html(data);
        },
        error: function(e) {
          showMsg(e);
        }
      });

      return "<div id ='" + id + "'>" + settings.loading + "</div>";
    },
    ok: settings.btn_ok_text,
    cancel: settings.btn_cancel_text,
    onAccept: function() {
      $("#" + settings.name_form).submit();
    },
    size: 750,
    maxHeight: 500
  });
}
