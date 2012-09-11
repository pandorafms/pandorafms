console.log("hola");
var url = window.location.href;
var re = /\?event=(\d+)/;
console.log("hola");
if(re.exec(url)) {
	if(!isNaN(RegExp.$1)) {
		var eventId = RegExp.$1;
		document.write(chrome.extension.getBackgroundPage().getNotification(eventId));
	}
}
console.log("hola");
window.onload = function () {
	setTimeout(function() {
		window.close();
	}, 10000);	
}
console.log("hola");
