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
