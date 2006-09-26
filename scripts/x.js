/* x.js compiled from X 4.0 with XC 0.27b. Distributed by GNU LGPL. For copyrights, license, documentation and more visit Cross-Browser.com */
// globals, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

var xOp7Up,xOp6Dn,xIE4Up,xIE4,xIE5,xNN4,xUA=navigator.userAgent.toLowerCase();
if(window.opera){
  var i=xUA.indexOf('opera');
  if(i!=-1){
    var v=parseInt(xUA.charAt(i+6));
    xOp7Up=v>=7;
    xOp6Dn=v<7;
  }
}
else if(navigator.vendor!='KDE' && document.all && xUA.indexOf('msie')!=-1){
  xIE4Up=parseFloat(navigator.appVersion)>=4;
  xIE4=xUA.indexOf('msie 4')!=-1;
  xIE5=xUA.indexOf('msie 5')!=-1;
}
else if(document.layers){xNN4=true;}
xMac=xUA.indexOf('mac')!=-1;
// xAddEventListener, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL
                                                      
function xAddEventListener(e,eT,eL,cap)
{
  if(!(e=xGetElementById(e))) return;
  eT=eT.toLowerCase();
  if((!xIE4Up && !xOp7Up) && e==window) {
    if(eT=='resize') { window.xPCW=xClientWidth(); window.xPCH=xClientHeight(); window.xREL=eL; xResizeEvent(); return; }
    if(eT=='scroll') { window.xPSL=xScrollLeft(); window.xPST=xScrollTop(); window.xSEL=eL; xScrollEvent(); return; }
  }
  var eh='e.on'+eT+'=eL';
  if(e.addEventListener) e.addEventListener(eT,eL,cap);
  else if(e.attachEvent) e.attachEvent('on'+eT,eL);
  else eval(eh);
}
// called only from the above
function xResizeEvent()
{
  if (window.xREL) setTimeout('xResizeEvent()', 250);
  var cw = xClientWidth(), ch = xClientHeight();
  if (window.xPCW != cw || window.xPCH != ch) { window.xPCW = cw; window.xPCH = ch; if (window.xREL) window.xREL(); }
}
function xScrollEvent()
{
  if (window.xSEL) setTimeout('xScrollEvent()', 250);
  var sl = xScrollLeft(), st = xScrollTop();
  if (window.xPSL != sl || window.xPST != st) { window.xPSL = sl; window.xPST = st; if (window.xSEL) window.xSEL(); }
}
// xAppendChild, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xAppendChild(oParent, oChild)
{
  if (oParent.appendChild) return oParent.appendChild(oChild);
  else return null;
}
// xBackground, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xBackground(e,c,i)
{
  if(!(e=xGetElementById(e))) return '';
  var bg='';
  if(e.style) {
    if(xStr(c)) {
      if(!xOp6Dn) e.style.backgroundColor=c;
      else e.style.background=c;
    }
    if(xStr(i)) e.style.backgroundImage=(i!='')? 'url('+i+')' : null;
    if(!xOp6Dn) bg=e.style.backgroundColor;
    else bg=e.style.background;
  }
  return bg;
}
// xBar, Copyright 2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// Bar-Graph Object

function xBar(dir,                // direction, 'ltr', 'rtl', 'ttb', or 'btt'
              conStyle, barStyle) // container and bar style class names
{
  //// Public Properties

  this.value = 0; // current value, read-only

  //// Public Methods

  // Update current value
  this.update = function(v)
  {
    if (v < 0) v = 0;
    else if (v > this.inMax) v = this.inMax;
    this.con.title = this.bar.title = this.value = v;
    switch(this.dir) {
      case 'ltr': // left to right
      v = this.scale(v, this.w);
      xLeft(this.bar, v - this.w);
      break;
      case 'rtl': // right to left
      v = this.scale(v, this.w);
      xLeft(this.bar, this.w - v);
      break;
      case 'btt': // bottom to top
      v = this.scale(v, this.h);
      xTop(this.bar, this.h - v);
      break;
      case 'ttb': // top to bottom
      v = this.scale(v, this.h);
      xTop(this.bar, v - this.h);
      break;
    }
  };

  // Change position and/or size
  this.paint = function(x, y, // container position
                        w, h) // container size
  {
    if (xNum(x)) this.x = x;
    if (xNum(y)) this.y = y;
    if (xNum(w)) this.w = w;
    if (xNum(h)) this.h = h;
    xResizeTo(this.con, this.w, this.h);
    xMoveTo(this.con, this.x, this.y);
    xResizeTo(this.bar, this.w, this.h);
    xMoveTo(this.bar, 0, 0);
  };

  // Change scale and/or start value
  this.reset = function(max, start) // non-scaled values
  {
    if (xNum(max)) this.inMax = max;
    if (xNum(start)) this.start = start;
    this.update(this.start);
  };

  //// Private Methods
  
  this.scale = function(v, outMax)
  {
    return Math.round(xLinearScale(v, 0, this.inMax, 0, outMax));
  };

  //// Private Properties

  this.dir = dir;
  this.x = 0;
  this.y = 0;
  this.w = 100;
  this.h = 100;
  this.inMax = 100;
  this.start = 0;
  this.conStyle = conStyle;
  this.barStyle = barStyle;

  //// Constructor

  // Create container
  this.con = document.createElement('DIV');
  this.con.className = this.conStyle;
  // Create bar
  this.bar = document.createElement('DIV');
  this.bar.className = this.barStyle;
  // Insert in object tree
  this.con.appendChild(this.bar);
  document.body.appendChild(this.con);

} // end xBar
// xCapitalize, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// Capitalize the first letter of every word in str.

function xCapitalize(str)
{
  var i, c, wd, s='', cap = true;
  
  for (i = 0; i < str.length; ++i) {
    c = str.charAt(i);
    wd = isWordDelim(c);
    if (wd) {
      cap = true;
    }  
    if (cap && !wd) {
      c = c.toUpperCase();
      cap = false;
    }
    s += c;
  }
  return s;

  function isWordDelim(c)
  {
    // add other word delimiters as needed
    // (for example '-' and other punctuation)
    return c == ' ' || c == '\n' || c == '\t';
  }
}
// xCardinalPosition, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xCardinalPosition(e, cp, margin, outside)
{
  if(!(e=xGetElementById(e))) return;
  if (typeof(cp)!='string'){window.status='xCardinalPosition error: cp=' + cp + ', id=' + e.id; return;}
  var x=xLeft(e), y=xTop(e), w=xWidth(e), h=xHeight(e);
  var pw,ph,p = xParent(e);
  if (p == document || p.nodeName.toLowerCase() == 'html') {pw = xClientWidth(); ph = xClientHeight();}
  else {pw=xWidth(p); ph=xHeight(p);}
  var sx=xScrollLeft(p), sy=xScrollTop(p);
  var right=sx + pw, bottom=sy + ph;
  var cenLeft=sx + Math.floor((pw-w)/2), cenTop=sy + Math.floor((ph-h)/2);
  if (!margin) margin=0;
  else{
    if (outside) margin=-margin;
    sx +=margin; sy +=margin; right -=margin; bottom -=margin;
  }
  switch (cp.toLowerCase()){
    case 'n': x=cenLeft; if (outside) y=sy - h; else y=sy; break;
    case 'ne': if (outside){x=right; y=sy - h;}else{x=right - w; y=sy;}break;
    case 'e': y=cenTop; if (outside) x=right; else x=right - w; break;
    case 'se': if (outside){x=right; y=bottom;}else{x=right - w; y=bottom - h}break;
    case 's': x=cenLeft; if (outside) y=sy - h; else y=bottom - h; break;
    case 'sw': if (outside){x=sx - w; y=bottom;}else{x=sx; y=bottom - h;}break;
    case 'w': y=cenTop; if (outside) x=sx - w; else x=sx; break;
    case 'nw': if (outside){x=sx - w; y=sy - h;}else{x=sx; y=sy;}break;
    case 'cen': x=cenLeft; y=cenTop; break;
    case 'cenh': x=cenLeft; break;
    case 'cenv': y=cenTop; break;
  }
  var o = new Object();
  o.x = x; o.y = y;
  return o;
}
// xClientHeight, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xClientHeight()
{
  var h=0;
  if(xOp6Dn) h=window.innerHeight;
  else if(document.compatMode == 'CSS1Compat' && !window.opera && document.documentElement && document.documentElement.clientHeight)
    h=document.documentElement.clientHeight;
  else if(document.body && document.body.clientHeight)
    h=document.body.clientHeight;
  else if(xDef(window.innerWidth,window.innerHeight,document.width)) {
    h=window.innerHeight;
    if(document.width>window.innerWidth) h-=16;
  }
  return h;
}
// xClientWidth, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xClientWidth()
{
  var w=0;
  if(xOp6Dn) w=window.innerWidth;
  else if(document.compatMode == 'CSS1Compat' && !window.opera && document.documentElement && document.documentElement.clientWidth)
    w=document.documentElement.clientWidth;
  else if(document.body && document.body.clientWidth)
    w=document.body.clientWidth;
  else if(xDef(window.innerWidth,window.innerHeight,document.height)) {
    w=window.innerWidth;
    if(document.height>window.innerHeight) w-=16;
  }
  return w;
}
// xClip, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xClip(e,t,r,b,l)
{
  if(!(e=xGetElementById(e))) return;
  if(e.style) {
    if (xNum(l)) e.style.clip='rect('+t+'px '+r+'px '+b+'px '+l+'px)';
    else e.style.clip='rect(0 '+parseInt(e.style.width)+'px '+parseInt(e.style.height)+'px 0)';
  }
}
// xCollapsible, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xCollapsible(outerEle, bShow) // object prototype
{
  // Constructor

  var container = xGetElementById(outerEle);
  if (!container) {return null;}
  var isUL = container.nodeName.toUpperCase() == 'UL';
  var i, trg, aTgt = xGetElementsByTagName(isUL ? 'UL':'DIV', container);
  for (i = 0; i < aTgt.length; ++i) {
    trg = xPrevSib(aTgt[i]);
    if (trg && (isUL || trg.nodeName.charAt(0).toUpperCase() == 'H')) {
      aTgt[i].xTrgPtr = trg;
      aTgt[i].style.display = bShow ? 'block' : 'none';
      trg.style.cursor = 'pointer';
      trg.xTgtPtr = aTgt[i];
      trg.onclick = trg_onClick;
    }  
  }
  
  // Private

  function trg_onClick()
  {
    var tgt = this.xTgtPtr.style;
    if (tgt.display == 'none') {
      tgt.display = 'block';
    }  
    else {
      tgt.display = 'none';
    }
  }

  // Public

  this.displayAll = function(bShow)
  {
    for (var i = 0; i < aTgt.length; ++i) {
      if (aTgt[i].xTrgPtr) {
        xDisplay(aTgt[i], bShow ? "block":"none");
      }
    }
  };

  // The unload listener is for IE's circular reference memory leak bug.
  this.onUnload = function()
  {
    if (!xIE4Up || !container || !aTgt) {return;}
    for (i = 0; i < aTgt.length; ++i) {
      trg = aTgt[i].xTrgPtr;
      if (trg) {
        if (trg.xTgtPtr) {
          trg.xTgtPtr.TrgPtr = null;
          trg.xTgtPtr = null;
        }
        trg.onclick = null;
      }
    }
  };
}
// xColor, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xColor(e,s)
{
  if(!(e=xGetElementById(e))) return '';
  var c='';
  if(e.style && xDef(e.style.color)) {
    if(xStr(s)) e.style.color=s;
    c=e.style.color;
  }
  return c;
}
// xCreateElement, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xCreateElement(sTag)
{
  if (document.createElement) return document.createElement(sTag);
  else return null;
}
// xDef, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDef()
{
  for(var i=0; i<arguments.length; ++i){if(typeof(arguments[i])=='undefined') return false;}
  return true;
}
// xDeg, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDeg(rad)
{
  return rad * (180 / Math.PI);
}
// xDeleteCookie, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDeleteCookie(name, path)
{
  if (xGetCookie(name)) {
    document.cookie = name + "=" +
                    "; path=" + ((!path) ? "/" : path) +
                    "; expires=" + new Date(0).toGMTString();
  }
}
// xDisableDrag, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDisableDrag(id, last)
{
  if (!window._xDrgMgr) return;
  var ele = xGetElementById(id);
  ele.xDraggable = false;
  ele.xODS = null;
  ele.xOD = null;
  ele.xODE = null;
  xRemoveEventListener(ele, 'mousedown', _xOMD, false);
  if (_xDrgMgr.mm && last) {
    _xDrgMgr.mm = false;
    xRemoveEventListener(document, 'mousemove', _xOMM, false);
  }
}
// xDisplay, Copyright 2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDisplay(e,s)
{
  if(!(e=xGetElementById(e))) return null;
  if(e.style && xDef(e.style.display)) {
    if (xStr(s)) e.style.display = s;
    return e.style.display;
  }
  return null;
}
// xEllipse, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xEllipse(e, xRadius, yRadius, radiusInc, totalTime, startAngle, stopAngle)
{
  if (!(e=xGetElementById(e))) return;
  if (!e.timeout) e.timeout = 25;
  e.xA = xRadius;
  e.yA = yRadius;
  e.radiusInc = radiusInc;
  e.slideTime = totalTime;
  startAngle *= (Math.PI / 180);
  stopAngle *= (Math.PI / 180);
  var startTime = (startAngle * e.slideTime) / (stopAngle - startAngle);
  e.stopTime = e.slideTime + startTime;
  e.B = (stopAngle - startAngle) / e.slideTime;
  e.xD = xLeft(e) - Math.round(e.xA * Math.cos(e.B * startTime)); // center point
  e.yD = xTop(e) - Math.round(e.yA * Math.sin(e.B * startTime)); 
  e.xTarget = Math.round(e.xA * Math.cos(e.B * e.stopTime) + e.xD); // end point
  e.yTarget = Math.round(e.yA * Math.sin(e.B * e.stopTime) + e.yD); 
  var d = new Date();
  e.C = d.getTime() - startTime;
  if (!e.moving) {e.stop=false; _xEllipse(e);}
}
function _xEllipse(e)
{
  if (!(e=xGetElementById(e))) return;
  var now, t, newY, newX;
  now = new Date();
  t = now.getTime() - e.C;
  if (e.stop) { e.moving = false; }
  else if (t < e.stopTime) {
    setTimeout("_xEllipse('"+e.id+"')", e.timeout);
    if (e.radiusInc) {
      e.xA += e.radiusInc;
      e.yA += e.radiusInc;
    }
    newX = Math.round(e.xA * Math.cos(e.B * t) + e.xD);
    newY = Math.round(e.yA * Math.sin(e.B * t) + e.yD);
    xMoveTo(e, newX, newY);
    e.moving = true;
  }  
  else {
    if (e.radiusInc) {
      e.xTarget = Math.round(e.xA * Math.cos(e.B * e.slideTime) + e.xD);
      e.yTarget = Math.round(e.yA * Math.sin(e.B * e.slideTime) + e.yD); 
    }
    xMoveTo(e, e.xTarget, e.yTarget);
    e.moving = false;
  }  
}
// xEnableDrag, Copyright 2002,2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

//// Private Data
var _xDrgMgr = {ele:null, mm:false};
//// Public Functions
function xEnableDrag(id,fS,fD,fE)
{
  var ele = xGetElementById(id);
  ele.xDraggable = true;
  ele.xODS = fS;
  ele.xOD = fD;
  ele.xODE = fE;
  xAddEventListener(ele, 'mousedown', _xOMD, false);
  if (!_xDrgMgr.mm) {
    _xDrgMgr.mm = true;
    xAddEventListener(document, 'mousemove', _xOMM, false);
  }
}
//// Private Event Listeners
function _xOMD(e) // drag start
{
  var evt = new xEvent(e);
  var ele = evt.target;
  while(ele && !ele.xDraggable) {
    ele = xParent(ele);
  }
  if (ele) {
    xPreventDefault(e);
    ele.xDPX = evt.pageX;
    ele.xDPY = evt.pageY;
    _xDrgMgr.ele = ele;
    xAddEventListener(document, 'mouseup', _xOMU, false);
    if (ele.xODS) {
      ele.xODS(ele, evt.pageX, evt.pageY);
    }
  }
}
function _xOMM(e) // drag
{
  var evt = new xEvent(e);
  if (_xDrgMgr.ele) {
    xPreventDefault(e);
    var ele = _xDrgMgr.ele;
    var dx = evt.pageX - ele.xDPX;
    var dy = evt.pageY - ele.xDPY;
    ele.xDPX = evt.pageX;
    ele.xDPY = evt.pageY;
    if (ele.xOD) {
      ele.xOD(ele, dx, dy);
    }
    else {
      xMoveTo(ele, xLeft(ele) + dx, xTop(ele) + dy);
    }
  }  
}
function _xOMU(e) // drag end
{
  if (_xDrgMgr.ele) {
    xPreventDefault(e);
    xRemoveEventListener(document, 'mouseup', _xOMU, false);
    if (_xDrgMgr.ele.xODE) {
      var evt = new xEvent(e);
      _xDrgMgr.ele.xODE(_xDrgMgr.ele, evt.pageX, evt.pageY);
    }
    _xDrgMgr.ele = null;
  }  
}
// xEvalTextarea, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xEvalTextarea()
{
  var f = document.createElement('FORM');
  f.onsubmit = 'return false';
  var t = document.createElement('TEXTAREA');
  t.id='xDebugTA';
  t.name='xDebugTA';
  t.rows='20';
  t.cols='60';
  var b = document.createElement('INPUT');
  b.type = 'button';
  b.value = 'Evaluate';
  b.onclick = function() {eval(this.form.xDebugTA.value);};
  f.appendChild(t);
  f.appendChild(b);
  document.body.appendChild(f);
}
// xEvent, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xEvent(evt) // object prototype
{
  var e = evt || window.event;
  if(!e) return;
  if(e.type) this.type = e.type;
  if(e.target) this.target = e.target;
  else if(e.srcElement) this.target = e.srcElement;

  // Section B
  if (e.relatedTarget) this.relatedTarget = e.relatedTarget;
  else if (e.type == 'mouseover' && e.fromElement) this.relatedTarget = e.fromElement;
  else if (e.type == 'mouseout') this.relatedTarget = e.toElement;
  // End Section B

  if(xOp6Dn) { this.pageX = e.clientX; this.pageY = e.clientY; }
  else if(xDef(e.pageX,e.pageY)) { this.pageX = e.pageX; this.pageY = e.pageY; }
  else if(xDef(e.clientX,e.clientY)) { this.pageX = e.clientX + xScrollLeft(); this.pageY = e.clientY + xScrollTop(); }

  // Section A
  if (xDef(e.offsetX,e.offsetY)) {
    this.offsetX = e.offsetX;
    this.offsetY = e.offsetY;
  }
  else if (xDef(e.layerX,e.layerY)) {
    this.offsetX = e.layerX;
    this.offsetY = e.layerY;
  }
  else {
    this.offsetX = this.pageX - xPageX(this.target);
    this.offsetY = this.pageY - xPageY(this.target);
  }
  // End Section A
  
  if (e.keyCode) { this.keyCode = e.keyCode; } // for moz/fb, if keyCode==0 use which
  else if (xDef(e.which) && e.type.indexOf('key')!=-1) { this.keyCode = e.which; }

  this.shiftKey = e.shiftKey;
  this.ctrlKey = e.ctrlKey;
  this.altKey = e.altKey;
}

//  I need someone with IE/Mac to compare test snippets 1 and 2 in section A.
  
//  // Snippet 1
//  if(xDef(e.offsetX,e.offsetY)) {
//    this.offsetX = e.offsetX;
//    this.offsetY = e.offsetY;
//    if (xIE4Up && xMac) {
//      this.offsetX += xScrollLeft();
//      this.offsetY += xScrollTop();
//    }
//  }
//  else if (xDef(e.layerX,e.layerY)) {
//    this.offsetX = e.layerX;
//    this.offsetY = e.layerY;
//  }
//  else {
//    this.offsetX = this.pageX - xPageX(this.target);
//    this.offsetY = this.pageY - xPageY(this.target);
//  }

//  // Snippet 2
//  if (xDef(e.offsetX,e.offsetY) && !(xIE4Up && xMac)) {
//    this.offsetX = e.offsetX;
//    this.offsetY = e.offsetY;
//  }
//  else if (xDef(e.layerX,e.layerY)) {
//    this.offsetX = e.layerX;
//    this.offsetY = e.layerY;
//  }
//  else {
//    this.offsetX = this.pageX - xPageX(this.target);
//    this.offsetY = this.pageY - xPageY(this.target);
//  }

//  This was in section B:

//  if (e.relatedTarget) this.relatedTarget = e.relatedTarget;
//  else if (xIE4Up) {
//    if (e.type == 'mouseover') this.relatedTarget = e.fromElement;
//    else if (e.type == 'mouseout') this.relatedTarget = e.toElement;
//  }
//  changed to remove sniffer after discussion with Hallvord

// Possible optimization:

//  if (e.keyCode) { this.keyCode = e.keyCode; } // for moz/fb, if keyCode==0 use which
//  else if (xDef(e.which) && e.type.indexOf('key')!=-1) { this.keyCode = e.which; }
//  // replace the above 2 lines with the following?
//  // this.keyCode = e.keyCode || e.which || 0;
// xFenster, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xFenster(eleId, iniX, iniY, barId, resBtnId, maxBtnId) // object prototype
{
  // Private Properties
  var me = this;
  var ele = xGetElementById(eleId);
  var rBtn = xGetElementById(resBtnId);
  var mBtn = xGetElementById(maxBtnId);
  var x, y, w, h, maximized = false;
  // Public Methods
  this.onunload = function()
  {
    if (xIE4Up) { // clear cir refs
      xDisableDrag(barId);
      xDisableDrag(rBtn);
      mBtn.onclick = ele.onmousedown = null;
      me = ele = rBtn = mBtn = null;
    }
  }
  this.paint = function()
  {
    xMoveTo(rBtn, xWidth(ele) - xWidth(rBtn), xHeight(ele) - xHeight(rBtn));
    xMoveTo(mBtn, xWidth(ele) - xWidth(rBtn), 0);
  }
  // Private Event Listeners
  function barOnDrag(e, mdx, mdy)
  {
    xMoveTo(ele, xLeft(ele) + mdx, xTop(ele) + mdy);
  }
  function resOnDrag(e, mdx, mdy)
  {
    xResizeTo(ele, xWidth(ele) + mdx, xHeight(ele) + mdy);
    me.paint();
  }
  function fenOnMousedown()
  {
    xZIndex(ele, xFenster.z++);
  }
  function maxOnClick()
  {
    if (maximized) {
      maximized = false;
      xResizeTo(ele, w, h);
      xMoveTo(ele, x, y);
    }
    else {
      w = xWidth(ele);
      h = xHeight(ele);
      x = xLeft(ele);
      y = xTop(ele);
      xMoveTo(ele, xScrollLeft(), xScrollTop());
      maximized = true;
      xResizeTo(ele, xClientWidth(), xClientHeight());
    }
    me.paint();
  }
  // Constructor Code
  xFenster.z++;
  xMoveTo(ele, iniX, iniY);
  this.paint();
  xEnableDrag(barId, null, barOnDrag, null);
  xEnableDrag(rBtn, null, resOnDrag, null);
  mBtn.onclick = maxOnClick;
  ele.onmousedown = fenOnMousedown;
  xShow(ele);
} // end xFenster object prototype

xFenster.z = 0; // xFenster static property
// xFirstChild, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xFirstChild(e, t)
{
  var c = e ? e.firstChild : null;
  if (t) while (c && c.nodeName != t) { c = c.nextSibling; }
  else while (c && c.nodeType != 1) { c = c.nextSibling; }
  return c;
}
// xGetComputedStyle, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetComputedStyle(oEle, sProp, bInt)
{
  var s, p = 'undefined';
  var dv = document.defaultView;
  if(dv && dv.getComputedStyle){
    s = dv.getComputedStyle(oEle,'');
    if (s) p = s.getPropertyValue(sProp);
  }
  else if(oEle.currentStyle) {
    // convert css property name to object property name for IE
    var a = sProp.split('-');
    sProp = a[0];
    for (var i=1; i<a.length; ++i) {
      c = a[i].charAt(0);
      sProp += a[i].replace(c, c.toUpperCase());
    }   
    p = oEle.currentStyle[sProp];
  }
  else return null;
  return bInt ? (parseInt(p) || 0) : p;
}

// xGetCookie, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetCookie(name)
{
  var value=null, search=name+"=";
  if (document.cookie.length > 0) {
    var offset = document.cookie.indexOf(search);
    if (offset != -1) {
      offset += search.length;
      var end = document.cookie.indexOf(";", offset);
      if (end == -1) end = document.cookie.length;
      value = unescape(document.cookie.substring(offset, end));
    }
  }
  return value;
}
// xGetElementById, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElementById(e)
{
  if(typeof(e)!='string') return e;
  if(document.getElementById) e=document.getElementById(e);
  else if(document.all) e=document.all[e];
  else e=null;
  return e;
}
// xGetElementsByAttribute, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElementsByAttribute(sTag, sAtt, sRE, fn)
{
  var a, list, found = new Array(), re = new RegExp(sRE, 'i');
  list = xGetElementsByTagName(sTag);
  for (var i = 0; i < list.length; ++i) {
    a = list[i].getAttribute(sAtt);
    if (!a) {a = list[i][sAtt];}
    if (typeof(a)=='string' && a.search(re) != -1) {
      found[found.length] = list[i];
      if (fn) fn(list[i]);
    }
  }
  return found;
}
// xGetElementsByClassName, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElementsByClassName(c,p,t,f)
{
  var found = new Array();
  var re = new RegExp('\\b'+c+'\\b', 'i');
  var list = xGetElementsByTagName(t, p);
  for (var i = 0; i < list.length; ++i) {
    if (list[i].className && list[i].className.search(re) != -1) {
      found[found.length] = list[i];
      if (f) f(list[i]);
    }
  }
  return found;
}
// xGetElementsByTagName, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElementsByTagName(t,p)
{
  var list = null;
  t = t || '*';
  p = p || document;
  if (xIE4 || xIE5) {
    if (t == '*') list = p.all;
    else list = p.all.tags(t);
  }
  else if (p.getElementsByTagName) list = p.getElementsByTagName(t);
  return list || new Array();
}
// xGetElePropsArray, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElePropsArray(ele, eleName)
{
  var u = 'undefined';
  var i = 0, a = new Array();
  
  nv('Element', eleName);
  nv('id', (xDef(ele.id) ? ele.id : u));
  nv('tagName', (xDef(ele.tagName) ? ele.tagName : u));

  nv('xWidth()', xWidth(ele));
  nv('style.width', (xDef(ele.style) && xDef(ele.style.width) ? ele.style.width : u));
  nv('offsetWidth', (xDef(ele.offsetWidth) ? ele.offsetWidth : u));
  nv('scrollWidth', (xDef(ele.offsetWidth) ? ele.offsetWidth : u));
  nv('clientWidth', (xDef(ele.clientWidth) ? ele.clientWidth : u));

  nv('xHeight()', xHeight(ele));
  nv('style.height', (xDef(ele.style) && xDef(ele.style.height) ? ele.style.height : u));
  nv('offsetHeight', (xDef(ele.offsetHeight) ? ele.offsetHeight : u));
  nv('scrollHeight', (xDef(ele.offsetHeight) ? ele.offsetHeight : u));
  nv('clientHeight', (xDef(ele.clientHeight) ? ele.clientHeight : u));

  nv('xLeft()', xLeft(ele));
  nv('style.left', (xDef(ele.style) && xDef(ele.style.left) ? ele.style.left : u));
  nv('offsetLeft', (xDef(ele.offsetLeft) ? ele.offsetLeft : u));
  nv('style.pixelLeft', (xDef(ele.style) && xDef(ele.style.pixelLeft) ? ele.style.pixelLeft : u));

  nv('xTop()', xTop(ele));
  nv('style.top', (xDef(ele.style) && xDef(ele.style.top) ? ele.style.top : u));
  nv('offsetTop', (xDef(ele.offsetTop) ? ele.offsetTop : u));
  nv('style.pixelTop', (xDef(ele.style) && xDef(ele.style.pixelTop) ? ele.style.pixelTop : u));

  nv('', '');
  nv('xGetComputedStyle()', '');

  nv('top');
  nv('right');
  nv('bottom');
  nv('left');

  nv('width');
  nv('height');

  nv('color');
  nv('background-color');
  nv('font-family');
  nv('font-size');
  nv('text-align');
  nv('line-height');
  nv('content');
  
  nv('float');
  nv('clear');

  nv('margin');
  nv('padding');
  nv('padding-top');
  nv('padding-right');
  nv('padding-bottom');
  nv('padding-left');

  nv('border-top-width');
  nv('border-right-width');
  nv('border-bottom-width');
  nv('border-left-width');

  nv('position');
  nv('overflow');
  nv('visibility');
  nv('display');
  nv('z-index');
  nv('clip');
  nv('cursor');

  return a;

  function nv(name, value)
  {
    a[i] = new Object();
    a[i].name = name;
    a[i].value = typeof(value)=='undefined' ? xGetComputedStyle(ele, name) : value;
    ++i;
  }
}
// xGetElePropsString, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElePropsString(ele, eleName, newLine)
{
  var s = '', a = xGetElePropsArray(ele, eleName);
  for (var i = 0; i < a.length; ++i) {
    s += a[i].name + ' = ' + a[i].value + (newLine || '\n');
  }
  return s;
}
// xGetURLArguments, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetURLArguments()
{
  var idx = location.href.indexOf('?');
  var params = new Array();
  if (idx != -1) {
    var pairs = location.href.substring(idx+1, location.href.length).split('&');
    for (var i=0; i<pairs.length; i++) {
      nameVal = pairs[i].split('=');
      params[i] = nameVal[1];
      params[nameVal[0]] = nameVal[1];
    }
  }
  return params;
}
// xHasPoint, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xHasPoint(e,x,y,t,r,b,l)
{
  if (!xNum(t)){t=r=b=l=0;}
  else if (!xNum(r)){r=b=l=t;}
  else if (!xNum(b)){l=r; b=t;}
  var eX = xPageX(e), eY = xPageY(e);
  return (x >= eX + l && x <= eX + xWidth(e) - r &&
          y >= eY + t && y <= eY + xHeight(e) - b );
}
// xHeight, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xHeight(e,h)
{
  if(!(e=xGetElementById(e))) return 0;
  if (xNum(h)) {
    if (h<0) h = 0;
    else h=Math.round(h);
  }
  else h=-1;
  var css=xDef(e.style);
  if (e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    h = xClientHeight();
  }
  else if(css && xDef(e.offsetHeight) && xStr(e.style.height)) {
    if(h>=0) {
      var pt=0,pb=0,bt=0,bb=0;
      if (document.compatMode=='CSS1Compat') {
        var gcs = xGetComputedStyle;
        pt=gcs(e,'padding-top',1);
        if (pt !== null) {
          pb=gcs(e,'padding-bottom',1);
          bt=gcs(e,'border-top-width',1);
          bb=gcs(e,'border-bottom-width',1);
        }
        // Should we try this as a last resort?
        // At this point getComputedStyle and currentStyle do not exist.
        else if(xDef(e.offsetHeight,e.style.height)){
          e.style.height=h+'px';
          pt=e.offsetHeight-h;
        }
      }
      h-=(pt+pb+bt+bb);
      if(isNaN(h)||h<0) return;
      else e.style.height=h+'px';
    }
    h=e.offsetHeight;
  }
  else if(css && xDef(e.style.pixelHeight)) {
    if(h>=0) e.style.pixelHeight=h;
    h=e.style.pixelHeight;
  }
  return h;
}
// xHex, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xHex(n, digits, prefix)
{
  var p = '', n = Math.ceil(n);
  if (prefix) p = prefix;
  n = n.toString(16);
  for (var i=0; i < digits - n.length; ++i) {
    p += '0';
  }
  return p + n;
}
// xHide, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xHide(e){return xVisibility(e,0);}
// xImgAsyncWait, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xImgAsyncWait(fnStatus, fnInit, fnError, sErrorImg, sAbortImg, imgArray)
{
  var i, imgs = imgArray || document.images;
  
  for (i = 0; i < imgs.length; ++i) {
    imgs[i].onload = imgOnLoad;
    imgs[i].onerror = imgOnError;
    imgs[i].onabort = imgOnAbort;
  }
  
  xIAW.fnStatus = fnStatus;
  xIAW.fnInit = fnInit;
  xIAW.fnError = fnError;
  xIAW.imgArray = imgArray;

  xIAW();

  function imgOnLoad()
  {
    this.wasLoaded = true;
  }
  function imgOnError()
  {
    if (sErrorImg && !this.wasError) {
      this.src = sErrorImg;
    }
    this.wasError = true;
  }
  function imgOnAbort()
  {
    if (sAbortImg && !this.wasAborted) {
      this.src = sAbortImg;
    }
    this.wasAborted = true;
  }
}
// end xImgAsyncWait()

// Don't call xIAW() directly. It is only called from xImgAsyncWait().

function xIAW()
{
  var me = arguments.callee;
  if (!me) {
    return; // I could have used a global object instead of callee
  }
  var i, imgs = me.imgArray ? me.imgArray : document.images;
  var c = 0, e = 0, a = 0, n = imgs.length;
  for (i = 0; i < n; ++i) {
    if (imgs[i].wasError) {
      ++e;
    }
    else if (imgs[i].wasAborted) {
      ++a;
    }
    else if (imgs[i].complete || imgs[i].wasLoaded) {
      ++c;
    }
  }
  if (me.fnStatus) {
    me.fnStatus(n, c, e, a);
  }
  if (c + e + a == n) {
    if ((e || a) && me.fnError) {
      me.fnError(n, c, e, a);
    }
    else if (me.fnInit) {
      me.fnInit();
    }
  }
  else setTimeout('xIAW()', 250);
}
// end xIAW()
// xImgRollSetup, Copyright 2002,2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xImgRollSetup(p,s,x)
{
  var ele, id;
  for (var i=3; i<arguments.length; ++i) {
    id = arguments[i];
    if (ele = xGetElementById(id)) {
      ele.xIOU = p + id + x;
      ele.xIOO = new Image();
      ele.xIOO.src = p + id + s + x;
      ele.onmouseout = imgOnMouseout;
      ele.onmouseover = imgOnMouseover;
    }
  }
  function imgOnMouseout(e)
  {
    if (this.xIOU) {
      this.src = this.xIOU;
    }
  }
  function imgOnMouseover(e)
  {
    if (this.xIOO && this.xIOO.complete) {
      this.src = this.xIOO.src;
    }
  }
}
// xInclude, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xInclude(url1, url2, etc)
{
  if (document.getElementById || document.all || document.layers) { // minimum dhtml support required
    var h, f, i, j, a, n, inc;

    for (var i=0; i<arguments.length; ++i) { // loop thru all the url arguments

      h = ''; // html (script or link element) to be written into the document
      f = arguments[i].toLowerCase(); // f is current url in lowercase
      inc = false; // if true the file has already been included

      // Extract the filename from the url

      // Should I extract the file name? What if there are two files with the same name 
      // but in different directories? If I don't extract it what about: '../foo.js' and '../../foo.js' ?

      a = f.split('/');
      n = a[a.length-1]; // the file name

      // loop thru the list to see if this file has already been included
      for (j = 0; j < xIncludeList.length; ++j) {
        if (n == xIncludeList[j]) { // should I use '==' or a string cmp func?
          inc = true;
          break;
        }
      }

      if (!inc) { // if the file has not yet been included

        xIncludeList[xIncludeList.length] = n; // add it to the list of included files

        // is it a .js file?
        if (f.indexOf('.js') != -1) {
          if (xNN4) { // if nn4 use nn4 versions of certain lib files
            var c='x_core', e='x_event', d='x_dom', n='_n4';
            if (f.indexOf(c) != -1) { f = f.replace(c, c+n); }
            else if (f.indexOf(e) != -1) { f = f.replace(e, e+n); }
            else if (f.indexOf(d) != -1) { f = f.replace(d, d+n); }
          }
          h = "<script type='text/javascript' src='" + f + "'></script>";
        }

        // else is it a .css file?
        else if (f.indexOf('.css') != -1) { // CSS file
          h = "<link rel='stylesheet' type='text/css' href='" + f + "'>";
        }    
        
        // write the link or script element into the document
        if (h.length) { document.writeln(h); }

      } // end if (!inc)
    } // end outer for
    return true;
  } // end if (min dhtml support)
  return false;
}
// xInnerHtml, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xInnerHtml(e,h)
{
  if(!(e=xGetElementById(e)) || !xStr(e.innerHTML)) return null;
  var s = e.innerHTML;
  if (xStr(h)) {e.innerHTML = h;}
  return s;
}
// xIntersection, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xIntersection(e1, e2, o)
{
  var ix1, iy2, iw, ih, intersect = true;
  var e1x1 = xPageX(e1);
  var e1x2 = e1x1 + xWidth(e1);
  var e1y1 = xPageY(e1);
  var e1y2 = e1y1 + xHeight(e1);
  var e2x1 = xPageX(e2);
  var e2x2 = e2x1 + xWidth(e2);
  var e2y1 = xPageY(e2);
  var e2y2 = e2y1 + xHeight(e2);
  // horizontal
  if (e1x1 <= e2x1) {
    ix1 = e2x1;
    if (e1x2 < e2x1) intersect = false;
    else iw = Math.min(e1x2, e2x2) - e2x1;
  }
  else {
    ix1 = e1x1;
    if (e2x2 < e1x1) intersect = false;
    else iw = Math.min(e1x2, e2x2) - e1x1;
  }
  // vertical
  if (e1y2 >= e2y2) {
    iy2 = e2y2;
    if (e1y1 > e2y2) intersect = false;
    else ih = e2y2 - Math.max(e1y1, e2y1);
  }
  else {
    iy2 = e1y2;
    if (e2y1 > e1y2) intersect = false;
    else ih = e1y2 - Math.max(e1y1, e2y1);
  }
  // intersected rectangle
  if (intersect && typeof(o)=='object') {
    o.x = ix1;
    o.y = iy2 - ih;
    o.w = iw;
    o.h = ih;
  }
  return intersect;
}
// xLeft, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xLeft(e, iX)
{
  if(!(e=xGetElementById(e))) return 0;
  var css=xDef(e.style);
  if (css && xStr(e.style.left)) {
    if(xNum(iX)) e.style.left=iX+'px';
    else {
      iX=parseInt(e.style.left);
      if(isNaN(iX)) iX=0;
    }
  }
  else if(css && xDef(e.style.pixelLeft)) {
    if(xNum(iX)) e.style.pixelLeft=iX;
    else iX=e.style.pixelLeft;
  }
  return iX;
}
// xLinearScale, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xLinearScale(val,iL,iH,oL,oH)
{
  var m=(oH-oL)/(iH-iL);
  var b=oL-(iL*m);
  return m*val+b;
}
// xLoadScript, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xLoadScript(url)
{
  if (document.createElement && document.getElementsByTagName) {
    var s = document.createElement('script');
    var h = document.getElementsByTagName('head');
    if (s && h.length) {
      s.src = url;
      h[0].appendChild(s);
    }
  }
}
// xMenu1, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xMenu1(triggerId, menuId, mouseMargin, openEvent)
{
  var isOpen = false;
  var trg = xGetElementById(triggerId);
  var mnu = xGetElementById(menuId);
  if (trg && mnu) {
    xAddEventListener(trg, openEvent, onOpen, false);
  }
  function onOpen()
  {
    if (!isOpen) {
      xMoveTo(mnu, xPageX(trg), xPageY(trg) + xHeight(trg));
      xShow(mnu);
      xAddEventListener(document, 'mousemove', onMousemove, false);
      isOpen = true;
    }
  }
  function onMousemove(ev)
  {
    var e = new xEvent(ev);
    if (!xHasPoint(mnu, e.pageX, e.pageY, -mouseMargin) &&
        !xHasPoint(trg, e.pageX, e.pageY, -mouseMargin))
    {
      xHide(mnu);
      xRemoveEventListener(document, 'mousemove', onMousemove, false);
      isOpen = false;
    }
  }
} // end xMenu1
// xMenu1A, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xMenu1A(triggerId, menuId, mouseMargin, slideTime, openEvent)
{
  var isOpen = false;
  var trg = xGetElementById(triggerId);
  var mnu = xGetElementById(menuId);
  if (trg && mnu) {
    xHide(mnu);
    xAddEventListener(trg, openEvent, onOpen, false);
  }
  function onOpen()
  {
    if (!isOpen) {
      xMoveTo(mnu, xPageX(trg), xPageY(trg));
      xShow(mnu);
      xSlideTo(mnu, xPageX(trg), xPageY(trg) + xHeight(trg), slideTime);
      xAddEventListener(document, 'mousemove', onMousemove, false);
      isOpen = true;
    }
  }
  function onMousemove(ev)
  {
    var e = new xEvent(ev);
    if (!xHasPoint(mnu, e.pageX, e.pageY, -mouseMargin) &&
        !xHasPoint(trg, e.pageX, e.pageY, -mouseMargin))
    {
      xRemoveEventListener(document, 'mousemove', onMousemove, false);
      xSlideTo(mnu, xPageX(trg), xPageY(trg), slideTime);
      setTimeout("xHide('" + menuId + "')", slideTime);
      isOpen = false;
    }
  }
} // end xMenu1A
// xMenu1B, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xMenu1B(openTriggerId, closeTriggerId, menuId, slideTime, bOnClick)
{
  xMenu1B.instances[xMenu1B.instances.length] = this;
  var isOpen = false;
  var oTrg = xGetElementById(openTriggerId);
  var cTrg = xGetElementById(closeTriggerId);
  var mnu = xGetElementById(menuId);
  if (oTrg && cTrg && mnu) {
    xHide(mnu);
    if (bOnClick) oTrg.onclick = openOnEvent;
    else oTrg.onmouseover = openOnEvent;
    cTrg.onclick = closeOnClick;
  }
  function openOnEvent()
  {
    if (!isOpen) {
      for (var i = 0; i < xMenu1B.instances.length; ++i) {
        xMenu1B.instances[i].close();
      }
      xMoveTo(mnu, xPageX(oTrg), xPageY(oTrg));
      xShow(mnu);
      xSlideTo(mnu, xPageX(oTrg), xPageY(oTrg) + xHeight(oTrg), slideTime);
      isOpen = true;
    }
  }
  function closeOnClick()
  {
    if (isOpen) {
      xSlideTo(mnu, xPageX(oTrg), xPageY(oTrg), slideTime);
      setTimeout("xHide('" + menuId + "')", slideTime);
      isOpen = false;
    }
  }
  this.close = function()
  {
    closeOnClick();
  }
} // end xMenu1B

xMenu1B.instances = new Array(); // static member of xMenu1B
// xMenu5, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xMenu5(idUL, btnClass, idAutoOpen) // object prototype
{
  // Constructor

  var i, ul, btns, mnu = xGetElementById(idUL);
  btns = xGetElementsByClassName(btnClass, mnu, 'DIV');
  for (i = 0; i < btns.length; ++i) {
    ul = xNextSib(btns[i], 'UL');
    btns[i].xClpsTgt = ul;
    btns[i].onclick = btn_onClick;
    set_display(btns[i], 0);
  }
  if (idAutoOpen) {
    var e = xGetElementById(idAutoOpen);
    while (e && e != mnu) {
      if (e.xClpsTgt) set_display(e, 1);
      while (e && e != mnu && e.nodeName != 'LI') e = e.parentNode;
      e = e.parentNode; // UL
      while (e && !e.xClpsTgt) e = xPrevSib(e);
    }
  }

  // Private
  
  function btn_onClick()
  {
    var thisLi, fc, pUl;
    if (this.xClpsTgt.style.display == 'none') {
      set_display(this, 1);
      // get this label's parent LI
      var li = this.parentNode;
      thisLi = li;
      pUl = li.parentNode; // get this LI's parent UL
      li = xFirstChild(pUl); // get the UL's first LI child
      // close all labels' ULs on this level except for thisLI's label
      while (li) {
        if (li != thisLi) {
          fc = xFirstChild(li);
          if (fc && fc.xClpsTgt) {
            set_display(fc, 0);
          }
        }
        li = xNextSib(li);
      }
    }  
    else {
      set_display(this, 0);
    }
  }

  function set_display(ele, bBlock)
  {
    if (bBlock) {
      ele.xClpsTgt.style.display = 'block';
      ele.innerHTML = '-';
    }
    else {
      ele.xClpsTgt.style.display = 'none';
      ele.innerHTML = '+';
    }
  }

  // Public

  this.onUnload = function()
  {
    for (i = 0; i < btns.length; ++i) {
      btns[i].xClpsTgt = null;
      btns[i].onclick = null;
    }
  }
} // end xMenu5 prototype


// xMoveTo, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xMoveTo(e,x,y)
{
  xLeft(e,x);
  xTop(e,y);
}
// xName, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xName(e)
{
  if (!e) return e;
  else if (e.id && e.id != "") return e.id;
  else if (e.name && e.name != "") return e.name;
  else if (e.nodeName && e.nodeName != "") return e.nodeName;
  else if (e.tagName && e.tagName != "") return e.tagName;
  else return e;
}
// xNextSib, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xNextSib(e,t)
{
  var s = e ? e.nextSibling : null;
  if (t) while (s && s.nodeName != t) { s = s.nextSibling; }
  else while (s && s.nodeType != 1) { s = s.nextSibling; }
  return s;
}
// xNum, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xNum()
{
  for(var i=0; i<arguments.length; ++i){if(isNaN(arguments[i]) || typeof(arguments[i])!='number') return false;}
  return true;
}
// xOffsetLeft, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xOffsetLeft(e)
{
  if (!(e=xGetElementById(e))) return 0;
  if (xDef(e.offsetLeft)) return e.offsetLeft;
  else return 0;
}
// xOffsetTop, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xOffsetTop(e)
{
  if (!(e=xGetElementById(e))) return 0;
  if (xDef(e.offsetTop)) return e.offsetTop;
  else return 0;
}
// xPad, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPad(s,len,c,left)
{
  if(typeof s != 'string') s=s+'';
  if(left) {for(var i=s.length; i<len; ++i) s=c+s;}
  else {for (var i=s.length; i<len; ++i) s+=c;}
  return s;
}
// xPageX, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPageX(e)
{
  if (!(e=xGetElementById(e))) return 0;
  var x = 0;
  while (e) {
    if (xDef(e.offsetLeft)) x += e.offsetLeft;
    e = xDef(e.offsetParent) ? e.offsetParent : null;
  }
  return x;
}
// xPageY, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPageY(e)
{
  if (!(e=xGetElementById(e))) return 0;
  var y = 0;
  while (e) {
    if (xDef(e.offsetTop)) y += e.offsetTop;
    e = xDef(e.offsetParent) ? e.offsetParent : null;
  }
//  if (xOp7Up) return y - document.body.offsetTop; // v3.14, temporary hack for opera bug 130324 (reported 1nov03)
  return y;
}
// xParaEq, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// Animation with Parametric Equations

function xParaEq(e, xExpr, yExpr, totalTime)
{
  if (!(e=xGetElementById(e))) return;
  e.t = 0;
  e.tStep = .008;
  if (!e.timeout) e.timeout = 25;
  e.xExpr = xExpr;
  e.yExpr = yExpr;
  e.slideTime = totalTime;
  var d = new Date();
  e.C = d.getTime();
  if (!e.moving) {e.stop=false; _xParaEq(e);}
}
function _xParaEq(e)
{
  if (!(e=xGetElementById(e))) return;
  var now = new Date();
  var et = now.getTime() - e.C;
  e.t += e.tStep;
  t = e.t;
  if (e.stop) { e.moving = false; }
  else if (!e.slideTime || et < e.slideTime) {
    setTimeout("_xParaEq('"+e.id+"')", e.timeout);
    var p = xParent(e), centerX, centerY;
    centerX = (xWidth(p)/2)-(xWidth(e)/2);
    centerY = (xHeight(p)/2)-(xHeight(e)/2);
    e.xTarget = Math.round((eval(e.xExpr) * centerX) + centerX) + xScrollLeft(p);
    e.yTarget = Math.round((eval(e.yExpr) * centerY) + centerY) + xScrollTop(p);
    xMoveTo(e, e.xTarget, e.yTarget);
    e.moving = true;
  }  
  else {
    e.moving = false;
  }  
}
// xParent, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xParent(e, bNode)
{
  if (!(e=xGetElementById(e))) return null;
  var p=null;
  if (!bNode && xDef(e.offsetParent)) p=e.offsetParent;
  else if (xDef(e.parentNode)) p=e.parentNode;
  else if (xDef(e.parentElement)) p=e.parentElement;
  return p;
}
// xParentChain, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xParentChain(e,delim,bNode)
{
  if (!(e=xGetElementById(e))) return;
  var lim=100, s = "", d = delim || "\n";
  while(e) {
    s += xName(e) + ', ofsL:'+e.offsetLeft + ', ofsT:'+e.offsetTop + d;
    e = xParent(e,bNode);
    if (!lim--) break;
  }
  return s;
}
// xPopup, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPopup(sTmrType, uTimeout, sPos1, sPos2, sPos3, sStyle, sId, sUrl)
{
  if (document.getElementById && document.createElement &&
      document.body && document.body.appendChild)
  { 
    // create popup element
    //var e = document.createElement('DIV');
    var e = document.createElement('IFRAME');
    this.ele = e;
    e.id = sId;
    e.style.position = 'absolute';
    e.className = sStyle;
    //e.innerHTML = sHtml;
    e.src = sUrl;
    document.body.appendChild(e);
    xShow(e);
    this.tmr = xTimer.set(sTmrType, this, sTmrType, uTimeout);
    // init
    this.open = false;
    this.margin = 10;
    this.pos1 = sPos1;
    this.pos2 = sPos2;
    this.pos3 = sPos3;
    this.slideTime = 500; // slide time in ms
    this.interval();
  } 
} // end xPopup
// methods
xPopup.prototype.show = function()
{
  this.interval();
};
xPopup.prototype.hide = function()
{
  this.timeout();
};
// timer event listeners
xPopup.prototype.timeout = function() // hide popup
{
  if (this.open) {
    var e = this.ele;
    var pos = xCardinalPosition(e, this.pos3, this.margin, true);
    xSlideTo(e, pos.x, pos.y, this.slideTime);
    setTimeout("xHide('" + e.id + "')", this.slideTime);
    this.open = false;
  }
};
xPopup.prototype.interval = function() // size, position and show popup
{
  if (!this.open) {
    var e = this.ele;
    var pos = xCardinalPosition(e, this.pos1, this.margin, true);
    xMoveTo(e, pos.x, pos.y);
    xShow(e);
    pos = xCardinalPosition(e, this.pos2, this.margin, false);
    xSlideTo(e, pos.x, pos.y, this.slideTime);
    this.open = true;
  }
};
// xPreventDefault, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPreventDefault(e)
{
  if (e && e.preventDefault) e.preventDefault();
  else if (window.event) window.event.returnValue = false;
}
// xPrevSib, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPrevSib(e,t)
{
  var s = e ? e.previousSibling : null;
  if (t) while(s && s.nodeName != t) {s=s.previousSibling;}
  else while(s && s.nodeType != 1) {s=s.previousSibling;}
  return s;
}
// xRad, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xRad(deg)
{
  return deg*(Math.PI/180);
}
// xRemoveEventListener, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xRemoveEventListener(e,eT,eL,cap)
{
  if(!(e=xGetElementById(e))) return;
  eT=eT.toLowerCase();
  if((!xIE4Up && !xOp7Up) && e==window) {
    if(eT=='resize') { window.xREL=null; return; }
    if(eT=='scroll') { window.xSEL=null; return; }
  }
  var eh='e.on'+eT+'=null';
  if(e.removeEventListener) e.removeEventListener(eT,eL,cap);
  else if(e.detachEvent) e.detachEvent('on'+eT,eL);
  else eval(eh);
}
// xResizeTo, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xResizeTo(e,w,h)
{
  xWidth(e,w);
  xHeight(e,h);
}
// xScrollLeft, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xScrollLeft(e, bWin)
{
  var offset=0;
  if (!xDef(e) || bWin || e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    var w = window;
    if (bWin && e) w = e;
    if(w.document.documentElement && w.document.documentElement.scrollLeft) offset=w.document.documentElement.scrollLeft;
    else if(w.document.body && xDef(w.document.body.scrollLeft)) offset=w.document.body.scrollLeft;
  }
  else {
    e = xGetElementById(e);
    if (e && xNum(e.scrollLeft)) offset = e.scrollLeft;
  }
  return offset;
}
// xScrollTop, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xScrollTop(e, bWin)
{
  var offset=0;
  if (!xDef(e) || bWin || e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    var w = window;
    if (bWin && e) w = e;
    if(w.document.documentElement && w.document.documentElement.scrollTop) offset=w.document.documentElement.scrollTop;
    else if(w.document.body && xDef(w.document.body.scrollTop)) offset=w.document.body.scrollTop;
  }
  else {
    e = xGetElementById(e);
    if (e && xNum(e.scrollTop)) offset = e.scrollTop;
  }
  return offset;
}
// xSelect, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSelect(sId, fnSubOnChange)
{
  //// Properties
  
  this.ready = false;
  
  //// Constructor

  // Check for required browser objects
  var s0 = xGetElementById(sId);
  if (!s0 || !s0.firstChild || !s0.nodeName || !document.createElement || !s0.form || !s0.form.appendChild)
  {
    return;
  }
  
  // Create main category SELECT element
  var s1 = document.createElement('SELECT');
  s1.id = sId + '_main';
  s1.display = 'block'; // for opera bug?
  s1.style.position = 'absolute';
  s1.xSelObj = this;
  s1.xSelData = new Array();
  // append s1 to s0's form
  s0.form.appendChild(s1);

  // Iterate thru s0 and fill array.
  // For each OPTGROUP, a[og][0] == OPTGROUP label, and...
  // a[og][n] = innerHTML of OPTION n.
  var ig=0, io, op, og, a = s1.xSelData;
  og = s0.firstChild;
  while (og) {
    if (og.nodeName.toLowerCase() == 'optgroup') {
      io = 0;
      a[ig] = new Array();
      a[ig][io] = og.label;
      op = og.firstChild;
      while (op) {
        if (op.nodeName.toLowerCase() == 'option') {
          io++;
          a[ig][io] = op.innerHTML;
        }
        op = op.nextSibling;
      }
      ig++;
    }
    og = og.nextSibling;
  }

  // in s1 insert a new OPTION for each OPTGROUP in s0
  for (ig=0; ig<a.length; ++ig) {
    op = new Option(a[ig][0]);
    s1.options[ig] = op;
  }
  
  // Create sub-category SELECT element
  var s2 = document.createElement('SELECT');
  s2.id = sId + '_sub';
  s2.display = 'block'; // for opera bug?
  s2.style.position = 'absolute';
  s2.xSelMain = s1;
  s1.xSelSub = s2;
  // append s2 to s0's form
  s0.form.appendChild(s2);
  
  // Add event listeners
  s1.onchange = xSelectMain_OnChange;
  s2.onchange = fnSubOnChange;
  // Hide s0. Position and show s1 where s0 was.
  // Position and show s2 to the right of s1.
  xHide(s0);
//alert(s1.offsetParent.nodeName + '\n' + xPageX(s0) + ', ' + xPageY(s0) + '\n' + xOffsetLeft(s0) + ', ' + xOffsetTop(s0));//////////
  xMoveTo(s1, xOffsetLeft(s0), xOffsetTop(s0));
//  xMoveTo(s1, xOffsetLeft(s0), xPageY(s0));
//  xMoveTo(s1, s0.offsetLeft, xPageY(s0));
//  xMoveTo(s1, s0.offsetLeft, s0.offsetTop);
  xShow(s1);
  xMoveTo(s2, xOffsetLeft(s0) + xWidth(s1), xOffsetTop(s0));
//  xMoveTo(s2, s0.offsetLeft + xWidth(s1), xPageY(s0));
//  xMoveTo(s2, s0.offsetLeft + xWidth(s1), s0.offsetTop);
  xShow(s2);

  // Initialize s2
  s1.onchange();
  // Ready to rock!
  this.ready = true;
  
} // end xSelect object prototype

function xSelectMain_OnChange()
{
  var io, s2 = this.xSelSub;
  // clear existing
  for (io=0; io<s2.options.length; ++io) {
    s2.options[io] = null;
  }
  // insert new
  var a = this.xSelData, ig = this.selectedIndex;
  for (io=1; io<a[ig].length; ++io) {
    op = new Option(a[ig][io]);
    s2.options[io-1] = op;
  }
}
// xSetCookie, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSetCookie(name, value, expire, path)
{
  document.cookie = name + "=" + escape(value) +
                    ((!expire) ? "" : ("; expires=" + expire.toGMTString())) +
                    "; path=" + ((!path) ? "/" : path);
}
// xSetIETitle, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSetIETitle()
{
  if (xIE4Up) {
    var i = xUA.indexOf('msie') + 1;
    var v = xUA.substr(i + 4, 3);
    document.title = 'IE ' + v + ' - ' + document.title;
  }
}
// xShow, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xShow(e) {return xVisibility(e,1);}
// xSlideCornerTo, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSlideCornerTo(e, corner, targetX, targetY, totalTime)
{
  if (!(e=xGetElementById(e))) return;
  if (!e.timeout) e.timeout = 25;
  e.xT = targetX;
  e.yT = targetY;
  e.slideTime = totalTime;
  e.corner = corner.toLowerCase();
  e.stop = false;
  switch(e.corner) {
    case 'nw': e.xA = e.xT - xLeft(e); e.yA = e.yT - xTop(e); e.xD = xLeft(e); e.yD = xTop(e); break;
    case 'sw': e.xA = e.xT - xLeft(e); e.yA = e.yT - (xTop(e) + xHeight(e)); e.xD = xLeft(e); e.yD = xTop(e) + xHeight(e); break;
    case 'ne': e.xA = e.xT - (xLeft(e) + xWidth(e)); e.yA = e.yT - xTop(e); e.xD = xLeft(e) + xWidth(e); e.yD = xTop(e); break;
    case 'se': e.xA = e.xT - (xLeft(e) + xWidth(e)); e.yA = e.yT - (xTop(e) + xHeight(e)); e.xD = xLeft(e) + xWidth(e); e.yD = xTop(e) + xHeight(e); break;
    default: alert("xSlideCornerTo: Invalid corner"); return;
  }
  e.B = Math.PI / ( 2 * e.slideTime );
  var d = new Date();
  e.C = d.getTime();
  if (!e.moving) _xSlideCornerTo(e);
}

function _xSlideCornerTo(e)
{
  if (!(e=xGetElementById(e))) return;
  var now, seX, seY;
  now = new Date();
  t = now.getTime() - e.C;
  if (e.stop) { e.moving = false; e.stop = false; return; }
  else if (t < e.slideTime) {
    setTimeout("_xSlideCornerTo('"+e.id+"')", e.timeout);
    s = Math.sin( e.B * t );
    newX = Math.round(e.xA * s + e.xD);
    newY = Math.round(e.yA * s + e.yD);
  }
  else { newX = e.xT; newY = e.yT; }  
  seX = xLeft(e) + xWidth(e);
  seY = xTop(e) + xHeight(e);
  switch(e.corner) {
    case 'nw': xMoveTo(e, newX, newY); xResizeTo(e, seX - xLeft(e), seY - xTop(e)); break;
    case 'sw': if (e.xT != xLeft(e)) { xLeft(e, newX); xWidth(e, seX - xLeft(e)); } xHeight(e, newY - xTop(e)); break;
    case 'ne': xWidth(e, newX - xLeft(e)); if (e.yT != xTop(e)) { xTop(e, newY); xHeight(e, seY - xTop(e)); } break;
    case 'se': xWidth(e, newX - xLeft(e)); xHeight(e, newY - xTop(e)); break;
    default: e.stop = true;
  }
  //window.status = ('Target: ' + e.xT + ', ' + e.yT);//////debug///////
//  xClip(e, 'auto'); // ?????is this needed? it was used in the original CBE method?????
  e.moving = true;
  if (t >= e.slideTime) {
    e.moving = false;
  }
}
// xSlideTo, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSlideTo(e, x, y, uTime)
{
  if (!(e=xGetElementById(e))) return;
  if (!e.timeout) e.timeout = 25;
  e.xTarget = x; e.yTarget = y; e.slideTime = uTime; e.stop = false;
  e.yA = e.yTarget - xTop(e); e.xA = e.xTarget - xLeft(e); // A = distance
  if (e.slideLinear) e.B = 1/e.slideTime;
  else e.B = Math.PI / (2 * e.slideTime); // B = period
  e.yD = xTop(e); e.xD = xLeft(e); // D = initial position
  var d = new Date(); e.C = d.getTime();
  if (!e.moving) _xSlideTo(e);
}
function _xSlideTo(e)
{
  if (!(e=xGetElementById(e))) return;
  var now, s, t, newY, newX;
  now = new Date();
  t = now.getTime() - e.C;
  if (e.stop) { e.moving = false; }
  else if (t < e.slideTime) {
    setTimeout("_xSlideTo('"+e.id+"')", e.timeout);
    if (e.slideLinear) s = e.B * t;
    else s = Math.sin(e.B * t);
    newX = Math.round(e.xA * s + e.xD);
    newY = Math.round(e.yA * s + e.yD);
    xMoveTo(e, newX, newY);
    e.moving = true;
  }  
  else {
    xMoveTo(e, e.xTarget, e.yTarget);
    e.moving = false;
  }  
}

// xStopPropagation, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xStopPropagation(evt)
{
  if (evt && evt.stopPropagation) evt.stopPropagation();
  else if (window.event) window.event.cancelBubble = true;
}
// xStr, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xStr(s)
{
  for(var i=0; i<arguments.length; ++i){if(typeof(arguments[i])!='string') return false;}
  return true;
}
// xTableCellVisibility, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableCellVisibility(bShow, sec, nRow, nCol)
{
  sec = xGetElementById(sec);
  if (sec && nRow < sec.rows.length && nCol < sec.rows[nRow].cells.length) {
    sec.rows[nRow].cells[nCol].style.visibility = bShow ? 'visible' : 'hidden';
  }
}
// xTableColDisplay, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableColDisplay(bShow, sec, nCol)
{
  var r;
  sec = xGetElementById(sec);
  if (sec && nCol < sec.rows[0].cells.length) {
    for (r = 0; r < sec.rows.length; ++r) {
      sec.rows[r].cells[nCol].style.display = bShow ? '' : 'none';
    }
  }
}
// xTableCursor, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableCursor(id, inh, def, hov, sel) // object prototype
{
  var tbl = xGetElementById(id);
  if (tbl) {
    xTableIterate(tbl, init);
  }
  function init(obj, isRow)
  {
    if (isRow) {
      obj.className = def;
      obj.onmouseover = trOver;
      obj.onmouseout = trOut;
    }
    else {
      obj.className = inh;
      obj.onmouseover = tdOver;
      obj.onmouseout = tdOut;
    }
  }
  this.unload = function() { xTableIterate(tbl, done); };
  function done(o) { o.onmouseover = o.onmouseout = null; }
  function trOver() { this.className = hov; }
  function trOut() { this.className = def; }
  function tdOver() { this.className = sel; }
  function tdOut() { this.className = inh; }
}
// xTableIterate, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableIterate(sec, fnCallback, data)
{
  var r, c;
  sec = xGetElementById(sec);
  if (!sec || !fnCallback) { return; }
  for (r = 0; r < sec.rows.length; ++r) {
    if (false == fnCallback(sec.rows[r], true, r, c, data)) { return; }
    for (c = 0; c < sec.rows[r].cells.length; ++c) {
      if (false == fnCallback(sec.rows[r].cells[c], false, r, c, data)) { return; }
    }
  }
}

// xTableRowDisplay, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableRowDisplay(bShow, sec, nRow)
{
  sec = xGetElementById(sec);
  if (sec && nRow < sec.rows.length) {
    sec.rows[nRow].style.display = bShow ? '' : 'none';
  }
}
// xTabPanelGroup, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTabPanelGroup(id, w, h, th, clsTP, clsTG, clsTD, clsTS) // object prototype
{
  // Private Methods

  function onClick() //r7
  {
    paint(this);
    return false;
  }
  function onFocus() //r7
  {
    paint(this);
  }
  function paint(tab)
  {
    tab.className = clsTS;
    xZIndex(tab, highZ++);
    xDisplay(panels[tab.xTabIndex], 'block'); //r6
  
    if (selectedIndex != tab.xTabIndex) {
      xDisplay(panels[selectedIndex], 'none'); //r6
      tabs[selectedIndex].className = clsTD;
  
      selectedIndex = tab.xTabIndex;
    }
  }

  // Public Methods

  this.select = function(n) //r7
  {
    if (n && n <= tabs.length) {
      var t = tabs[n-1];
      if (t.focus) t.focus();
      else t.onclick();
    }
  }

  this.onUnload = function()
  {
    if (xIE4Up) for (var i = 0; i < tabs.length; ++i) {tabs[i].onclick = null;}
  }

  // Constructor Code (note that all these vars are 'private')

  var panelGrp = xGetElementById(id);
  if (!panelGrp) { return null; }
  var panels = xGetElementsByClassName(clsTP, panelGrp);
  var tabs = xGetElementsByClassName(clsTD, panelGrp);
  var tabGrp = xGetElementsByClassName(clsTG, panelGrp);
  if (!panels || !tabs || !tabGrp || panels.length != tabs.length || tabGrp.length != 1) { return null; }
  var selectedIndex = 0, highZ, x = 0, i;
  xResizeTo(panelGrp, w, h);
  xResizeTo(tabGrp[0], w, th);
  xMoveTo(tabGrp[0], 0, 0);
  w -= 2; // remove border widths
  var tw = w / tabs.length;
  for (i = 0; i < tabs.length; ++i) {
    xResizeTo(tabs[i], tw, th); 
    xMoveTo(tabs[i], x, 0);
    x += tw;
    tabs[i].xTabIndex = i;
    tabs[i].onclick = onClick;
    tabs[i].onfocus = onFocus; //r7
    xDisplay(panels[i], 'none'); //r6
    xResizeTo(panels[i], w, h - th - 2); // -2 removes border widths
    xMoveTo(panels[i], 0, th);
  }
  highZ = i;
  tabs[0].onclick();
}
// xTimer, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTimerMgr()
{
  this.timers = new Array();
}

// xTimerMgr Methods
xTimerMgr.prototype.set = function(type, obj, sMethod, uTime, data) // type: 'interval' or 'timeout'
{
  return (this.timers[this.timers.length] = new xTimerObj(type, obj, sMethod, uTime, data));
};
xTimerMgr.prototype.run = function()
{
  var i, t, d = new Date(), now = d.getTime();
  for (i = 0; i < this.timers.length; ++i) {
    t = this.timers[i];
    if (t && t.running) {
      t.elapsed = now - t.time0;
      if (t.elapsed >= t.preset) { // timer event on t
        t.obj[t.mthd](t); // pass listener this xTimerObj
        if (t.type.charAt(0) == 'i') { t.time0 = now; }
        else { t.stop(); }
      }  
    }
  }
};

// Object Prototype used only by xTimerMgr
function xTimerObj(type, obj, mthd, preset, data)
{
  // Public Properties
  this.data = data;
  // Read-only Properties
  this.type = type; // 'interval' or 'timeout'
  this.obj = obj;
  this.mthd = mthd; // string
  this.preset = preset;
  this.reset();
} // end xTimerObj constructor
// xTimerObj Methods
xTimerObj.prototype.stop = function() { this.running = false; };
xTimerObj.prototype.start = function() { this.running = true; }; // continue after a stop
xTimerObj.prototype.reset = function()
{
  var d = new Date();
  this.time0 = d.getTime();
  this.elapsed = 0;
  this.running = true;
};

var xTimer = new xTimerMgr(); // applications assume global name is 'xTimer'
setInterval('xTimer.run()', 250);
// xTooltipGroup, Copyright 2002,2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

document.write("<style type='text/css'>#xTooltipElement{position:absolute;visibility:hidden;}</style>");
document.write("<div id='xTooltipElement'>xTooltipElement</div>");

var xttTrigger = null; // current trigger element

function xTooltipGroup(grpClassOrIdList, tipClass, origin, xOffset, yOffset, textList)
{

  //// Properties

  this.c = tipClass;
  this.o = origin;
  this.x = xOffset;
  this.y = yOffset;
  this.t = null; // tooltip element - all groups use the same element

  //// Constructor Code

  var i, tips;
  if (xStr(grpClassOrIdList)) {
    tips = xGetElementsByClassName(grpClassOrIdList);
    for (i = 0; i < tips.length; ++i) {
      tips[i].xTooltip = this;
    }
  }
  else {
    tips = new Array();
    for (i = 0; i < grpClassOrIdList.length; ++i) {
      tips[i] = xGetElementById(grpClassOrIdList[i]);
      if (!tips[i]) {
        alert('Element not found for id = ' + grpClassOrIdList[i]);
      }  
      else {
        tips[i].xTooltip = this;
        tips[i].xTooltipText = textList[i];
      }
    }
  }
  if (!this.t) { // only execute once
    this.t = xGetElementById('xTooltipElement');
    xAddEventListener(document, 'mousemove', this.docOnMousemove, false);
  }
} // end xTooltipGroup ctor

//// xTooltipGroup Methods

xTooltipGroup.prototype.show = function(trigEle, mx, my)
{
  if (xttTrigger != trigEle) { // if not active or moved to an adjacent trigger
    this.t.className = trigEle.xTooltip.c;
    this.t.innerHTML = trigEle.xTooltipText ? trigEle.xTooltipText : trigEle.title;
    xttTrigger = trigEle;
  }  
  var x, y;
  switch(this.o) {
    case 'right':
      x = xPageX(trigEle) + xWidth(trigEle);
      y = xPageY(trigEle);
      break;
    case 'top':
      x = xPageX(trigEle);
      y = xPageY(trigEle) - xHeight(trigEle);
      break;
    case 'mouse':
      x = mx;
      y = my;
      break;
  }
  xMoveTo(this.t, x + this.x, y + this.y);
  xShow(this.t);
};

xTooltipGroup.prototype.hide = function()
{
  xMoveTo(this.t, -1000, -1000);
  xttTrigger = null;
};

xTooltipGroup.prototype.docOnMousemove = function(oEvent)
{
  // this == document at runtime
  var o, e = new xEvent(oEvent);
  if (e.target && (o = e.target.xTooltip)) {
    o.show(e.target, e.pageX, e.pageY);
  }
  else if (xttTrigger) {
    xttTrigger.xTooltip.hide();
  }
};
// xTop, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTop(e, iY)
{
  if(!(e=xGetElementById(e))) return 0;
  var css=xDef(e.style);
  if(css && xStr(e.style.top)) {
    if(xNum(iY)) e.style.top=iY+'px';
    else {
      iY=parseInt(e.style.top);
      if(isNaN(iY)) iY=0;
    }
  }
  else if(css && xDef(e.style.pixelTop)) {
    if(xNum(iY)) e.style.pixelTop=iY;
    else iY=e.style.pixelTop;
  }
  return iY;
}
// xTriStateImage, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTriStateImage(idOut, urlOver, urlDown, fnUp) // Object Prototype
{
  var img;
  // Downgrade Detection
  if (typeof Image != 'undefined' && document.getElementById) {
    img = document.getElementById(idOut);
    if (img) {
      // Constructor Code
      var urlOut = img.src;
      var i = new Image();
      i.src = urlOver;
      i = new Image();
      i.src = urlDown;
      // Event Listeners (closure)
      img.onmouseover = function()
      {
        this.src = urlOver;
      };
      img.onmouseout = function()
      {
        this.src = urlOut;
      };
      img.onmousedown = function()
      {
        this.src = urlDown;
      };
      img.onmouseup = function()
      {
        this.src = urlOver;
        if (fnUp) {
          fnUp();
        }
      };
    }
  }
  // Destructor Method
  this.onunload = function()
  {
    if (xIE4Up && img) { // Remove any circular references for IE
      img.onmouseover = img.onmouseout = img.onmousedown = null;
      img = null;
    }
  };    
}
var xVersion = "4.0";// xVisibility, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xVisibility(e, bShow)
{
  if(!(e=xGetElementById(e))) return null;
  if(e.style && xDef(e.style.visibility)) {
    if (xDef(bShow)) e.style.visibility = bShow ? 'visible' : 'hidden';
    return e.style.visibility;
  }
  return null;
}

//function xVisibility(e,s)
//{
//  if(!(e=xGetElementById(e))) return null;
//  var v = 'visible', h = 'hidden';
//  if(e.style && xDef(e.style.visibility)) {
//    if (xDef(s)) {
//      // try to maintain backwards compatibility (???)
//      if (xStr(s)) e.style.visibility = s;
//      else e.style.visibility = s ? v : h;
//    }
//    return e.style.visibility;
//    // or...
//    // if (e.style.visibility.length) return e.style.visibility;
//    // else return xGetComputedStyle(e, 'visibility');
//  }
//  else if (xDef(e.visibility)) { // NN4
//    if (xDef(s)) {
//      // try to maintain backwards compatibility
//      if (xStr(s)) e.visibility = (s == v) ? 'show' : 'hide';
//      else e.visibility = s ? v : h;
//    }
//    return (e.visibility == 'show') ? v : h;
//  }
//  return null;
//}
// xWalkEleTree, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWalkEleTree(n,f,d,l,b)
{
  if (typeof l == 'undefined') l = 0;
  if (typeof b == 'undefined') b = 0;
  var v = f(n,l,b,d);
  if (!v) return 0;
  if (v == 1) {
    for (var c = n.firstChild; c; c = c.nextSibling) {
      if (c.nodeType == 1) {
        if (!l) ++b;
        if (!xWalkEleTree(c,f,d,l+1,b)) return 0;
      }
    }
  }
  return 1;
}
// xWalkTree, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWalkTree(n, f)
{
  f(n);
  for (var c = n.firstChild; c; c = c.nextSibling) {
    if (c.nodeType == 1) xWalkTree(c, f);
  }
}

// original implementation:
// function xWalkTree(oNode, fnVisit)
// {
//   if (oNode) {
//     if (oNode.nodeType == 1) {fnVisit(oNode);}
//     for (var c = oNode.firstChild; c; c = c.nextSibling) {
//       xWalkTree(c, fnVisit);
//     }
//   }
// }
// xWidth, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWidth(e,w)
{
  if(!(e=xGetElementById(e))) return 0;
  if (xNum(w)) {
    if (w<0) w = 0;
    else w=Math.round(w);
  }
  else w=-1;
  var css=xDef(e.style);
  if (e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    w = xClientWidth();
  }
  else if(css && xDef(e.offsetWidth) && xStr(e.style.width)) {
    if(w>=0) {
      var pl=0,pr=0,bl=0,br=0;
      if (document.compatMode=='CSS1Compat') {
        var gcs = xGetComputedStyle;
        pl=gcs(e,'padding-left',1);
        if (pl !== null) {
          pr=gcs(e,'padding-right',1);
          bl=gcs(e,'border-left-width',1);
          br=gcs(e,'border-right-width',1);
        }
        // Should we try this as a last resort?
        // At this point getComputedStyle and currentStyle do not exist.
        else if(xDef(e.offsetWidth,e.style.width)){
          e.style.width=w+'px';
          pl=e.offsetWidth-w;
        }
      }
      w-=(pl+pr+bl+br);
      if(isNaN(w)||w<0) return;
      else e.style.width=w+'px';
    }
    w=e.offsetWidth;
  }
  else if(css && xDef(e.style.pixelWidth)) {
    if(w>=0) e.style.pixelWidth=w;
    w=e.style.pixelWidth;
  }
  return w;
}
// xWinClass, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// xWinClass Object Prototype

function xWinClass(clsName, winName, w, h, x, y, loc, men, res, scr, sta, too)
{
  var thisObj = this;
  var e='',c=',',xf='left=',yf='top='; this.n = name;
  if (document.layers) {xf='screenX='; yf='screenY=';}
  this.f = (w?'width='+w+c:e)+(h?'height='+h+c:e)+(x>=0?xf+x+c:e)+
    (y>=0?yf+y+c:e)+'location='+loc+',menubar='+men+',resizable='+res+
    ',scrollbars='+scr+',status='+sta+',toolbar='+too;
  this.opened = function() {return this.w && !this.w.closed;};
  this.close = function() {if(this.opened()) this.w.close();};
  this.focus = function() {if(this.opened()) this.w.focus();};
  this.load = function(sUrl) {
    if (this.opened()) this.w.location.href = sUrl;
    else this.w = window.open(sUrl,this.n,this.f);
    this.focus();
    return false;
  };
  // Closures
  // this == <A> element reference, thisObj == xWinClass object reference
  function onClick() {return thisObj.load(this.href);}
  // '*' works with any element, not just A
  xGetElementsByClassName(clsName, document, '*', bindOnClick);
  function bindOnClick(e) {e.onclick = onClick;}
}
// xWindow, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWindow(name, w, h, x, y, loc, men, res, scr, sta, too)
{
  var e='',c=',',xf='left=',yf='top='; this.n = name;
  if (document.layers) {xf='screenX='; yf='screenY=';}
  this.f = (w?'width='+w+c:e)+(h?'height='+h+c:e)+(x>=0?xf+x+c:e)+
    (y>=0?yf+y+c:e)+'location='+loc+',menubar='+men+',resizable='+res+
    ',scrollbars='+scr+',status='+sta+',toolbar='+too;
  this.opened = function() {return this.w && !this.w.closed;};
  this.close = function() {if(this.opened()) this.w.close();};
  this.focus = function() {if(this.opened()) this.w.focus();};
  this.load = function(sUrl) {
    if (this.opened()) this.w.location.href = sUrl;
    else this.w = window.open(sUrl,this.n,this.f);
    this.focus();
    return false;
  };
}

// Previous implementation:
// function xWindow(name, w, h, x, y, loc, men, res, scr, sta, too)
// {
//   var f = '';
//   if (w && h) {
//     if (document.layers) f = 'screenX=' + x + ',screenY=' + y;
//     else f = 'left=' + x + ',top=' + y;
//     f += ',width=' + w + ',height=' + h + ',';
//   }
//   f += ('location='+loc+',menubar='+men+',resizable='+res
//     +',scrollbars='+scr+',status='+sta+',toolbar='+too);
//   this.features = f;
//   this.name = name;
//   this.load = function(sUrl) {
//     if (this.wnd && !this.wnd.closed) this.wnd.location.href = sUrl;
//     else this.wnd = window.open(sUrl, this.name, this.features);
//     this.wnd.focus();
//     return false;
//   }
// }
// xWinOpen, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// A simple alternative to xWindow.

var xChildWindow = null;
function xWinOpen(sUrl)
{
  var features = "left=0,top=0,width=600,height=500,location=0,menubar=0," +
    "resizable=1,scrollbars=1,status=0,toolbar=0";
  if (xChildWindow && !xChildWindow.closed) {xChildWindow.location.href  = sUrl;}
  else {xChildWindow = window.open(sUrl, "myWinName", features);}
  xChildWindow.focus();
  return false;
}
// xWinScrollTo, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

var xWinScrollWin = null;
function xWinScrollTo(win,x,y,uTime) {
  var e = win;
  if (!e.timeout) e.timeout = 25;
  var st = xScrollTop(e, 1);
  var sl = xScrollLeft(e, 1);
  e.xTarget = x; e.yTarget = y; e.slideTime = uTime; e.stop = false;
  e.yA = e.yTarget - st;
  e.xA = e.xTarget - sl; // A = distance
  e.B = Math.PI / (2 * e.slideTime); // B = period
  e.yD = st;
  e.xD = sl; // D = initial position
  var d = new Date(); e.C = d.getTime();
  if (!e.moving) {
    xWinScrollWin = e;
    _xWinScrollTo();
  }
}
function _xWinScrollTo() {
  var e = xWinScrollWin || window;
  var now, s, t, newY, newX;
  now = new Date();
  t = now.getTime() - e.C;
  if (e.stop) { e.moving = false; }
  else if (t < e.slideTime) {
    setTimeout("_xWinScrollTo()", e.timeout);
    s = Math.sin(e.B * t);
    newX = Math.round(e.xA * s + e.xD);
    newY = Math.round(e.yA * s + e.yD);
    e.scrollTo(newX, newY);
    e.moving = true;
  }  
  else {
    e.scrollTo(e.xTarget, e.yTarget);
    xWinScrollWin = null;
    e.moving = false;
  }  
}
// xZIndex, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xZIndex(e,uZ)
{
  if(!(e=xGetElementById(e))) return 0;
  if(e.style && xDef(e.style.zIndex)) {
    if(xNum(uZ)) e.style.zIndex=uZ;
    uZ=parseInt(e.style.zIndex);
  }
  return uZ;
}
