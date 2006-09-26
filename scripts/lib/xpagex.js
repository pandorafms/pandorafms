// xPageX, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPageX(e)
{
  if (!(e=xGetElementById(e))) return 0;
  var x = 0;
  while (e) {
    if (xDef(e.offsetLeft)) x += e.offsetLeft;
    e = xDef(e.offsetParent) ? e.offsetParent : null;
  }
  return x;
}
