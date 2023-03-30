(function($) {
  $.fn.check = function() {
    return this.each(function() {
      this.checked = true;
    });
  };

  $.fn.uncheck = function() {
    return this.each(function() {
      this.checked = false;
    });
  };

  $.fn.enable = function() {
    return $(this).removeAttr("disabled");
  };

  $.fn.disable = function() {
    return $(this).attr("disabled", "disabled");
  };

  $.fn.pulsate = function() {
    var i = 0;
    for (i = 0; i <= 2; i++) {
      $(this)
        .fadeOut("slow")
        .fadeIn("slow");
    }
  };

  $.fn.showMessage = function(msg) {
    return (
      $(this)
        .hide()
        .empty()
        // here, previously .text (msg)
        .html(msg)
        .slideDown()
    );
  };
})(jQuery);

$(document).ready(function() {
  $("a#show_messages_dialog").click(function() {
    jQuery.post(
      "ajax.php",
      {
        page: "operation/messages/message_list"
      },
      function(data, status) {
        $("#dialog_messages")
          .hide()
          .empty()
          .append(data)
          .dialog({
            title: $("a#show_messages_dialog").attr("title"),
            resizable: false,
            modal: true,
            overlay: {
              opacity: 0.5,
              background: "black"
            },
            width: 700,
            height: 300
          })
          .show();
      },
      "html"
    );

    return false;
  });

  $("a.show_systemalert_dialog").click(function() {
    $("body").append(
      "<div id='opacidad' style='position:fixed;background:black;z-index:1'></div>"
    );
    $("#opacidad").css("opacity", 0.5);

    jQuery.post(
      "ajax.php",
      {
        page: "operation/system_alert"
      },
      function(data, status) {
        $("#alert_messages").show();
        $("#alert_messages")
          .empty()
          .append(data);
        $("#alert_messages").css("opacity", 1);
      },
      "html"
    );
  });

  $("a.modalpopup").click(function() {
    var elem = $(this).attr("id");
    $("body").append(
      "<div id='opacidad' style='position:fixed;background:black;z-index:1'></div>"
    );
    $("#opacidad").css("opacity", 0.5);

    jQuery.post(
      "ajax.php",
      {
        page: "general/alert_enterprise",
        message: elem
      },
      function(data, status) {
        $("#alert_messages").show();
        $("#alert_messages")
          .empty()
          .append(data);
        $("#alert_messages").css("opacity", 1);
      },
      "html"
    );
    return false;
  });

  // Creacion de ventana modal y botones
  $(".publienterprise").click(function() {
    var elem = $(this).attr("id");
    $("body").append(
      "<div id='opacidad' style='position:fixed;background:black;z-index:1'></div>"
    );
    $("#opacidad").css("opacity", 0.5);

    jQuery.post(
      "ajax.php",
      {
        page: "general/alert_enterprise",
        message: elem
      },
      function(data, status) {
        $("#alert_messages").show();
        $("#alert_messages")
          .empty()
          .append(data);
        $("#alert_messages").css("opacity", 1);
      },
      "html"
    );
    return false;
  });

  $(".publienterprisehide").click(function() {
    var elem = $(this).attr("id");
    $("body").append(
      "<div id='opacidad' style='position:fixed;background:black;z-index:1'></div>"
    );
    $("#opacidad").css("opacity", 0.5);

    jQuery.post(
      "ajax.php",
      {
        page: "general/alert_enterprise",
        message: elem
      },
      function(data, status) {
        $("#alert_messages").show();
        $("#alert_messages")
          .empty()
          .append(data);
        $("#alert_messages").css("opacity", 1);
      },
      "html"
    );
    return false;
  });

  if ($("#license_error_msg_dialog").length) {
    if (typeof process_login_ok == "undefined") process_login_ok = 0;

    if (typeof show_error_license == "undefined") show_error_license = 0;

    if (typeof hide_counter == "undefined") hide_counter = 0;

    let height = 300;
    if (typeof invalid_license != "undefined") height = 350;

    if (process_login_ok || show_error_license) {
      $("#license_error_msg_dialog").dialog({
        dialogClass: "no-close",
        closeOnEscape: false,
        resizable: false,
        draggable: true,
        modal: true,
        height: height,
        width: 850,
        overlay: {
          opacity: 0.5,
          background: "black"
        },
        open: function() {
          if (hide_counter != 1) {
            var remaining = 30;

            // Timeout counter.
            var count = function() {
              if (remaining > 0) {
                $("#license_error_remaining").text(remaining);
                remaining -= 1;
              } else {
                $("#license_error_remaining").hide();
                $("#ok_buttom").show();
                clearInterval(count);
              }
            };

            setInterval(count, 1000);
          } else {
            $("#ok_buttom").show();
          }
        }
      });

      $("#ok_buttom").click(function() {
        $("#license_error_msg_dialog").dialog("close");
      });
    }
  }

  if ($("#msg_change_password").length) {
    $("#msg_change_password").dialog({
      resizable: false,
      draggable: true,
      modal: true,
      height: 410,
      width: 390,
      overlay: {
        opacity: 0.5,
        background: "black"
      }
    });
  }

  if ($("#login_blocked").length) {
    $("#login_blocked").dialog({
      resizable: true,
      draggable: true,
      modal: true,
      height: 200,
      width: 520,
      overlay: {
        opacity: 0.5,
        background: "black"
      }
    });
  }

  if ($("#login_correct_pass").length) {
    $("#login_correct_pass").dialog({
      resizable: true,
      draggable: true,
      modal: true,
      height: 200,
      width: 520,
      overlay: {
        opacity: 0.5,
        background: "black"
      }
    });
  }

  forced_title_callback();

  $(document).on("scroll", function() {
    if (
      document.documentElement.scrollTop != 0 ||
      document.body.scrollTop != 0
    ) {
      if ($("#head").css("position") == "fixed") {
        if ($("#menu").css("position") == "fixed") {
          $("#menu").css("top", "80px");
        } else {
          $("#menu").css("top", "60px");
        }
      } else {
        if ($("#menu").css("position") == "fixed") {
          $("#menu").css("top", "20px");
        } else {
          $("#menu").css("top", "80px");
        }
      }
    } else {
      if ($("#head").css("position") == "fixed") {
        if ($("#menu").css("position") == "fixed") {
          $("#menu").css("top", "80px");
        } else {
          $("#menu").css("top", "60px");
        }
      } else {
        if ($("#menu").css("position") == "fixed") {
          $("#menu").css("top", "80px");
        } else {
          $("#menu").css("top", "80px");
        }
      }
    }

    // if((document.documentElement.scrollTop != 0 || document.body.scrollTop != 0) && $('#menu').css('position') =='fixed'){
    // 	if($('#head').css('position') =='fixed'){
    // 		$('#menu').css('top','80px');
    // 	}
    // 	else{
    // 		$('#menu').css('top','20px');
    // 	}
    // }
    // else{
    // 	if($('#head').css('position') =='fixed'){
    // 		if(document.documentElement.scrollTop != 0 || document.body.scrollTop != 0){
    // 			$('#menu').css('top','60px');
    // 		}else{
    // 			$('#menu').css('top','80px');
    // 		}
    //
    // 	}
    // 	else{
    // 		$('#menu').css('top','60px');
    // 	}
    // }
  });

  $("#alert_messages").draggable();
  $("#alert_messages").css({
    left:
      +parseInt(screen.width / 2) -
      parseInt($("#alert_messages").css("width")) / 2 +
      "px"
  });
});

function forced_title_callback() {
  // Forced title code
  $("body").on("mouseenter", ".forced_title", function() {
    ///////////////////////////////////////////
    // Put the layer in the left-top corner to fill it
    ///////////////////////////////////////////
    $("#forced_title_layer").css("left", 0);
    $("#forced_title_layer").css("top", 0);

    ///////////////////////////////////////////
    // Get info of the image
    ///////////////////////////////////////////

    var img_top = $(this).offset().top;
    var img_width = $(this).width();
    var img_height = $(this).height();
    var img_id = $(this).attr("id");
    var img_left_mid = $(this).offset().left + img_width / 2;

    ///////////////////////////////////////////
    // Put title in the layer
    ///////////////////////////////////////////

    // If the '.forced_title' element has 'use_title_for_force_title' = 1
    // into their 'data' prop, the element title will be used for the
    // content.
    if ($(this).data("use_title_for_force_title")) {
      var title = $(this).data("title");
    } else {
      var title = $("#forced_title_" + img_id).html();
    }

    $("#forced_title_layer").html(title);

    ///////////////////////////////////////////
    // Get info of the layer
    ///////////////////////////////////////////

    var layer_width = $("#forced_title_layer").width();
    var layer_height = $("#forced_title_layer").height();

    ///////////////////////////////////////////
    // Obtain the new position of the layer
    ///////////////////////////////////////////

    // Jquery doesnt know the padding of the layer
    var layer_padding = 4;

    // Deduct padding of both sides
    var layer_top = img_top - layer_height - layer_padding * 2 - 5;
    if (layer_top < 0) {
      layer_top = img_top + img_height + layer_padding * 2;
    }

    // Deduct padding of one side
    var layer_left = img_left_mid - layer_width / 2 - layer_padding;
    if (layer_left < 0) {
      layer_left = 0;
    }

    var real_layer_width = layer_width + layer_padding * 2 + 5;
    var layer_right = layer_left + real_layer_width;
    var screen_width = $(window).width();
    if (screen_width < layer_right) {
      layer_left = screen_width - real_layer_width;
    }

    ///////////////////////////////////////////
    // Set the layer position and show
    ///////////////////////////////////////////

    $("#forced_title_layer").css("left", layer_left);
    $("#forced_title_layer").css("top", layer_top);
    $("#forced_title_layer").show();
  });
  $("body").on("mouseout", ".forced_title", function() {
    $("#forced_title_layer")
      .hide()
      .empty();
  });
}
