// xName, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xName(e)
{
  if (!e) return e;
  else if (e.id && e.id != "") return e.id;
  else if (e.name && e.name != "") return e.name;
  else if (e.nodeName && e.nodeName != "") return e.nodeName;
  else if (e.tagName && e.tagName != "") return e.tagName;
  else return e;
}
