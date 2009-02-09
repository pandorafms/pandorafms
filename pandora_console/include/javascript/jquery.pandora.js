$(document).ready (function () {
	/* Menu activation */
	$(".toggle").click (function () {
		parents = $(this).parents ("li");
		if ($(parents).hasClass ("has_submenu_visible")) {
			$(".submenu", parents).hide ();
			$(parents).removeClass ("has_submenu_visible");
			$.cookie ($(parents).attr ("id"), null);
			return;
		}
		$(parents).addClass ("has_submenu_visible");
		$(".submenu", parents).show ();
		$.cookie ($(parents).attr ("id"), true);
	});

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

});
