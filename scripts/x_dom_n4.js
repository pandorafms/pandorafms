// x_dom_n4.js, Copyright (C) 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser.com Javascript Library, Distributed under the terms of the GNU LGPL
// r2, 25Feb04, mf, Built from x_dom.js, X v3.15.2.

// X library with NN4.x support

// Returns a new Element object.
// For NN4, returns a new Object object - xAppendChild will return the Layer object.
function xCreateElement(sTag)
{
  var ele=null;
  if (xOp5or6 || xIE4) return null;
  if (document.createElement) ele = document.createElement(sTag);
  else if (xNN4) ele = new Object();
  return ele;
}
function xAppendChild(oParent, oChild, nn4Width) // returns oChild
{
  var ele=null;
  if (!oChild) return;
  if (oParent && oParent.appendChild) ele = oParent.appendChild(oChild);
  else if (xNN4) {
    if (typeof(oChild)=='object') delete oChild;
    if (!oParent || oParent.id.indexOf('_layer')==-1) {
      oParent = window;
      if (!nn4Width) nn4Width = xClientWidth();
    }
    else if (!nn4Width) nn4Width = xWidth(oParent);
    ele = new Layer(nn4Width, oParent);
  }
  return ele;
}
