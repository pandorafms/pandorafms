/* global $, interval */
$(document).ready(() => {
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
