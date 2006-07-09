// xEllipse, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xEllipse(e, xRadius, yRadius, radiusInc, totalTime, startAngle, stopAngle)
{
  if (!(e=xGetElementById(e))) return;
  if (!e.timeout) e.timeout = 25;
  e.xA = xRadius;
  e.yA = yRadius;
  e.radiusInc = radiusInc;
  e.slideTime = totalTime;
  startAngle *= (Math.PI / 180);
  stopAngle *= (Math.PI / 180);
  var startTime = (startAngle * e.slideTime) / (stopAngle - startAngle);
  e.stopTime = e.slideTime + startTime;
  e.B = (stopAngle - startAngle) / e.slideTime;
  e.xD = xLeft(e) - Math.round(e.xA * Math.cos(e.B * startTime)); // center point
  e.yD = xTop(e) - Math.round(e.yA * Math.sin(e.B * startTime)); 
  e.xTarget = Math.round(e.xA * Math.cos(e.B * e.stopTime) + e.xD); // end point
  e.yTarget = Math.round(e.yA * Math.sin(e.B * e.stopTime) + e.yD); 
  var d = new Date();
  e.C = d.getTime() - startTime;
  if (!e.moving) {e.stop=false; _xEllipse(e);}
}
function _xEllipse(e)
{
  if (!(e=xGetElementById(e))) return;
  var now, t, newY, newX;
  now = new Date();
  t = now.getTime() - e.C;
  if (e.stop) { e.moving = false; }
  else if (t < e.stopTime) {
    setTimeout("_xEllipse('"+e.id+"')", e.timeout);
    if (e.radiusInc) {
      e.xA += e.radiusInc;
      e.yA += e.radiusInc;
    }
    newX = Math.round(e.xA * Math.cos(e.B * t) + e.xD);
    newY = Math.round(e.yA * Math.sin(e.B * t) + e.yD);
    xMoveTo(e, newX, newY);
    e.moving = true;
  }  
  else {
    if (e.radiusInc) {
      e.xTarget = Math.round(e.xA * Math.cos(e.B * e.slideTime) + e.xD);
      e.yTarget = Math.round(e.yA * Math.sin(e.B * e.slideTime) + e.yD); 
    }
    xMoveTo(e, e.xTarget, e.yTarget);
    e.moving = false;
  }  
}
