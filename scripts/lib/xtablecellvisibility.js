// xTableCellVisibility, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTableCellVisibility(bShow, sec, nRow, nCol)
{
  sec = xGetElementById(sec);
  if (sec && nRow < sec.rows.length && nCol < sec.rows[nRow].cells.length) {
    sec.rows[nRow].cells[nCol].style.visibility = bShow ? 'visible' : 'hidden';
  }
}
