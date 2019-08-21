var max_events;
var bg;
$(document).ready(function() {
  max_events = localStorage["events"];
  if (localStorage["events"] == undefined) {
    localStorage["events"] = "20";
  }
  bg = chrome.extension.getBackgroundPage();

  // Display the information
  if (bg.fetchEvents().length == 0) {
    showError("No events");
  } else {
    showEvents();
  }

  // Adding buttons listeners
  document.getElementById("m_refresh").addEventListener("click", mrefresh);

  // Added listener to background messages
  chrome.runtime.onMessage.addListener(function(message, sender, sendResponse) {
    switch (message.text) {
      case "FETCH_EVENTS":
        setSpinner();
        //$('div.b').hide();
        break;
      case "FETCH_EVENTS_SUCCESS":
        unsetSpinner();
        showEvents();
        break;
      case "FETCH_EVENTS_DATA_ERROR":
        unsetSpinner();
        showError("Error in fetching data!! Check your internet connection");
        break;
      case "FETCH_EVENTS_URL_ERROR":
        unsetSpinner();
        showError(
          "Configure ip address,API password, user name and password with correct values"
        );
        break;
      default:
        console.log("Unrecognized message: ", message.text);
        break;
    }
  });
});

function setSpinner() {
  $("#refr_img_id").attr("src", "images/spinny.gif");
}

function unsetSpinner() {
  $("#refr_img_id").attr("src", "images/refresh.png");
}

function clearError() {
  $(".error").hide();
  $(".error a").text("");
  $(".result").css("height", null);
}

function showError(text) {
  $(".error a").text(text);
  $(".error").show();
  $(".result").height(420);
}

function showEvents() {
  clearError();
  $("#events").empty();
  var e_refr = document.getElementById("event_temp");
  if (e_refr) {
    wrapper.removeChild(e_refr);
  }
  var allEvents = bg.fetchEvents();
  var notVisitedEvents = bg.fetchNotVisited();
  var eve = document.createElement("div");
  eve.id = "event_temp";
  eve.setAttribute("class", "b");

  var i = 0;
  if (allEvents.length > 0) {
    while (i < max_events && i < allEvents.length) {
      var eve_title = document.createElement("div");
      eve_title.id = "e_" + i + "_" + allEvents[i]["id"];
      var img = document.createElement("img");
      img.src = "images/plus.png";
      img.className = "pm";
      img.id = "i_" + i + "_" + allEvents[i]["id"];
      eve_title.appendChild(img);
      var div_empty = document.createElement("img");
      var a = document.createElement("a");
      var temp_style;

      var agent_url =
        allEvents[i]["agent"] == 0
          ? localStorage["ip_address"] +
            "/index.php?sec=eventos&sec2=operation/events/events"
          : localStorage["ip_address"] +
            "/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" +
            allEvents[i]["agent"];
      a.setAttribute("href", agent_url);
      a.target = "_blank";
      a.className = "a_2_mo";

      a.innerText = allEvents[i]["title"];
      eve_title.setAttribute("class", "event sev-" + allEvents[i]["severity"]);

      if (notVisitedEvents[allEvents[i]["id"]] === true) {
        eve_title.style.fontWeight = 600;
      }

      eve_title.appendChild(a);
      eve.appendChild(eve_title);

      var time = allEvents[i]["date"].split(" ");
      var time_text = time[0] + " " + time[1];

      var p = document.createElement("p");
      var id =
        allEvents[i]["module"] == 0
          ? "."
          : " in the module with Id " + allEvents[i]["module"] + ".";

      p.innerText =
        allEvents[i]["type"] +
        " : " +
        allEvents[i]["source"] +
        ". Event occured at " +
        time_text +
        id;
      p.id = "p_" + i;
      eve_title.appendChild(p);
      i++;
    }

    $("#events").append(eve);

    $("img.pm").click(showHide);
    $("div.b").show();
  } else {
    showError("No events");
  }

  localStorage["new_events"] = 0;
  bg.updateBadge();
}

function showHide() {
  var id = $(this).attr("id");
  // Image id has the form i_<position>_<eventId>
  var nums = id.split("_");
  var pid = "p_" + nums[1];

  // Mark as visited if visited
  if (
    $(this)
      .parent()
      .css("font-weight") == "600"
  ) {
    bg.removeNotVisited(nums[2]);
    $(this)
      .parent()
      .css("font-weight", "");
  }

  // Toggle information
  if ($("#" + pid).css("display") == "none") {
    $("#" + pid).slideDown();
    $(this).attr({ src: "images/minus.png" });
  } else {
    $("#" + pid).slideUp();
    $(this).attr({ src: "images/plus.png" });
  }
}

function mrefresh() {
  localStorage["new_events"] = 0;
  bg.updateBadge();
  clearError();
  bg.resetInterval();
  bg.main();
}
