// xZIndex, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xZIndex(e,uZ)
{
  if(!(e=xGetElementById(e))) return 0;
  if(e.style && xDef(e.style.zIndex)) {
    if(xNum(uZ)) e.style.zIndex=uZ;
    uZ=parseInt(e.style.zIndex);
  }
  return uZ;
}
