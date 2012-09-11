function initialise(){
	                
	document.getElementById('ip_address').value = localStorage["ip_address"];
	document.getElementById('api_pass').value = localStorage["api_pass"];
	document.getElementById('user_name').value = localStorage["user_name"];

	document.getElementById('pass').value = localStorage["pass"]; 
	document.getElementById('critical').value = localStorage["critical"]; 
	document.getElementById('informational').value = localStorage["informational"]; 
	document.getElementById('maintenance').value = localStorage["maintenance"]; 
	document.getElementById('normal').value = localStorage["normal"]; 
	document.getElementById('warning').value = localStorage["warning"];
	document.getElementById('refresh').value = localStorage["refresh"];
	document.getElementById('events').value = localStorage["events"];
	if(localStorage["sound_alert"]=="on"){
		disable(false);
		document.getElementById('sound_alert_o').checked=true;
	}
	if(localStorage["sound_alert"]=="off"){
		disable(true);
		document.getElementById('sound_alert_f').checked=true;
	}
                
}
            
function change(value, id){
	playSound(value);
	if(id=="critical"){
		localStorage["critical"]=value;
	}
	if(id=="informational"){
		localStorage["informational"]=value;
	}
	if(id=="maintenance"){
		localStorage["maintenance"]=value;
	}
	if(id=="normal"){
		localStorage["normal"]=value;
	}
	if(id=="warning"){
		localStorage["warning"]=value;
	}
}
            
function change_o(value, id){
	if(id=="refresh"){
		localStorage["refresh"]=value;
	}
	if(id=="events"){
		localStorage["events"]=value;
	}
}

function change_global(value,id){
	console.log("value => "+value);
	console.log("id => "+id);
	bg=chrome.extension.getBackgroundPage();
	bg.location.reload();
	if(id=="ip_address"){
		localStorage["ip_address"]=value;
	}
	if(id=="api_pass"){
		localStorage["api_pass"]=value;
	}
	if(id=="user_name"){
		localStorage["user_name"]=value;
	}
	if(id=="pass"){
		localStorage["pass"]=value;
	}
	if(id=="sound_alert"){
		localStorage["sound_alert"]=value;
		if(localStorage["sound_alert"]=="off"){
			disable(true);
		}
		if(localStorage["sound_alert"]=="on"){
			disable(false);
		}
	}
}

function disable(state){
	if(state){
		document.getElementById("critical").disabled=true;
		document.getElementById("informational").disabled=true;
		document.getElementById("maintenance").disabled=true;
		document.getElementById("normal").disabled=true;
		document.getElementById("warning").disabled=true;
	}
	if(!state){
		document.getElementById("critical").disabled=false;
		document.getElementById("informational").disabled=false;
		document.getElementById("maintenance").disabled=false;
		document.getElementById("normal").disabled=false;
		document.getElementById("warning").disabled=false;
	}
}

function windowClose() {
	//window.close();
	console.log("close");
}


//Add callbacks to elements
$(document).ready (function () {
	
	//Initialise all form fields
	initialise();
	
	//IP address field
	$("#ip_address").change(function () {
		change_global($(this).val(), "ip_address");
	});
	
	//API password field
	$("#api_pass").change(function () {
		change_global($(this).val(), "api_pass");
	});
	
	//User name field
	$("#user_name").change(function () {
		change_global($(this).val(), "user_name");
	});
	
	//Password field
	$("#pass").change(function () {
		change_global($(this).val(), "pass");
	});
	
	//Sound alerts on/off
	$("#sound_alert_o").change(function () {
		change_global($(this).val(), "sound_alert");
	});
	
	$("#sound_alert_f").change(function () {
		change_global($(this).val(), "sound_alert");
	});	
	
	//Alert sounds fields
	$("#critical").change(function () {
		change($(this).val(), "critical");
	});
	
	$("#informational").change(function () {
		change($(this).val(), "informational");
	});
	
	$("#maintenance").change(function () {
		change($(this).val(), "maintenance");
	});
	
	$("#normal").change(function () {
		change($(this).val(), "normal");
	});
	
	$("#warning").change(function () {
		change($(this).val(), "warning");
	});
	
	//Auto refresh field
	$("#refresh").change(function () {
		change_o($(this).val(), "refresh");
	});
	
	$("#events").change(function () {
		change_o($(this).val(), "events");
	});
		
	//Close button
	$("#close").click (function () {
		window.close();
	});
	
});
