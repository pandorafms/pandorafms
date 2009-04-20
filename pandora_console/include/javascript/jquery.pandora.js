(function($) {
	$.fn.check = function () {
		return this.each (function () {
			this.checked = true;
		})};
	
	$.fn.uncheck = function () {
		return this.each (function () {
			this.checked = false;
		})};
	
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
			{"page": "operation/messages/message"},
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
						bgiframe: jQuery.browser.msie,
						width: 700,
						height: 300
					})
					.show ();
			},
			"html"
		);
		
		return false;
	});
});
