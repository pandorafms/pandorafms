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
