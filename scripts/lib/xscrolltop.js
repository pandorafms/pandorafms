// xScrollTop, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xScrollTop(e, bWin)
{
  var offset=0;
  if (!xDef(e) || bWin || e == document || e.tagName.toLowerCase() == 'html' || e.tagName.toLowerCase() == 'body') {
    var w = window;
    if (bWin && e) w = e;
    if(w.document.documentElement && w.document.documentElement.scrollTop) offset=w.document.documentElement.scrollTop;
    else if(w.document.body && xDef(w.document.body.scrollTop)) offset=w.document.body.scrollTop;
  }
  else {
    e = xGetElementById(e);
    if (e && xNum(e.scrollTop)) offset = e.scrollTop;
  }
  return offset;
}
