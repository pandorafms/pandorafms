// browser/anyterm.js
// This file is part of Anyterm; see http://anyterm.org/
// (C) 2005-2006 Philip Endecott

// This program is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2 of the License, or
// any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.


var undefined;

var url_prefix = "";

var frame;
var term;
var open=false;
var session;

var method="POST";
//var method="GET";

// Random sequence numbers are needed to prevent Opera from caching
// replies

var is_opera = navigator.userAgent.toLowerCase().indexOf("opera") != -1;
if (is_opera) {
  method="GET";
}

var seqnum_val=Math.round(Math.random()*100000);
function cachebust() {
  if (is_opera) {
    seqnum_val++;
    return "&x="+seqnum_val;
  } else {
    return "";
  }
}


// Cross-platform creation of XMLHttpRequest object:

function new_XMLHttpRequest() {
  if (window.XMLHttpRequest) {
    // For most browsers:
    return new XMLHttpRequest();
  } else {
    // For IE, it's active-X voodoo.
    // There are different versions in different browsers.
    // The ones we try are the ones that Sarissa tried.  The disabled ones
    // apparently also exist, but it seems to work OK without trying them.

    //try{ return new ActiveXObject("MSXML3.XMLHTTP"); }   catch(e){}
    try{ return new ActiveXObject("Msxml2.XMLHTTP.5.0"); } catch(e){}
    try{ return new ActiveXObject("Msxml2.XMLHTTP.4.0"); } catch(e){}
    try{ return new ActiveXObject("MSXML2.XMLHTTP.3.0"); } catch(e){}
    try{ return new ActiveXObject("MSXML2.XMLHTTP"); }     catch(e){}
    //try{ return new ActiveXObject("Msxml2.XMLHTTP"); }   catch(e){}
    try{ return new ActiveXObject("Microsoft.XMLHTTP"); }  catch(e){}
    throw new Error("Could not find an XMLHttpRequest active-X class.")
  }
}


// Asynchronous and Synchronous XmlHttpRequest wrappers

// AsyncLoader is a class; an instance specifies a callback function.
// Call load to get something and the callback is invoked with the
// returned document.

function AsyncLoader(cb) {
  this.callback = cb;
  this.load =  function (url,query) {
    var xmlhttp = new_XMLHttpRequest();
    var cbk = this.callback;
    //var timeoutID = window.setTimeout("alert('No response after 20 secs')",20000);
    xmlhttp.onreadystatechange = function () {
      if (xmlhttp.readyState==4) {
	//window.clearTimeout(timeoutID);
	if (xmlhttp.status==200) {
	  cbk(xmlhttp.responseText);
	} else {
	  alert("Server returned status code "+xmlhttp.status+":\n"+xmlhttp.statusText);
	  cbk(null);
	}
      }
    }
    if (method=="GET") {
      xmlhttp.open(method, url+"?"+query, true);
      xmlhttp.send(null);
    } else if (method=="POST") {
      xmlhttp.open(method, url, true);
      xmlhttp.setRequestHeader('Content-Type',
			       'application/x-www-form-urlencoded');
      xmlhttp.send(query);
    }

  }
}


// Synchronous loader is a simple function

function sync_load(url,query) {
  var xmlhttp = new_XMLHttpRequest();
  if (method=="GET") {
    xmlhttp.open(method, url+"?"+query, false);
    xmlhttp.send(null);
  } else if (method=="POST") {
    xmlhttp.open(method, url, false);
    xmlhttp.setRequestHeader('Foo','1234');
    xmlhttp.setRequestHeader('Content-Type',
			     'application/x-www-form-urlencoded');
    xmlhttp.send(query);
  }
  if (xmlhttp.status!=200) {
    alert("Server returned status code "+xmlhttp.status+":\n"+xmlhttp.statusText);
    return null;
  }
  return xmlhttp.responseText;
}


// Process error message from server:

function handle_resp_error(resp) {
  if (resp.charAt(0)=="E") {
    var msg = resp.substr(1);
    alert(msg);
    return true;
  }
  return false;
}


// Receive channel:

var rcv_loader;

var disp="";



function process_editscript(edscr) {

  var ndisp="";
  
  var i=0;
  var dp=0;
  while (i<edscr.length) {
    var cmd=edscr.charAt(i);
    i++;
    var cp=edscr.indexOf(":",i);
    var num=Number(edscr.substr(i,cp-i));
    i=cp+1;
    //alert("cmd="+cmd+" num="+num);
    if (cmd=="d") {
      dp+=num;
    } else if (cmd=="k") {
      ndisp+=disp.substr(dp,num);
      dp+=num;
    } else if (cmd=="i") {
      //if (edscr.length<i+num) {
	//alert("edit script ended early; expecting "+num+" but got only "+edscr.length-cp);
      //}
      ndisp+=edscr.substr(i,num);
      i+=num;
    }
  }

  return ndisp;
}


var visible_height_frac = 1;

function display(edscr) {

  //alert(edscr);

  var ndisp;
  if (edscr=="n") {
    return;
  } else if (edscr.charAt(0)=="R") {
    ndisp = edscr.substr(1);
  } else {
    ndisp = process_editscript(edscr);
  }

  disp=ndisp;

  term.innerHTML=ndisp;

  if (visible_height_frac != 1) {
    var termheight = visible_height_frac * term.scrollHeight;
    term.style.height = termheight+"px";
    term.scrollTop = term.scrollHeight;
  }
}


function scrollterm(pages) {
  term.scrollTop += pages * visible_height_frac * term.scrollHeight;
}


var rcv_timeout;

function get() {
  //alert("get");
  rcv_loader.load(url_prefix+"anyterm-module","a=rcv&s="+session+cachebust());
  rcv_timeout = window.setTimeout("alert('no response from server after 60 secs')",60000);
}

function rcv(resp) {
  // Called asynchronously when the received document has returned
  // from the server.

  window.clearTimeout(rcv_timeout);

  if (!open) {
    return;
  }

  if (resp=="") {
    // We seem to get this if the connection to the server fails.
    alert("Connection to server failed");
    return;
  }

  if (handle_resp_error(resp)) {
    return;
  }

  display(resp);
  get();
}

rcv_loader = new AsyncLoader(rcv);


// Transmit channel:

var kb_buf="";
var send_loader;
var send_in_progress=false;

function send() {
  send_in_progress=true;
  send_loader.load(url_prefix+"anyterm-module",
                   "a=send&s="+session+cachebust()+"&k="+encodeURIComponent(kb_buf));
  kb_buf="";
}

function send_done(resp) {
  send_in_progress=false;
  if (handle_resp_error(resp)) {
    return;
  }
  if (kb_buf!="") {
    send();
  }
}

send_loader = new AsyncLoader(send_done);


function maybe_send() {
  if (!send_in_progress && open && kb_buf!="") {
    send();
  }
}


function process_key(k) {
//   alert("key="+k);
//   return;
  kb_buf+=k;
  maybe_send();
}


function esc_seq(s) {
  return String.fromCharCode(27)+"["+s;
}


function key_ev_stop(ev) {
  // We want this key event to do absolutely nothing else.
  ev.cancelBubble=true;
  if (ev.stopPropagation) ev.stopPropagation();
  if (ev.preventDefault)  ev.preventDefault();
  try { ev.keyCode=0; } catch(e){}
}

function key_ev_supress(ev) {
  // We want this keydown event to become a keypress event, but nothing else.
  ev.cancelBubble=true;
  if (ev.stopPropagation) ev.stopPropagation();
}


// When a key is pressed the browser delivers several events: typically first a keydown 
// event, then a keypress event, then a keyup event.  Ideally we'd just use the keypress 
// event, but there's a problem with that: the browser may not send a keypress event for
// unusual keys such as function keys, control keys, cursor keys and so on.  The exact
// behaviour varies between browsers and probably versions of browsers.
//
// So to get these keys we need to get the keydown events.  They have a couple of 
// problems.  Firstly, you get these events for things like pressing the shift key.  
// Secondly, unlike keypress events you don't get auto-repeat.

function keypress(ev) {
  if (!ev) var ev=window.event;

  // Only handle "safe" characters here.  Anything unusual is ignored; it would
  // have been handled earlier by the keydown function below.
  if ((ev.ctrlKey && !ev.altKey)  // Ctrl is pressed (but not altgr, which is reported
                                  // as ctrl+alt in at least some browsers).
      || (ev.which==0)        // there's no key in the event; maybe a shift key?
                              // (Mozilla sends which==0 && keyCode==0 when you press
                              // the 'windows logo' key.)
      || (ev.keyCode==8)      // backspace
      || (ev.keyCode==16)) {  // shift; Opera sends this.
    key_ev_stop(ev);
    return false;
  }

  var kc;
  if (ev.keyCode) kc=ev.keyCode;
  if (ev.which)   kc=ev.which;
  
  var k=String.fromCharCode(kc);

  // When a key is pressed with ALT, we send ESC followed by the key's normal
  // code.  But we don't want to do this when ALT-GR is pressed.
  if (ev.altKey && !ev.ctrlKey) {
    k = String.fromCharCode(27)+k;
  }

//     alert("keypress keyCode="+ev.keyCode+" which="+ev.which+
//   	" shiftKey="+ev.shiftKey+" ctrlKey="+ev.ctrlKey+" altKey="+ev.altKey);

  process_key(k);

  key_ev_stop(ev);
  return false;
}


function keydown(ev) {
  if (!ev) var ev=window.event;

  //  alert("keydown keyCode="+ev.keyCode+" which="+ev.which+
  // 	" shiftKey="+ev.shiftKey+" ctrlKey="+ev.ctrlKey+" altKey="+ev.altKey);

  var k;

  var kc=ev.keyCode;

  // Handle special keys.  We do this here because IE doesn't send
  // keypress events for these (or at least some versions of IE don't for
  // at least many of them).  This is unfortunate as it means that the
  // cursor keys don't auto-repeat, even in browsers where that would be
  // possible.  That could be improved.

  // Interpret shift-pageup/down locally
  if      (ev.shiftKey && kc==33) { scrollterm(-0.5); key_ev_stop(ev); return false; }
  else if (ev.shiftKey && kc==34) { scrollterm(0.5);  key_ev_stop(ev); return false; }

  else if (kc==33) k=esc_seq("5~");  // PgUp
  else if (kc==34) k=esc_seq("6~");  // PgDn
  else if (kc==35) k=esc_seq("4~");  // End
  else if (kc==36) k=esc_seq("1~");  // Home
  else if (kc==37) k=esc_seq("D");   // Left
  else if (kc==38) k=esc_seq("A");   // Up
  else if (kc==39) k=esc_seq("C");   // Right
  else if (kc==40) k=esc_seq("B");   // Down
  else if (kc==45) k=esc_seq("2~");  // Ins
  else if (kc==46) k=esc_seq("3~");  // Del
  else if (kc==27) k=String.fromCharCode(27); // Escape
  else if (kc==9)  k=String.fromCharCode(9);  // Tab
  else if (kc==8)  k=String.fromCharCode(8);  // Backspace
  else if (kc==112) k=esc_seq(ev.shiftKey ? "25~" : "[A");  // F1
  else if (kc==113) k=esc_seq(ev.shiftKey ? "26~" : "[B");  // F2
  else if (kc==114) k=esc_seq(ev.shiftKey ? "28~" : "[C");  // F3
  else if (kc==115) k=esc_seq(ev.shiftKey ? "29~" : "[D");  // F4
  else if (kc==116) k=esc_seq(ev.shiftKey ? "31~" : "[E");  // F5
  else if (kc==117) k=esc_seq(ev.shiftKey ? "32~" : "17~"); // F6
  else if (kc==118) k=esc_seq(ev.shiftKey ? "33~" : "18~"); // F7
  else if (kc==119) k=esc_seq(ev.shiftKey ? "34~" : "19~"); // F8
  else if (kc==120) k=esc_seq("20~"); // F9
  else if (kc==121) k=esc_seq("21~"); // F10
  else if (kc==122) k=esc_seq("23~"); // F11
  else if (kc==123) k=esc_seq("24~"); // F12

  else {

    // For most keys we'll stop now and let the subsequent keypress event
    // process the key.  This has the advantage that auto-repeat will work.
    // But we'll carry on here for control keys.
    // Note that when altgr is pressed, the event reports ctrl and alt being
    // pressed because it doesn't have a separate field for altgr.  We'll
    // handle altgr in the keypress handler.
    if (!ev.ctrlKey                   // ctrl not pressed
        || (ev.ctrlKey && ev.altKey)  // altgr pressed
        || (ev.keyCode==17)) {        // I think that if you press shift-control,
                                      // you'll get an event with !ctrlKey && keyCode==17.
      key_ev_supress(ev);
      return;  // Note that we don't "return false" here, as we want the
               // keypress handler to be invoked.
    }

    // OK, so now we're handling a ctrl key combination.

    // There are some assumptions below about whether these symbols are shifted
    // or not; does this work with different keyboards?
    if (ev.shiftKey) {
      if (kc==50) k=String.fromCharCode(0);        // Ctrl-@
      else if (kc==54) k=String.fromCharCode(30);  // Ctrl-^, doesn't work
      else if (kc==94) k=String.fromCharCode(30);  // Ctrl-^, doesn't work
      else if (kc==109) k=String.fromCharCode(31); // Ctrl-_
      else {
	key_ev_supress(ev);
	return;
      }
    } else {
      if (kc>=65 && kc<=90) k=String.fromCharCode(kc-64); // Ctrl-A..Z
      else if (kc==219) k=String.fromCharCode(27); // Ctrl-[
      else if (kc==220) k=String.fromCharCode(28); // Ctrl-\   .
      else if (kc==221) k=String.fromCharCode(29); // Ctrl-]
      else if (kc==190) k=String.fromCharCode(30); // Since ctrl-^ doesn't work, map
                                                   // ctrl-. to its code.
      else if (kc==32)  k=String.fromCharCode(0);  // Ctrl-space sends 0, like ctrl-@.
      else {
	key_ev_supress(ev);
	return;
      }
    }
  }

//   alert("keydown keyCode="+ev.keyCode+" which="+ev.which+
// 	" shiftKey="+ev.shiftKey+" ctrlKey="+ev.ctrlKey+" altKey="+ev.altKey);

  process_key(k);

  key_ev_stop(ev);
  return false;
}


// Open, close and initialisation:

function open_term(rows,cols,p,charset,scrollback) {
  var params = "a=open&rows="+rows+"&cols="+cols;
  if (p) {
    params += "&p="+p;
  }
  if (charset) {
    params += "&ch="+charset;
  }
  if (scrollback) {
    if (scrollback>1000) {
      alert("The maximum scrollback is currently limited to 1000 lines.  "
           +"Please choose a smaller value and try again.");
      return;
    }
    params += "&sb="+scrollback;
  }
  params += cachebust();
  var resp = sync_load(url_prefix+"anyterm-module",params);

  if (handle_resp_error(resp)) {
    return;
  }

  open=true;
  session=resp;
}

function close_term() {
  if (!open) {
    alert("Connection is not open");
    return;
  }
  open=false;
  var resp = sync_load(url_prefix+"anyterm-module","a=close&s="+session+cachebust());
  handle_resp_error(resp);  // If we get an error, we still close everything.
  document.onkeypress=null;
  document.onkeydown=null;
  window.onbeforeunload=null;
  var e;
  while (e=frame.firstChild) {
    frame.removeChild(e);
  }
  frame.className="";
  if (on_close_goto_url) {
    document.location = on_close_goto_url;
  }
}


function get_anyterm_version() {
  var svn_url="$URL: file:///var/lib/svn/anyterm/tags/releases/1.1/1.1.29/browser/anyterm.js $";
  var re = /releases\/[0-9]+\.[0-9]+\/([0-9\.]+)/;
  var match = re.exec(svn_url);
  if (match) {
    return match[1];
  } else {
    return "";
  }
}

function substitute_variables(s) {
  var version = get_anyterm_version();
  if (version!="") {
    version="-"+version;
  }
  var hostname=document.location.host;
  return s.replace(/%v/g,version).replace(/%h/g,hostname);
}


// Copying

function copy_ie_clipboard() {
  try {
    window.document.execCommand("copy",false,null);
  } catch (err) {
    return undefined;
  }
  return 1;
}

function copy_mozilla_clipboard() {
  // Thanks to Simon Wissinger for this function.

  try {
    netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
  } catch (err) {
    return undefined;
  }

  var sel=window.getSelection();
  var copytext=sel.toString();
  
  var str=Components.classes["@mozilla.org/supports-string;1"]
    .createInstance(Components.interfaces.nsISupportsString);
  if (!str) return undefined;
  
  str.data=copytext;
  
  var trans=Components.classes["@mozilla.org/widget/transferable;1"]
    .createInstance(Components.interfaces.nsITransferable);
  if (!trans) return undefined;
  
  trans.addDataFlavor("text/unicode");
  trans.setTransferData("text/unicode", str, copytext.length * 2);
  
  var clipid=Components.interfaces.nsIClipboard;
  
  var clip=Components.classes["@mozilla.org/widget/clipboard;1"].getService(clipid);
  if (!clip) return undefined;
  
  clip.setData(trans, null, clipid.kGlobalClipboard);
  
  return 1;
}

function copy_to_clipboard() {
  var r=copy_ie_clipboard();
  if (r==undefined) {
    r=copy_mozilla_clipboard();
  }
  if (r==undefined) {
    alert("Copy seems to be disabled; maybe you need to change your security settings?"
         +"\n(Copy on the Edit menu will probably work)");
  }
}


// Pasting

function get_mozilla_clipboard() {
  // This function is taken from
  // http://www.nomorepasting.com/paste.php?action=getpaste&pasteID=41974&PHPSESSID=e6565dcf5de07256345e562b97ac9f46
  // which does not indicate any particular copyright conditions.  It
  // is a public forum, so one might conclude that it is public
  // domain.

  // IMHO it's disgraceful that Mozilla makes us use these 30 lines of
  // undocumented gobledegook to do what IE does, and documents, with
  // just 'window.clipboardData.getData("Text")'.  What on earth were
  // they thinking?

  try {
    netscape.security.PrivilegeManager.enablePrivilege("UniversalXPConnect");
  } catch (err) {
    return undefined;
  }

  var clip = Components.classes["@mozilla.org/widget/clipboard;1"]
    .createInstance(Components.interfaces.nsIClipboard);
  if (!clip) {
    return undefined;
  }

  var trans = Components.classes["@mozilla.org/widget/transferable;1"]
    .createInstance(Components.interfaces.nsITransferable);
  if (!trans) {
    return undefined;
  }

  trans.addDataFlavor("text/unicode");
  clip.getData(trans,clip.kGlobalClipboard);

  var str=new Object();
  var strLength=new Object();

  try {
    trans.getTransferData("text/unicode",str,strLength);
  } catch(err) {
    // One reason for getting here seems to be that nothing is selected
    return "";
  }

  if (str) {
    str=str.value.QueryInterface(Components.interfaces.nsISupportsString);
  }

  if (str) {
    return str.data.substring(0,strLength.value / 2);
  } else {
    return "";  // ? is this "clipboard empty" or "cannot access"?
  }
}

function get_ie_clipboard() {
  if (window.clipboardData) {
    return window.clipboardData.getData("Text");
  }
  return undefined;
}

function get_default_clipboard() {
  return prompt("Paste into this box and press OK:","");
}  

function paste_from_clipboard() {
  var p = get_ie_clipboard();
  if (p==undefined) {
    p = get_mozilla_clipboard();
  }
  if (p==undefined) {
    p = get_default_clipboard();
    if (p) {
      process_key(p);
    }
    return;
  }

  if (p=="") {
    alert("The clipboard seems to be empty");
    return;
  }

  if (confirm('Click OK to "type" the following into the terminal:\n'+p)) {
    process_key(p);
  }
}


function create_button(label,fn) {
  var button=document.createElement("A");
  var button_t=document.createTextNode("["+label+"] ");
  button.appendChild(button_t);
  button.onclick=fn;
  return button;
}

function create_img_button(imgfn,label,fn) {
  var button=document.createElement("A");
  var button_img=document.createElement("IMG");
  var class_attr=document.createAttribute("CLASS");
  class_attr.value="button";
  button_img.setAttributeNode(class_attr);
  var src_attr=document.createAttribute("SRC");
  src_attr.value=imgfn;
  button_img.setAttributeNode(src_attr);
  var alt_attr=document.createAttribute("ALT");
  alt_attr.value="["+label+"] ";
  button_img.setAttributeNode(alt_attr);
  var title_attr=document.createAttribute("TITLE");
  title_attr.value=label;
  button_img.setAttributeNode(title_attr);
  button.appendChild(button_img);
  button.onclick=fn;
  return button;
}

function create_term(elem_id,title,rows,cols,p,charset,scrollback) {
  if (open) {
    alert("Terminal is already open");
    return;
  }
  title=substitute_variables(title);
  frame=document.getElementById(elem_id);
  if (!frame) {
    alert("There is no element named '"+elem_id+"' in which to build a terminal");
    return;
  }
  frame.className="termframe";
  var title_p=document.createElement("P");
  title_p.appendChild(create_img_button("copy.gif","Copy",copy_to_clipboard));
  title_p.appendChild(create_img_button("paste.gif","Paste",paste_from_clipboard));
  title_p.appendChild(create_ctrlkey_menu());
  var title_t=document.createTextNode(" "+title+" ");
  title_p.appendChild(title_t);
//  title_p.appendChild(create_button("close",close_term));
  frame.appendChild(title_p);
  term=document.createElement("PRE");
  frame.appendChild(term);
  term.className="term a p";
  var termbody=document.createTextNode("");
  term.appendChild(termbody);
  visible_height_frac=Number(rows)/(Number(rows)+Number(scrollback));
  if (scrollback>0) {
    term.style.overflowY="scroll";
  }
  document.onhelp = function() { return false; };
  document.onkeypress=keypress;
  document.onkeydown=keydown;
  open_term(rows,cols,p,charset,scrollback);
  if (open) {
    window.onbeforeunload=warn_unload;
    get();
    maybe_send();
  }
}


function warn_unload() {
  if (open) {
    return "Leaving this page will close the terminal.";
  }
}


function create_ctrlkey_menu() {
  var sel=document.createElement("SELECT");
  create_ctrlkey_menu_entry(sel,"Control keys...",-1);
  create_ctrlkey_menu_entry(sel,"Ctrl-@",0);
  for (var code=1; code<27; code++) {
    var letter=String.fromCharCode(64+code);
    create_ctrlkey_menu_entry(sel,"Ctrl-"+letter,code);
  }
  create_ctrlkey_menu_entry(sel,"Ctrl-[",27);
  create_ctrlkey_menu_entry(sel,"Ctrl-\\",28);
  create_ctrlkey_menu_entry(sel,"Ctrl-]",29);
  create_ctrlkey_menu_entry(sel,"Ctrl-^",30);
  create_ctrlkey_menu_entry(sel,"Ctrl-_",31);
  sel.onchange=function() {
    var code = sel.options[sel.selectedIndex].value;
    if (code>=0) {
      process_key(String.fromCharCode(code));
    }
  };
  return sel;
}

function create_ctrlkey_menu_entry(sel,name,code) {
  var opt=document.createElement("OPTION");
  opt.appendChild(document.createTextNode(name));
  var value_attr=document.createAttribute("VALUE");
  value_attr.value=code;
  opt.setAttributeNode(value_attr);
  sel.appendChild(opt);
}

