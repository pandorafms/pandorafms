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
		var i=0;
		for (i=0; i<=2; i++) {
			$(this).fadeOut ("slow");
			$(this).fadeIn ("slow");
		}
	};
	
	$.fn.showMessage = function (msg) {
		return $(this).hide ().empty ()
				.text (msg)
				.slideDown ();
		};
		
	$("a[rel]").overlay (function() {
		var wrap = this.getContent().find("div.wrap");
		wrap.load(this.getTrigger().attr("href"));
	});
});
