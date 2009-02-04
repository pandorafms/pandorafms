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
	
	/**
	 * Cookie plugin
	 *
	 * Copyright (c) 2006 Klaus Hartl (stilbuero.de)
	 * Dual licensed under the MIT and GPL licenses:
	 * http://www.opensource.org/licenses/mit-license.php
	 * http://www.gnu.org/licenses/gpl.html
	 *
	*/
	/**
	 * Get the value of a cookie with the given name.
	 *
	 * @example $.cookie('the_cookie');
	 * @desc Get the value of a cookie.
	 *
	 * @param String name The name of the cookie.
	 * @return The value of the cookie.
	 * @type String
	 *
	 * @name $.cookie
	 * @cat Plugins/Cookie
	 * @author Klaus Hartl/klaus.hartl@stilbuero.de
	*/
	$.cookie = function(name, value, options) {
		if (typeof value != 'undefined') { // name and value given, set cookie
			options = options || {};
			if (value === null) {
				value = '';
				options = $.extend({}, options); // clone object since it's unexpected behavior if the expired property were changed
				options.expires = -1;
			}
			var expires = '';
			if (options.expires && (typeof options.expires == 'number' || options.expires.toUTCString)) {
				var date;
				if (typeof options.expires == 'number') {
					date = new Date();
					date.setTime(date.getTime() + (options.expires * 24 * 60 * 60 * 1000));
				} else {
					date = options.expires;
				}
				expires = '; expires=' + date.toUTCString(); // use expires attribute, max-age is not supported by IE
			}
			// NOTE Needed to parenthesize options.path and options.domain
			// in the following expressions, otherwise they evaluate to undefined
			// in the packed version for some reason...
			var path = options.path ? '; path=' + (options.path) : '';
			var domain = options.domain ? '; domain=' + (options.domain) : '';
			var secure = options.secure ? '; secure' : '';
			document.cookie = [name, '=', encodeURIComponent(value), expires, path, domain, secure].join('');
		} else { // only name given, get cookie
			var cookieValue = null;
			if (document.cookie && document.cookie != '') {
				var cookies = document.cookie.split(';');
				for (var i = 0; i < cookies.length; i++) {
					var cookie = jQuery.trim(cookies[i]);
					// Does this cookie string begin with the name we want?
					if (cookie.substring(0, name.length + 1) == (name + '=')) {
						cookieValue = decodeURIComponent(cookie.substring(name.length + 1));
						break;
					}
				}
			}
			return cookieValue;
		}
	};
});
