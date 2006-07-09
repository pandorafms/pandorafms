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
