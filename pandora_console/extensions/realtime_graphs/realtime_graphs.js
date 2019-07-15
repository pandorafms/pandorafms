(function() {
  var numberOfPoints = 100;
  var refresh = parseInt($("#refresh").val());
  var incremental =
    $("#checkbox-incremental").is(":checked") ||
    $("#hidden-incremental").val() == 1;
  var lastIncVal = null;
  var intervalRef = null;
  var currentXHR = null;

  var plot;
  var plotOptions = {
    legend: {
      container: $("#chartLegend")
    },
    xaxis: {
      tickFormatter: function(timestamp, axis) {
        var date = new Date(timestamp * 1000);

        var server_timezone_offset = get_php_value("timezone_offset");
        var local_timezone_offset = date.getTimezoneOffset() * 60 * -1;

        if (server_timezone_offset != local_timezone_offset) {
          // If timezone of server and client is different, adjust the time to the server
          date = new Date(
            (timestamp + (server_timezone_offset - local_timezone_offset)) *
              1000
          );
        }

        var hours =
          date.getHours() < 10 ? "0" + date.getHours() : date.getHours();
        var minutes =
          date.getMinutes() < 10 ? "0" + date.getMinutes() : date.getMinutes();
        var seconds =
          date.getSeconds() < 10 ? "0" + date.getSeconds() : date.getSeconds();
        var formattedTime = hours + ":" + minutes + ":" + seconds;
        return formattedTime;
      }
    },
    yaxis: {
      tickFormatter: function(value, axis) {
        return shortNumber(roundToTwo(value));
      }
    },
    series: {
      lines: {
        lineWidth: 2,
        fill: true
      }
    },
    colors: ["#6db431"]
  };

  function updatePlot(data) {
    plot = $.plot($(".graph"), data, plotOptions);
  }

  function requestData() {
    var rel_path = $("#hidden-rel_path").val();

    currentXHR = $.ajax({
      url: rel_path + "extensions/realtime_graphs/ajax.php",
      type: "POST",
      dataType: "json",
      data: {
        graph: $("#graph :selected").val(),
        graph_title: $("#graph :selected").html(),
        snmp_community: $("#text-snmp_community").val(),
        snmp_oid: $("#text-snmp_oid").val(),
        snmp_ver: $("#snmp_version :selected").val(),
        snmp_address: $("#text-ip_target").val(),
        refresh: refresh
      },
      success: function(serie) {
        var timestamp = serie.data[0][0];
        var data = plot.getData();

        if (incremental) {
          var currentVal = serie.data[0][1];
          // Try to avoid the first value, cause we need at least two values to get the increment
          serie.data[0][1] = lastIncVal == null ? 0 : currentVal - lastIncVal;
          // Incremental is always positive
          if (serie.data[0][1] < 0) serie.data[0][1] = 0;
          // Store the current value to use it into the next request
          lastIncVal = currentVal;
        }

        if (data.length === 0) {
          for (i = 0; i < numberOfPoints; i++) {
            var step = i * (refresh / 1000);
            serie.data.unshift([timestamp - step, 0]);
          }

          serie = [serie];
          updatePlot(serie);
          return;
        }

        data[0].label = serie.label;
        if (data[0].data.length >= numberOfPoints) {
          data[0].data.shift();
        }

        data[0].data.push(serie.data[0]);
        updatePlot(data);
      }
    });
  }

  function startDataPooling() {
    intervalRef = window.setInterval(requestData, refresh);
  }

  function resetDataPooling() {
    if (currentXHR !== null) currentXHR.abort();
    // Stop and start the interval
    window.clearInterval(intervalRef);
    startDataPooling();
  }

  function clearGraph() {
    var data = plot.getData();
    if (data.length === 0) return;

    for (i = 0; i < data[0].data.length; i++) {
      data[0].data[i][1] = 0;
    }
    if (incremental) lastIncVal = null;

    updatePlot(data);

    resetDataPooling();
  }

  function shortNumber(number) {
    if (Math.round(number) != number) return number;
    number = Number.parseInt(number);
    if (Number.isNaN(number)) return number;

    var shorts = ["", "K", "M", "G", "T", "P", "E", "Z", "Y"];
    var pos = 0;

    while (number >= 1000 || number <= -1000) {
      pos++;
      number = number / 1000;
    }

    return number + " " + shorts[pos];
  }

  function roundToTwo(num) {
    return +(Math.round(num + "e+2") + "e-2");
  }

  $("#graph").change(function() {
    $("form#realgraph").submit();
  });

  $("#refresh").change(function() {
    refresh = parseInt($("#refresh").val());
    resetDataPooling();
  });

  $("#checkbox-incremental").change(function() {
    incremental = $("#checkbox-incremental").is(":checked");
    clearGraph();
  });

  updatePlot([]);
  requestData();
  startDataPooling();

  // Expose this functions
  window.realtimeGraphs = {
    clearGraph: clearGraph,
    setOID: setOID,
    snmpBrowserWindow: snmpBrowserWindow
  };
})();
