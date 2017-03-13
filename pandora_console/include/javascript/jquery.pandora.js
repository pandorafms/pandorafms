(function($) {
	$.fn.check = function () {
		return this.each (function () {
			this.checked = true;
		});};

	$.fn.uncheck = function () {
		return this.each (function () {
			this.checked = false;
		});};

	$.fn.enable = function () {
		return $(this).removeAttr ("disabled");
		};

	$.fn.disable = function () {
		return $(this).attr ("disabled", "disabled");
		};

	$.fn.pulsate = function () {
		var i = 0;
		for (i = 0; i <= 2; i++) {
			$(this).fadeOut ("slow").fadeIn ("slow");
		}
	};

	$.fn.showMessage = function (msg) {
		return $(this).hide ().empty ()
				.text (msg)
				.slideDown ();
		};
}) (jQuery);

$(document).ready (function () {
	$("a#show_messages_dialog").click (function () {
		jQuery.get ("ajax.php",
			{"page": "operation/messages/message_list"},
			function (data, status) {
				$("#dialog_messages").hide ()
					.empty ()
					.append (data)
					.dialog ({
						title: $("a#show_messages_dialog").attr ("title"),
						resizable: false,
						modal: true,
						overlay: {
							opacity: 0.5,
							background: "black"
						},
						width: 700,
						height: 300
					})
					.show ();
			},
			"html"
		);

		return false;
	});

	$("a.show_systemalert_dialog").click (function () {
		$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
		jQuery.get ("ajax.php",
			{"page": "operation/system_alert"},
				function (data, status) {
					$("#alert_messages").hide ()
						.empty ()
						.append (data)
						.show ();
				},
				"html"
			);
		return false;
	});
	
	$("a.modalpopup").click (function () {
		$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
			jQuery.get ("ajax.php",
			{"page": "general/alert_enterprise",
			 "message":$(this).attr("id")},
				function (data, status) {
					$("#alert_messages").hide ()
						.empty ()
						.append (data)
						.show ();
				},
				"html"
			);
		return false;
	});

// Creacion de ventana modal y botones

	$(".publienterprise").click (function () {
		$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
		jQuery.get ("ajax.php",
			{
		"page": "general/alert_enterprise",
		"message":$(this).attr("id")},
			function (data, status) {
				$("#alert_messages").hide ()
					.empty ()
					.append (data)
					.show ();
			},
			"html"
		);


		return false;
	});
	
	$(".publienterprisehide").click (function () {
		$('body').append( "<div id='opacidad' style='position:fixed;background:black;opacity:0.6;z-index:1'></div>" );
		jQuery.get ("ajax.php",
			{
		"page": "general/alert_enterprise",
		"message":$(this).attr("id")},
			function (data, status) {
				$("#alert_messages").hide ()
					.empty ()
					.append (data)
					.show ();
			},
			"html"
		);


		return false;
	});



	if ($('#license_error_msg_dialog').length) {
		if (typeof(process_login_ok) == "undefined")
			process_login_ok = 0;

		if (typeof(show_error_license) == "undefined")
			show_error_license = 0;

		if (process_login_ok || show_error_license) {

			$( "#license_error_msg_dialog" ).dialog({
				dialogClass: "no-close",
				closeOnEscape: false,
				resizable: true,
				draggable: true,
				modal: true,
				height: 350,
				width: 720,
				overlay: {
					opacity: 0.5,
					background: "black"
				},
				open: function() {
					setTimeout(function(){
							$("#spinner_ok").hide();
							$("#ok_buttom").show();
						},
						30000
					);
				}
			});

			$("#submit-hide-license-error-msg").click (function () {
				$("#license_error_msg_dialog" ).dialog('close')
			});

		}
	}


	if ($('#msg_change_password').length) {

		$( "#msg_change_password" ).dialog({
			resizable: true,
			draggable: true,
			modal: true,
			height: 360,
			width: 590,
			overlay: {
				opacity: 0.5,
				background: "black"
			}
		});

	}

	if ($('#login_blocked').length) {

		$( "#login_blocked" ).dialog({
					resizable: true,
					draggable: true,
					modal: true,
					height: 180,
					width: 400,
					overlay: {
								opacity: 0.5,
								background: "black"
							}
		});

	}

	forced_title_callback();
	
	
	$(document).on("scroll", function(){	
		if((document.documentElement.scrollTop != 0 || document.body.scrollTop != 0) && $('#menu').css('position') =='fixed'){
				$('#menu').css('top','20px');
			}
		else{
				$('#menu').css('top','80px');
		}
});

$("#alert_messages").draggable();
$("#alert_messages").css({'left':+parseInt(screen.width/2)-parseInt($("#alert_messages").css('width'))/2+'px'});
	
});

function forced_title_callback() {
	// Forced title code
	$('body').on('mouseenter', '.forced_title', function() {
		///////////////////////////////////////////
		// Put the layer in the left-top corner to fill it
		///////////////////////////////////////////
		$('#forced_title_layer').css('left', 0);
		$('#forced_title_layer').css('top', 0);

		///////////////////////////////////////////
		// Get info of the image
		///////////////////////////////////////////

		var img_top = $(this).offset().top;
		var img_width = $(this).width();
		var img_height = $(this).height();
		var img_id = $(this).attr('id');
		var img_left_mid = $(this).offset().left + (img_width / 2);

		///////////////////////////////////////////
		// Put title in the layer
		///////////////////////////////////////////

		// If the '.forced_title' element has 'use_title_for_force_title' = 1
		// into their 'data' prop, the element title will be used for the
		// content.
		if ($(this).data("use_title_for_force_title")) {
			var title = $(this).data("title");
		}
		else {
			var title = $('#forced_title_'+img_id).html();
		}

		$('#forced_title_layer').html(title);

		///////////////////////////////////////////
		// Get info of the layer
		///////////////////////////////////////////

		var layer_width = $('#forced_title_layer').width();
		var layer_height = $('#forced_title_layer').height();

		///////////////////////////////////////////
		// Obtain the new position of the layer
		///////////////////////////////////////////

		// Jquery doesnt know the padding of the layer
		var layer_padding = 4;

		// Deduct padding of both sides
		var layer_top = img_top - layer_height - (layer_padding * 2) - 5;
		if (layer_top < 0) {
			layer_top = img_top + img_height + (layer_padding * 2);
		}

		// Deduct padding of one side
		var layer_left = img_left_mid - (layer_width / 2) - layer_padding;
		if (layer_left < 0) {
			layer_left = 0;
		}

		var real_layer_width = layer_width + (layer_padding * 2) + 5;
		var layer_right = layer_left + real_layer_width;
		var screen_width = $(window).width();
		if (screen_width < layer_right) {
			layer_left = screen_width - real_layer_width;
		}

		///////////////////////////////////////////
		// Set the layer position and show
		///////////////////////////////////////////

		$('#forced_title_layer').css('left', layer_left);
		$('#forced_title_layer').css('top', layer_top);
		$('#forced_title_layer').show();
	});
	$('body').on('mouseout', '.forced_title', function () {
		$('#forced_title_layer').hide().empty();
	});
}
