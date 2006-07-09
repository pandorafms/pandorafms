// xPrevSib, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPrevSib(e,t)
{
  var s = e ? e.previousSibling : null;
  if (t) while(s && s.nodeName != t) {s=s.previousSibling;}
  else while(s && s.nodeType != 1) {s=s.previousSibling;}
  return s;
}
