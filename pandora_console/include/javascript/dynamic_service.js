function updateSmartValue(event) {
  var eventType = event.type;
  var inputElement = $("#" + event.target.id);
  var inputValue = inputElement.val();
  var valueType = event.target.id.split("-");

  if (eventType === "focus") {
    inputElement.val(parseInt(inputValue));
  } else if (eventType === "blur") {
    change_mode();
  } else {
    var keyPressed = event.keyCode;
    if (
      (keyPressed <= 48 && keyPressed >= 57) ||
      inputElement.val() < 0 ||
      inputElement.val() > 100
    ) {
      event.preventDefault();
    } else {
      $("#text-" + valueType[0]).val(inputElement.val());
    }
  }
}

function change_mode(alt) {
  var modeStatus = $("#mode").val();
  var serviceMode = $("#hidden-service_mode_smart").val();
  if (modeStatus == serviceMode) {
    $(".smart_thresholds").css("display", "inline-flex");

    var crit = parseFloat($("#text-critical").val());
    var warn = parseFloat($("#text-warning").val());

    if (crit < warn) {
      $("#text-critical").val($("#text-warning").val());
    }

    $("#critical-val-d").val($("#text-critical").val() + " %");
    $("#warning-val-d").val($("#text-warning").val() + " %");

    if (alt != 1) {
      $("#text-critical")
        .prop("type", "range")
        .prop("step", "0.01")
        .prop("min", "0")
        .prop("max", "100")
        .on("input", function() {
          change_mode(1);
        });

      $("#text-warning")
        .prop("type", "range")
        .prop("step", "0.01")
        .prop("min", "0")
        .prop("max", "100")
        .on("input", function() {
          change_mode(1);
        });
    }
  } else {
    $(".smart_thresholds").css("display", "none");

    $("#text-critical")
      .prop("type", "number")
      .on("input", function() {})
      .show();
    $("#text-warning")
      .prop("type", "number")
      .on("input", function() {})
      .show();
  }
}
