/* global $ */
$(document).ready(function() {
  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/general_tactical_view.ajax",
      method: "getEventsGraph",
      class: "Events"
    },
    type: "POST",
    success: function(data) {
      $("#events-last-24").html(data);
    }
  });

  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/general_tactical_view.ajax",
      method: "getEventsCriticalityGraph",
      class: "Events"
    },
    type: "POST",
    success: function(data) {
      $("#events-criticality").html(data);
    }
  });

  // Prevent that graphs use same name.
  setTimeout(() => {
    $.ajax({
      url: "ajax.php",
      data: {
        page: "include/ajax/general_tactical_view.ajax",
        method: "getEventsStatusGraph",
        class: "Events"
      },
      type: "POST",
      success: function(data) {
        $("#events-status-validate").html(data);
      }
    });
  }, 100);

  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/general_tactical_view.ajax",
      method: "getEventsStatusValidateGraph",
      class: "Events"
    },
    type: "POST",
    success: function(data) {
      $("#events-status-pending-validate").html(data);
    }
  });

  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/general_tactical_view.ajax",
      method: "getStatusHeatMap",
      class: "Groups",
      width: $("#heatmap-group").width() - 50,
      height:
        $("#heatmap-group").height() < 280 ? 280 : $("#heatmap-group").height()
    },
    type: "POST",
    success: function(data) {
      var title = $(data)[1];
      var heatmap = $(data)[0];
      $("#heatmap-group").html(heatmap);
      $("#heatmap-title").html($(title).html());
    }
  });
});

function autoRefresh(interval, id, method, php_class) {
  setInterval(() => {
    $.ajax({
      url: "ajax.php",
      data: {
        page: "include/ajax/general_tactical_view.ajax",
        method: method,
        class: php_class
      },
      type: "POST",
      success: function(data) {
        var content = $(data).html();
        $("#" + id).html(content);
      }
    });
  }, interval);
}
