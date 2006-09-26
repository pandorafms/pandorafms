// xTableCursor, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableCursor(id, inh, def, hov, sel) // object prototype
{
  var tbl = xGetElementById(id);
  if (tbl) {
    xTableIterate(tbl, init);
  }
  function init(obj, isRow)
  {
    if (isRow) {
      obj.className = def;
      obj.onmouseover = trOver;
      obj.onmouseout = trOut;
    }
    else {
      obj.className = inh;
      obj.onmouseover = tdOver;
      obj.onmouseout = tdOut;
    }
  }
  this.unload = function() { xTableIterate(tbl, done); };
  function done(o) { o.onmouseover = o.onmouseout = null; }
  function trOver() { this.className = hov; }
  function trOut() { this.className = def; }
  function tdOver() { this.className = sel; }
  function tdOut() { this.className = inh; }
}
