// xDisplay, Copyright 2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDisplay(e,s)
{
  if(!(e=xGetElementById(e))) return null;
  if(e.style && xDef(e.style.display)) {
    if (xStr(s)) e.style.display = s;
    return e.style.display;
  }
  return null;
}
