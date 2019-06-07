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
      draggable: false,
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
      // TODO: Show info about the problem.
    }

    $elem.html(data.html);
    if (!$elem.dialog("isOpen")) $elem.dialog("open");

    if (data.status != -1) {
      timeoutRef = setTimeout(function() {
        xhr = fetchTaskList(id, handleFetchTaskList);
      }, 3000);
    }
  };

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
      page: "include/ajax/task_list.ajax",
      progress_task_discovery: 1,
      id: id
    },
    type: "POST",
    url: "ajax.php",
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
  $("#map_task")
    .empty()
    .hide()
    .append("<p>Loading map</p>")
    .dialog({
      title: "Task: " + name,
      resizable: true,
      draggable: true,
      modal: false,
      width: 1280,
      height: 700
    })
    .show();

  jQuery.ajax({
    data: {
      page: "include/ajax/task_list.ajax",
      showmap: 1,
      id: id
    },
    type: "POST",
    url: "ajax.php",
    dataType: "html",
    success: function(data) {
      $("#map_task")
        .empty()
        .append(data);
    }
  });
}
