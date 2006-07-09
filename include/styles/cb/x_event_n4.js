// x_event_n4.js, Copyright (C) 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser.com Javascript Library, Distributed under the terms of the GNU LGPL
// r2, 25Feb04, mf, Built from x_event.js, X v3.15.2.

function xAddEventListener(e,eventType,eventListener,useCapture) {
  if(!(e=xGetElementById(e))) return;
  eventType=eventType.toLowerCase();
  if((!xIE4Up && !xOp7) && e==window) {
    if(eventType=='resize') { window.xPCW=xClientWidth(); window.xPCH=xClientHeight(); window.xREL=eventListener; xResizeEvent(); return; }
    if(eventType=='scroll') { window.xPSL=xScrollLeft(); window.xPST=xScrollTop(); window.xSEL=eventListener; xScrollEvent(); return; }
  }
  var eh='e.on'+eventType+'=eventListener';
  if(e.addEventListener) e.addEventListener(eventType,eventListener,useCapture);
  else if(e.attachEvent) e.attachEvent('on'+eventType,eventListener);
  else if(e.captureEvents) {
    if(useCapture||(eventType.indexOf('mouse')!=-1)) { e.captureEvents(eval('Event.'+eventType.toUpperCase())); }
    eval(eh);
  }
  else eval(eh);
}
function xRemoveEventListener(e,eventType,eventListener,useCapture) {
  if(!(e=xGetElementById(e))) return;
  eventType=eventType.toLowerCase();
  if((!xIE4Up && !xOp7) && e==window) {
    if(eventType=='resize') { window.xREL=null; return; }
    if(eventType=='scroll') { window.xSEL=null; return; }
  }
  var eh='e.on'+eventType+'=null';
  if(e.removeEventListener) e.removeEventListener(eventType,eventListener,useCapture);
  else if(e.detachEvent) e.detachEvent('on'+eventType,eventListener);
  else if(e.releaseEvents) {
    if(useCapture||(eventType.indexOf('mouse')!=-1)) { e.releaseEvents(eval('Event.'+eventType.toUpperCase())); }
    eval(eh);
  }
  else eval(eh);
}
function xEvent(evt) {
  this.type = '';
  this.target = null;
  this.pageX = 0;
  this.pageY = 0;
  this.offsetX = 0;
  this.offsetY = 0;
  this.keyCode = 0;
//  var e = evt ? evt : window.event;
  var e = evt || window.event;
  if(!e) return;
  if(e.type) this.type = e.type;
  if(e.target) this.target = e.target;
  else if(e.srcElement) this.target = e.srcElement;
  else if(xNN4) this.target = xLayerFromPoint(e.pageX, e.pageY);
  if(xOp5or6) { this.pageX = e.clientX; this.pageY = e.clientY; }
  else if(xDef(e.pageX,e.pageY)) { this.pageX = e.pageX; this.pageY = e.pageY; } // v3.14
  else if(xDef(e.clientX,e.clientY)) { this.pageX = e.clientX + xScrollLeft(); this.pageY = e.clientY + xScrollTop(); }
//
//  if(xDef(e.offsetX,e.offsetY)) { this.offsetX = e.offsetX; this.offsetY = e.offsetY; }
//  else if(xDef(e.layerX,e.layerY)) { this.offsetX = e.layerX; this.offsetY = e.layerY; }
//  else { this.offsetX = this.pageX - xPageX(this.target); this.offsetY = this.pageY - xPageY(this.target); }
//
  if(xDef(e.offsetX,e.offsetY)) {
    this.offsetX = e.offsetX;
    this.offsetY = e.offsetY;
    if (xIE4Up && xMac) {
      this.offsetX += xScrollLeft();
      this.offsetY += xScrollTop();
    }
  }
  else if(xDef(e.layerX,e.layerY)) { this.offsetX = e.layerX; this.offsetY = e.layerY; }
  else { this.offsetX = this.pageX - xPageX(this.target); this.offsetY = this.pageY - xPageY(this.target); }
//

  if (e.keyCode) { this.keyCode = e.keyCode; } // for moz/fb, if keyCode==0 use which
  else if (xDef(e.which)) { this.keyCode = e.which; }
}
function xLayerFromPoint(x,y,root) {
  var i, hn=null, hz=-1, cn;
  if (!root) root = window;
  for (i=0; i < root.document.layers.length; ++i) {
    cn = root.document.layers[i];
    if (cn.visibility != "hide" && x >= cn.pageX && x <= cn.pageX + cn.clip.right && y >= cn.pageY && y <= cn.pageY + cn.clip.bottom ) {
      if (cn.zIndex > hz) { hz = cn.zIndex; hn = cn; }
    }
  }
  if (hn) {
    cn = xLayerFromPoint(x,y,hn);
    if (cn) hn = cn;
  }
  return hn;
}
function xResizeEvent() {
  if (window.xREL) setTimeout('xResizeEvent()', 250);
  var cw = xClientWidth(), ch = xClientHeight();
  if (window.xPCW != cw || window.xPCH != ch) { window.xPCW = cw; window.xPCH = ch; if (window.xREL) window.xREL(); }
}
function xScrollEvent() {
  if (window.xSEL) setTimeout('xScrollEvent()', 250);
  var sl = xScrollLeft(), st = xScrollTop();
  if (window.xPSL != sl || window.xPST != st) { window.xPSL = sl; window.xPST = st; if (window.xSEL) window.xSEL(); }
}
