$(document).ready (function () {
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
		return $(this).fadeIn ("normal", function () {
			$(this).fadeOut ("normal", function () {
				$(this).fadeIn ("normal", function () {
					$(this).fadeOut ("normal", function () {
						$(this).fadeIn ().focus ();
					});
				});
			});
		});
	};
	
	$.fn.showMessage = function (msg) {
		return $(this).hide ().empty ()
				.text (msg)
				.slideDown ();
		};
});
