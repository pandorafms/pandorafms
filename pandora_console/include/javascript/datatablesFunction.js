/* global $ */

var dt = dt;
var config = config;

var datacolumns = [];
var datacolumnsTemp = [];
dt.datacolumns.forEach(column => {
  if (column === null) return;
  if (typeof column !== "string") {
    datacolumnsTemp = { data: column.text, className: column.class };
    datacolumns.push(datacolumnsTemp);
  } else {
    datacolumnsTemp = { data: column, className: "no-class" };
    datacolumns.push(datacolumnsTemp);
  }
});

var paginationClass = "pandora_pagination";
if (typeof dt.pagination_class !== "undefined") {
  paginationClass = dt.pagination_class;
}

var processing = "";
if (typeof dt.processing === "undefined") {
  processing = dt.processing;
}

var ajaxReturn = "";
var ajaxReturnFunction = "";
if (
  typeof dt.ajax_return_operation !== "undefined" &&
  dt.ajax_return_operation !== "" &&
  typeof dt.ajax_return_operation_function !== "undefined" &&
  dt.ajax_return_operation_function !== ""
) {
  ajaxReturn = dt.ajax_return_operation;
  ajaxReturnFunction = dt.ajax_return_operation_function;
}

var serverSide = true;
if (typeof dt.data_element !== "undefined") {
  serverSide = false;
}

var paging = true;
if (typeof dt.paging !== "undefined") {
  paging = dt.paging;
}

var pageLength = parseInt(dt.default_pagination);

var searching = false;
if (typeof dt.searching !== "undefined" && dt.searching === true) {
  searching = dt.searching;
}

var dom = "plfrtiB";
if (typeof dt.dom_elements !== "undefined") {
  dom = dt.dom_elements;
}

var lengthMenu = [
  [pageLength, 5, 10, 20, 100, 200, 500, 1000, -1],
  [pageLength, 5, 10, 20, 100, 200, 500, 1000, "All"]
];
if (typeof dt.pagination_options !== "undefined") {
  lengthMenu = dt.pagination_options;
}

if (dt.pagination_options_order === "true") {
  lengthMenu[0] = lengthMenu[0].sort((a, b) => a - b);
  lengthMenu[1] = lengthMenu[1].sort((a, b) => a - b);
}

var ordering = true;
if (typeof dt.ordering !== "undefined" && dt.ordering === false) {
  ordering = dt.ordering;
}

var order = [[0, "asc"]];
if (typeof dt.order !== "undefined") {
  order = [[dt.order.order, dt.order.direction]];
}

var zeroRecords = "";
if (typeof dt.zeroRecords !== "undefined") {
  zeroRecords = `${dt.zeroRecords}`;
}

var emptyTable = "";
if (typeof dt.emptyTable !== "undefined") {
  emptyTable = `${dt.emptyTable}`;
}

var no_sortable_columns = [];
if (typeof dt.no_sortable_columns !== "undefined") {
  no_sortable_columns = Object.values(dt.no_sortable_columns);
}

var columnDefs = [];
if (typeof dt.columnDefs === "undefined") {
  columnDefs = [
    { className: "no-class", targets: "_all" },
    { bSortable: false, targets: no_sortable_columns }
  ];
} else {
  columnDefs = dt.columnDefs;
}

var csvClassName = "csv-button";
if (dt.mini_csv === true) {
  csvClassName = "mini-csv-button";
}

var csvFieldSeparator = ";";
if (typeof dt.csv_field_separator !== "undefined") {
  csvFieldSeparator = dt.csv_field_separator;
}

var csvHeader = true;
if (dt.csv_header === false) {
  csvHeader = false;
}

var csvExcludeLast = "";
if (dt.csv_exclude_latest === true) {
  csvExcludeLast = "th:not(:last-child)";
}

var ajaxData = "";
if (typeof dt.ajax_data !== "undefined") {
  ajaxData = dt.ajax_data;
}

var startDisabled = false;
if (dt.startDisabled === true) {
  startDisabled = true;
}

var noMoveElementsToAction = false;
if (dt.no_move_elements_to_action === true) {
  noMoveElementsToAction = true;
}

var showAlwaysPagination = false;

$(document).ready(function() {
  function checkPages() {
    if (dt_table.page.info().pages > 1) {
      $(
        "div.pagination-child-div > .dataTables_paginate.paging_simple_numbers"
      ).show();
      $(`#${dt.id}_paginate`).show();
    } else {
      $(
        "div.pagination-child-div > .dataTables_paginate.paging_simple_numbers"
      ).hide();
      $(`#${dt.id}_paginate`).hide();
    }
  }

  function moveElementsToActionButtons() {
    $(".action_buttons_right_content").html(
      '<div class="pagination-child-div"></div>'
    );
    $(".pagination-child-div").append(
      $(`#${dt.id}_wrapper > .dataTables_paginate.paging_simple_numbers`).attr(
        "style",
        "margin-right: 10px;"
      )
    );
    $(".pagination-child-div").append(
      $(`#${dt.id}_wrapper > .dataTables_length`)
    );
    $(".pagination-child-div").append($(`#${dt.id}_wrapper > .dt-buttons`));
    $(".pagination-child-div").append(
      $(`#${dt.id}_wrapper > .dataTables_filter`)
    );
  }

  $.fn.dataTable.ext.errMode = "none";
  $.fn.dataTable.ext.classes.sPageButton = paginationClass;

  if (dt.mini_pagination === true) {
    $.fn.dataTable.ext.classes.sPageButton = `${paginationClass} mini-pandora-pagination`;
  }

  var settings_datatable = {
    processing: true,
    responsive: true,
    serverSide,
    paging,
    pageLength,
    searching,
    dom,
    lengthMenu,
    ordering,
    order,
    columns: eval(datacolumns),
    columnDefs,
    language: {
      url: dt.language,
      processing,
      zeroRecords,
      emptyTable
    },
    buttons:
      dt.csv == 1
        ? [
            {
              extend: "csv",
              className: csvClassName,
              text: dt.csvTextInfo,
              titleAttr: dt.csvTextInfo,
              title: dt.csvFileTitle,
              fieldSeparator: csvFieldSeparator,
              header: csvHeader,
              action: function(e, dt, node, config) {
                blockResubmit(node);
                // Call the default csvHtml5 action method to create the CSV file
                $.fn.dataTable.ext.buttons.csvHtml5.action.call(
                  this,
                  e,
                  dt,
                  node,
                  config
                );
              },
              exportOptions: {
                modifier: {
                  // DataTables core
                  order: "current",
                  page: "All",
                  search: "applied"
                },
                columns: csvExcludeLast
              }
            }
          ]
        : [],
    initComplete: function(settings, json) {
      if (noMoveElementsToAction === false) {
        moveElementsToActionButtons();
      }

      checkPages();

      $(`div#${dt.id}-spinner`).hide();
    },
    drawCallback: function(settings) {
      if ($(`#${dt.id} tr td`).length == 1) {
        $(`.datatable-msg-info-${dt.id}`)
          .removeClass("invisible_important")
          .show();
        $(`table#${dt.id}`).hide();
        $("div.pagination-child-div").hide();
        $("div.dataTables_info").hide();
        $(`#${dt.id}_wrapper`).hide();
        if (showAlwaysPagination) {
          $(`.action_buttons_right_content .pagination-child-div`).show();
        } else {
          $(`.action_buttons_right_content .pagination-child-div`).hide();
        }
      } else {
        $(`.datatable-msg-info-${dt.id}`).hide();
        $(`table#${dt.id}`).show();
        $("div.pagination-child-div").show();
        $("div.dataTables_info").show();
        $(`#${dt.id}_wrapper`).show();

        if (typeof dt.drawCallback !== "undefined") {
          eval(dt.drawCallback);
        }
      }

      $(`div#${dt.id}-spinner`).hide();

      checkPages();
    }
  };

  var ajaxOrData = {};
  if (typeof dt.data_element == "undefined") {
    ajaxOrData = {
      ajax: {
        url: dt.ajax_url_full,
        type: "POST",
        dataSrc: function(json) {
          if ($(`#${dt.form_id}_search_bt`) != undefined) {
            $(`#${dt.form_id}_loading`).remove();
          }

          if (json.showAlwaysPagination) {
            showAlwaysPagination = true;
          } else {
            showAlwaysPagination = false;
          }

          if (json.error) {
            console.error(json.error);
            $(`#error-${dt.id}`).html(json.error);
            $(`#error-${dt.id}`)
              .dialog({
                title: "Filter failed",
                width: 630,
                resizable: true,
                draggable: true,
                modal: false,
                closeOnEscape: true,
                buttons: {
                  Ok: function() {
                    $(this).dialog("close");
                  }
                }
              })
              .parent()
              .addClass("ui-state-error");
          } else {
            if (json.ajaxReturn !== "undefined") {
              eval(`${ajaxReturnFunction}(${json.ajaxReturn})`);
            }

            if (typeof dt.ajax_postprocess !== "undefined") {
              if (json.data) {
                json.data.forEach(function(item) {
                  eval(dt.ajax_postprocess);
                });
              } else {
                json.data = {};
              }
            }

            return json.data;
          }
        },
        data: function(data) {
          $(`div#${dt.id}-spinner`).show();
          if ($(`#button-${dt.form_id}_search_bt`) != undefined) {
            var loading = `<img src="images/spinner.gif" id="${dt.form_id}_loading" class="loading-search-datatables-button" />`;
            $(`#button-${dt.form_id}_search_bt`)
              .parent()
              .append(loading);
          }

          var inputs = $(`#${dt.form_id} :input`);

          var values = {};
          inputs.each(function() {
            values[this.name] = $(this).val();
          });

          $.extend(data, ajaxData);

          $.extend(data, {
            filter: values,
            page: dt.ajax_url
          });

          return data;
        }
      }
    };
  } else {
    ajaxOrData = { data: dt.data_element };
  }

  $.extend(settings_datatable, ajaxOrData);

  var dt_table;

  if (startDisabled === true) {
    $(`.datatable-msg-info-filter-${dt.id}`)
      .removeClass("invisible_important")
      .show();

    $(`div#${dt.id}-spinner`).hide();
    $(`#${dt.table_id}`).hide();

    $(`#button-form_${dt.table_id}_search_bt`).click(function() {
      $(`.datatable-msg-info-filter-${dt.id}`).hide();
      $(`#${dt.table_id}`).show();
      dt_table = $(`#${dt.table_id}`).DataTable(settings_datatable);
    });
  } else {
    dt_table = $(`#${dt.table_id}`).DataTable(settings_datatable);
  }

  $(`#button-${dt.form_id}_search_bt`).click(function() {
    dt_table.draw().page(0);
  });

  if (typeof dt.caption !== "undefined" && dt.caption !== "") {
    $(`#${dt.table_id}`).append(`<caption>${dt.caption}</caption>`);
    $(".datatables_thead_tr").css("height", 0);
  }

  $(function() {
    $(document).on("init.dt", function(ev, settings) {
      if (dt.mini_search === true) {
        $(`#${dt.id}_filter > label > input`).addClass("mini-search-input");
      }

      $("div.dataTables_length").show();
      $("div.dataTables_filter").show();
      $("div.dt-buttons").show();

      if (dt_table.page.info().pages === 0) {
        $(`.action_buttons_right_content .pagination-child-div`).hide();
      }

      if (dt_table.page.info().pages === 1) {
        $(`div.pagination-child-div > #${dt.table_id}_paginate`).hide();
      } else {
        $(`div.pagination-child-div > #${dt.table_id}_paginate`).show();
      }
    });
  });
});

$(function() {
  $(document).on("preInit.dt", function(ev, settings) {
    $(`#${dt.id}_wrapper  div.dataTables_length`).hide();
    $(`#${dt.id}_wrapper  div.dataTables_filter`).hide();
    $(`#${dt.id}_wrapper  div.dt-buttons`).hide();
  });
});
