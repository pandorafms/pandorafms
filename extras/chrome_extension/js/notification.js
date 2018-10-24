
var url = window.location.href;
var re = /\?event=(\d+)/;
if(re.exec(url)) {
	if(!isNaN(RegExp.$1)) {
		var eventId = RegExp.$1;
		document.write(chrome.extension.getBackgroundPage().getNotification(eventId));
	}
}
window.onload = function () {
	setTimeout(function() {
		window.close();
	}, 10000);	
}
