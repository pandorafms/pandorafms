if ("undefined" == typeof(PandoraPoPUp)) {
    var PandoraPoPUp = {};
    var timer = null;
    var event_array;
    var max_events;
    var htmlns = "http://www.w3.org/1999/xhtml";
    var allEvents = new Array();
    var newEvents = new Array();
    var oldEvents = new Array();
    var api_div_numbers = 21;
    var prefmanager = Components.classes["@mozilla.org/preferences-service;1"].getService(Components.interfaces.nsIPrefService).getBranch("pandora.");
}
;


PandoraPoPUp = function () {
    return{

        displayDialog:function () {
            window.openDialog("chrome://pandorasidebar/content/options.xul", "", "chrome, centerscreen, dialog, modal, resizable=no", null).focus();
        },

        onLoadPopUp:function () {
            max_events = prefmanager.getIntPref("events");
            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(PandoraPoPUp.mainPoP, 2000);
        },

        mainPoP:function () {
            if (prefmanager.getBoolPref("data_check")) {
                if (prefmanager.getCharPref("data").length > 1000) {
                    PandoraPoPUp.showEvents();
                }
                else {
                    PandoraPoPUp.showUrlError();
                }
            }
            else {
                PandoraPoPUp.showDataError();
            }
        },

        showUrlError:function () {
            var res = document.createDocumentFragment();
            var r = document.getElementById('e');
            var eve = document.createElement('label');
            eve.setAttribute("value", "Configure ip address,API password, user name and password with correct values");
            eve.setAttribute("class", "b");
            res.appendChild(eve);
            r.parentNode.insertBefore(res, r);

            $('.loader').hide();
            $('vbox.b').show();

            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(PandoraPoPUp.refresh, 1000);
        },


        showDataError:function () {
            var res = document.createDocumentFragment();
            var r = document.getElementById('e');
            var eve = document.createElement('label');
            eve.setAttribute("value", "Error in fetching data!! Check your internet connection");
            eve.setAttribute("class", "b");
            res.appendChild(eve);
            r.parentNode.insertBefore(res, r);

            $('.loader').hide();
            $('vbox.b').show();

            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(PandoraPoPUp.refresh, 1000);
        },

        showEvents:function () {
            var data = prefmanager.getCharPref("data");
            PandoraPoPUp.getEvents(data);
            var allEventsPoP = allEvents;
            //alert(allEventsPoP[0]);
            var r = document.getElementById('e');
            var res = document.createDocumentFragment();
            var eve = document.createElement('vbox');
            eve.setAttribute("id", "event_temp");
            eve.setAttribute("class", "b");
            eve.setAttribute("style", "display:none;");
            var i = 0;
            if (allEventsPoP.length > 0) {

                while (i < max_events) {
                    var eve_title = document.createElement('hbox');
                    var img = document.createElement('image');
                    img.setAttribute("src", 'images/plus.gif');
                    img.setAttribute("width", '9');
                    img.setAttribute("height", '9');
                    img.setAttribute("id", 'i_' + i);
                    img.setAttribute("class", "pm");

                    eve_title.appendChild(img);
                    var a = document.createElement("label");
                    var temp_style;
                    a.setAttribute("value", allEventsPoP[i][6]);

                    //var temp_style="font-weight:bold;";      new_events
                    if (i < prefmanager.getIntPref("new_events")) {
                        var temp_style = "font-weight:bold;";
                    }
                    else {
                        var temp_style = "font-weight:normal;";
                    }


                    if (allEventsPoP[i][19] == "Warning") {
                        eve_title.setAttribute("style", "background:#FDE84F; margin-bottom:1px;" + temp_style);
                    }
                    if (allEventsPoP[i][19] == "Critical") {
                        eve_title.setAttribute("style", "background:#C60700; margin-bottom:1px;" + temp_style);
                    }
                    if (allEventsPoP[i][19] == "Informational") {
                        eve_title.setAttribute("style", "background:#739FD0; margin-bottom:1px;" + temp_style);
                    }
                    if (allEventsPoP[i][19] == "Normal") {
                        eve_title.setAttribute("style", "background:#8AE234; margin-bottom:1px;" + temp_style);
                    }
                    if (allEventsPoP[i][19] == "Maintenance") {
                        eve_title.setAttribute("style", "background:#BABDB6; margin-bottom:1px;" + temp_style);
                    }
                    eve_title.appendChild(a);
                    eve.appendChild(eve_title);
                    var b = document.createElement('br');
                    eve.appendChild(b);

                    var time = allEventsPoP[i][5].split(" ");
                    var time_text = time[0] + " " + time[1];


                    var p = document.createElement('description');
                    var p1 = document.createElement('description');
                    var p2= document.createElement('description');
                    var id;
                    if (allEventsPoP[i][9] == 0) {
                        id = ".";
                    }
                    else {
                        id = " in the module with Id " + allEventsPoP[i][9] + ".";
                    }

                    p1.setAttribute("value", allEventsPoP[i][14] + " : " + allEventsPoP[i][17] + ".");
                    p2.setAttribute("value",  "Event occured at " + time_text + id);
                    p.appendChild(p1);
                    p.appendChild(b);
                    p.appendChild(p2);
                    p.setAttribute("id", 'p_' + i);
                    p.setAttribute("style", "display:none;");
                    eve.appendChild(p);
                    i++;
                }
                prefmanager.setIntPref("new_events", 0);
                res.appendChild(eve);
                r.parentNode.insertBefore(res, r);

                $('image.pm').click(PandoraPoPUp.showHide);
                $('.loader').hide();
                $('.b').show();
            }
            else {
                PandoraPoPUp.showDataError();
            }

            if (timer) {
                clearTimeout(timer);
            }
            timer = setTimeout(PandoraPoPUp.refresh, 30 * 1000);
        },

        showHide:function () {
            var id = $(this).attr('id');
            var num = id.split("_")[1];
            var pid = "p_" + num;
            if ($('#' + pid).css('display') == 'none') {
                $('#' + pid).show();
                $(this).attr({
                    src:'images/minus.gif'
                });
            }
            else {
                $('#' + pid).slideUp("fast");
                $(this).attr({
                    src:'images/plus.gif'
                });
            }
        },

        refresh:function () {
            prefmanager.setIntPref("new_events", 0);
            hideBadge();
            var e = document.getElementById('event_temp');
            if (e) {
                e.parentNode.removeChild(e);
            }
            PandoraPoPUp.mainPoP();
        },


        mrefresh:function () {
            prefmanager.setIntPref("new_events", 0);
            document.getElementById("m_refresh").disabled = true;
            setTimeout(function () {
                document.getElementById("m_refresh").disabled = false;
            }, 5000);
            var e = document.getElementById('event_temp');
            if (e) {
                e.parentNode.removeChild(e);
            }
            PandoraPoPUp.mainPoP();
        },


        getEvents:function (data) {
            all_event_array = data.split("\n");
            allEvents = PandoraPoPUp.divideArray(all_event_array);
        },

        divideArray:function (e_array) {
            var Events = new Array();
            for (var i = 0; i < e_array.length; i++) {
                var event = e_array[i].split(";");
                Events.push(event);
            }
            return Events;
        }
    };
}();

window.addEventListener("load", PandoraPoPUp.onLoadPopUp(), false);
