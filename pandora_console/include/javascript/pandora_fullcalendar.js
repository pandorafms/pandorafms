/* globals $, FullCalendar, uniqId, confirmDialog*/
// eslint-disable-next-line no-unused-vars
function fullCalendarPandora(calendarEl, settings, initialEvents) {
  var calendar = new FullCalendar.Calendar(calendarEl, {
    //height: 200,
    contentHeight: "auto",
    headerToolbar: {
      left: "",
      center: "",
      right: "timeGridWeek,dayGridWeek"
    },
    buttonText: {
      dayGridWeek: settings.simple,
      timeGridWeek: settings.detailed
    },
    dayHeaderFormat: { weekday: "short" },
    initialView: "dayGridWeek",
    navLinks: false,
    selectable: true,
    selectMirror: true,
    slotDuration: "01:00:00",
    slotLabelInterval: "02:00:00",
    snapDuration: "01:00:00",
    slotLabelFormat: {
      hour: "numeric",
      minute: "2-digit",
      hour12: false
    },
    slotMinTime: "00:00:00",
    slotMaxTime: "24:00:00",
    scrollTime: "01:00:00",
    locale: "en-GB",
    //timeZone: "local",
    firstDay: 1,
    eventTimeFormat: {
      hour: "numeric",
      minute: "2-digit",
      hour12: false
    },
    eventColor: "#82b92e",
    editable: true,
    dayMaxEvents: 3,
    events: initialEvents,
    defaultAllDay: false,
    select: function(info) {
      var nextDay = info.start.getDay() === 6 ? 0 : info.start.getDay() + 1;
      if (
        info.start.getDay() == info.end.getDay() ||
        (nextDay == info.end.getDay() && time_format(info.end) == "00:00:00")
      ) {
        recalculate_events(calendar, {}, info.start, info.end, true);
      }
      calendar.unselect();
      save_data_input(calendar);
    },
    selectAllow: function(info) {
      var nextDay = info.start.getDay() === 6 ? 0 : info.start.getDay() + 1;
      if (
        info.start.getDay() == info.end.getDay() ||
        (nextDay == info.end.getDay() && time_format(info.end) == "00:00:00")
      ) {
        return true;
      }
      return false;
    },
    eventAllow: function(dropInfo, draggedEvent) {
      if (dropInfo.allDay != true) {
        var nextDay =
          draggedEvent.start.getDay() === 6
            ? 0
            : draggedEvent.start.getDay() + 1;
        if (
          (draggedEvent.start.getDay() == dropInfo.start.getDay() &&
            dropInfo.start.getDay() == dropInfo.end.getDay()) ||
          (nextDay == dropInfo.end.getDay() &&
            time_format(dropInfo.end) == "00:00:00")
        ) {
          return true;
        }
      }
      return false;
    },
    eventDrop: function(info) {
      if (info.event.allDay != true) {
        var nextDay =
          info.event.start.getDay() === 6 ? 0 : info.event.start.getDay() + 1;
        if (
          info.event.start.getDay() == info.event.end.getDay() ||
          (nextDay == info.event.end.getDay() &&
            time_format(info.event.end) == "00:00:00")
        ) {
          recalculate_events(
            calendar,
            info.event,
            info.event.start,
            info.event.end,
            false
          );
          save_data_input(calendar);
        }
      }
    },
    eventDragStop: function(info) {
      var trashEl = $("#calendar_map");
      var ofs = trashEl.offset();

      var x1 = ofs.left;
      var x2 = ofs.left + trashEl.outerWidth(true);
      var y1 = ofs.top;
      var y2 = ofs.top + trashEl.outerHeight(true);

      if (
        x1 >= info.jsEvent.pageX ||
        x2 <= info.jsEvent.pageX ||
        y1 >= info.jsEvent.pageY ||
        y2 <= info.jsEvent.pageY
      ) {
        // Remove event.
        info.event.remove();
        save_data_input(calendar);
      }
    },
    eventResize: function(info) {
      var nextDay =
        info.event.start.getDay() === 6 ? 0 : info.event.start.getDay() + 1;
      if (
        info.event.start.getDay() == info.event.end.getDay() ||
        (nextDay == info.event.end.getDay() &&
          time_format(info.event.end) == "00:00:00")
      ) {
        recalculate_events(
          calendar,
          info.event,
          info.event.start,
          info.event.end,
          false
        );
        save_data_input(calendar);
      }
    },
    eventMouseEnter: function(info) {
      var tooltip = '<div class="tooltipevent">';
      tooltip += settings.tooltipText;
      tooltip += "</div>";

      $(tooltip).appendTo(info.el);
    },
    eventMouseLeave: function() {
      $(".tooltipevent").remove();
    },
    eventClick: function(info) {
      var calendar_date_from = new Date(calendar.view.activeStart);
      var calendar_days = [];
      var acum = 1;
      var i = 0;
      // Week date.
      for (var index = 1; index <= 7; index++) {
        // Sunday key 0.
        if (acum === 7) {
          acum = 0;
        }
        var date_acum = new Date(calendar_date_from);
        calendar_days[acum] = date_acum.setDate(
          calendar_date_from.getDate() + i
        );
        acum++;
        i++;
      }

      confirmDialog({
        title: "Event",
        message: function() {
          var id = "div-" + uniqId();
          var loading = settings.loadingText;
          $.ajax({
            method: "post",
            url: settings.url,
            data: {
              page: "include/ajax/alert_list.ajax",
              resize_event_week: true,
              day_from: info.event.start.getDay(),
              day_to: info.event.end.getDay(),
              time_from: time_format(info.event.start),
              time_to: time_format(info.event.end)
            },
            dataType: "html",
            success: function(data) {
              $("#" + id)
                .empty()
                .append(data);
              $("#text-time_from_event, #text-time_to_event").timepicker({
                timeFormat: settings.timeFormat,
                timeOnlyTitle: settings.timeOnlyTitle,
                timeText: settings.timeText,
                hourText: settings.hourText,
                minuteText: settings.minuteText,
                secondText: settings.secondText,
                currentText: settings.currentText,
                closeText: settings.closeText
              });

              $.datepicker.setDefaults(
                $.datepicker.regional[settings.userLanguage]
              );
            },
            error: function(error) {
              console.error(error);
            }
          });

          return "<div id ='" + id + "'>" + loading + "</div>";
        },
        onAccept: function() {
          var replace_day_from = $("#hidden-day_from").val();
          var replace_time_from = $("#text-time_from_event").val();

          var array_time_from = replace_time_from.split(":");
          var new_date_from = new Date(calendar_days[replace_day_from]);
          new_date_from.setHours(
            array_time_from[0],
            array_time_from[1],
            array_time_from[2]
          );

          var replace_day_to = $("#hidden-day_to").val();
          var replace_time_to = $("#text-time_to_event").val();
          if (replace_time_to === "23:59:59") {
            replace_day_to++;
            replace_time_to = "00:00:00";
          }

          var array_time_to = replace_time_to.split(":");
          var new_date_to = new Date(calendar_days[replace_day_to]);
          new_date_to.setHours(
            array_time_to[0],
            array_time_to[1],
            array_time_to[2]
          );

          if (new_date_from < new_date_to) {
            recalculate_events(
              calendar,
              info.event,
              new_date_from,
              new_date_to,
              false
            );
          } else {
            console.error("You cannot add smaller events");
          }
          save_data_input(calendar);
        },
        newButton: {
          text: settings.removeText,
          class: "",
          onFunction: function() {
            // Remove event.
            info.event.remove();
            save_data_input(calendar);
          }
        }
      });
    },
    selectOverlap: false,
    eventOverlap: false,
    allDaySlot: true
  });

  return calendar;
}

function time_format(date) {
  var d = new Date(date);
  var hours = format_two_digits(d.getHours());
  var minutes = format_two_digits(d.getMinutes());
  var seconds = format_two_digits(d.getSeconds());
  return hours + ":" + minutes + ":" + seconds;
}

function format_two_digits(n) {
  return n < 10 ? "0" + n : n;
}

function recalculate_events(calendar, newEvent, from, to, create) {
  var allEvents = calendar.getEvents();
  allEvents.forEach(function(oldEvent) {
    if (create === false) {
      // Avoid the same event.
      if (newEvent.id === oldEvent.id) {
        return;
      }

      // New event inside complete in old event.
      if (oldEvent.start < from && oldEvent.end > to) {
        // Remove new event.
        newEvent.remove();
        return;
      }

      // New event inside complete in old event.
      if (oldEvent.start > from && oldEvent.end < to) {
        // Remove new event.
        oldEvent.remove();
        return;
      }
    }

    // Inside From.
    if (oldEvent.start > from && oldEvent.start <= to) {
      if (time_format(oldEvent.start) !== "00:00:00") {
        to = oldEvent.end;
        oldEvent.remove();
      }
    }

    // Inside To.
    if (oldEvent.end >= from && oldEvent.end < to) {
      if (time_format(oldEvent.end) !== "00:00:00") {
        from = oldEvent.start;
        oldEvent.remove();
      }
    }
  });

  if (create === true) {
    calendar.addEvent({
      title: "",
      start: from,
      end: to,
      id: uniqId()
    });
  } else {
    // Update event.
    newEvent.setDates(from, to);
  }
}

function save_data_input(calendar) {
  var allEvents = calendar.getEvents();
  var data = {};
  var day_names = [
    "sunday",
    "monday",
    "tuesday",
    "wednesday",
    "thursday",
    "friday",
    "saturday"
  ];
  allEvents.forEach(function(event) {
    var obj = {
      start: time_format(event.start),
      end: time_format(event.end)
    };
    if (data[day_names[event.start.getDay()]] == undefined) {
      data[day_names[event.start.getDay()]] = [];
    }
    data[day_names[event.start.getDay()]].push(obj);
  });

  if (data && Object.keys(data).length === 0) {
    $(".warning.textodialogo").show();
  } else {
    $(".warning").hide();
  }

  $("#hidden-schedule").val(JSON.stringify(data));
}

// eslint-disable-next-line no-unused-vars
function loadEventBBDD(events) {
  if (events == undefined || events === null || events === "") {
    $(".warning").show();
    return {};
  }

  $(".warning").hide();

  var current_day = new Date();

  var day_names = [
    "monday",
    "tuesday",
    "wednesday",
    "thursday",
    "friday",
    "saturday",
    "sunday",
    "sun"
  ];

  var keys_days_names = {
    monday: 0,
    tuesday: 1,
    wednesday: 2,
    thursday: 3,
    friday: 4,
    saturday: 5,
    sunday: 6,
    sun: 7
  };

  var dates = [];
  day_names.forEach(function(element, i) {
    dates[element] = getDays(current_day, i);
  });

  var result = [];
  Object.entries(JSON.parse(events)).forEach(function(element) {
    var day_string = element[0];
    var events_day = element[1];
    events_day.forEach(function(event) {
      if (event != null) {
        var time_from = event.start.split(":");
        var time_to = event.end.split(":");
        var end = dates[day_string].setHours(
          time_to[0],
          time_to[1],
          time_to[2],
          0
        );
        if (event.end === "00:00:00") {
          end = dates[day_names[keys_days_names[day_string] + 1]].setHours(
            time_to[0],
            time_to[1],
            time_to[2],
            0
          );
        }
        result.push({
          title: "",
          start: dates[day_string].setHours(
            time_from[0],
            time_from[1],
            time_from[2],
            0
          ),
          end: end,
          id: uniqId()
        });
      }
    });
  });

  return result;
}

function getDays(d, i) {
  d = new Date(d);
  var day = d.getDay(),
    diff = d.getDate() - day + i + (day == 0 ? -6 : 1);
  return new Date(d.setDate(diff));
}
