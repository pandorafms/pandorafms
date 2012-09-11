var timer = null;
var event_array;
var max_events;
var bg;
$(document).ready(function(){
	max_events=localStorage["events"];
	if(localStorage["events"]==undefined){
		localStorage["events"]="20";
	}
	bg=chrome.extension.getBackgroundPage();
	if(timer) {
		 clearTimeout(timer);
	}
	timer =setTimeout(mainPoP , 2000 ); 
});

function mainPoP(){
	if(bg.check()){
		showEvents();
	}
	else
	{
		showUrlError();
	}
}
function showUrlError(){
	var res=document.createDocumentFragment();
	var r = document.getElementById('e');              
	var eve=document.createElement('div');
	eve.id="event_temp";
	eve.setAttribute("class","b");
	
	var p = document.createElement('a'); 
	p.href="options.html";
	p.target="_blank";
	p.innerText="Configure ip address,API password, user name and password with correct values";
	eve.appendChild(p);
	res.appendChild(eve);
	r.parentNode.insertBefore(res,r);
	$('.loader').hide();
	$('div.b').show();
	
	if(timer) {
		clearTimeout(timer);
	}
	timer =setTimeout(refresh , 1000 );
}

function showDataError(){
	var res=document.createDocumentFragment();
	var r = document.getElementById('e');              
	var eve=document.createElement('div');
	eve.id="event_temp";
	eve.setAttribute("class","b");
	
	var p = document.createElement('a'); 
	p.innerText="Error in fetching data!! Check your internet connection";
	eve.appendChild(p);
	res.appendChild(eve);
	r.parentNode.insertBefore(res,r);
	$('.loader').hide();
	$('div.b').show();
	
	if(timer) {
		clearTimeout(timer);
	}
	timer =setTimeout(refresh , 1000 );
}

function showEvents(){
	var allEvents=bg.fetchEvents();
	var r = document.getElementById('e'); 
	var res=document.createDocumentFragment();               
	var eve=document.createElement('div');
	eve.id="event_temp";
	eve.setAttribute("class","b");
	
	var i=0;
	if(allEvents.length>0){
		while(i<max_events){               
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
			if(allEvents[i][1]==""){;
					agent_url=localStorage["ip_address"]+"/index.php?sec=eventos&sec2=operation/events/events" ; 
			}else{
					agent_url=localStorage["ip_address"]+"/index.php?sec=estado&sec2=operation/agentes/ver_agente&id_agente="+allEvents[i][1];
			}
			a.setAttribute("href",agent_url);
			a.target="_blank";
			
			
			a.innerText = allEvents[i][6];
			
			if(i<localStorage["new_events"]){
					var temp_style="font-weight:bold;";
			}
			else{
					var temp_style="font-weight:normal;";
			}
			
			
			if(allEvents[i][19]=="Warning"){
				eve_title.setAttribute("style","background:#FCED7E; margin-bottom:-12px;"+temp_style);
			}
			if(allEvents[i][19]=="Critical"){
				eve_title.setAttribute("style","background:#FA7A7A; margin-bottom:-12px;"+temp_style);
			}
			if(allEvents[i][19]=="Informational"){
				eve_title.setAttribute("style","background:#7FB9FA; margin-bottom:-12px;"+temp_style);
			}
			if(allEvents[i][19]=="Normal"){
				eve_title.setAttribute("style","background:#A8D96C; margin-bottom:-12px;"+temp_style);
			}
			if(allEvents[i][19]=="Maintenance"){
				eve_title.setAttribute("style","background:#BABDB6; margin-bottom:-12px;"+temp_style);
			}
			eve_title.appendChild(a);
			eve.appendChild(eve_title);
			var b = document.createElement('br');
			eve.appendChild(b);
			
			var time=allEvents[i][5].split(" ");
			var time_text = time[0]+" "+time[1];
			
			
			var p = document.createElement('p');
			var id;
			if(allEvents[i][9]==0){
				id=".";
			}
			else {
				id= " in the module with Id "+ allEvents[i][9] + ".";
			}
		   
			p.innerText = allEvents[i][14]+" : "+allEvents[i][17]+". Event occured at "+ time_text+id;
			p.id = 'p_' + i;
			eve.appendChild(p);
			i++;
		}
	
		res.appendChild(eve);
		r.parentNode.insertBefore(res,r);

		$('img.pm').click(showHide);
		$('.loader').hide();
		$('div.b').show();
	}
	else{
	showDataError();
	}
	
	localStorage["new_events"]=0;
	bg.hideBadge();
	if(timer) {
		clearTimeout(timer);
	}
	timer =setTimeout(refresh , 30*1000 );
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

function refresh(){
	localStorage["new_events"]=0;
	bg.hideBadge();
	var e = document.getElementById('event_temp');
	if(e){
			e.parentNode.removeChild(e);
	}
	mainPoP();
}


function mrefresh(){
	localStorage["new_events"]=0;
	bg.hideBadge();
	var bg=chrome.extension.getBackgroundPage();
	bg.location.reload();
	var e = document.getElementById('event_temp');
	if(e){
			e.parentNode.removeChild(e);
	}
	mainPoP();
}
