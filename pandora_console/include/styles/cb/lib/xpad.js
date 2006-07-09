// xPad, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xPad(s,len,c,left)
{
  if(typeof s != 'string') s=s+'';
  if(left) {for(var i=s.length; i<len; ++i) s=c+s;}
  else {for (var i=s.length; i<len; ++i) s+=c;}
  return s;
}
