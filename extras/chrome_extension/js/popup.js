var max_events;
var bg;
$(document).ready(function(){
	max_events=localStorage["events"];
	if(localStorage["events"]==undefined){
		localStorage["events"]="20";
	}
	bg=chrome.extension.getBackgroundPage();

	// Display the information
	if (bg.fetchEvents().length == 0) {
		showError("Error in fetching data!! Check your internet connection");
	} else {
		showEvents();
	}

	// Adding buttons listeners
	document.getElementById("m_refresh").addEventListener("click", mrefresh);

	// Added listener to background messages
	chrome.runtime.onMessage.addListener(function(message,sender,sendResponse){
		switch (message.text) {
			case "FETCH_EVENTS":
				setSpinner();
				//$('div.b').hide();
				break;
			case "FETCH_EVENTS_SUCCESS":
				unsetSpinner();
				showEvents();
				break;
			case "FETCH_EVENTS_DATA_ERROR":
				unsetSpinner();
				showError("Error in fetching data!! Check your internet connection");
				break;
			case "FETCH_EVENTS_URL_ERROR":
				unsetSpinner();
				showError("Configure ip address,API password, user name and password with correct values");
				break;
			default:
				console.log("Unrecognized message: ", message.text);
				break;
		}
	});
});

function setSpinner () {
	$('#refr_img_id').attr("src", "images/spinny.gif");
}

function unsetSpinner() {
	$('#refr_img_id').attr("src", "images/refresh.png");
}

function clearError() {
	$('.error').hide();
	$('.error a').text("");
}

function showError(text){
	$('.error a').text(text);
	$('.error').show();
}

function showEvents(){

	clearError();
	$('#events').empty();
	var e_refr = document.getElementById('event_temp');
	if(e_refr){
			wrapper.removeChild(e_refr);
	}
	var allEvents=bg.fetchEvents();            
	var eve=document.createElement('div');
	eve.id="event_temp";
	eve.setAttribute("class","b");
	
	var i=0;
	if(allEvents.length>0){
		while(i<max_events && i<allEvents.length){
			var eve_title=document.createElement('div');
			var img = document.createElement('img');
			img.src = 'images/plus.gif';
			img.width = '9';
			img.height='9';
			img.className ='pm';
			img.id='i_' + i;
			eve_title.appendChild(img);
			var a = document.createElement('a');
			var temp_style;
			
			var agent_url;
			if (allEvents[i]["agent_name"] == 0) {
					agent_url=localStorage["ip_address"]+"/index.php?sec=eventos&sec2=operation/events/events"; 
			} else {
					agent_url=localStorage["ip_address"]+"/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente="+allEvents[i]['agent_name'];
			}
			a.setAttribute("href",agent_url);
			a.target="_blank";
			
			
			a.innerText = allEvents[i]['title'];			
			
			switch (allEvents[i]['severity']){
				case "Warning":
					eve_title.setAttribute("style","background:#FCED7E; margin-bottom:-12px;"+temp_style);
					break;
				case "Critical":
					eve_title.setAttribute("style","background:#FA7A7A; margin-bottom:-12px;"+temp_style);
					break;
				case "Informational":
					eve_title.setAttribute("style","background:#7FB9FA; margin-bottom:-12px;"+temp_style);
					break;
				case "Normal":
					eve_title.setAttribute("style","background:#A8D96C; margin-bottom:-12px;"+temp_style);
					break;
				case "Maintenance":
					eve_title.setAttribute("style","background:#BABDB6; margin-bottom:-12px;"+temp_style);
					break;
			}
			
			eve_title.appendChild(a);
			eve.appendChild(eve_title);
			var b = document.createElement('br');
			eve.appendChild(b);
			
			var time=allEvents[i]['date'].split(" ");
			var time_text = time[0]+" "+time[1];
			
			
			var p = document.createElement('p');
			var id = (allEvents[i]['module']==0)
				? "."
				: " in the module with Id "+ allEvents[i]['module'] + ".";
		   
			p.innerText = allEvents[i]['type']+" : "+allEvents[i]['source']+". Event occured at "+ time_text+id;
			p.id = 'p_' + i;
			eve.appendChild(p);
			i++;
		}
	
		$('#events').append(eve);

		$('img.pm').click(showHide);
		$('div.b').show();
	} else {
		showError("Error in fetching data!! Check your internet connection");
	}
	
	localStorage["new_events"]=0;
	bg.updateBadge();
}

function showHide() {
	var id = $(this).attr('id');
	var num = id.split("_")[1];
	var pid = "p_" + num;
	if($('#' + pid).css('display') == 'none') {
		$('#' + pid).slideDown("fast");
		$(this).attr({src: 'images/minus.gif'});
	}
	else {
		$('#' + pid).slideUp("fast");
		$(this).attr({src: 'images/plus.gif'});
	}
}

function mrefresh(){
	localStorage["new_events"]=0;
	bg.updateBadge();
	clearError();
	bg.resetInterval();
	bg.main();
}
