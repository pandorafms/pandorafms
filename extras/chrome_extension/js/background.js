var refreshTimer = null;
var isFetching = null;
var storedEvents = new Array();
var notVisited = {};

$(window).on('load', function() {
	initilise();
	// Wait some ms to throw main function
	var delay = setTimeout(main, 100);
	resetInterval();
});

function fetchEvents() {
	return storedEvents;
}

function fetchNotVisited() {
	return notVisited;
}

function removeNotVisited(eventId) {
	if (notVisited[eventId] === true) delete notVisited[eventId];
}

function main() {

	chrome.runtime.sendMessage({text: "FETCH_EVENTS"});
	// Do not fetch if is fetching now
	if (isFetching) return;
	isFetching = true;

	var feedUrl = localStorage["ip_address"]+'/include/api.php?op=get&op2=events&return_type=json&apipass='+localStorage["api_pass"]+'&user='+localStorage["user_name"]+'&pass='+localStorage["pass"];

	req = new XMLHttpRequest();
	req.onload = handleResponse;
	req.onerror = handleError;
	req.open("GET", feedUrl, true);
	req.withCredentials = true
	req.send(null);
}

function handleError() {
	chrome.runtime.sendMessage({text: "FETCH_EVENTS_URL_ERROR"});
	isFetching = false;
}

function handleResponse() {
	var doc = req.responseText;
	if (doc=="auth error") {
		chrome.runtime.sendMessage({text: "FETCH_EVENTS_URL_ERROR"});
	} else {
		var n = doc.search("404 Not Found");
		if (n>0) {
			chrome.runtime.sendMessage({text: "FETCH_EVENTS_DATA_ERROR"});
		} else {
			getEvents(doc);
			chrome.runtime.sendMessage({text: "FETCH_EVENTS_SUCCESS"});
		}
	}
	isFetching = false;
}

function getEvents(reply){
	var fetchedEvents = parseReplyEvents(reply);

	// If there is no events requested, mark all as visited
	if (storedEvents.length == 0) {
		notVisited = {};
		storedEvents = fetchedEvents;
		return;
	}

	// Discriminate the new events
	newEvents=fetchNewEvents(fetchedEvents,storedEvents);
	var newNotVisited = {};
	var notVisitedCount = 0;
	
	// Check if popup is displayed to make some actions
	var views = chrome.extension.getViews({ type: "popup" });
	for(var k=0;k<newEvents.length;k++){
		newNotVisited[newEvents[k]['id']] = true;
		if (views.length == 0) {
			notVisitedCount++;
			displayNotification (newEvents[k])
			alertsSound(newEvents[k]);
		}
	}

	// Make that the old events marked as not visited remains with the
	// same status
	for(var k=0;k<fetchedEvents.length;k++){
		if (notVisited[fetchedEvents[k]['id']] === true) {
			newNotVisited[fetchedEvents[k]['id']] = true;
			notVisitedCount++;
		}
	}
	notVisited = newNotVisited;

	// Update the number
	localStorage["new_events"] = (views.length == 0) ? notVisitedCount : 0;
	updateBadge();

	// Store the requested events
	storedEvents = fetchedEvents;
}

function updateBadge() {
	if (localStorage["new_events"] != 0) {
		chrome.browserAction.setBadgeBackgroundColor({color:[0,200,0,255]});
		chrome.browserAction.setBadgeText({ text: localStorage["new_events"] });
	} else {
		chrome.browserAction.setBadgeText({ text: "" });
	}
}

function fetchNewEvents(A,B){
	var arrDiff = new Array();
	for(var i = 0; i < A.length; i++) {
		var id = false;
		for(var j = 0; j < B.length; j++) {
			if(A[i]['id'] == B[j]['id']) {
				id = true;
				break;
			}
		}
		if(!id) {
			arrDiff.push(A[i]);
		}
	}
	return arrDiff;
}


function parseReplyEvents (reply) {

	// Split the API response
	var data = JSON.parse(reply)
	var e_array = JSON.parse(reply).data;

	// Form a properly object
	var fetchedEvents=new Array();
	for(var i=0;i<e_array.length;i++){
		var event=e_array[i];
		fetchedEvents.push({
			'id' : event.id_evento,
			'agent_name' : event.agent_name,
			'agent' : event.id_agente,
			'date' : event.timestamp,
			'title' : event.evento,
			'module' : event.id_agentmodule,
			'type' : event.event_type,
			'source' : event.source,
			'severity' : event.criticity_name,
			'visited' : false
		});
	}
	// Return the events
	return fetchedEvents;
}

function alertsSound(pEvent){
	if(localStorage["sound_alert"]!="on"){
		return;
	}

	switch (pEvent['severity']) {
		case "Critical":
			playSound(localStorage["critical"]);
			break;
		case "Informational":
			playSound(localStorage["informational"]);
			break;
		case "Maintenance":
			playSound(localStorage["maintenance"]);
			break;
		case "Normal":
			playSound(localStorage["normal"]);
			break;
		case "Warning":
			playSound(localStorage["warning"]);
			break;
	}
}

function displayNotification (pEvent) {

	// Check if the user is okay to get some notification
	if (Notification.permission === "granted") {
		// If it's okay create a notification
		getNotification(pEvent);
	}
	
	// Otherwise, we need to ask the user for permission
	// Note, Chrome does not implement the permission static property
	// So we have to check for NOT 'denied' instead of 'default'
	else if (Notification.permission !== 'denied') {
		Notification.requestPermission(function (permission) {
			// Whatever the user answers, we make sure we store the information
			if(!('permission' in Notification)) {
				Notification.permission = permission;
			}
	
			// If the user is okay, let's create a notification
			if (permission === "granted") getNotification(pEvent);
		});
	}
}

function getNotification(pEvent){
	
	// Build the event text
	var even = pEvent['type'];
	if (pEvent['source'] != '')	even += " : " + pEvent['source'];
	even += ". Event occured at " + pEvent['date'];
	if(pEvent['module'] != 0) even += " in the module with Id "+ pEvent['module'];
	even += ".";

	var url = (pEvent['agent'] == 0)
		? localStorage["ip_address"]+"/index.php?sec=eventos&sec2=operation/events/events"
		: localStorage["ip_address"]+"/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente=" + pEvent['agent'];

	var notification = new Notification(
		pEvent['title'],
		{
			body: even,
			icon: "images/icon.png"
		}
	);

	// Add the link
	notification.onclick = function (event) {
		event.preventDefault();
		window.open(url, '_blank');
	}

	// Close notification after 10 secs
	setTimeout(function() {notification.close()}, 10000);
}

function resetInterval () {
	if (refreshTimer) clearInterval(refreshTimer);
	refreshTimer = setInterval(main, localStorage["refresh"]*1000);
}

function initilise(){

	if (isFetching == null) isFetching = false;
	if(localStorage["ip_address"]==undefined){
		localStorage["ip_address"]="http://firefly.artica.es/pandora_demo";
	}
	
	if(localStorage["api_pass"]==undefined){
		localStorage["api_pass"]="doreik0";
	}
	
	if(localStorage["user_name"]==undefined){
		localStorage["user_name"]="demo";
	}
	
	if(localStorage["pass"]==undefined){
		localStorage["pass"]="demo";
	}
	if(localStorage["critical"]==null){
		localStorage["critical"]="11";
	}
	if(localStorage["informational"]==null){
		localStorage["informational"]="1";
	}
	if(localStorage["maintenance"]==null){
		localStorage["maintenance"]="10";
	}
	if(localStorage["normal"]==null){
		localStorage["normal"]="6";
	}
	if(localStorage["warning"]==null){
		localStorage["warning"]="2";
	}
	if(localStorage["events"]==null){
		localStorage["events"]=20;
	}
	if(localStorage["refresh"]==null){
		localStorage["refresh"]="10";
	}
	if(localStorage["ip_address"]==null){
		localStorage["ip_address"]="http://firefly.artica.es/pandora_demo";
	}
	
	if(localStorage["api_pass"]==null){
		localStorage["api_pass"]="doreik0";
	}
	
	if(localStorage["user_name"]==null){
		localStorage["user_name"]="demo";
	}
	
	if(localStorage["pass"]==null){
		localStorage["pass"]="demo";
	}
	if(localStorage["sound_alert"]==null){
		localStorage["sound_alert"]="on";
	}
	if(localStorage["changed"]==null){
		localStorage["changed"]="false";
	}
	if(localStorage["new_events"]==null){
		localStorage["new_events"]=parseInt(localStorage["events"]);
	}
	if(localStorage["error"]==null) {
		localStorage["error"] = true;
	}
}
