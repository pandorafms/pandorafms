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
