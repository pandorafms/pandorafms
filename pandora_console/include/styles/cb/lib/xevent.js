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
