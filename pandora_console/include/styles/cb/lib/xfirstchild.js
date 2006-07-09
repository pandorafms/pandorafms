// xFirstChild, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xFirstChild(e, t)
{
  var c = e ? e.firstChild : null;
  if (t) while (c && c.nodeName != t) { c = c.nextSibling; }
  else while (c && c.nodeType != 1) { c = c.nextSibling; }
  return c;
}
