// xDeleteCookie, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xDeleteCookie(name, path)
{
  if (xGetCookie(name)) {
    document.cookie = name + "=" +
                    "; path=" + ((!path) ? "/" : path) +
                    "; expires=" + new Date(0).toGMTString();
  }
}
