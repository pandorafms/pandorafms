// xGetElePropsArray, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xGetElePropsArray(ele, eleName)
{
  var u = 'undefined';
  var i = 0, a = new Array();
  
  nv('Element', eleName);
  nv('id', (xDef(ele.id) ? ele.id : u));
  nv('tagName', (xDef(ele.tagName) ? ele.tagName : u));

  nv('xWidth()', xWidth(ele));
  nv('style.width', (xDef(ele.style) && xDef(ele.style.width) ? ele.style.width : u));
  nv('offsetWidth', (xDef(ele.offsetWidth) ? ele.offsetWidth : u));
  nv('scrollWidth', (xDef(ele.offsetWidth) ? ele.offsetWidth : u));
  nv('clientWidth', (xDef(ele.clientWidth) ? ele.clientWidth : u));

  nv('xHeight()', xHeight(ele));
  nv('style.height', (xDef(ele.style) && xDef(ele.style.height) ? ele.style.height : u));
  nv('offsetHeight', (xDef(ele.offsetHeight) ? ele.offsetHeight : u));
  nv('scrollHeight', (xDef(ele.offsetHeight) ? ele.offsetHeight : u));
  nv('clientHeight', (xDef(ele.clientHeight) ? ele.clientHeight : u));

  nv('xLeft()', xLeft(ele));
  nv('style.left', (xDef(ele.style) && xDef(ele.style.left) ? ele.style.left : u));
  nv('offsetLeft', (xDef(ele.offsetLeft) ? ele.offsetLeft : u));
  nv('style.pixelLeft', (xDef(ele.style) && xDef(ele.style.pixelLeft) ? ele.style.pixelLeft : u));

  nv('xTop()', xTop(ele));
  nv('style.top', (xDef(ele.style) && xDef(ele.style.top) ? ele.style.top : u));
  nv('offsetTop', (xDef(ele.offsetTop) ? ele.offsetTop : u));
  nv('style.pixelTop', (xDef(ele.style) && xDef(ele.style.pixelTop) ? ele.style.pixelTop : u));

  nv('', '');
  nv('xGetComputedStyle()', '');

  nv('top');
  nv('right');
  nv('bottom');
  nv('left');

  nv('width');
  nv('height');

  nv('color');
  nv('background-color');
  nv('font-family');
  nv('font-size');
  nv('text-align');
  nv('line-height');
  nv('content');
  
  nv('float');
  nv('clear');

  nv('margin');
  nv('padding');
  nv('padding-top');
  nv('padding-right');
  nv('padding-bottom');
  nv('padding-left');

  nv('border-top-width');
  nv('border-right-width');
  nv('border-bottom-width');
  nv('border-left-width');

  nv('position');
  nv('overflow');
  nv('visibility');
  nv('display');
  nv('z-index');
  nv('clip');
  nv('cursor');

  return a;

  function nv(name, value)
  {
    a[i] = new Object();
    a[i].name = name;
    a[i].value = typeof(value)=='undefined' ? xGetComputedStyle(ele, name) : value;
    ++i;
  }
}
