/* global $ */

// eslint-disable-next-line no-unused-vars
function pandoraFlotPie(
  graph_id,
  values,
  labels,
  nseries,
  width,
  font_size,
  water_mark,
  separator,
  legend_position,
  height,
  colors,
  hide_labels
) {
  height = parseInt(height);

  labels = labels.split(separator);
  var data = values.split(separator);

  if (colors != "") {
    colors = colors.split(separator);
  }

  var pieRadius = 0.9;

  var color = null;
  for (var i = 0; i < nseries; i++) {
    if (colors != "") {
      color = colors[i];
    }
    data[i] = { label: labels[i], data: parseFloat(data[i]), color: color };
  }

  var show_legend = true;
  if (legend_position == "hidden") {
    show_legend = false;
  }

  var conf_pie = {
    series: {
      pie: {
        show: true,
        radius: pieRadius
      }
    },
    legend: {
      show: show_legend
    },
    grid: {
      hoverable: true,
      clickable: true
    }
  };

  if (hide_labels != false && hide_labels != 0) {
    conf_pie.series.pie.label = {
      show: true,
      radius: 2 / 3,
      formatter: labelFormatter,
      threshold: 0.1
    };
  } else {
    conf_pie.series.pie.label = {
      show: false
    };
  }

  switch (legend_position) {
    case "bottom":
      conf_pie.legend.position = "se";
      break;
    case "right":
    case "inner":
      conf_pie.legend.container = $("#" + graph_id + "_legend");
      break;
    default:
      // TODO FOR TOP OR LEFT OR RIGHT.
      break;
  }

  var discount = 20;
  if (water_mark) {
    discount = 40;
  }

  var plot = $.plot($("#" + graph_id), data, conf_pie);

  var legends = $("#" + graph_id + " .legendLabel");
  legends.css("font-size", font_size + "pt");

  var tableDiv = $("#" + graph_id + " .legend > div");
  tableDiv.css("max-height", height - discount + "px");

  var tableLegend = $("#" + graph_id + " .legend table");
  tableLegend.css("max-height", height - discount + "px");

  // Events
  $("#" + graph_id).bind("plothover", pieHover);
  //$("#" + graph_id).bind("plotclick", pieClick);
  $("#" + graph_id).bind("mouseout", resetInteractivity);
  $("#" + graph_id).css("margin-left", "auto");
  $("#" + graph_id).css("margin-right", "auto");

  function pieHover(event, pos, obj) {
    if (!obj) return;

    var index = obj.seriesIndex;
    legends.css("color", "#3F3F3D");
    legends.eq(index).css("color", "");
  }

  function labelFormatter(label, series) {
    return (
      '<div style="font-size:' +
      font_size +
      "pt;" +
      'text-align:center;padding:2px;color:#4a4a4a;">' +
      label +
      "<br/>" +
      series.percent.toFixed(2) +
      "%</div>"
    );
  }

  // Reset styles
  function resetInteractivity() {
    legends.css("color", "#3F3F3D");
    legends.attr("class", "invert_filter");
  }

  if (water_mark) {
    set_watermark(
      graph_id,
      plot,
      $("#watermark_image_" + graph_id).attr("src")
    );
  }
}

// eslint-disable-next-line no-unused-vars
function pandoraFlotPieCustom(
  graph_id,
  values,
  labels,
  width,
  font_size,
  font,
  water_mark,
  separator,
  legend_position,
  height,
  colors,
  legend,
  background_color
) {
  font = font
    .split("/")
    .pop()
    .split(".")
    .shift();
  labels = labels.split(separator);
  legend = legend.split(separator);
  var data = values.split(separator);
  var no_data = 0;
  if (colors != "") {
    colors = colors.split(separator);
  }

  var color = null;
  for (var i = 0; i < data.length; i++) {
    if (colors != "") {
      color = colors[i];
    }
    var datos = data[i];
    data[i] = { label: labels[i], data: parseFloat(data[i]), color: color };
    if (!datos) no_data++;
  }

  var label_conf;
  var show_legend = true;

  // Set default value if not come like a number.
  if (isNaN(width) === true) {
    width = 451;
  }

  if (width <= 450) {
    show_legend = false;
    label_conf = {
      show: true,
      radius: 5 / 8,
      formatter: function(label, series) {
        return (
          '<div style="font-size:' +
          font_size +
          "pt; font-weight:bolder;" +
          "text-align:center;padding:2px;color:rgb(63, 63, 61)" +
          '">' +
          label +
          ":<br>" +
          series.data[0][1] +
          "</div>"
        );
      },
      background: {
        opacity: 0.5
      }
    };
  } else {
    label_conf = {
      show: false
    };
  }

  var conf_pie = {
    series: {
      pie: {
        show: true,
        radius: 3 / 4,
        innerRadius: 0.4,
        label: label_conf
      }
    },
    legend: {
      show: show_legend
    },
    grid: {
      hoverable: true,
      clickable: true
    }
  };
  if (
    /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(
      navigator.userAgent
    )
  ) {
    conf_pie.series.pie.label = { show: false };
  }

  // Avoid issues with 0 width values.
  $("#" + graph_id).width(width);

  var plot = $.plot($("#" + graph_id), data, conf_pie);
  if (no_data == data.length) {
    $("#" + graph_id + " .overlay").remove();
    $("#" + graph_id + " .base").remove();
    $("#" + graph_id).prepend(
      "<img style='width:50%;' src='images/no_data_toshow.png' />"
    );
  }
  var legends = $("#" + graph_id + " .legendLabel");

  if (background_color == "transparent") {
    $(".legend>table").css("background-color", "");
    $(".legend>div").css("background-color", "");
    $(".legend>table").css("color", "#000");
  } else if (background_color == "white") {
    $(".legend>table").css("background-color", "white");
    $(".legend>table").css("color", "black");
  } else if (background_color == "black") {
    $(".legend>table").css("background-color", "black");
    $(".legend>table").css("color", "#aaa");
  } else if (background_color == "black_theme") {
    $(".legend>table").css("background-color", "#222");
    $(".legend>div").css("background-color", "#222");
    $(".legend>table").css("color", "#fff !important");
  }

  $(".legend").hover(function() {
    return false;
  });

  // Events
  $("#" + graph_id).bind("plothover", pieHover);
  $("#" + graph_id).bind("plotclick", Clickpie);
  $("#" + graph_id).bind("mouseout", resetInteractivity);
  $("#" + graph_id).css("margin-left", "auto");
  $("#" + graph_id).css("margin-right", "auto");

  function pieHover(event, pos, obj) {
    if (!obj) return;

    var index = obj.seriesIndex;
    if (background_color == "black_theme") {
      legends.css("color", "#fff");
    } else {
      legends.css("color", "#3F3F3D");
    }
    legends.eq(index).css("color", "");
  }

  function Clickpie(event, pos, obj) {
    if (!obj) return;
    var percent = parseFloat(obj.series.percent).toFixed(2);
    var valor = parseFloat(obj.series.data[0][1]);

    var value = "";
    if (valor > 1000000) {
      value = Math.round((valor / 1000000) * 100) / 100;
      value = value + "M";
    } else {
      if (valor > 1000) {
        value = Math.round((valor / 1000) * 100) / 100;
        value = value + "K";
      } else value = valor;
    }

    alert("" + obj.series.label + ": " + value + " (" + percent + "%)");
  }

  // Reset styles
  function resetInteractivity() {
    legends.each(function() {
      // fix the widths so they don't jump around
      $(this).css("color", "#3F3F3D");
    });
  }

  if (water_mark) {
    set_watermark(
      graph_id,
      plot,
      $("#watermark_image_" + graph_id).attr("src")
    );
  }
}

// eslint-disable-next-line no-unused-vars
function pandoraFlotHBars(
  graph_id,
  values,
  labels,
  maxvalue,
  water_mark,
  separator,
  separator2,
  font,
  font_size,
  background_color,
  tick_color,
  min,
  max
) {
  var colors_data = [
    "#e63c52",
    "#FFA631",
    "#f3b200",
    "#4a83f3",
    "#F2919D",
    "#82b92e"
  ];
  values = values.split(separator2);
  font = font
    .split("/")
    .pop()
    .split(".")
    .shift();
  var datas = new Array();
  for (i = 0; i < values.length; i++) {
    var serie = values[i].split(separator);

    var aux = new Array();
    for (var j = 0; j < serie.length; j++) {
      var aux2 = parseFloat(serie[j]);
      aux.push([aux2, j]);
      datas.push({
        data: [[aux2, j]],
        color: colors_data[j]
      });
    }
  }

  var labels_total = new Array();
  labels = labels.split(separator);
  for (var i = 0; i < labels.length; i++) {
    labels_total.push([i, labels[i]]);
  }

  var ycolor = "rgb(84, 84, 84)";

  if (background_color == "#222") {
    var ycolor = "#fff";
  }
  var options = {
    series: {
      bars: {
        show: true,
        barWidth: 0.75,
        align: "center",
        lineWidth: 1,
        fill: 1,
        horizontal: true
      }
    },
    grid: {
      hoverable: true,
      borderWidth: 1,
      tickColor: tick_color,
      borderColor: "#C1C1C1",
      backgroundColor: { colors: [background_color, background_color] }
    },
    xaxis: {
      color: tick_color,
      axisLabelUseCanvas: true,
      axisLabelFontSizePixels: font_size,
      axisLabelFontFamily: "lato",
      tickFormatter: xFormatter
    },
    yaxis: {
      font: {
        size: font_size + 2,
        color: ycolor,
        family: "lato"
      },
      ticks: yFormatter
    },
    legend: {
      show: false
    }
  };

  // Fixed to avoid the graphs with all 0 datas
  // the X axis show negative part instead to
  // show the axis only the positive part.
  if (maxvalue == 0) {
    options["yaxis"]["min"] = 0;
    // Fixed the values with a lot of decimals in the situation
    // with all 0 values.
    options["yaxis"]["tickDecimals"] = 0;
  }

  if (max) {
    options["xaxis"]["max"] = max;
  }
  if (min) {
    options["xaxis"]["min"] = min;
  }

  var plot = $.plot($("#" + graph_id), datas, options);

  $("#" + graph_id).HUseTooltip();
  $("#" + graph_id).css("margin-left", "auto");
  $("#" + graph_id).css("margin-right", "auto");

  // v, axis;
  function yFormatter() {
    var format = new Array();
    for (var i = 0; i < labels_total.length; i++) {
      var label = labels_total[i][1];
      var div_attributes =
        'style="font-size:' +
        font_size +
        "pt !important;" +
        "margin: 0; max-width: 200px;" +
        "margin-right:5px;" +
        "margin-left: -1.5em;" +
        "text-align: right;" +
        "text-overflow: ellipsis;" +
        "overflow: hidden;" +
        "white-space: pre;";

      if (label.indexOf("<br>") != -1) {
        var label_array = label.split("<br>");
        label = label_array[0] + label_array[1];
      }

      div_attributes += '" title="' + label + '" style="overflow: hidden;"';

      format.push([i, "<div " + div_attributes + ">" + label + "</div>"]);
    }
    return format;
  }

  // v, axis;
  function xFormatter(v) {
    var label = parseFloat(v);

    const unit_prefixes = ["K", "M", "G"];
    var unit_prefix = "";
    var i = 0;

    while (label >= 1000 && i < 3) {
      label = label / 1000;
      unit_prefix = unit_prefixes[i];
      i++;
    }
    var text = label.toLocaleString();

    text = text + unit_prefix;

    return (
      '<div style="font-size:' +
      font_size +
      'pt !important;">' +
      text +
      "</div>"
    );
  }

  if (water_mark) {
    set_watermark(
      graph_id,
      plot,
      $("#watermark_image_" + graph_id).attr("src")
    );
  }
}

var previousPoint = null,
  previousLabel = null;

$.fn.HUseTooltip = function() {
  $(this).bind("plothover", function(event, pos, item) {
    if (item) {
      if (
        previousLabel != item.series.label ||
        previousPoint != item.seriesIndex
      ) {
        previousPoint = item.seriesIndex;
        previousLabel = item.series.label;
        $("#tooltip").remove();

        var x = item.datapoint[0];
        // var y = item.datapoint[1];

        var color = item.series.color;
        showTooltip(
          item.pageX,
          item.pageY,
          color,
          "<strong>" + x + "</strong>"
        );
      }
    } else {
      $("#tooltip").remove();
      previousPoint = null;
    }
  });
};

$.fn.VUseTooltip = function() {
  $(this).bind("plothover", function(event, pos, item) {
    if (item) {
      if (
        previousLabel != item.series.label ||
        previousPoint != item.dataIndex
      ) {
        previousPoint = item.dataIndex;
        previousLabel = item.series.label;
        $("#tooltip").remove();

        //var x = item.datapoint[0];

        var y = item.datapoint[1];
        if (typeof y != "string") {
          y = number_format(y, false, "", 2, 1000);
        }

        var color = item.series.color;

        /*
        "<strong>" +
            item.series.label +
            "</strong><br>" +
            item.series.xaxis.ticks[x].label +
            " : <strong>" +
            y +
            "</strong>"
        */

        showTooltip(
          item.pageX,
          item.pageY,
          color,
          "<strong>" + item.series.label + " : " + y + "</strong>"
        );
      }
    } else {
      $("#tooltip").remove();
      previousPoint = null;
    }
  });
};

function showTooltip(x, y, color, contents) {
  $('<div id="tooltip">' + contents + "</div>")
    .css({
      position: "absolute",
      display: "none",
      top: y,
      left: x,
      border: "2px solid " + color,
      padding: "3px",
      "font-size": "9px",
      "border-radius": "5px",
      "background-color": "#fff",
      color: "#111",
      opacity: 0.9
    })
    .appendTo("body")
    .fadeIn(200);
}

// eslint-disable-next-line no-unused-vars
function pandoraFlotVBars(settings) {
  settings = JSON.parse(atob(settings));

  var unit = settings.generals.unit;
  var divisor = settings.generals.divisor;

  var datas = [];
  var ticks = [];
  var labels = [];
  settings.data.forEach(function(element, index) {
    var color = element.color;
    if (settings.generals.arrayColors) {
      if (settings.generals.arrayColors[index] != undefined) {
        color = settings.generals.arrayColors[index].color;
      }
    }

    datas.push({
      label: element.tick,
      color: color,
      data: [[index, element.data]]
    });

    if (settings.generals.forceTicks === true) {
      ticks.push([index, element.tick]);
    } else {
      ticks.push([element.tick]);
    }

    labels.push([index, element.tick]);
  });

  var formatterTicks = "";
  if (settings.generals.forceTicks === true) {
    formatterTicks = "";
  } else {
    var ticksformatter = ticks;
    ticks = null;
    formatterTicks = function(v) {
      return ticksformatter[Math.round(v)];
    };
  }
  settings.x.font.family = "lato";
  settings.y.font.family = "lato";
  settings.y.title.fontFamily = "lato";
  var options = {
    series: {
      bars: {
        show: settings.seriesBars.show,
        lineWidth: settings.seriesBars.lineWidth,
        fill: settings.seriesBars.fill,
        fillColor: settings.seriesBars.fillColor
      }
    },
    bars: {
      align: settings.bars.align,
      barWidth: settings.bars.barWidth,
      horizontal: settings.bars.horizontal
    },
    xaxis: {
      axisLabel: settings.x.title.title,
      axisLabelUseCanvas: true,
      axisLabelFontSizePixels: settings.x.title.fontSize,
      axisLabelFontFamily: settings.x.title.fontFamily,
      axisLabelPadding: settings.x.title.padding,
      font: {
        size: settings.x.font.size,
        lineHeight: settings.x.font.lineHeight,
        style: settings.x.font.style,
        weight: settings.x.font.weight,
        family: settings.x.font.family,
        variant: settings.x.font.variant,
        color: settings.x.font.color
      },
      show: settings.x.show,
      position: settings.x.position,
      color: settings.x.color,
      ticks: ticks,
      tickFormatter: formatterTicks,
      labelWidth: settings.x.labelWidth,
      labelHeight: settings.x.labelHeight
    },
    yaxis: {
      axisLabel: settings.y.title.title,
      axisLabelUseCanvas: true,
      axisLabelFontSizePixels: settings.y.title.fontSize,
      axisLabelFontFamily: settings.y.title.fontFamily,
      axisLabelPadding: settings.y.title.padding,
      font: {
        size: settings.y.font.size,
        lineHeight: settings.y.font.lineHeight,
        style: settings.y.font.style,
        weight: settings.y.font.weight,
        family: settings.y.font.family,
        variant: settings.y.font.variant,
        color: settings.y.font.color
      },
      show: settings.y.show,
      position: settings.y.position,
      color: settings.y.color,
      tickFormatter: function(v) {
        var formatted = v;
        if (typeof formatted != "string") {
          formatted = number_format(v, false, unit, 2, divisor);
        }

        return formatted;
      },
      labelWidth: settings.y.labelWidth,
      labelHeight: settings.y.labelHeight,
      reserveSpace: true
    },
    legend: {
      // TODO: settings.
      show: false
    },
    grid: {
      show: settings.grid.show,
      aboveData: settings.grid.aboveData,
      color: settings.grid.color,
      backgroundColor: settings.grid.backgroundColor,
      margin: settings.grid.margin,
      labelMargin: settings.grid.labelMargin,
      axisMargin: settings.grid.axisMargin,
      markings: settings.grid.markings,
      borderWidth: settings.grid.borderWidth,
      borderColor: settings.grid.borderColor,
      minBorderMargin: settings.grid.minBorderMargin,
      clickable: settings.grid.clickable,
      hoverable: settings.grid.hoverable,
      autoHighlight: settings.grid.autoHighlight,
      mouseActiveRadius: settings.grid.mouseActiveRadius
    }
  };

  $.plot($("#" + settings.graphId), datas, options);

  // Hover in bars graph.
  $("#" + settings.graphId).VUseTooltip();
}

// eslint-disable-next-line no-unused-vars
function pandoraFlotSlicebar(
  graph_id,
  values,
  datacolor,
  legend,
  intervaltick,
  font,
  font_size,
  separator,
  separator2,
  id_agent,
  full_legend,
  not_interactive,
  show_date,
  datelimit,
  server_id
) {
  values = values.split(separator2);
  legend = legend.split(separator);
  datacolor = datacolor.split(separator);

  if (full_legend != false) {
    full_legend = full_legend.split(separator);
  }

  font_size = parseInt(font_size);
  font = font
    .split("/")
    .pop()
    .split(".")
    .shift();

  // Check possible adapt_keys on classes
  check_adaptions(graph_id);

  var datas = new Array();

  for (var i = 0; i < values.length; i++) {
    var serie = values[i].split(separator);

    var aux = new Array();
    aux.push([parseInt(serie[0]), 0]);

    datas.push({
      data: aux,
      bars: {
        show: true,
        fill: 1,
        fillColor: { colors: [{ opacity: 1 }, { opacity: 1 }] },
        lineWidth: 0,
        horizontal: true,
        steps: false,
        barWidth: 24 * 60 * 60 * 600
      },
      color: datacolor[i]
    });
  }

  var regex = /visual_console/;
  var match = regex.exec(window.location.href);

  var options = {
    series: {
      stack: true,
      bars: {
        align: "center"
      }
    },
    grid: {
      borderWidth: 0,
      borderColor: "transparent",
      tickColor: "#fff"
    },
    xaxes: [
      {
        show: show_date,
        tickFormatter: xFormatter,
        color: "",
        tickSize: intervaltick,
        tickLength: 0
      }
    ],
    yaxes: [
      {
        show: false,
        tickLength: 0
      }
    ],
    legend: {
      show: false
    }
  };

  if (match == null && not_interactive == 0) {
    options.grid["hoverable"] = true;
    options.grid["clickable"] = true;
  } else {
    options.grid["hoverable"] = false;
    options.grid["clickable"] = false;
  }

  $.plot($("#" + graph_id), datas, options);

  // Added for correct handle of the font size.
  // xaxes-yaxes object not handle font-size properly.
  $(".flot-x-axis .flot-tick-label").css("font-size", font_size);

  if (match == null && not_interactive == 0) {
    // Events
    $("#" + graph_id).bind("plothover", function(event, pos, item) {
      if (item) {
        var from = legend[item.seriesIndex];
        var to = legend[item.seriesIndex + 1];

        if (to == undefined) {
          to = ">";
        }

        $("#extra_" + graph_id).text(from + "-" + to);
        var extra_width = parseInt(
          $("#extra_" + graph_id)
            .css("width")
            .split("px")[0]
        );

        $("#extra_" + graph_id).css(
          "left",
          parseInt(pos.pageX - extra_width - 200) + "px"
        );
        $("#extra_" + graph_id).show();
      }
    });

    $("#" + graph_id).bind("plotclick", function(event, pos, item) {
      if (item) {
        //from time
        var from = legend[item.seriesIndex];
        //to time
        var to = legend[item.seriesIndex + 1];
        //current date
        var dateObj = new Date();

        var newdate = "";
        var newdate2 = "";
        if (full_legend != "") {
          newdate = full_legend[item.seriesIndex];
          newdate2 = full_legend[item.seriesIndex + 1];
        } else {
          var month = dateObj.getUTCMonth() + 1; //months from 1-12
          var day = dateObj.getUTCDate();
          var year = dateObj.getUTCFullYear();
          newdate = year + "/" + month + "/" + day;
        }

        if (!to) {
          to = "23:59";
        }

        var url_filters = {
          id_agent: id_agent,
          date_from: newdate,
          time_from: from + ":00",
          status: "-1",
          group_rep: "1",
          from_event_graph: 1,
          id_server_meta: server_id
        };

        if (full_legend != "") {
          if (newdate2 != undefined) {
            url_filters.date_to = newdate2;
            url_filters.time_to = to + ":00";
          }
        } else {
          url_filters.date_to = newdate;
          url_filters.time_to = to + ":00";
        }

        var url_filters_fb64 = btoa(JSON.stringify(url_filters));

        window.location =
          "index.php?sec=eventos&sec2=operation/events/events&fb64=" +
          url_filters_fb64;
      }
    });

    $("#" + graph_id).bind("mouseout", resetInteractivity);
  }

  // Reset interactivity styles
  function resetInteractivity() {
    $("#extra_" + graph_id).hide();
  }

  // Format functions
  function xFormatter(v) {
    var ct = new Date();
    var currentTime = ct.getTime();

    var diffDates = (currentTime - 1000 * datelimit) / 1000;

    var d = new Date(1000 * (v + datelimit));
    var monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec"
    ];

    var date_format = "";
    if (diffDates > 86500) {
      date_format =
        (d.getDate() < 10 ? "0" : "") +
        d.getDate() +
        " " +
        monthNames[d.getMonth()] +
        "<br>" +
        (d.getHours() < 10 ? "0" : "") +
        d.getHours() +
        ":" +
        (d.getMinutes() < 10 ? "0" : "") +
        d.getMinutes();
    } else {
      date_format =
        (d.getHours() < 10 ? "0" : "") +
        d.getHours() +
        ":" +
        (d.getMinutes() < 10 ? "0" : "") +
        d.getMinutes();
    }
    return date_format;
  }
}

// eslint-disable-next-line no-unused-vars
function pandoraFlotArea(
  graph_id,
  values,
  legend,
  series_type,
  color,
  date_array,
  data_module_graph,
  params,
  events_array
) {
  //diferents vars
  var unit = params.unit ? params.unit : "";
  var homeurl = params.homeurl;
  var font_size = parseInt(params.font_size);
  var font = params.font
    .split("/")
    .pop()
    .split(".")
    .shift();
  var width = params.width;
  var vconsole = params.vconsole;
  var menu = params.menu;
  var min_x = date_array["start_date"] * 1000;
  var max_x = date_array["final_date"] * 1000;
  var type = parseInt(params.stacked);
  var show_legend = params.show_legend;
  var image_threshold = params.image_threshold;
  var short_data = params.short_data != "" ? params.short_data : 3;
  var grid_color = params.grid_color;
  var background_color = params.backgroundColor;
  var legend_color = params.legend_color;
  var update_legend = {};
  var force_integer = 0;
  var divisor = params.divisor;
  var maximum_y_axis = params.maximum_y_axis;
  var basic_chart = params.basic_chart;

  if (typeof divisor === "undefined") {
    divisor = 1000;
  }

  if (typeof type === "undefined" || type == "") {
    type = params.type_graph;
  }

  //for threshold
  var threshold = true;
  var thresholded = false;
  var yellow_threshold = parseFloat(data_module_graph.w_min);
  var red_threshold = parseFloat(data_module_graph.c_min);
  var yellow_up = parseFloat(data_module_graph.w_max);
  var red_up = parseFloat(data_module_graph.c_max);
  var yellow_inverse = parseInt(data_module_graph.w_inv);
  var red_inverse = parseInt(data_module_graph.c_inv);
  var markins_graph = true;

  // If threshold and up are the same, that critical or warning is disabled
  if (yellow_threshold == yellow_up) {
    yellow_inverse = false;
  }

  if (red_threshold == red_up) {
    red_inverse = false;
  }

  //Array with points to be painted
  var threshold_data = new Array();
  //Array with some interesting points
  var extremes = new Array();

  var yellow_only_min = yellow_up == 0 && yellow_threshold != 0;
  var red_only_min = red_up == 0 && red_threshold != 0;

  //color
  var warning = "yellow";
  var critical = "red";

  if (threshold) {
    // Warning interval. Change extremes depends on critical interval
    if (yellow_inverse && red_inverse) {
      if (red_only_min && yellow_only_min) {
        // C: |--------         |
        // W: |········====     |
        if (yellow_threshold > red_threshold) {
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: {
                colors: [
                  {
                    opacity: 0.1
                  },
                  {
                    opacity: 0.1
                  }
                ]
              }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });

          extremes["warning_normal_fdown_1"] = red_threshold;
          extremes["warning_normal_fdown_2"] = yellow_threshold;
        }
      } else if (!red_only_min && yellow_only_min) {
        // C: |--------   ------|
        // W: |········===·     |
        if (yellow_threshold > red_up) {
          yellow_threshold = red_up;
        }
        if (yellow_threshold > red_threshold) {
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = red_threshold;
          extremes["warning_normal_fdown_2"] = yellow_threshold;
        }
      } else if (red_only_min && !yellow_only_min) {
        // C: |-------          |
        // W: |·······====   ===|
        if (red_threshold < yellow_threshold) {
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = red_threshold;
          extremes["warning_normal_fdown_2"] = yellow_threshold;
        }

        if (yellow_up < red_threshold) {
          yellow_up = red_threshold;
        }
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "warning_up",
          data: [[max_x, yellow_up]],
          label: null,
          color: warning,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 255, 198, 0)"
        });
        extremes["warning_up"] = yellow_up;
      } else {
        if (yellow_threshold > red_threshold) {
          // C: |--------   ------|
          // W: |········===·  ···|
          if (yellow_threshold > red_up) {
            yellow_threshold = red_up;
          }
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = red_threshold;
          extremes["warning_normal_fdown_2"] = yellow_threshold;
        }
        if (yellow_up < red_up) {
          // C: |--------      ---|
          // W: |·····  ·======···|
          if (yellow_up < red_threshold) {
            yellow_up = red_up;
          }
          threshold_data.push({
            id: "warning_normal_fup",
            data: [[max_x, yellow_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_up - yellow_up,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fup_1"] = red_up;
          extremes["warning_normal_fup_2"] = yellow_up;
        }
        // If warning is under critical completely do not paint anything yellow
        // C: |--------    -----|
        // W: |····          ···|
      }
    } else if (yellow_inverse && !red_inverse) {
      if (red_only_min && yellow_only_min) {
        // C: |            -----|
        // W: |============···  |
        if (yellow_threshold > red_threshold) {
          yellow_threshold = red_threshold;
        }
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "warning_down",
          data: [[max_x, yellow_threshold]],
          label: null,
          color: warning,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 255, 198, 0)"
        });
        extremes["warning_down"] = yellow_threshold;
      } else if (!red_only_min && yellow_only_min) {
        // C: |      ----       |
        // W: |======····===    |

        if (yellow_threshold > red_up) {
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_up,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = red_up;
          extremes["warning_normal_fdown_2"] = yellow_threshold;
        }

        if (yellow_threshold > red_threshold) {
          yellow_threshold = red_threshold;
        }
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "warning_down",
          data: [[max_x, yellow_threshold]],
          label: null,
          color: warning,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 255, 198, 0)"
        });
        extremes["warning_down"] = yellow_threshold;
      } else if (red_only_min && !yellow_only_min) {
        if (yellow_threshold < red_threshold) {
          // C: |            -----|
          // W: |=======  ===·····|
          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_down",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_down"] = yellow_threshold;

          if (red_threshold > yellow_up) {
            threshold_data.push({
              id: "warning_normal_fup",
              data: [[max_x, yellow_up]],
              label: null,
              color: warning,
              bars: {
                show: true,
                align: "left",
                barWidth: red_threshold - yellow_up,
                lineWidth: 0,
                horizontal: true,
                fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
              },
              highlightColor: "rgba(254, 255, 198, 0)"
            });
            extremes["warning_normal_fup_1"] = yellow_up;
            extremes["warning_normal_fup_2"] = red_threshold;
          }
        } else {
          // C: |     ------------|
          // W: |=====··  ········|
          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_down",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_down"] = red_threshold;
        }
      } else {
        if (yellow_threshold > red_up) {
          // C: |    -----        |
          // W: |====·····===  ===|
          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_down",
            data: [[max_x, red_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_down"] = red_threshold;

          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, red_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_threshold - red_up,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = red_up;
          extremes["warning_normal_fdown_2"] = yellow_threshold;

          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_up",
            data: [[max_x, yellow_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_up"] = yellow_up;
        } else if (red_threshold > yellow_up) {
          // C: |          -----  |
          // W: |===    ===·····==|
          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_down",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_down"] = yellow_threshold;

          threshold_data.push({
            id: "warning_normal_fup",
            data: [[max_x, yellow_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_threshold - yellow_up,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fup_1"] = yellow_up;
          extremes["warning_normal_fup_2"] = red_threshold;

          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_up",
            data: [[max_x, red_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_up"] = red_up;
        } else {
          // C: |  --------       |
          // W: |==·    ···=======|
          if (yellow_threshold > red_threshold) {
            yellow_threshold = red_threshold;
          }
          if (yellow_up < red_up) {
            yellow_up = red_up;
          }

          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_down",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_down"] = yellow_threshold;

          threshold_data.push({
            // barWidth will be correct on draw time
            id: "warning_up",
            data: [[max_x, yellow_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: 1,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_up"] = yellow_up;
        }
      }
    } else if (!yellow_inverse && red_inverse) {
      if (yellow_only_min && red_only_min) {
        // C: |-----            |
        // W: |   ··============|
        if (yellow_threshold < red_threshold) {
          yellow_threshold = red_threshold;
        }
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "warning_up",
          data: [[max_x, yellow_threshold]],
          label: null,
          color: warning,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 255, 198, 0)"
        });
        extremes["warning_up"] = yellow_threshold;
      } else if (!yellow_only_min && red_only_min) {
        // C: |-----            |
        // W: |   ··========    |
        if (yellow_threshold < red_threshold) {
          yellow_threshold = red_threshold;
        }
        if (yellow_up > red_threshold) {
          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_up - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = yellow_up;
        }
      } else if (yellow_only_min && !red_only_min) {
        // C: |-----      ------|
        // W: |   ··======······|
        if (yellow_threshold < red_threshold) {
          yellow_threshold = red_threshold;
        }
        if (yellow_threshold < red_up) {
          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_up - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = red_up;
        }
        // If warning is under critical completely do not paint anything yellow
        // C: |--------    -----|
        // W: |              ···|
      } else {
        if (red_up > yellow_threshold && red_threshold < yellow_up) {
          // C: |-----      ------|
          // W: |   ··======·     |
          if (yellow_threshold < red_threshold) {
            yellow_threshold = red_threshold;
          }
          if (yellow_up > red_up) {
            yellow_up = red_up;
          }

          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_up - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = yellow_up;
        }
      }
    }
    // If warning is under critical completely do not paint anything yellow
    // C: |--------    -----|   or	// C: |--------    -----|
    // W: |   ····          |		// W: |             ··  |
    else {
      if (red_only_min && yellow_only_min) {
        if (yellow_threshold < red_threshold) {
          // C: |        ---------|
          // W: |   =====·········|
          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_threshold - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = red_threshold;
        }
      } else if (red_only_min && !yellow_only_min) {
        // C: |        ---------|
        // W: |   =====···      |
        if (yellow_up > red_threshold) {
          yellow_up = red_threshold;
        }
        if (yellow_threshold < red_threshold) {
          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_up - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = yellow_up;
        }
      } else if (!red_only_min && yellow_only_min) {
        // C: |     -------     |
        // W: |   ==·······=====|
        if (yellow_threshold < red_threshold) {
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_threshold - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = yellow_threshold;
          extremes["warning_normal_fdown_2"] = red_threshold;
        }

        if (yellow_threshold < red_up) {
          yellow_threshold = red_up;
        }

        threshold_data.push({
          // barWidth will be correct on draw time
          id: "warning_up",
          data: [[max_x, yellow_threshold]],
          label: null,
          color: warning,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 255, 198, 0)"
        });
        extremes["warning_up"] = yellow_threshold;
      } else {
        if (red_threshold > yellow_threshold && red_up < yellow_up) {
          // C: |    ------       |
          // W: |  ==······====   |
          threshold_data.push({
            id: "warning_normal_fdown",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: red_threshold - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fdown_1"] = yellow_threshold;
          extremes["warning_normal_fdown_2"] = red_threshold;

          threshold_data.push({
            id: "warning_normal_fup",
            data: [[max_x, red_up]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_up - red_up,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_fup_1"] = red_up;
          extremes["warning_normal_fup_2"] = yellow_up;
        } else if (red_threshold < yellow_threshold && red_up > yellow_up) {
          // If warning is under critical completely do not paint anything yellow
          // C: |  --------        |
          // W: |    ····          |
        } else {
          // C: |     --------    |   or	// C: |     ------      |
          // W: |   ==··          |		// W: |        ···====  |
          if (yellow_up > red_threshold && yellow_up < red_up) {
            yellow_up = red_threshold;
          }
          if (yellow_threshold < red_up && yellow_threshold > red_threshold) {
            yellow_threshold = red_up;
          }
          threshold_data.push({
            id: "warning_normal",
            data: [[max_x, yellow_threshold]],
            label: null,
            color: warning,
            bars: {
              show: true,
              align: "left",
              barWidth: yellow_up - yellow_threshold,
              lineWidth: 0,
              horizontal: true,
              fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
            },
            highlightColor: "rgba(254, 255, 198, 0)"
          });
          extremes["warning_normal_1"] = yellow_threshold;
          extremes["warning_normal_2"] = yellow_up;
        }
      }
    }

    // Critical interval
    if (red_inverse) {
      if (!red_only_min) {
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "critical_up",
          data: [[max_x, red_up]],
          label: null,
          color: critical,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 236, 234, 0)"
        });
        extremes["critical_normal_1"] = red_threshold;
        extremes["critical_normal_2"] = red_up;
      }
      threshold_data.push({
        // barWidth will be correct on draw time
        id: "critical_down",
        data: [[max_x, red_threshold]],
        label: null,
        color: critical,
        bars: {
          show: true,
          align: "left",
          barWidth: 1,
          lineWidth: 0,
          horizontal: true,
          fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
        },
        highlightColor: "rgba(254, 236, 234, 0)"
      });
      extremes["critical_normal_3"] = red_threshold;
      extremes["critical_normal_4"] = red_threshold;
    } else {
      if (red_up == 0 && red_threshold != 0) {
        threshold_data.push({
          // barWidth will be correct on draw time
          id: "critical_up",
          data: [[max_x, red_threshold]],
          label: null,
          color: critical,
          bars: {
            show: true,
            align: "left",
            barWidth: 1,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 236, 234, 0)"
        });
        extremes["critical_normal_1"] = red_threshold;
        extremes["critical_normal_2"] = red_up;
      } else {
        threshold_data.push({
          id: "critical_normal",
          data: [[max_x, red_threshold]],
          label: null,
          color: critical,
          bars: {
            show: true,
            align: "left",
            barWidth: red_up - red_threshold,
            lineWidth: 0,
            horizontal: true,
            fillColor: { colors: [{ opacity: 0.1 }, { opacity: 0.1 }] }
          },
          highlightColor: "rgba(254, 236, 234, 0)"
        });
        extremes["critical_normal_1"] = red_threshold;
        extremes["critical_normal_2"] = red_up;
      }
    }
  }

  var stacked = null;
  var filled_s = 0.3;

  switch (type) {
    case "line":
    case 2:
      stacked = null;
      filled_s = false;
      break;
    case 3:
      stacked = "stack";
      filled_s = false;
      break;
    default:
    case "area":
    case 0:
      stacked = null;
      filled_s = 0.3;
      break;
    case 1:
      stacked = "stack";
      filled_s = 0.3;
      break;
  }

  var datas = new Array();
  var data_base = new Array();
  var lineWidth = $("#hidden-line_width_graph").val() || 1;

  var i = 0;
  var fill_color = "green";
  var line_show = true;
  var points_show = false;
  var filled = false;
  var steps_chart = false;
  var radius = false;
  var fill_points = fill_color;

  $.each(values, function(index, value) {
    if (typeof value.data !== "undefined") {
      if (index.search("alert") >= 0) {
        fill_color = "#ff7f00";
      } else if (index.search("event") >= 0) {
        fill_color = "#ff0000";
      } else {
        fill_color = "green";
      }

      if (typeof maximum_y_axis !== "undefined" && maximum_y_axis != 0) {
        maximum_y_axis =
          parseInt(value.max) > parseInt(maximum_y_axis)
            ? parseInt(value.max)
            : parseInt(maximum_y_axis);
      }

      switch (series_type[index]) {
        case "area":
          line_show = true;
          points_show = false;
          filled = filled_s;
          steps_chart = false;
          radius = false;
          fill_points = fill_color;
          break;
        case "percentil":
        case "line":
        default:
          line_show = true;
          points_show = false;
          filled = false;
          steps_chart = false;
          radius = false;
          fill_points = fill_color;
          break;
        case "points":
          line_show = false;
          points_show = true;
          filled = true;
          radius = 3;
          fill_points = fill_color;
          break;
        case "unknown":
        case "boolean":
          line_show = true;
          points_show = false;
          filled = filled_s;
          steps_chart = true;
          radius = false;
          fill_points = fill_color;
          break;
      }

      if (series_type[index] != "boolean") {
        if (value.slice_data) {
          update_legend[index] = value.slice_data;
        }
      }

      //in graph stacked unset percentil
      if (
        !(type == 1 && /percentil/.test(index) == true) &&
        !(type == 3 && /percentil/.test(index) == true)
      ) {
        if (color[index] !== null) {
          data_base.push({
            id: "serie_" + i,
            data: value.data,
            label: index,
            color: color[index]["color"],
            lines: {
              show: line_show,
              fill: filled,
              lineWidth: lineWidth,
              steps: steps_chart
            },
            points: {
              show: points_show,
              radius: radius,
              fillColor: fill_points,
              fill: filled
            },
            legend: legend.index
          });
        }
      }
    }
    i++;
  });

  // The first execution, the graph data is the base data
  datas = data_base;

  var number_ticks = 8;
  if (vconsole) {
    number_ticks = 5;
  }

  // masticks this variable is commented because the library defines the tick number by itself
  // var maxticks = date_array["period"] / 3600 / number_ticks;

  var options = {
    series: {
      stack: stacked,
      shadowSize: 0.1
    },
    crosshair: {
      mode: "xy",
      color: "grey"
    },
    selection: {
      mode: "xy",
      color: "#777"
    },
    export: {
      export_data: true,
      labels_long: legend,
      homeurl: homeurl
    },
    grid: {
      hoverable: true,
      clickable: true,
      borderColor: "#C1C1C1",
      color: grid_color
    },
    xaxis: {
      min: min_x,
      max: max_x,
      font: {
        size: font_size + 2,
        color: legend_color,
        family: "lato"
      }
    },
    xaxes: [
      {
        mode: "time",
        timezone: "browser",
        localTimezone: true
        //tickSize: [maxticks, 'hour']
      }
    ],
    yaxis: {
      font: {
        size: font_size + 2,
        color: legend_color,
        family: "lato",
        variant: "small-caps"
      }
    },
    yaxes: [
      {
        tickFormatter: yFormatter,
        position: "left",
        minTickSize: 0.5
      }
    ],
    legend: {
      position: "se",
      container: $("#legend_" + graph_id),
      labelFormatter: lFormatter
    }
  };

  if (basic_chart === true) {
    options.grid.borderWidth = 0;
    options.grid.backgroundColor = "rgba(255,255,255,0)";
    options.grid.autoHighlight = false;
    options.xaxis.show = false;
    options.xaxis.tickLength = 0;
    options.yaxis.show = false;
    options.yaxis.tickLength = 0;
  }

  if (typeof maximum_y_axis !== "undefined" && maximum_y_axis != 0) {
    options.yaxis.max = maximum_y_axis;
  }

  if (vconsole) {
    options.grid["hoverable"] = false;
    options.grid["clickable"] = false;
    options.crosshair = false;
    options.selection = false;
  }

  var plot = $.plot($("#" + graph_id), datas, options);

  // Adjust the overview plot to the width and position of the main plot
  adjust_left_width_canvas(graph_id, "overview_" + graph_id);
  update_left_width_canvas(graph_id);

  // Adjust overview when main chart is resized
  $("#" + graph_id).resize(function() {
    update_left_width_canvas(graph_id);
  });

  // Adjust linked graph to the width and position of the main plot
  // Miniplot
  if (!vconsole) {
    var overview = $.plot($("#overview_" + graph_id), datas, {
      series: {
        stack: stacked,
        shadowSize: 0.1
      },
      crosshair: {
        mode: "xy"
      },
      selection: {
        mode: "xy",
        color: "#777"
      },
      export: {
        export_data: true,
        labels_long: legend,
        homeurl: homeurl
      },
      grid: {
        hoverable: true,
        clickable: true,
        borderWidth: 1,
        borderColor: "#C1C1C1",
        tickColor: background_color,
        color: grid_color,
        autoHighlight: true
      },
      xaxis: {
        min: date_array.start_date * 1000,
        max: date_array.final_date * 1000,
        font: {
          size: font_size + 2,
          color: legend_color,
          family: "lato"
        }
      },
      xaxes: [
        {
          mode: "time",
          timezone: "browser",
          localTimezone: true
          //tickSize: [maxticks, 'hour']
        }
      ],
      yaxis: {
        font: {
          size: font_size + 2,
          color: legend_color,
          family: "lato"
        }
      },
      yaxes: [
        {
          tickFormatter: yFormatter,
          position: "left"
        }
      ],
      legend: {
        position: "se",
        container: $("#legend_" + graph_id),
        labelFormatter: lFormatter
      }
    });
  }

  // Adjust overview when main chart is resized
  $("#overview_" + graph_id).resize(function() {
    update_left_width_canvas(graph_id);
  });

  var max_draw = [];
  max_draw["max"] = plot.getAxes().yaxis.max;
  max_draw["min"] = plot.getAxes().yaxis.min;

  // Connection between plot and miniplot
  $("#" + graph_id).bind("plotselected", function(event, ranges) {
    // do the zooming if exist menu to undo it
    if (menu == 0) {
      return;
    }

    var dataInSelection = ranges.xaxis.to - ranges.xaxis.from;

    var maxticks_zoom = dataInSelection / 3600000 / number_ticks;
    if (maxticks_zoom < 0.001) {
      maxticks_zoom = dataInSelection / 60000 / number_ticks;
      if (maxticks_zoom < 0.001) {
        maxticks_zoom = 0;
      }
    }

    var y_recal = "";
    if (thresholded) {
      y_recal = axis_thresholded(
        threshold_data,
        plot.getAxes().yaxis.min,
        plot.getAxes().yaxis.max,
        red_threshold,
        extremes,
        red_up
      );
    } else {
      y_recal = ranges.yaxis;
    }

    if (thresholded) {
      var data_base_threshold = add_threshold(
        data_base,
        threshold_data,
        ranges.yaxis.from,
        y_recal.max,
        red_threshold,
        extremes,
        red_up,
        markins_graph
      );

      plot = $.plot(
        $("#" + graph_id),
        data_base_threshold,
        $.extend(true, {}, options, {
          grid: {
            borderWidth: 1,
            hoverable: true,
            autoHighlight: true
          },
          xaxis: {
            min: ranges.xaxis.from,
            max: ranges.xaxis.to,
            font: {
              size: font_size + 2,
              color: legend_color,
              family: "lato"
            }
          },
          xaxes: [
            {
              mode: "time",
              timezone: "browser",
              localTimezone: true,
              tickSize: [maxticks_zoom, "hour"]
            }
          ],
          yaxis: {
            min: ranges.yaxis.from,
            max: y_recal.max,
            font: {
              size: font_size + 2,
              color: legend_color,
              family: "lato"
            }
          },
          yaxes: [
            {
              tickFormatter: yFormatter,
              position: "left"
            }
          ],
          legend: {
            show: true
          }
        })
      );
    } else {
      plot = $.plot(
        $("#" + graph_id),
        data_base,
        $.extend(true, {}, options, {
          grid: {
            borderWidth: 1,
            hoverable: true,
            autoHighlight: true
          },
          xaxis: {
            min: ranges.xaxis.from,
            max: ranges.xaxis.to,
            font: {
              size: font_size + 2,
              color: legend_color,
              family: "lato"
            }
          },
          xaxes: [
            {
              mode: "time",
              timezone: "browser",
              localTimezone: true,
              tickSize: [maxticks_zoom, "hour"]
            }
          ],
          yaxis: {
            min: ranges.yaxis.from,
            max: ranges.yaxis.to,
            font: {
              size: font_size + 2,
              color: legend_color,
              family: "lato"
            }
          },
          yaxes: [
            {
              tickFormatter: yFormatter,
              position: "left"
            }
          ],
          legend: {
            show: true
          }
        })
      );
    }

    $("#menu_cancelzoom_" + graph_id)
      .attr("src", homeurl + "images/disable.svg")
      .removeClass("alpha50");

    max_draw["max"] = ranges.yaxis.to;
    max_draw["min"] = ranges.yaxis.from;

    // don't fire event on the overview to prevent eternal loop
    overview.setSelection(ranges, true);
  });

  $("#overview_" + graph_id).bind("plotselected", function(event, ranges) {
    plot.setSelection(ranges);
  });

  var updateLegendTimeout = null;
  var latestPosition = null;
  var currentPlot = null;

  // Update legend with the data of the plot in the mouse position
  function updateLegend() {
    updateLegendTimeout = null;
    var pos = latestPosition;
    var axes = currentPlot.getAxes();
    if (
      pos.x < axes.xaxis.min ||
      pos.x > axes.xaxis.max ||
      pos.y < axes.yaxis.min ||
      pos.y > axes.yaxis.max
    ) {
      return;
    }

    $("#timestamp_" + graph_id).show();

    var d = new Date(pos.x);
    var monthNames = [
      "Jan",
      "Feb",
      "Mar",
      "Apr",
      "May",
      "Jun",
      "Jul",
      "Aug",
      "Sep",
      "Oct",
      "Nov",
      "Dec"
    ];

    var date_format =
      (d.getDate() < 10 ? "0" : "") +
      d.getDate() +
      " " +
      monthNames[d.getMonth()] +
      " " +
      d.getFullYear() +
      "\n" +
      (d.getHours() < 10 ? "0" : "") +
      d.getHours() +
      ":" +
      (d.getMinutes() < 10 ? "0" : "") +
      d.getMinutes() +
      ":" +
      (d.getSeconds() < 10 ? "0" : "") +
      d.getSeconds();

    $("#timestamp_" + graph_id).text(date_format);

    var timesize = $("#timestamp_" + graph_id).width();

    var dataset = currentPlot.getData();

    var timenewpos =
      dataset[0].xaxis.p2c(pos.x) +
      $(".yAxis>div")
        .eq(0)
        .width();
    var canvaslimit = $("#" + graph_id).width();

    $("#timestamp_" + graph_id).css(
      "top",
      currentPlot.getPlotOffset().top -
        $("#timestamp_" + graph_id).height() +
        $("#legend_" + graph_id).height()
    );

    $("#timestamp_" + graph_id).css("color", "#000");
    $("#timestamp_" + graph_id).css("font-size", font_size + 2 + "px");
    $("#timestamp_" + graph_id).css("font-family", font);

    if (timesize + timenewpos > canvaslimit) {
      $("#timestamp_" + graph_id).css("left", timenewpos - timesize);
    } else {
      $("#timestamp_" + graph_id).css("left", timenewpos);
    }

    var i = 0;
    for (var k = 0; k < dataset.length; k++) {
      // k is the real series counter
      // i is the series counter without thresholds
      var series = dataset[k];
      if (series.label == null) {
        continue;
      }

      // find the nearest points, x-wise
      for (var j = 0; j < series.data.length; ++j) {
        if (series.data[j][0] > pos.x) {
          break;
        }

        if (series.data[j]) {
          var y = series.data[j][1];
          var x = Math.round(series.data[j][0]);
        }
      }

      var data_legend = [];

      // The graphs of points type and unknown graphs will dont be updated
      if (
        series_type[dataset[k]["label"]] != "points" &&
        series_type[dataset[k]["label"]] != "unknown" &&
        series_type[dataset[k]["label"]] != "percentil"
      ) {
        var label_aux = "";
        if (Object.keys(update_legend).length == 0) {
          label_aux = legend[series.label];

          $("#legend_" + graph_id + " .legendLabel")
            .eq(i)
            .html(
              label_aux +
                " value = " +
                number_format(y, 0, "", short_data, divisor) +
                " " +
                unit
            );
        } else {
          $.each(update_legend, function(index, value) {
            if (typeof value[x - 1] !== "undefined") {
              data_legend[index] =
                "<span class='legend-font-small'> Min: </span><span class='bolder'>" +
                number_format(value[x - 1].min, 0, unit, short_data, divisor) +
                "</span><span class='legend-font-small'> Max: </span><span class='bolder'>" +
                number_format(value[x - 1].max, 0, unit, short_data, divisor) +
                "</span><span class='legend-font-small'> Avg: </span><span class='bolder'>" +
                number_format(value[x - 1].avg, 0, unit, short_data, divisor) +
                "</span>";
            } else {
              data_legend[index] = " Min: " + 0 + " Max: " + 0 + " Avg: " + 0;
            }
          });

          if (typeof data_legend[series.label] !== "undefined") {
            label_aux =
              legend[series.label].split(
                '<span class="legend-font-small">'
              )[0] + data_legend[series.label];
            $("#legend_" + graph_id + " .legendLabel")
              .eq(i)
              .html(label_aux);
          }
        }
      }

      $("#legend_" + graph_id + " .legendLabel")
        .eq(i)
        .css("color", legend_color);
      $("#legend_" + graph_id + " .legendLabel")
        .eq(i)
        .css("font-size", font_size + 2 + "px");
      $("#legend_" + graph_id + " .legendLabel")
        .eq(i)
        .css("font-family", font);

      i++;
    }
  }

  // Events
  $("#overview_" + graph_id).bind("plothover", function(event, pos) {
    plot.setCrosshair({ x: pos.x, y: pos.y });
    currentPlot = plot;
    latestPosition = pos;
    if (!updateLegendTimeout) {
      updateLegendTimeout = setTimeout(updateLegend, 50);
    }
  });

  $("#" + graph_id).bind("plothover", function(event, pos) {
    overview.setCrosshair({ x: pos.x, y: pos.y });
    currentPlot = plot;
    latestPosition = pos;
    if (!updateLegendTimeout) {
      updateLegendTimeout = setTimeout(updateLegend, 50);
    }
  });

  $("#" + graph_id).bind("plotclick", function(event, pos, item) {
    plot.unhighlight();
    if (
      item &&
      item.series.label != "" &&
      item.series.label != null &&
      (item.series.label.search("alert") >= 0 ||
        item.series.label.search("event") >= 0)
    ) {
      plot.unhighlight();

      $("#extra_" + graph_id).css("width", "170px");
      $("#extra_" + graph_id).css("height", "auto");

      var extra_info = "<i>No info to show</i>";
      var extra_show = false;
      var extra_height = $("#extra_" + graph_id).height();
      var extra_width = parseInt(
        $("#extra_" + graph_id)
          .css("width")
          .split("px")[0]
      );
      var events_data = new Array();
      var offset_graph = plot.getPlotOffset();
      var offset_relative = plot.offset();
      var width_graph = plot.width();
      var height_legend = $("#legend_" + graph_id).height();
      var coord_x = pos.pageX - offset_relative.left + offset_graph.left;
      var coord_y = offset_graph.top + height_legend + extra_height + 50;

      if (coord_x + extra_width > width_graph) {
        coord_x = coord_x - extra_width;
      }

      $("#extra_" + graph_id).css("left", coord_x);
      $("#extra_" + graph_id).css("top", coord_y);

      if (
        item.series.label.search("alert") >= 0 ||
        item.series.label.search("event") >= 0
      ) {
        $.each(events_array, function(i, v) {
          $.each(v, function(index, value) {
            if (
              value.utimestamp == item.datapoint[0] / 1000 ||
              value.utimestamp == item.datapoint[0] / 1000 - 1
            ) {
              events_data = value;
            }
          });
        });

        var extra_color = "#ffffff";
        if (events_data.event_type.search("alert") >= 0) {
          extra_color = "#FFA631";
        } else if (events_data.event_type.search("critical") >= 0) {
          extra_color = "#e63c52";
        } else if (events_data.event_type.search("warning") >= 0) {
          extra_color = "#f3b200";
        } else if (events_data.event_type.search("unknown") >= 0) {
          extra_color = "#4a83f3";
        } else if (events_data.event_type.search("normal") >= 0) {
          extra_color = "#82b92e";
        } else {
          extra_color = "#ffffff";
        }

        $("#extra_" + graph_id).css("background-color", extra_color);

        extra_info = "<b>" + events_data.evento + ":";
        extra_info +=
          '<br><br><span style="font-weight: normal;">Time: ' +
          events_data.timestamp;
        extra_show = true;
      }

      if (extra_show) {
        $("#extra_" + graph_id).html(extra_info);
        $("#extra_" + graph_id).css("display", "");
      }
      plot.highlight(item.series, item.datapoint);
    } else {
      $("#extra_" + graph_id).html("");
      $("#extra_" + graph_id).css("display", "none");
    }
  });

  if (!vconsole) {
    $("#" + graph_id).bind("mouseout", resetInteractivity);
    $("#overview_" + graph_id).bind("mouseout", resetInteractivity);
  }

  if (image_threshold) {
    var y_recal = plot.getAxes().yaxis.max;
    if (!thresholded) {
      // Recalculate the y axis
      y_recal = axis_thresholded(
        threshold_data,
        plot.getAxes().yaxis.min,
        plot.getAxes().yaxis.max,
        red_threshold,
        extremes,
        red_up
      );
    }

    var datas_threshold = add_threshold(
      data_base,
      threshold_data,
      plot.getAxes().yaxis.min,
      y_recal.max,
      red_threshold,
      extremes,
      red_up,
      markins_graph
    );

    plot = $.plot(
      $("#" + graph_id),
      datas_threshold,
      $.extend(true, {}, options, {
        yaxis: {
          max: y_recal.max
        },
        xaxis: {
          min: plot.getAxes().xaxis.min,
          max: plot.getAxes().xaxis.max
        }
      })
    );

    thresholded = true;
  }

  // Reset interactivity styles
  function resetInteractivity() {
    $("#timestamp_" + graph_id).hide();
    var dataset = plot.getData();
    for (i = 0; i < dataset.length; ++i) {
      var series = dataset[i];
      var label_aux = legend[series.label];
      $("#legend_" + graph_id + " .legendLabel")
        .eq(i)
        .html(label_aux);
    }
    $("#legend_" + graph_id + " .legendLabel").css("color", legend_color);
    $("#legend_" + graph_id + " .legendLabel").css(
      "font-size",
      font_size + 2 + "px"
    );
    $("#legend_" + graph_id + " .legendLabel").css("font-family", font);

    plot.clearCrosshair();
    overview.clearCrosshair();
  }

  function yFormatter(v) {
    var formatted = v;

    if (short_data) {
      formatted = number_format(v, force_integer, unit, short_data, divisor);
    } else {
      // It is an integer.
      if (v - Math.floor(v) == 0) {
        formatted = number_format(v, force_integer, unit, 2, divisor);
      }
    }

    // Get only two decimals.
    if (typeof formatted != "string") {
      formatted = Math.round(formatted * 100) / 100;
    }
    return formatted;
  }

  function lFormatter(v) {
    var style =
      "color:" +
      legend_color +
      "; font-family: " +
      "lato" +
      "Font; font-size:" +
      (parseInt(font_size) + 2) +
      "px;";
    return '<span style="' + style + '" class="lato">' + legend[v] + "</span>";
  }

  $("#overview_" + graph_id).css("display", "none");

  if (menu) {
    var parent_height;
    $("#menu_overview_" + graph_id).click(function() {
      $("#overview_" + graph_id).toggle();
    });

    $("#menu_export_csv_" + graph_id).click(function(e) {
      e.preventDefault();
      blockResubmit($(this));
      plot.exportDataCSV();
      var es_firefox =
        navigator.userAgent.toLowerCase().indexOf("firefox") > -1;
      if (es_firefox) {
        $("#dialog")
          .css("visibility", "visible")
          .dialog();
      }
      delete data_base[0].threshold;
      plot = $.plot(
        $("#" + graph_id),
        data_base,
        $.extend(true, {}, options, {
          legend: { show: true }
        })
      );
    });

    $("#menu_threshold_" + graph_id).click(function() {
      datas = new Array();
      if (thresholded) {
        $.each(data_base, function() {
          datas.push(this);
        });

        delete data_base[0].threshold;

        plot = $.plot(
          $("#" + graph_id),
          data_base,
          $.extend(true, {}, options, {
            yaxis: {
              min: max_draw["min"],
              max: max_draw["max"]
            },
            xaxis: {
              min: plot.getAxes().xaxis.min,
              max: plot.getAxes().xaxis.max
            }
          })
        );
        thresholded = false;
      } else {
        var y_recal = plot.getAxes().yaxis.max;
        if (!thresholded) {
          // Recalculate the y axis
          y_recal = axis_thresholded(
            threshold_data,
            plot.getAxes().yaxis.min,
            plot.getAxes().yaxis.max,
            red_threshold,
            extremes,
            red_up
          );
        }

        datas_threshold = add_threshold(
          data_base,
          threshold_data,
          plot.getAxes().yaxis.min,
          y_recal.max,
          red_threshold,
          extremes,
          red_up,
          markins_graph
        );

        plot = $.plot(
          $("#" + graph_id),
          datas_threshold,
          $.extend(true, {}, options, {
            yaxis: {
              min: max_draw["min"],
              max: y_recal.max
            },
            xaxis: {
              min: plot.getAxes().xaxis.min,
              max: plot.getAxes().xaxis.max
            }
          })
        );

        thresholded = true;
      }
    });

    $("#menu_cancelzoom_" + graph_id).click(function() {
      // cancel the zooming
      delete data_base[0].threshold;
      plot = $.plot(
        $("#" + graph_id),
        data_base,
        $.extend(true, {}, options, {
          legend: { show: true }
        })
      );
      $("#menu_cancelzoom_" + graph_id).addClass("alpha50");
      //$("#menu_cancelzoom_" + graph_id).attr("class", "invert_filter");
      overview.clearSelection();
      thresholded = false;
      max_draw = [];
    });

    // Adjust the menu image on top of the plot
    // If there is no legend we increase top-padding to make space to the menu
    if (legend.length == 0) {
      $("#menu_" + graph_id)
        .parent()
        .css("padding-top", $("#menu_" + graph_id).css("height"));
    }

    // Add bottom margin in the legend
    // Estimated height of 24 (works fine with this data in all browsers)
    $("#legend_" + graph_id).css("margin-bottom", "10px");
    parent_height = parseInt(
      $("#menu_" + graph_id)
        .parent()
        .css("height")
        .split("px")[0]
    );
    adjust_menu(graph_id, plot, parent_height, width, show_legend);
  }
}

function format_unit_yaxes(y) {
  var how_bigger = [];

  if (y > 1000000) {
    how_bigger["unit"] = "M";
    how_bigger["y"] = y / 1000000;
  } else if (y > 1000) {
    how_bigger["unit"] = "K";
    how_bigger["y"] = y / 1000;
  } else if (y < -1000000) {
    how_bigger["unit"] = "M";
    how_bigger["y"] = y / 1000000;
  } else if (y < -1000) {
    how_bigger["unit"] = "K";
    how_bigger["y"] = y / 1000;
  } else {
    how_bigger["unit"] = "";
    how_bigger["y"] = y;
  }

  return how_bigger;
}

function adjust_menu(graph_id, plot, parent_height, width, show_legend) {
  var left_ticks_width = 0;
  if (
    $("#" + graph_id + " .xAxis .tickLabel")
      .eq(0)
      .css("width") != undefined
  ) {
    left_ticks_width = $("#" + graph_id + " .xAxis .tickLabel")
      .eq(0)
      .css("width")
      .split("px")[0];
  }

  var legend_height = 0;
  if (show_legend) {
    legend_height =
      parseInt(
        $("#legend_" + graph_id)
          .css("height")
          .split("px")[0]
      ) +
      parseInt(
        $("#legend_" + graph_id)
          .css("margin-top")
          .split("px")[0]
      );
  }

  var menu_height = "25";
  if (
    $("#menu_" + graph_id).height() != undefined &&
    $("#menu_" + graph_id).height() > 20
  ) {
    menu_height = $("#menu_" + graph_id).height();
  }

  var offset = $("#" + graph_id)[0].offsetTop;

  $("#menu_" + graph_id).css("top", offset + "px");

  $("#menu_" + graph_id).show();
}

function set_watermark(graph_id, plot, watermark_src) {
  var img = new Image();

  img.src = watermark_src;
  var context = plot.getCanvas().getContext("2d");

  // Once it's loaded draw the image on the canvas.
  img.addEventListener(
    "load",
    function() {
      // Now resize the image: x, y, w, h.
      var down_ticks_height = 0;
      if (
        $("#" + graph_id + " .yAxis .tickLabel")
          .eq(0)
          .css("height") != undefined
      ) {
        down_ticks_height = $("#" + graph_id + " .yAxis .tickLabel")
          .eq(0)
          .css("height")
          .split("px")[0];
      }

      var left_pos =
        parseInt(context.canvas.width) -
        $("#watermark_image_" + graph_id)[0].width -
        30;
      var top_pos = 7;
      //var top_pos = parseInt(context.canvas.height - down_ticks_height - 10) - $('#watermark_image_'+graph_id)[0].height;
      //var left_pos = 380;
      context.drawImage(this, left_pos, top_pos);
    },
    false
  );
}

//Ajusta la grafica pequenña con el desplazamiento del eje y
function adjust_left_width_canvas(adapter_id, adapted_id) {
  var adapter_left_margin = $("#" + adapter_id + " .yAxis .tickLabel").width();
  var adapted_pix = $("#" + adapted_id).width();
  var new_adapted_width = adapted_pix - adapter_left_margin;
  $("#" + adapted_id).width(new_adapted_width);
  $("#" + adapted_id).css("margin-left", adapter_left_margin);
}

//Ajusta el ancho de la grafica pequeña con respecto a la grande
function update_left_width_canvas(graph_id) {
  $("#overview_" + graph_id).width($("#" + graph_id).width());
  $("#overview_" + graph_id).css(
    "margin-left",
    $("#" + graph_id + " .yAxis .tickLabel").width()
  );
}

function check_adaptions(graph_id) {
  var classes = $("#" + graph_id)
    .attr("class")
    .split(" ");
  $.each(classes, function(i, v) {
    // If has a class starting with adapted, we adapt it
    if (v.split("_")[0] == "adapted") {
      var adapter_id = $(".adapter_" + v.split("_")[1]).attr("id");
      adjust_left_width_canvas(adapter_id, graph_id);
    }
  });
}

function number_format(number, force_integer, unit, short_data, divisor) {
  divisor = typeof divisor !== "undefined" ? divisor : 1000;
  var decimals = 2;

  if (unit == "KB") {
    return Math.round(number * decimals) + unit;
  }

  // Set maximum decimal precision to 99 in case short_data is not set.
  if (!short_data) {
    short_data = 99;
  }

  if (force_integer) {
    if (Math.round(number) != number) {
      return "";
    }
  } else {
    short_data++;
    decimals = pad(1, short_data, 0);
    number = Math.round(number * decimals) / decimals;
  }

  var shorts = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
  var pos = 0;

  while (Math.abs(number) >= divisor) {
    // As long as the number can be divided by 1000 or 1024.
    pos++;
    number = number / divisor;
  }

  if (divisor) {
    number = Math.round(number * decimals) / decimals;
  } else {
    number = Math.round(number * decimals);
  }

  if (isNaN(number)) {
    number = 0;
  }

  return number + " " + shorts[pos] + unit;
}

function pad(input, length, padding) {
  var str = input + "";
  return length <= str.length ? str : pad(str + padding, length, padding);
}

// Recalculate the threshold data depends on warning and critical
function axis_thresholded(
  threshold_data,
  y_min,
  y_max,
  red_threshold,
  extremes,
  red_up
) {
  var y = {
    min: 0,
    max: 0
  };

  // Default values
  var yaxis_resize = {
    up: null,
    normal_up: 0,
    normal_down: 0,
    down: null
  };
  // Resize the y axis to display all intervals
  $.each(threshold_data, function() {
    if (/_up/.test(this.id)) {
      yaxis_resize["up"] = this.data[0][1];
    }
    if (/_down/.test(this.id)) {
      if (/critical/.test(this.id)) {
        yaxis_resize["down"] = red_threshold;
      } else {
        yaxis_resize["down"] = extremes[this.id];
      }
    }
    if (/_normal/.test(this.id)) {
      var end;
      if (/critical/.test(this.id)) {
        end = red_up;
      } else {
        end = extremes[this.id + "_2"];
      }
      if (yaxis_resize["normal_up"] < end) yaxis_resize["normal_up"] = end;
      if (yaxis_resize["normal_down"] > this.data[0][1])
        yaxis_resize["normal_down"] = this.data[0][1];
    }
  });

  // If you need to display a up or a down bar, display 10% of data height
  var margin_up_or_down = (y_max - y_min) * 0.1;

  // Calculate the new axis
  y["max"] =
    yaxis_resize["normal_up"] > y_max ? yaxis_resize["normal_up"] : y_max;
  y["min"] =
    yaxis_resize["normal_down"] > y_min ? yaxis_resize["normal_down"] : y_min;
  if (yaxis_resize["up"] !== null) {
    y["max"] =
      yaxis_resize["up"] + margin_up_or_down < y_max
        ? y_max
        : yaxis_resize["up"] + margin_up_or_down;
  }
  if (yaxis_resize["down"] !== null) {
    y["min"] =
      yaxis_resize["down"] - margin_up_or_down < y_min
        ? yaxis_resize["up"] + margin_up_or_down
        : y_min;
  }

  return y;
}

//add threshold
function add_threshold(
  data_base,
  threshold_data,
  y_min,
  y_max,
  red_threshold,
  extremes,
  red_up,
  markins_graph
) {
  var datas = new Array();

  $.each(data_base, function() {
    datas.push(this);
  });

  var threshold_array = [];

  // Resize the threshold data
  $.each(threshold_data, function(index, value) {
    threshold_array[index] = [];

    if (/_up/.test(this.id)) {
      this.bars.barWidth = y_max - this.data[0][1];

      if (/critical/.test(this.id)) {
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = y_max;
        threshold_array[index]["color"] = "red";
      } else {
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = y_max;
        threshold_array[index]["color"] = "yellow";
      }

      if (y_min > this.data[0][1]) {
        this.bars.barWidth = this.bars.barWidth - (y_min - this.data[0][1]);
        this.data[0][1] = y_min;
      }
    }

    if (/_down/.test(this.id)) {
      var end;
      if (/critical/.test(this.id)) {
        end = red_threshold;
      } else {
        end = extremes[this.id];
      }

      this.bars.barWidth = end - y_min;
      this.data[0][1] = y_min;

      if (/critical/.test(this.id)) {
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = this.bars.barWidth;
        threshold_array[index]["color"] = "red";
      } else {
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = this.bars.barWidth;
        threshold_array[index]["color"] = "yellow";
      }
    }

    if (/_normal/.test(this.id)) {
      var end;
      if (/critical/.test(this.id)) {
        end = red_up;
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = end;
        threshold_array[index]["color"] = "red";
      } else {
        var first = extremes[this.id + "_1"];
        var second = extremes[this.id + "_2"];
        if (first > second) {
          end = first;
        } else {
          end = second;
        }
        threshold_array[index]["min"] = this.data[0][1];
        threshold_array[index]["max"] = end;
        threshold_array[index]["color"] = "yellow";
      }

      if (this.data[0][1] < y_min) {
        this.bars.barWidth = end - y_min;
        this.data[0][1] = y_min;
        end = this.bars.barWidth + this.data[0][1];

        if (/critical/.test(this.id)) {
          threshold_array[index]["min"] = this.data[0][1];
          threshold_array[index]["max"] = this.data[0][1] + this.bars.barWidth;
          threshold_array[index]["color"] = "red";
        } else {
          threshold_array[index]["min"] = this.data[0][1];
          threshold_array[index]["max"] = this.data[0][1] + this.bars.barWidth;
          threshold_array[index]["color"] = "yellow";
        }
      }

      if (end > y_max) {
        this.bars.barWidth = y_max - this.data[0][1];

        if (/critical/.test(this.id)) {
          threshold_array[index]["min"] = this.data[0][1];
          threshold_array[index]["max"] = this.data[0][1] + this.bars.barWidth;
          threshold_array[index]["color"] = "red";
        } else {
          threshold_array[index]["min"] = this.data[0][1];
          threshold_array[index]["max"] = this.data[0][1] + this.bars.barWidth;
          threshold_array[index]["color"] = "yellow";
        }
      }
    }

    if (markins_graph && this.bars.barWidth > 0) {
      datas.push(this);
    }
  });

  var extreme_threshold_array = [];
  var i = 0;
  var flag = true;

  $.each(threshold_array, function(index, value) {
    flag = true;
    extreme_threshold_array[i] = {
      below: value["max"],
      color: value["color"]
    };
    i++;
    $.each(threshold_array, function(i, v) {
      if (value["min"] == v["max"]) {
        return (flag = false);
      }
    });
    if (flag) {
      extreme_threshold_array[i] = {
        below: value["min"],
        color: datas[0].color
      };
      i++;
    }
  });

  datas[0].threshold = extreme_threshold_array;

  return datas;
}

function reduceText(text, maxLength) {
  if (!text) return text;
  if (text.length <= maxLength) return text;
  var firstSlideEnd = parseInt((maxLength - 3) / 1.6);
  var str_cut = text.substr(0, firstSlideEnd);
  //return str_cut + "...<br>" + text.substr(-firstSlideEnd - 3);
  return str_cut + "..." + text.substr(-firstSlideEnd - 3);
}
