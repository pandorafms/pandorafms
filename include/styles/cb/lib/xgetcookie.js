// xGetCookie, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetCookie(name)
{
  var value=null, search=name+"=";
  if (document.cookie.length > 0) {
    var offset = document.cookie.indexOf(search);
    if (offset != -1) {
      offset += search.length;
      var end = document.cookie.indexOf(";", offset);
      if (end == -1) end = document.cookie.length;
      value = unescape(document.cookie.substring(offset, end));
    }
  }
  return value;
}
