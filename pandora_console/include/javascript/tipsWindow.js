/* globals $, idTips, totalTips, url, page, uniqId */
$(document).ready(function() {
  $("#button-button_add_image").on("click", function() {
    var numberImages = $("#inputs_images").children().length;
    $(".input-file").each(function(index) {
      $(this).attr("name", "file_" + index);
    });
    var div_image = document.createElement("div");
    $(div_image).attr("class", "action_image");
    $(div_image).append(
      `<input type="file" accept="image/png,image/jpeg,image/gif" class="input-file" name="file_${numberImages}"  onchange="checkImage(this)" required/>`
    );
    $(div_image).append(
      `<input type="image" src="images/delete.svg" onclick="removeInputImage(this);" class="remove-image main_menu_icon" value="-"/>`
    );
    $("#inputs_images").append(div_image);
  });

  $("#image-delete_image_tip1").on("click", function(e) {
    e.preventDefault();
  });
  $("#button-preview_button").on("click", function(e) {
    e.preventDefault();
    previewTip();
  });
});

function show_tips_startup(e) {
  $.ajax({
    method: "POST",
    url: url,
    dataType: "json",
    data: {
      page: page,
      method: "setShowTipsAtStartup",
      show_tips_startup: e.checked ? "1" : "0"
    },
    success: function({ success }) {
      if (!success) {
        $("#checkbox_tips_startup").prop("checked", true);
      }
    }
  });
}
function checkImage(e) {
  var maxWidth = 464;
  var maxHeight = 260;

  var reader = new FileReader();
  reader.readAsDataURL(e.files[0]);
  reader.onload = function(e) {
    var img = new Image();
    img.src = e.target.result;
    img.onload = function() {
      if (this.width !== maxWidth || this.height !== maxHeight) {
        $("#notices_images").removeClass("invisible");
      } else {
        $("#notices_images").addClass("invisible");
      }
    };
  };
}
function deleteImage(e, id, path) {
  var imagesToDelete = JSON.parse($("#hidden-images_to_delete").val());
  imagesToDelete[id] = path;
  $("#hidden-images_to_delete").val(JSON.stringify(imagesToDelete));
  $(e)
    .parent()
    .remove();
}
function activeCarousel() {
  if ($(".carousel .images img").length > 1) {
    $(".carousel .images").bxSlider({ controls: true });
  }
}
function removeInputImage(e) {
  $(e)
    .parent()
    .remove();
  if ($(".action_image").length === 0) {
    $("#notices_images").addClass("invisible");
  }
}
function render({ title, text, url, files }) {
  $("#title_tip").html(title);
  $("#text_tip").html(text);
  if (url) {
    $("#url_tip").removeClass("invisible");
    $("#url_tip").attr("href", url);
  } else {
    $("#url_tip").addClass("invisible");
  }

  $(".carousel").empty();
  $(".carousel").append("<div class='images'></div>");
  if (files) {
    files.forEach(file => {
      $(".carousel .images").append(
        `<img src="${file.path + file.filename}" />`
      );
    });
    $(".carousel").removeClass("invisible");
  } else {
    $(".carousel").addClass("invisible");
  }
  var limitRound = totalTips > 28 ? 28 : totalTips;
  $(".count-round-tip").each(function(index) {
    if ($(this).hasClass("active")) {
      $(this).removeClass("active");
      if (index >= limitRound - 1) {
        $($(".count-round-tip")[0]).addClass("active");
      } else {
        $($(".count-round-tip")[index + 1]).addClass("active");
      }
      return false;
    }
  });
  activeCarousel();
}

function close_dialog() {
  if ($("#tips_window_modal").length > 0) {
    $("#tips_window_modal").dialog("close");
    $("#tips_window_modal").remove();
  }

  if ($("#tips_window_modal_preview").length > 0) {
    $("#tips_window_modal_preview").dialog("close");
    $("#tips_window_modal_preview").empty();
  }
}

function render_counter() {
  $(".counter-tips img:eq(0)").after(
    "<span class='count-round-tip active'></span>"
  );
  if (totalTips > 1) {
    for (let i = 1; i <= totalTips - 1; i++) {
      $(".count-round-tip:eq(0)").after(
        "<span class='count-round-tip'></span>"
      );
    }
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
      }
    }
  });
}

function load_tips_modal(settings) {
  var data = new FormData();
  if (settings.extradata) {
    Object.keys(settings.extradata).forEach(key => {
      data.append(key, settings.extradata[key]);
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
        resizable: false,
        draggable: true,
        modal: true,
        header: false,
        dialogClass: "dialog_tips",
        title: "",
        width: width,
        minHeight:
          settings.onshow.minHeight != undefined
            ? settings.onshow.minHeight
            : "auto",
        maxHeight:
          settings.onshow.maxHeight != undefined
            ? settings.onshow.maxHeight
            : "auto",
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
          if ($("#tips_window_modal").length > 0) {
            $("#tips_window_modal").remove();
          }

          if ($("#tips_window_modal_preview").length > 0) {
            $("#tips_window_modal_preview").empty();
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
      render_counter();
      activeCarousel();
    },
    error: function(data) {
      console.error(data);
    }
  });
}

function previewTip() {
  var extradata = {
    title: $("input[name=title]").val(),
    text: $("textarea[name=text]").val(),
    url: $("input[name=url]").val(),
    files: []
  };

  //images in server
  if ($(".image_tip img").length > 0) {
    $(".image_tip img").each(function(index) {
      extradata["files"].push($(".image_tip img")[index].src);
    });
  }

  //Images in client
  var totalInputsFiles = $("input[type=file]").length;
  if (totalInputsFiles > 0 && validateImages()) {
    extradata["totalFiles64"] = totalInputsFiles;
    $("input[type=file]").each(function(index) {
      var reader = new FileReader();
      if (this.files.length > 0) {
        reader.readAsDataURL(this.files[0]);
        reader.onload = function(e) {
          var img = new Image();
          img.src = e.target.result;
          img.onload = function() {
            extradata[`file64_${index}`] = this.currentSrc;
            if (totalInputsFiles - 1 === index) {
              load_tips_modal({
                target: $("#tips_window_modal_preview"),
                url: url,
                onshow: {
                  page: page,
                  method: "renderPreview"
                },
                extradata //Receive json
              });
            }
          };
        };
      }
    });
  } else {
    load_tips_modal({
      target: $("#tips_window_modal_preview"),
      url: url,
      onshow: {
        page: page,
        method: "renderPreview"
      },
      extradata //Receive json
    });
  }
}

function validateImages() {
  $(".empty_input_images").addClass("invisible");
  let validate = true;
  $("input[type=file]").each(function() {
    if (this.files.length == 0) {
      $(".empty_input_images").removeClass("invisible");
      validate = false;
    }
  });
  return validate;
}
