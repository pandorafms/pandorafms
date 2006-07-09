// xCollapsible, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xCollapsible(outerEle, bShow) // object prototype
{
  // Constructor

  var container = xGetElementById(outerEle);
  if (!container) {return null;}
  var isUL = container.nodeName.toUpperCase() == 'UL';
  var i, trg, aTgt = xGetElementsByTagName(isUL ? 'UL':'DIV', container);
  for (i = 0; i < aTgt.length; ++i) {
    trg = xPrevSib(aTgt[i]);
    if (trg && (isUL || trg.nodeName.charAt(0).toUpperCase() == 'H')) {
      aTgt[i].xTrgPtr = trg;
      aTgt[i].style.display = bShow ? 'block' : 'none';
      trg.style.cursor = 'pointer';
      trg.xTgtPtr = aTgt[i];
      trg.onclick = trg_onClick;
    }  
  }
  
  // Private

  function trg_onClick()
  {
    var tgt = this.xTgtPtr.style;
    if (tgt.display == 'none') {
      tgt.display = 'block';
    }  
    else {
      tgt.display = 'none';
    }
  }

  // Public

  this.displayAll = function(bShow)
  {
    for (var i = 0; i < aTgt.length; ++i) {
      if (aTgt[i].xTrgPtr) {
        xDisplay(aTgt[i], bShow ? "block":"none");
      }
    }
  };

  // The unload listener is for IE's circular reference memory leak bug.
  this.onUnload = function()
  {
    if (!xIE4Up || !container || !aTgt) {return;}
    for (i = 0; i < aTgt.length; ++i) {
      trg = aTgt[i].xTrgPtr;
      if (trg) {
        if (trg.xTgtPtr) {
          trg.xTgtPtr.TrgPtr = null;
          trg.xTgtPtr = null;
        }
        trg.onclick = null;
      }
    }
  };
}
