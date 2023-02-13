/* globals $, idTips, totalTips, idTips, url, page */
$(".carousel .images").ready(function() {
  if ($(".carousel .images img").length > 1) {
    $(".carousel .images").bxSlider({ controls: true });
  }
});
function render({ title, text, url, files }) {
  $("#title_tip").html(title);
  $("#text_tip").html(text);
  $("#url_tip").attr("href", url);
  $(".carousel .images").empty();

  if (files) {
    files.forEach(file => {
      $(".carousel .images").append(`<img src="${file.filename}" />`);
    });
    $(".carousel").removeClass("invisible");
  } else {
    $(".carousel").addClass("invisible");
  }
}

function next_tip() {
  if (idTips.length >= totalTips) {
    idTips = [];
  }
  $.ajax({
    method: "POST",
    url: url,
    dataType: "json",
    data: {
      page: page,
      method: "getRandomTip",
      exclude: JSON.stringify(idTips)
    },
    success: function({ success, data }) {
      if (success) {
        idTips.push(parseInt(data.id));
        render(data);
      } else {
        //TODO control error
      }
    }
  });
}

function load_tips_modal(settings) {
  var data = new FormData();
  if (settings.extradata) {
    settings.extradata.forEach(function(item) {
      if (item.value != undefined) {
        if (item.value instanceof Object || item.value instanceof Array) {
          data.append(item.name, JSON.stringify(item.value));
        } else {
          data.append(item.name, item.value);
        }
      }
    });
  }
  data.append("page", settings.onshow.page);
  data.append("method", settings.onshow.method);
  if (settings.onshow.extradata != undefined) {
    data.append("extradata", JSON.stringify(settings.onshow.extradata));
  }

  if (settings.target == undefined) {
    var uniq = uniqId();
    var div = document.createElement("div");
    div.id = "div-modal-" + uniq;
    div.style.display = "none";

    if (document.getElementById("main") == null) {
      // MC env.
      document.getElementById("page").append(div);
    } else {
      document.getElementById("main").append(div);
    }

    var id_modal_target = "#div-modal-" + uniq;

    settings.target = $(id_modal_target);
  }

  var width = 630;
  if (settings.onshow.width) {
    width = settings.onshow.width;
  }

  if (settings.modal.overlay == undefined) {
    settings.modal.overlay = {
      opacity: 0.5,
      background: "black"
    };
  }

  if (settings.beforeClose == undefined) {
    settings.beforeClose = function() {};
  }

  settings.target.html("Loading modal...");
  settings.target
    .dialog({
      title: "Loading",
      close: false,
      width: 200,
      buttons: []
    })
    .show();

  $.ajax({
    method: "post",
    url: settings.url,
    processData: false,
    contentType: false,
    data: data,
    success: function(data) {
      if (settings.onshow.parser) {
        data = settings.onshow.parser(data);
      } else {
        data = (function(d) {
          try {
            d = JSON.parse(d);
          } catch (e) {
            // Not JSON
            return d;
          }
          if (d.error) return d.error;

          if (d.result) return d.result;
        })(data);
      }
      settings.target.html(data);
      if (settings.onload != undefined) {
        settings.onload(data);
      }
      settings.target.dialog({
        resizable: true,
        draggable: true,
        modal: true,
        header: false,
        dialogClass: "dialog_tips",
        title: settings.modal.title,
        width: width,
        minHeight:
          settings.onshow.minHeight != undefined
            ? settings.onshow.minHeight
            : "auto",
        maxHeight:
          settings.onshow.maxHeight != undefined
            ? settings.onshow.maxHeight
            : "auto",
        overlay: settings.modal.overlay,
        position: {
          my: "top+20%",
          at: "top",
          of: window,
          collision: "fit"
        },
        closeOnEscape: true,
        close: function() {
          $(this).dialog("destroy");

          if (id_modal_target != undefined) {
            $(id_modal_target).remove();
          }

          if (settings.cleanup != undefined) {
            settings.cleanup();
          }
        },
        beforeClose: settings.beforeClose()
      });
      $(".dialog_tips .ui-dialog-titlebar").empty();
      $(".dialog_tips .ui-dialog-titlebar").append($(".tips_header").html());
      $(".tips_header").remove();
      $(".dialog_tips .ui-dialog-titlebar").addClass("tips_header");
      $(".dialog_tips .ui-dialog-titlebar").removeClass("ui-helper-clearfix");
    },
    error: function(data) {
      console.error(data);
    }
  });
}
