// xImgAsyncWait, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xImgAsyncWait(fnStatus, fnInit, fnError, sErrorImg, sAbortImg, imgArray)
{
  var i, imgs = imgArray || document.images;
  
  for (i = 0; i < imgs.length; ++i) {
    imgs[i].onload = imgOnLoad;
    imgs[i].onerror = imgOnError;
    imgs[i].onabort = imgOnAbort;
  }
  
  xIAW.fnStatus = fnStatus;
  xIAW.fnInit = fnInit;
  xIAW.fnError = fnError;
  xIAW.imgArray = imgArray;

  xIAW();

  function imgOnLoad()
  {
    this.wasLoaded = true;
  }
  function imgOnError()
  {
    if (sErrorImg && !this.wasError) {
      this.src = sErrorImg;
    }
    this.wasError = true;
  }
  function imgOnAbort()
  {
    if (sAbortImg && !this.wasAborted) {
      this.src = sAbortImg;
    }
    this.wasAborted = true;
  }
}
// end xImgAsyncWait()

// Don't call xIAW() directly. It is only called from xImgAsyncWait().

function xIAW()
{
  var me = arguments.callee;
  if (!me) {
    return; // I could have used a global object instead of callee
  }
  var i, imgs = me.imgArray ? me.imgArray : document.images;
  var c = 0, e = 0, a = 0, n = imgs.length;
  for (i = 0; i < n; ++i) {
    if (imgs[i].wasError) {
      ++e;
    }
    else if (imgs[i].wasAborted) {
      ++a;
    }
    else if (imgs[i].complete || imgs[i].wasLoaded) {
      ++c;
    }
  }
  if (me.fnStatus) {
    me.fnStatus(n, c, e, a);
  }
  if (c + e + a == n) {
    if ((e || a) && me.fnError) {
      me.fnError(n, c, e, a);
    }
    else if (me.fnInit) {
      me.fnInit();
    }
  }
  else setTimeout('xIAW()', 250);
}
// end xIAW()
