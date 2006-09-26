// xMenu2
// Copyright (C) 2002,2003,2004,2005 Michael Foster (cross-browser.com)
// X and xMenu2 are distributed under the terms of the LGPL (gnu.org)

var menu1, menu2, menu3, menu4;
window.onload = function() {
  // relative, under heading
  menu1 = new xMenu2(
    false, true, false,         // absolute, horizontal, floating,
    0, 0, 3,                    // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000000', '#333333',       // activeColor, inactiveColor,
    '#cccc99', '#cccc99',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );
  // top-right
  menu2 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    xClientWidth() - 75, 20, 5, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#ffccff', '#000080',       // activeColor, inactiveColor,
    '#FF6600', '#CC6600',       // activeBkgnd, inactiveBkgnd
    '#FF6600'                   // boxBkgnd
  );
  // bottom-left
  menu3 = new xMenu2(
    true, false, true,          // absolute, horizontal, floating,
    0, xClientHeight() - 80, 2, // menuX, menuY, menuZ
    0, [75,75,75,75], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000080', '#FF9999',       // activeColor, inactiveColor,
    '#00CCFF', '#0099FF',       // activeBkgnd, inactiveBkgnd
    '#00CCFF'                   // boxBkgnd
  );
  // bottom-right
  menu4 = new xMenu2(
    true, true, true,           // absolute, horizontal, floating,
    xClientWidth()-205, xClientHeight()-20, 4, // menuX, menuY, menuZ
    0, [75,50,40,40], 20,       // lblOffset, lblWidthsArray, lblHeight,
    [120,150,140,140],          // boxWidthsArray,
    '#000066', '#ffff66',       // activeColor, inactiveColor,
    '#cccc99', '#9999cc',       // activeBkgnd, inactiveBkgnd
    '#cccc99'                   // boxBkgnd
  );
  scrollListener(); // initial slide
  xAddEventListener(document, "mousemove", menuHideListener, false);
  xAddEventListener(window, "resize", resizeListener, false);
  xAddEventListener(window, "scroll", scrollListener, false);
}
function resizeListener(e) {
  if (xOp6Dn || xNN4) location.replace(location.href);
  else {
    menu1.paint();
    menu2.paint(xClientWidth() - 75, 20);
    menu3.paint(0, xClientHeight() - 60);
    menu4.paint(xClientWidth()-(3*75), xClientHeight()-20);
  }
}
function scrollListener(e) {
  var i,y;
  for (i=0; i < xFloatingMenus.length; ++i) {
    if (xFloatingMenus[i]) {
/*
      y = xScrollTop();
      if (y <= xFloatingMenus[i].y) y += xFloatingMenus[i].y;
      else if (i && xFloatingMenus[i].hz) y += xHeight('menu' + xFloatingMenus[i-1].n);
      xSlideTo('menu' + xFloatingMenus[i].n, xScrollLeft() + xFloatingMenus[i].x, y, 500);
*/
      xSlideTo('menu' + xFloatingMenus[i].n, xScrollLeft() + xFloatingMenus[i].x, xScrollTop() + xFloatingMenus[i].y, 500);
    }
  }
}

//// menu implementation

var xFloatingMenus = new Array(), xTotalMenus=0, xActiveMenu=null;

function xMenu2(
  absolute, horizontal, floating, menuX, menuY, menuZ, lblOffset,
  lblWidthsArray, lblHeight, boxWidthsArray,
  activeColor, inactiveColor,
  activeBkgnd, inactiveBkgnd,
  boxBkgnd
) {
  // properties
  this.n = ++xTotalMenus;
  this.abs = absolute;
  this.hz = horizontal;
  this.flt = floating;
  this.x = menuX;
  this.y = menuY;
  this.z = menuZ;
  this.lblW = lblWidthsArray;
  this.lblH = lblHeight;
  this.lblOfs = lblOffset;
  this.boxW = boxWidthsArray;
  this.ac = activeColor;
  this.ic = inactiveColor;
  this.ab = activeBkgnd;
  this.ib = inactiveBkgnd;
  this.bb = boxBkgnd;
  this.active = null;
  // methods
  this.paint = function(menuX, menuY) {
    var i=1, x, y, mnu, lbl, box;
    mnu = xGetElementById('menu'+this.n);
    if (!mnu) return;
    xZIndex(mnu, this.z);
    if (this.hz) {
//      xResizeTo(mnu,xClientWidth(),this.lblH);
//      xBackground(mnu, this.ib);
      y = 0;
      x = this.lblOfs;
    }
    else {
      y = this.lblOfs;
      x = 0;
    }
    if (arguments.length > 1) {
      this.x = menuX;
      this.y = menuY;
    }
    if (this.abs) {
      if (arguments.length > 1) {
        if (this.flt) xMoveTo(mnu, xScrollLeft() + menuX, xScrollTop() + menuY);
        else xMoveTo(mnu, menuX, menuY);
      }
    }
    lbl = xGetElementById('label'+this.n+""+i);
    while (lbl) {
      xResizeTo(lbl, this.lblW[i-1], this.lblH);
      xMoveTo(lbl, x, y);
      xColor(lbl, this.ic);
      xBackground(lbl, this.ib);
      xShow(lbl);
      lbl.menu = this;
      if (arguments.length==3) xAddEventListener(lbl, 'mouseover', menuShowListener, false);
      lbl.box = xGetElementById('box'+this.n+""+i);
      if (lbl.box) {
        xWidth(lbl.box, this.boxW[i-1]);
        var bx, by;
        if (this.hz) { // horizontal
          if (xPageX(lbl) + this.boxW[i-1] > xScrollLeft() + xClientWidth()) { bx = x - (this.boxW[i-1] - this.lblW[i-1]); }
          else { bx = x; }
          if (xPageY(lbl) + this.lblH + xHeight(lbl.box) > xScrollTop() + xClientHeight()) { by = y - xHeight(lbl.box); }
          else { by = y + this.lblH; }
        }
        else { // vertical
          if (xPageX(lbl) + this.lblW[i-1] + this.boxW[i-1] > xScrollLeft() + xClientWidth()) { bx = x - this.boxW[i-1]; }
          else { bx = x + this.lblW[i-1]; }
          if (xPageY(lbl) + xHeight(lbl.box) > xScrollTop() + xClientHeight()) { by = y + this.lblH - xHeight(lbl.box); }
          else { by = y; }
        }
        xMoveTo(lbl.box, bx, by);
        lbl.box.lbl = lbl;
        xZIndex(lbl, i);
        xZIndex(lbl.box, i);
        xBackground(lbl.box, this.bb);
        xHide(lbl.box);
      }
      if (this.hz) x += this.lblW[i-1];
      else y += this.lblH;
      lbl = xGetElementById('label'+this.n+""+(++i)); // for next iteration
    }
    xShow(mnu);
  }
  // constructor code
  this.paint(this.x, this.y, 'init');
  if (this.flt) xFloatingMenus[this.n-1] = this;
}
function menuShowListener(e) {
  var evt = new xEvent(e);
  var lbl = evt.target;
  while (lbl && !lbl.menu) { lbl = xParent(lbl); }
  if (!lbl) return;
  var menu = lbl.menu;
  if (!menu) return;
  if (menu.active) {
    if (menu.active == lbl) return;
    xHide(menu.active.box);
    xColor(menu.active, menu.ic);
    xBackground(menu.active, menu.ib);
    if (menu.active.box.lbl.className) menu.active.box.lbl.className = 'mLabel'; // experiment
  }
  if (xActiveMenu && xActiveMenu != menu) { menuHide(xActiveMenu); }
  if (menu.hz && xNN4) { // hack!
    xBackground('menu'+menu.n,null);
    xResizeTo('menu'+menu.n,xClientWidth(),xClientHeight());
  }
  if (lbl.className) lbl.className = 'mLabelOver'; // experiment
  xShow(lbl.box);
  xColor(lbl, menu.ac);
  xBackground(lbl, menu.ab);
  menu.active = lbl;
  xActiveMenu = menu;
}
var tmr;
function menuHideListener(e) {
  var evt = new xEvent(e);
  var ele = evt.target;
  while (ele && !ele.lbl && !ele.box) { ele = xParent(ele); }
  if (xActiveMenu && xActiveMenu.active && !ele && !tmr) tmr = setTimeout('menuHide()', 500);  // experiment
  else if (ele && tmr) {clearTimeout(tmr); tmr = null;}  // experiment

//  if (xActiveMenu && xActiveMenu.active && !ele) menuHide(xActiveMenu);
}
function menuHide(menu) {
  
  if (!menu) menu = xActiveMenu;  // experiment
  if (!menu || !menu.active || !menu.active.box) return;
  
  xHide(menu.active.box);
  xColor(menu.active, menu.ic);
  xBackground(menu.active, menu.ib);
  if (menu.active.box.lbl.className) menu.active.box.lbl.className = 'mLabel'; // experiment
  menu.active = null;
  xActiveMenu = null;
  if (menu.hz && xNN4) { // hack!
    xResizeTo('menu'+menu.n,xClientWidth(),menu.lblH + 2);
    xBackground('menu'+menu.n, menu.ib);
  }
}

function xName(e) {
  if (!e) return e;
  else if (e.id && e.id != "") return e.id;
  else if (e.nodeName && e.nodeName != "") return e.nodeName;
  else if (e.tagName && e.tagName != "") return e.tagName;
  else return e;
}

