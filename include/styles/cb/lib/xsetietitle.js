// xSetIETitle, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSetIETitle()
{
  if (xIE4Up) {
    var i = xUA.indexOf('msie') + 1;
    var v = xUA.substr(i + 4, 3);
    document.title = 'IE ' + v + ' - ' + document.title;
  }
}
