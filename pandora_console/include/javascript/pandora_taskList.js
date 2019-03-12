/**
 *
 * @param {*} id
 * @param {*} name
 */
function progress_task_list(id, name, url) {
  var params = [];
  params.push("page=include/ajax/task_list.ajax");
  params.push("progress_task_discovery=1");
  params.push("id=" + id);

  jQuery.ajax({
    data: params.join("&"),
    type: "POST",
    url: (action = url),
    dataType: "html",
    success: function(data) {
      $("#progress_task")
        .hide()
        .empty()
        .append(data)
        .dialog({
          title: "Task: " + name,
          resizable: true,
          draggable: true,
          modal: false,
          width: 600,
          height: 400
        })
        .show();
    }
  });
}

function show_map(id, name, url) {
  var params = [];
  params.push("page=include/ajax/task_list.ajax");
  params.push("showmap=1");
  params.push("id=" + id);

  $("#progress_task")
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
    data: params.join("&"),
    type: "POST",
    url: (action = url),
    dataType: "html",
    success: function(data) {
      $("#progress_task")
        .empty()
        .append(data);
    }
  });
}
