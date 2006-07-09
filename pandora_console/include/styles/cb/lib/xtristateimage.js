// xTriStateImage, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTriStateImage(idOut, urlOver, urlDown, fnUp) // Object Prototype
{
  var img;
  // Downgrade Detection
  if (typeof Image != 'undefined' && document.getElementById) {
    img = document.getElementById(idOut);
    if (img) {
      // Constructor Code
      var urlOut = img.src;
      var i = new Image();
      i.src = urlOver;
      i = new Image();
      i.src = urlDown;
      // Event Listeners (closure)
      img.onmouseover = function()
      {
        this.src = urlOver;
      };
      img.onmouseout = function()
      {
        this.src = urlOut;
      };
      img.onmousedown = function()
      {
        this.src = urlDown;
      };
      img.onmouseup = function()
      {
        this.src = urlOver;
        if (fnUp) {
          fnUp();
        }
      };
    }
  }
  // Destructor Method
  this.onunload = function()
  {
    if (xIE4Up && img) { // Remove any circular references for IE
      img.onmouseover = img.onmouseout = img.onmousedown = null;
      img = null;
    }
  };    
}
