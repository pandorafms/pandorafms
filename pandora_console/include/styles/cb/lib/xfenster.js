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
