// xTableRowDisplay, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableRowDisplay(bShow, sec, nRow)
{
  sec = xGetElementById(sec);
  if (sec && nRow < sec.rows.length) {
    sec.rows[nRow].style.display = bShow ? '' : 'none';
  }
}
