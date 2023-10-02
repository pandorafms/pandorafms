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

  $.ajax({
    url: "ajax.php",
    data: {
      page: "include/ajax/general_tactical_view.ajax",
      method: "getEventsStatusValidateGraph",
      class: "Events"
    },
    type: "POST",
    success: function(data) {
      $("#events-status-validate").html(data);
    }
  });

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
