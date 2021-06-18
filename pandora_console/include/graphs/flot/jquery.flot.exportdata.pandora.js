(function($) {
  var options = {
    export: {
      export_data: false, // or true
      labels_long: null,
      homeurl: ""
    }
  };

  function init(plot) {
    plot.exportDataCSV = function(args) {
      //amount = plot.getOptions().export.type,
      //options = options || {};

      // Options
      var type = "csv";
      type = type.toLowerCase().trim();

      var graphData,
        dataObject,
        dataObjects = plot.getData(),
        result = [];

      // Throw errors
      var retrieveDataOject = function(dataObjects) {
        var result;

        if (typeof dataObjects === "undefined")
          throw new Error("Empty parameter");

        // Try to retrieve the avg set (not 100% reliable, I know)
        if (dataObjects.length == 1) {
          result = dataObjects.shift();
        }
        if (dataObjects.length > 1) {
          dataObjects.forEach(function(element) {
            if (/^Avg.:/i.test(element.label)) result = element;
          });

          // If the avg set is missing, retrieve the first set
          if (typeof result === "undefined") result = dataObjects.shift();
        }

        if (typeof result === "undefined") throw new Error("Empty result");

        return result;
      };

      // Throw errors
      var processDataObject = function(dataObject) {
        var result;

        if (typeof dataObject === "undefined")
          throw new Error("Empty parameter");

        if (
          typeof dataObject.data === "undefined" ||
          !(dataObject.data instanceof Array)
        )
          throw new Error("Object malformed");

        /* {
         *   head: [<column>,<column>,...,<column>],
         *   data: [
         *     [<data>,<data>,...,<data>],
         *     [<data>,<data>,...,<data>],
         *     ...,
         *     [<data>,<data>,...,<data>],
         *   ]
         * }
         */
        if (type === "csv") {
          result = {
            head: ["timestamp", "date", "value", "label"],
            data: []
          };

          dataObject.data.forEach(function(item, index) {
            var timestamp = item[0];

            var d = new Date(item[0]);
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

            date_format =
              (d.getDate() < 10 ? "0" : "") +
              d.getDate() +
              " " +
              monthNames[d.getMonth()] +
              " " +
              d.getFullYear() +
              " " +
              (d.getHours() < 10 ? "0" : "") +
              d.getHours() +
              ":" +
              (d.getMinutes() < 10 ? "0" : "") +
              d.getMinutes() +
              ":" +
              (d.getSeconds() < 10 ? "0" : "") +
              d.getSeconds();

            var date = date_format;

            var value = item[1];

            var clean_label = plot.getOptions().export.labels_long[
              dataObject.label
            ];
            clean_label = clean_label.replace(new RegExp("&#x20;", "g"), " ");
            result.data.push([timestamp, date, value, clean_label]);
          });
        } else if (type === "json") {
          /* [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ],
           * [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ],
           * ...,
           * [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ]
           */
          result = [];

          dataObject.data.forEach(function(item, index) {
            var date = "",
              value = item[1];

            // Long labels are preferred
            if (typeof labels_long[index] !== "undefined")
              date = labels_long[index];
            else if (typeof labels[index] !== "undefined") date = labels[index];

            result.push({
              date: date,
              value: value,
              label: dataObject.label
            });
          });
        }

        if (typeof result === "undefined") throw new Error("Empty result");

        return result;
      };

      try {
        var elements = [];
        dataObject = retrieveDataOject(dataObjects);
        if (dataObject) {
          elements.push(processDataObject(dataObject));
        }
        dataObjects.forEach(function(element) {
          elements.push(processDataObject(element));
        });
        graphData = elements;

        // Transform the object data into a string
        // cause PHP has limitations in the number
        // of POST params received.
        var graphDataStr = JSON.stringify(graphData);

        // Build form
        var $form = $("<form></form>"),
          $dataInput = $("<input>"),
          $typeInput = $("<input>"),
          $separatorInput = $("<input>"),
          $excelInput = $("<input>");

        $dataInput
          .prop("name", "data")
          .prop("type", "text")
          .prop("value", graphDataStr);

        $typeInput
          .prop("name", "type")
          .prop("type", "text")
          .prop("value", type);

        $separatorInput
          .prop("name", "separator")
          .prop("type", "text")
          .prop("value", ";");

        $excelInput
          .prop("name", "excel_encoding")
          .prop("type", "text")
          .prop("value", 0);

        $form
          .prop("method", "POST")
          .prop(
            "action",
            plot.getOptions().export.homeurl + "include/graphs/export_data.php"
          )
          .append($dataInput, $typeInput, $separatorInput, $excelInput)
          .hide()
          // Firefox made me write into the DOM for this :(
          .appendTo("body")
          .submit();
      } catch (e) {
        alert("There was an error exporting the data");
      }
    };

    plot.exportDataJSON = function(args) {
      //amount = plot.getOptions().export.type,
      //options = options || {};

      // Options
      var type = "json";
      type = type.toLowerCase().trim();

      var graphData,
        dataObject,
        dataObjects = plot.getData(),
        result = [];

      // Throw errors
      var retrieveDataOject = function(dataObjects) {
        var result;

        if (typeof dataObjects === "undefined")
          throw new Error("Empty parameter");

        // Try to retrieve the avg set (not 100% reliable, I know)
        if (dataObjects.length == 1) {
          result = dataObjects.shift();
        }
        if (dataObjects.length > 1) {
          dataObjects.forEach(function(element) {
            if (/^Avg.:/i.test(element.label)) result = element;
          });

          // If the avg set is missing, retrieve the first set
          if (typeof result === "undefined") result = dataObjects.shift();
        }

        if (typeof result === "undefined") throw new Error("Empty result");

        return result;
      };

      // Throw errors
      var processDataObject = function(dataObject) {
        var result;

        if (typeof dataObject === "undefined")
          throw new Error("Empty parameter");

        if (
          typeof dataObject.data === "undefined" ||
          !(dataObject.data instanceof Array)
        )
          throw new Error("Object malformed");

        /* {
         *   head: [<column>,<column>,...,<column>],
         *   data: [
         *     [<data>,<data>,...,<data>],
         *     [<data>,<data>,...,<data>],
         *     ...,
         *     [<data>,<data>,...,<data>],
         *   ]
         * }
         */
        if (type === "csv") {
          result = {
            head: ["date", "value", "label"],
            data: []
          };

          dataObject.data.forEach(function(item, index) {
            var date = "",
              value = item[1];

            // Long labels are preferred
            if (
              typeof plot.getOptions().export.labels_long[index] !== "undefined"
            )
              date = plot.getOptions().export.labels_long[index];
            else if (typeof labels[index] !== "undefined") date = labels[index];

            result.data.push([date, value, dataObject.label]);
          });
        } else if (type === "json") {
          /* [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ],
           * [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ],
           * ...,
           * [
           *   {
           *     'date': <date>,
           *     'value': <value>
           *   }
           * ]
           */
          result = [];

          dataObject.data.forEach(function(item, index) {
            var date = "",
              value = item[1];

            // Long labels are preferred
            if (typeof labels_long[index] !== "undefined")
              date = labels_long[index];
            else if (typeof labels[index] !== "undefined") date = labels[index];

            result.push({
              date: date,
              value: value,
              label: dataObject.label
            });
          });
        }

        if (typeof result === "undefined") throw new Error("Empty result");

        return result;
      };

      try {
        var elements = [];
        var custom_graph = $("input:hidden[name=custom_graph]").value;

        if (custom_graph) {
          dataObject = retrieveDataOject(dataObjects);
          dataObjects.forEach(function(element) {
            elements.push(processDataObject(element));
          });
          graphData = elements;
        } else {
          dataObject = retrieveDataOject(dataObjects);
          elements.push(processDataObject(dataObject));
          graphData = elements;
        }

        // Transform the object data into a string
        // cause PHP has limitations in the number
        // of POST params received.
        var graphDataStr = JSON.stringify(graphData);

        // Build form
        var $form = $("<form></form>"),
          $dataInput = $("<input>"),
          $typeInput = $("<input>"),
          $separatorInput = $("<input>"),
          $excelInput = $("<input>");

        $dataInput
          .prop("name", "data")
          .prop("type", "text")
          .prop("value", graphDataStr);

        $typeInput
          .prop("name", "type")
          .prop("type", "text")
          .prop("value", type);

        $separatorInput
          .prop("name", "separator")
          .prop("type", "text")
          .prop("value", ";");

        $excelInput
          .prop("name", "excel_encoding")
          .prop("type", "text")
          .prop("value", 0);

        $form
          .prop("method", "POST")
          .prop(
            "action",
            plot.getOptions().export.homeurl + "include/graphs/export_data.php"
          )
          .append($dataInput, $typeInput, $separatorInput, $excelInput)
          .hide()
          // Firefox made me write into the DOM for this :(
          .appendTo("body")
          .submit();
      } catch (e) {
        alert("There was an error exporting the data");
      }
    };
  }

  $.plot.plugins.push({
    init: init,
    options: options,
    name: "exportdata",
    version: "0.1"
  });
})(jQuery);
