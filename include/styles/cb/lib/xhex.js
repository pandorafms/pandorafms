// xHex, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xHex(n, digits, prefix)
{
  var p = '', n = Math.ceil(n);
  if (prefix) p = prefix;
  n = n.toString(16);
  for (var i=0; i < digits - n.length; ++i) {
    p += '0';
  }
  return p + n;
}
