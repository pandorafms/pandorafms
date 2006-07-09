// xVisibility, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xVisibility(e, bShow)
{
  if(!(e=xGetElementById(e))) return null;
  if(e.style && xDef(e.style.visibility)) {
    if (xDef(bShow)) e.style.visibility = bShow ? 'visible' : 'hidden';
    return e.style.visibility;
  }
  return null;
}

//function xVisibility(e,s)
//{
//  if(!(e=xGetElementById(e))) return null;
//  var v = 'visible', h = 'hidden';
//  if(e.style && xDef(e.style.visibility)) {
//    if (xDef(s)) {
//      // try to maintain backwards compatibility (???)
//      if (xStr(s)) e.style.visibility = s;
//      else e.style.visibility = s ? v : h;
//    }
//    return e.style.visibility;
//    // or...
//    // if (e.style.visibility.length) return e.style.visibility;
//    // else return xGetComputedStyle(e, 'visibility');
//  }
//  else if (xDef(e.visibility)) { // NN4
//    if (xDef(s)) {
//      // try to maintain backwards compatibility
//      if (xStr(s)) e.visibility = (s == v) ? 'show' : 'hide';
//      else e.visibility = s ? v : h;
//    }
//    return (e.visibility == 'show') ? v : h;
//  }
//  return null;
//}
