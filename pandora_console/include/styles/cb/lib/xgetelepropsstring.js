// xGetElePropsString, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElePropsString(ele, eleName, newLine)
{
  var s = '', a = xGetElePropsArray(ele, eleName);
  for (var i = 0; i < a.length; ++i) {
    s += a[i].name + ' = ' + a[i].value + (newLine || '\n');
  }
  return s;
}
