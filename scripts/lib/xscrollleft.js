// xScrollLeft, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xScrollLeft(e, bWin)
{
  var offset=0;
  if (!xDef(e) || bWin || e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    var w = window;
    if (bWin && e) w = e;
    if(w.document.documentElement && w.document.documentElement.scrollLeft) offset=w.document.documentElement.scrollLeft;
    else if(w.document.body && xDef(w.document.body.scrollLeft)) offset=w.document.body.scrollLeft;
  }
  else {
    e = xGetElementById(e);
    if (e && xNum(e.scrollLeft)) offset = e.scrollLeft;
  }
  return offset;
}
