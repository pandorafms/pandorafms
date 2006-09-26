// xAppendChild, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xAppendChild(oParent, oChild)
{
  if (oParent.appendChild) return oParent.appendChild(oChild);
  else return null;
}
