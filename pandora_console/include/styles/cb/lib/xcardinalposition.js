// xCardinalPosition, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xCardinalPosition(e, cp, margin, outside)
{
  if(!(e=xGetElementById(e))) return;
  if (typeof(cp)!='string'){window.status='xCardinalPosition error: cp=' + cp + ', id=' + e.id; return;}
  var x=xLeft(e), y=xTop(e), w=xWidth(e), h=xHeight(e);
  var pw,ph,p = xParent(e);
  if (p == document || p.nodeName.toLowerCase() == 'html') {pw = xClientWidth(); ph = xClientHeight();}
  else {pw=xWidth(p); ph=xHeight(p);}
  var sx=xScrollLeft(p), sy=xScrollTop(p);
  var right=sx + pw, bottom=sy + ph;
  var cenLeft=sx + Math.floor((pw-w)/2), cenTop=sy + Math.floor((ph-h)/2);
  if (!margin) margin=0;
  else{
    if (outside) margin=-margin;
    sx +=margin; sy +=margin; right -=margin; bottom -=margin;
  }
  switch (cp.toLowerCase()){
    case 'n': x=cenLeft; if (outside) y=sy - h; else y=sy; break;
    case 'ne': if (outside){x=right; y=sy - h;}else{x=right - w; y=sy;}break;
    case 'e': y=cenTop; if (outside) x=right; else x=right - w; break;
    case 'se': if (outside){x=right; y=bottom;}else{x=right - w; y=bottom - h}break;
    case 's': x=cenLeft; if (outside) y=sy - h; else y=bottom - h; break;
    case 'sw': if (outside){x=sx - w; y=bottom;}else{x=sx; y=bottom - h;}break;
    case 'w': y=cenTop; if (outside) x=sx - w; else x=sx; break;
    case 'nw': if (outside){x=sx - w; y=sy - h;}else{x=sx; y=sy;}break;
    case 'cen': x=cenLeft; y=cenTop; break;
    case 'cenh': x=cenLeft; break;
    case 'cenv': y=cenTop; break;
  }
  var o = new Object();
  o.x = x; o.y = y;
  return o;
}
