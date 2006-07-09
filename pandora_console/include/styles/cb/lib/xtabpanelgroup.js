// xTabPanelGroup, Copyright 2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTabPanelGroup(id, w, h, th, clsTP, clsTG, clsTD, clsTS) // object prototype
{
  // Private Methods

  function onClick() //r7
  {
    paint(this);
    return false;
  }
  function onFocus() //r7
  {
    paint(this);
  }
  function paint(tab)
  {
    tab.className = clsTS;
    xZIndex(tab, highZ++);
    xDisplay(panels[tab.xTabIndex], 'block'); //r6
  
    if (selectedIndex != tab.xTabIndex) {
      xDisplay(panels[selectedIndex], 'none'); //r6
      tabs[selectedIndex].className = clsTD;
  
      selectedIndex = tab.xTabIndex;
    }
  }

  // Public Methods

  this.select = function(n) //r7
  {
    if (n && n <= tabs.length) {
      var t = tabs[n-1];
      if (t.focus) t.focus();
      else t.onclick();
    }
  }

  this.onUnload = function()
  {
    if (xIE4Up) for (var i = 0; i < tabs.length; ++i) {tabs[i].onclick = null;}
  }

  // Constructor Code (note that all these vars are 'private')

  var panelGrp = xGetElementById(id);
  if (!panelGrp) { return null; }
  var panels = xGetElementsByClassName(clsTP, panelGrp);
  var tabs = xGetElementsByClassName(clsTD, panelGrp);
  var tabGrp = xGetElementsByClassName(clsTG, panelGrp);
  if (!panels || !tabs || !tabGrp || panels.length != tabs.length || tabGrp.length != 1) { return null; }
  var selectedIndex = 0, highZ, x = 0, i;
  xResizeTo(panelGrp, w, h);
  xResizeTo(tabGrp[0], w, th);
  xMoveTo(tabGrp[0], 0, 0);
  w -= 2; // remove border widths
  var tw = w / tabs.length;
  for (i = 0; i < tabs.length; ++i) {
    xResizeTo(tabs[i], tw, th); 
    xMoveTo(tabs[i], x, 0);
    x += tw;
    tabs[i].xTabIndex = i;
    tabs[i].onclick = onClick;
    tabs[i].onfocus = onFocus; //r7
    xDisplay(panels[i], 'none'); //r6
    xResizeTo(panels[i], w, h - th - 2); // -2 removes border widths
    xMoveTo(panels[i], 0, th);
  }
  highZ = i;
  tabs[0].onclick();
}
