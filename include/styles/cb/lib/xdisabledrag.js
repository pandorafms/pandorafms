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
