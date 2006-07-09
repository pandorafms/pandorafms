// xBackground, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xBackground(e,c,i)
{
  if(!(e=xGetElementById(e))) return '';
  var bg='';
  if(e.style) {
    if(xStr(c)) {
      if(!xOp6Dn) e.style.backgroundColor=c;
      else e.style.background=c;
    }
    if(xStr(i)) e.style.backgroundImage=(i!='')? 'url('+i+')' : null;
    if(!xOp6Dn) bg=e.style.backgroundColor;
    else bg=e.style.background;
  }
  return bg;
}
