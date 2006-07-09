// xNextSib, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xNextSib(e,t)
{
  var s = e ? e.nextSibling : null;
  if (t) while (s && s.nodeName != t) { s = s.nextSibling; }
  else while (s && s.nodeType != 1) { s = s.nextSibling; }
  return s;
}
