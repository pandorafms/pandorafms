// xOffsetLeft, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xOffsetLeft(e)
{
  if (!(e=xGetElementById(e))) return 0;
  if (xDef(e.offsetLeft)) return e.offsetLeft;
  else return 0;
}
