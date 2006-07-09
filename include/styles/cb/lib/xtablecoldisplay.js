// xTableColDisplay, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableColDisplay(bShow, sec, nCol)
{
  var r;
  sec = xGetElementById(sec);
  if (sec && nCol < sec.rows[0].cells.length) {
    for (r = 0; r < sec.rows.length; ++r) {
      sec.rows[r].cells[nCol].style.display = bShow ? '' : 'none';
    }
  }
}
