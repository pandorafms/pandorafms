// xLinearScale, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xLinearScale(val,iL,iH,oL,oH)
{
  var m=(oH-oL)/(iH-iL);
  var b=oL-(iL*m);
  return m*val+b;
}
