// xIntersection, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xIntersection(e1, e2, o)
{
  var ix1, iy2, iw, ih, intersect = true;
  var e1x1 = xPageX(e1);
  var e1x2 = e1x1 + xWidth(e1);
  var e1y1 = xPageY(e1);
  var e1y2 = e1y1 + xHeight(e1);
  var e2x1 = xPageX(e2);
  var e2x2 = e2x1 + xWidth(e2);
  var e2y1 = xPageY(e2);
  var e2y2 = e2y1 + xHeight(e2);
  // horizontal
  if (e1x1 <= e2x1) {
    ix1 = e2x1;
    if (e1x2 < e2x1) intersect = false;
    else iw = Math.min(e1x2, e2x2) - e2x1;
  }
  else {
    ix1 = e1x1;
    if (e2x2 < e1x1) intersect = false;
    else iw = Math.min(e1x2, e2x2) - e1x1;
  }
  // vertical
  if (e1y2 >= e2y2) {
    iy2 = e2y2;
    if (e1y1 > e2y2) intersect = false;
    else ih = e2y2 - Math.max(e1y1, e2y1);
  }
  else {
    iy2 = e1y2;
    if (e2y1 > e1y2) intersect = false;
    else ih = e1y2 - Math.max(e1y1, e2y1);
  }
  // intersected rectangle
  if (intersect && typeof(o)=='object') {
    o.x = ix1;
    o.y = iy2 - ih;
    o.w = iw;
    o.h = ih;
  }
  return intersect;
}
