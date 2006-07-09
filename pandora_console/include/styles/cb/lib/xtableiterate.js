// xTableIterate, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableIterate(sec, fnCallback, data)
{
  var r, c;
  sec = xGetElementById(sec);
  if (!sec || !fnCallback) { return; }
  for (r = 0; r < sec.rows.length; ++r) {
    if (false == fnCallback(sec.rows[r], true, r, c, data)) { return; }
    for (c = 0; c < sec.rows[r].cells.length; ++c) {
      if (false == fnCallback(sec.rows[r].cells[c], false, r, c, data)) { return; }
    }
  }
}

