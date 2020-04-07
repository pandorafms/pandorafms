/* 
  global $ 
  global jQuery
*/

/**
 * Function for create modal with progress task
 * and recalculate 3 second.
 * @param {int} id
 * @param {string} name
 */
function progress_task_list(id, title) {
  var timeoutRef = null;
  var xhr = null;
  var $elem = $("#progress_task_" + id);

  $elem
    .hide()
    .empty()
    .dialog({
      title: title,
      autoOpen: false,
      modal: false,
      resizable: false,
      draggable: true,
      closeOnEscape: true,
      width: 800,
      height: 600,
      close: function() {
        if (xhr != null) xhr.abort();
        if (timeoutRef != null) clearTimeout(timeoutRef);
      }
    });

  // Function var.
  var handleFetchTaskList = function(err, data) {
    if (err) {
      console.error(err);
    }
    if (data.error) {
      // TODO: Show info about the problem.
      $elem.html(data.error);
    } else {
      $elem.html(data.html);
    }

    if (!$elem.dialog("isOpen")) $elem.dialog("open");
  };

  if (!$elem.dialog("isOpen"))
    timeoutRef = setInterval(function() {
      xhr = fetchTaskList(id, handleFetchTaskList);
    }, 3000);

  xhr = fetchTaskList(id, handleFetchTaskList);
}

/**
 * Function that performs ajax request to return
 * the progress of the task.
 * @param {int} id Id task.
 * @param {function} callback Function callback.
 */
function fetchTaskList(id, callback) {
  return jQuery.ajax({
    data: {
      page: "godmode/servers/discovery",
      wiz: "tasklist",
      method: "progressTaskDiscovery",
      id: id
    },
    type: "POST",
    url: $("#ajax-url").val(),
    dataType: "json",
    success: function(data) {
      callback(null, data);
    },
    error: function() {
      callback(new Error("cannot fetch the list"));
    }
  });
}

function show_map(id, name) {
  var myPos = ["center" / 2, 1];
  $("#map_task")
    .empty()
    .hide()
    .append("<p>Loading map</p>")
    .dialog({
      title: "Task: " + name,
      resizable: true,
      draggable: true,
      modal: false,
      width: 900,
      height: 550,
      position: { my: "center", at: "center", of: window }
    })
    .show();

  jQuery.ajax({
    data: {
      page: "godmode/servers/discovery",
      wiz: "tasklist",
      method: "taskShowmap",
      id: id
    },
    type: "POST",
    url: $("#ajax-url").val(),
    dataType: "html",
    success: function(data) {
      $("#map_task")
        .empty()
        .append(data);
    }
  });
}

function show_review(id, name) {
  load_modal({
    target: $("#task_review"),
    form: "review",
    url: $("#ajax-url").val(),
    modal: {
      title: "Review " + name,
      ok: "OK",
      cancel: "Cancel"
    },
    ajax_callback: function(data) {
      var title = $("#success-str").val();
      var text = "";
      var failed = 0;
      try {
        data = JSON.parse(data);
        text = data["result"];
      } catch (err) {
        title = $("#failed-str").val();
        text = err.message;
        failed = 1;
      }
      if (!failed && data["error"] != undefined) {
        title = $("#failed-str").val();
        text = data["error"];
        failed = 1;
      }
      if (data["report"] != undefined) {
        data["report"].forEach(function(item) {
          text += "<br>" + item;
        });
      }

      $("#msg").empty();
      $("#msg").html(text);
      $("#msg").dialog({
        width: 450,
        position: {
          my: "center",
          at: "center",
          of: window,
          collision: "fit"
        },
        maxHeight: 400,
        title: title,
        buttons: [
          {
            class:
              "ui-widget ui-state-default ui-corner-all ui-button-text-only sub ok submit-next",
            text: "OK",
            click: function(e) {
              if (!failed) {
                $(".ui-dialog-content").dialog("close");
                $("#task_review").empty();
                location.reload();
              } else {
                $(this).dialog("close");
              }
            }
          }
        ]
      });
    },
    extradata: [
      {
        name: "id",
        value: id
      },
      {
        name: "wiz",
        value: "tasklist"
      }
    ],
    onshow: {
      page: "godmode/servers/discovery",
      method: "showTaskReview",
      maxHeight: 800
    },
    onsubmit: {
      page: "godmode/servers/discovery",
      method: "parseTaskReview"
    }
  });
}

function force_task_run(url) {
  window.location = url;
}

function force_task(url, ask) {
  if (ask != undefined) {
    confirmDialog({
      title: ask.title,
      message: ask.message,
      onAccept: function() {
        force_task_run(url);
      }
    });
  } else {
    force_task_run(url);
  }
}
