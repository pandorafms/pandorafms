/* global $, interval */
$(document).ready(() => {
  // Set close on select.
  $("#_credentials_").select2({
    closeOnSelect: true
  });
  var interval;
  if (interval === "0") {
    setTimeout(() => {
      $("#mode_interval")
        .parent()
        .find("[id^='interval']")
        .hide();
    }, 100);
  }
});

function changeModeInterval(e) {
  if ($(e).val() === "manual") {
    $(e)
      .parent()
      .find("[id^='interval']")
      .hide();
  } else {
    var interval = $(e)
      .parent()
      .find("div[id^='interval']")[0];
    $(interval).show();
  }
}
