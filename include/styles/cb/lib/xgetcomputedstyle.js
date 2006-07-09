// xGetComputedStyle, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetComputedStyle(oEle, sProp, bInt)
{
  var s, p = 'undefined';
  var dv = document.defaultView;
  if(dv && dv.getComputedStyle){
    s = dv.getComputedStyle(oEle,'');
    if (s) p = s.getPropertyValue(sProp);
  }
  else if(oEle.currentStyle) {
    // convert css property name to object property name for IE
    var a = sProp.split('-');
    sProp = a[0];
    for (var i=1; i<a.length; ++i) {
      c = a[i].charAt(0);
      sProp += a[i].replace(c, c.toUpperCase());
    }   
    p = oEle.currentStyle[sProp];
  }
  else return null;
  return bInt ? (parseInt(p) || 0) : p;
}

