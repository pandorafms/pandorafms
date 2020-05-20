/* global $ */

/**
 * Add modules from available to selected.
 */
function addItems(id, noneStr) {
  $("#available-select-" + id + " :selected")
    .toArray()
    .forEach(function(item) {
      $("#selected-select-" + id).append(item);
    });

  keepSelectClean("available-select-" + id, noneStr);
  keepSelectClean("selected-select-" + id, noneStr);
}

/**
 * Mark all options for given id.
 */
function markAll(id) {
  $("#" + id + " option").prop("selected", true);
}

/**
 * Remove modules from selected back to available.
 */
function removeItems(id, noneStr) {
  $("#selected-select-" + id + " :selected")
    .toArray()
    .forEach(function(item) {
      $("#available-select-" + id).append(item);
    });

  keepSelectClean("available-select-" + id, noneStr);
  keepSelectClean("selected-select-" + id, noneStr);
}

/**
 * 'None' option, if needed.
 */
function keepSelectClean(id, noneStr) {
  $("#" + id + " option[value=0]").remove();

  if ($("#" + id + " option").length == 0) {
    $("#" + id).append(new Option(noneStr, 0));
  }

  $("#" + id + " option").each(function() {
    $(this).prop("selected", false);
  });
}

function filterItems(id, str) {
  // Remove option 0 - None.
  $("#" + id + " option[value=0]").remove();

  // Move not matching elements filtered to tmp-id.
  var tmp = $("#" + id + " option:not(:contains(" + str + "))").toArray();
  tmp.forEach(function(item) {
    $("#tmp-" + id).append(item);
    $(this).remove();
  });

  // Move matching filter back to id.
  tmp = $("#tmp-" + id + " option:contains(" + str + ")").toArray();
  tmp.forEach(function(item) {
    $("#" + id).append(item);
    $(this).remove();
  });
}

function filterAvailableItems(txt, id, noneStr) {
  filterItems("available-select-" + id, txt);
  keepSelectClean("available-select-" + id, noneStr);
}

function filterSelectedItems(txt, id, noneStr) {
  filterItems("selected-select-" + id, txt);
  keepSelectClean("selected-select-" + id, noneStr);
}

function reloadContent(id, url, options, side, noneStr) {
  var current;
  var opposite;

  if (side == "right") {
    current = "selected-select-" + id;
    opposite = "available-select-" + id;
  } else if (side == "left") {
    current = "available-select-" + id;
    opposite = "selected-select-" + id;
  } else {
    console.error("reloadContent bad usage.");
    return;
  }

  var data = JSON.parse(atob(options));
  data.side = side;
  data.group_recursion = $("#checkbox-id-group-recursion-" + current).prop(
    "checked"
  );
  data.group_id = $("#id-group-" + current).val();

  $.ajax({
    method: "post",
    url: url,
    dataType: "json",
    data: data,
    success: function(data) {
      // Cleanup previous content.
      $("#" + current).empty();

      for (var [value, label] of Object.entries(data)) {
        if (
          $("#" + opposite + " option[value=" + value + "]").length == 0 &&
          $("#tmp-" + current + " option[value=" + value + "]").length == 0
        ) {
          // Does not exist in opposite box nor is filtered.
          $("#" + current).append(new Option(label, value));
        }
      }

      keepSelectClean(current, noneStr);
    },
    error: function(data) {
      console.error(data.responseText);
    }
  });
}

$(document).submit(function() {
  // Force select all 'selected' items to send them on submit.
  $("[id*=text-filter-item-selected-")
    .val("")
    .keyup();
  $("[id^=selected-select-] option").prop("selected", true);
});
