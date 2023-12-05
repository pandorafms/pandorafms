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
  rescaling();

  $(window).on("resize", function() {
    rescaling();
  });
});

function showLabel(element, event, label) {
  $(".label_heatmap").remove();
  const tooltip = $(document.createElement("div"));
  tooltip.html(label);
  tooltip.attr("class", "label_heatmap");
  $("#heatmap-group").append(tooltip);
  var x = event.clientX;
  var y = event.clientY;
  tooltip.attr(
    "style",
    "position: fixed; top:" + (y + 15) + "px; left:" + (x + 20) + "px;"
  );
}

function hideLabel() {
  $(".label_heatmap").remove();
}

function rescaling() {
  if (window.innerWidth < 1300) {
    $(".trigger-100").attr("style", "width: 100%;");
    $(".trigger-100").addClass("br-b");
  } else {
    $(".trigger-100").removeAttr("style");
    $(".trigger-100").removeClass("br-b");
  }
}
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

function redirectStatus(e, element) {
  if (element.length > 0) {
    switch (e.chart.legend.legendItems[element[0].index].text) {
      case "Critical":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=1&sort_field=&sort=none&pure=`
        );
        break;

      case "Warning":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=2&sort_field=&sort=none&pure=`
        );
        break;

      case "Unknown":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=3&sort_field=&sort=none&pure=`
        );
        break;

      case "Not init":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=5&sort_field=&sort=none&pure=`
        );
        break;

      case "Normal":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=0&sort_field=&sort=none&pure=`
        );
        break;

      default:
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/status_monitor&refr=0&ag_group=0&ag_freestring=&module_option=1&ag_modulename=&moduletype=&datatype=&status=-1&sort_field=&sort=none&pure=`
        );
        break;
    }
  }
}

function redirectHeatmap(view, id, id_agente = 0) {
  switch (view) {
    case "group":
      window.location.assign(
        `index.php?sec=view&sec2=godmode/groups/tactical&id_group=${id}`
      );
      break;

    case "agent":
      window.location.assign(
        `index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=${id}`
      );
      break;

    case "module":
      if (id_agente > 0) {
        window.location.assign(
          `index.php?sec=gagente&sec2=godmode/agentes/configurar_agente&id_agente=${id_agente}&tab=module&id_agent_module=${id}&edit_module=1`
        );
      }
      break;

    default:
      break;
  }
}

function redirectAgentStatus(e, element) {
  if (element.length > 0) {
    switch (e.chart.legend.legendItems[element[0].index].text) {
      case "No monitors":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente`
        );
        break;

      case "CRITICAL":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente&status=1`
        );
        break;

      case "WARNING":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente&status=2`
        );
        break;

      case "UKNOWN":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente&status=3`
        );
        break;

      case "NORMAL":
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente&status=0`
        );
        break;

      default:
        window.location.assign(
          `index.php?sec=view&sec2=operation/agentes/estado_agente`
        );
        break;
    }
  }
}
