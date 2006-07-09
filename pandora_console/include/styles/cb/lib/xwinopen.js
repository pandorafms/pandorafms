// xWinOpen, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// A simple alternative to xWindow.

var xChildWindow = null;
function xWinOpen(sUrl)
{
  var features = "left=0,top=0,width=600,height=500,location=0,menubar=0," +
    "resizable=1,scrollbars=1,status=0,toolbar=0";
  if (xChildWindow && !xChildWindow.closed) {xChildWindow.location.href  = sUrl;}
  else {xChildWindow = window.open(sUrl, "myWinName", features);}
  xChildWindow.focus();
  return false;
}
