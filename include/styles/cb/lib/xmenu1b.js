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
