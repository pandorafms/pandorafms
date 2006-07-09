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
