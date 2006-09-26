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
