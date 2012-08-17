if ("undefined" == typeof(PandoraChrome)) {
    var PandoraChrome = {};
    var prefManager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefService).getBranch("pandora.");

    var timer = null;
    var allEvents=new Array();
    var newEvents=new Array();
    var oldEvents=new Array();
    var api_div_numbers=21;
};
 
    
     
        
        
PandoraChrome.fn = function(){
    return {
        displaySideBar: function(){
            PandoraChrome.fn.hideNotification();
            PandoraChrome.fn.hideBadge();
            toggleSidebar('viewPandoraSidebar');
        },
        displayDialog : function(){
            window.openDialog("chrome://pandorasidebar/content/options.xul", "","chrome, centerscreen, dialog, modal, resizable=no", null).focus();
        },
        handleClick: function(e){
            if(e.button == 0){
                toggleSidebar('viewPandoraSidebar'); 
                PandoraChrome.fn.setIcon('icon16');
            }
        },
                
        Onloading: function () {
            if(prefManager.getBoolPref("firstLoad")){
                prefManager.setIntPref("new_events",prefManager.getIntPref("events"));
                prefManager.setBoolPref("firstLoad",false);
            }

            if(timer) {
                clearTimeout(timer);
            }
            timer =setTimeout(PandoraChrome.fn.main , 100 );
        },

        

        main: function() {
           // alert('test_main');
            var url=prefManager.getCharPref("ip_address")+'/include/api.php?op=get&op2=events&return_type=csv&apipass='+prefManager.getCharPref("api_pass")+'&user='+prefManager.getCharPref("user_name")+'&pass='+prefManager.getCharPref("pass");
            var feedUrl = url;
            prefManager.setBoolPref("data_check", true);
            req = new XMLHttpRequest();
            req.onload = PandoraChrome.fn.handleResponse;
            req.onerror = PandoraChrome.fn.handleError;
            req.open("GET", feedUrl, true);
            req.send(null);
        },

        handleError: function() {
            //alert("error");
            prefManager.setCharPref("data",null);
            prefManager.setBoolPref("data_check", false);
            if(timer) {
                clearTimeout(timer);
            }
            timer =setTimeout(PandoraChrome.fn.main , 1000);
        },

        handleResponse: function () {
            var doc = req.responseText;
            if (doc=="auth error") {
                prefManager.setCharPref("data",null);
                prefManager.setBoolPref("data_check", false);
                if(timer) {
                    clearTimeout(timer);
                }
                timer =setTimeout(PandoraChrome.fn.main , 1000);

            }
            else{
                var n=doc.search("404 Not Found");
                if(n>0){

                    prefManager.setCharPref("data",null);
                    prefManager.setBoolPref("data_check", false);
                    if(timer) {
                        clearTimeout(timer);
                    }
                    timer =setTimeout(PandoraChrome.fn.main , 1000);
                }
                
                else{
                    prefManager.setBoolPref("data_check", true);

                    prefManager.setCharPref("data",doc);
                    PandoraChrome.fn.getEvents(doc);
                }
            }
        },

        getEvents: function (reply){
            if(reply.length>100){
                all_event_array=reply.split("\n");
                allEvents=PandoraChrome.fn.divideArray(all_event_array);
                if(oldEvents.length==0){
                    oldEvents=allEvents;
                }


                newEvents=PandoraChrome.fn.fetchNewEvents(allEvents,oldEvents);
                 if(newEvents.length!=0){
                    for(var k=0;k<newEvents.length;k++){
                        var temp=prefManager.getIntPref("new_events")+1;
                        prefManager.setIntPref("new_events",temp);
                        PandoraChrome.fn.showNotification(k);
                        PandoraChrome.fn.showBadge(prefManager.getIntPref("new_events"));

                    }
                }
                oldEvents=allEvents;
                if(prefManager.getIntPref("new_events")>0){
                        PandoraChrome.fn.showBadge(prefManager.getIntPref("new_events"));
                }
                else{
                        PandoraChrome.fn.hideBadge();
                }

                
                
                if(timer) {
                    clearTimeout(timer);
                }
                timer =setTimeout(PandoraChrome.fn.main , prefManager.getIntPref("refresh")*1000 );
            }
        },

        showNotification: function(eventId){
            //alert("notify"+eventId);
            if(prefManager.getBoolPref("sound_alert")){
                if(newEvents[eventId][19]=="Critical"){
                    Sounds.playSound(prefManager.getIntPref("critical"));
                }
                if(newEvents[eventId][19]=="Informational"){
                    Sounds.playSound(prefManager.getIntPref("informational"));
                }
                if(newEvents[eventId][19]=="Maintenance"){
                    Sounds.playSound(prefManager.getIntPref("maintenance"));
                }
                if(newEvents[eventId][19]=="Normal"){
                    Sounds.playSound(prefManager.getIntPref("normal"));
                }
                if(newEvents[eventId][19]=="Warning"){
                    Sounds.playSound(prefManager.getIntPref("warning"));
                }

            }

            var newEve = document.getElementById('newEvent');
            newEve.label="Last Event : "+newEvents[eventId][6];
            var id;
            if(newEvents[eventId][9]==0){
                id=".";
            }
            else {
                id= " in the module with Id "+ newEvents[eventId][9] + ".";
            }

            var event = newEvents[eventId][14]+" : "+newEvents[eventId][17]+". Event occured at "+ newEvents[eventId][5]+id;
            newEve.tooltipText=event;
            $('#newEvent').show();
            return;
        },


        hideNotification:function(){
            //alert("Hide Notif");
            $('#newEvent').hide();
        },

        
        showBadge: function (txt) {
            //alert(txt);
            var updateCount = document.getElementById('temp');
            updateCount.setAttribute("style","cursor:pointer; font-size:11px; color:#123863; font-weight:bold; display:none;") ;
            updateCount.label=txt;
            $('#temp').show();
        },

        hideBadge: function () {
            var updateCount = document.getElementById('temp');
            //alert("hide B");
            $('#temp').hide();
        },

        divideArray: function (e_array){
            var Events=new Array();
            for(var i=0;i<e_array.length;i++){
                var event=e_array[i].split(";");
                Events.push(event); 
            }
            return Events;
        },
       
        
        fetchNewEvents: function (A,B){
            var arrDiff = new Array();
            // alert(A.length);
            //alert(B.length);
            for(var i = 0; i < A.length; i++) {
                var id = false;
                for(var j = 0; j < B.length; j++) {
                    if(A[i][0] == B[j][0]) {
                        id = true;
                        break;
                    }
                }
                if(!id) {
                    arrDiff.push(A[i]);
                }
            }
            return arrDiff;
        },
    
        
        getNotification:function (eventId){
            var title=newEvents[eventId][6];
            var id;
            if(newEvents[eventId][9]==0){
                id=".";
            }
            else {
                id= " in the module with Id "+ newEvents[eventId][9] + ".";
            }
                       
            var event = newEvents[eventId][14]+" : "+newEvents[eventId][17]+". Event occured at "+ newEvents[eventId][5]+id;
            //var event=newEvents[eventId][14]+' '+newEvents[eventId][17]+' Event occured at:'+ newEvents[eventId][5] +'in the module with Id '+ newEvents[eventId][9];
            return '<a>' + title + '</a> <br/> <span style="font-size:80%">' + event + '</span>';
            
        }

    };
}();

/* Add Event Listener */
window.addEventListener("load", PandoraChrome.fn.Onloading(), false);
