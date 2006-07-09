// xImgRollSetup, Copyright 2002,2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xImgRollSetup(p,s,x)
{
  var ele, id;
  for (var i=3; i<arguments.length; ++i) {
    id = arguments[i];
    if (ele = xGetElementById(id)) {
      ele.xIOU = p + id + x;
      ele.xIOO = new Image();
      ele.xIOO.src = p + id + s + x;
      ele.onmouseout = imgOnMouseout;
      ele.onmouseover = imgOnMouseover;
    }
  }
  function imgOnMouseout(e)
  {
    if (this.xIOU) {
      this.src = this.xIOU;
    }
  }
  function imgOnMouseover(e)
  {
    if (this.xIOO && this.xIOO.complete) {
      this.src = this.xIOO.src;
    }
  }
}
