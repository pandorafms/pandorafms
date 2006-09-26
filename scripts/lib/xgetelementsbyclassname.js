// xGetElementsByClassName, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElementsByClassName(c,p,t,f)
{
  var found = new Array();
  var re = new RegExp('\\b'+c+'\\b', 'i');
  var list = xGetElementsByTagName(t, p);
  for (var i = 0; i < list.length; ++i) {
    if (list[i].className && list[i].className.search(re) != -1) {
      found[found.length] = list[i];
      if (f) f(list[i]);
    }
  }
  return found;
}
