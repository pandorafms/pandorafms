// xCapitalize, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// Capitalize the first letter of every word in str.

function xCapitalize(str)
{
  var i, c, wd, s='', cap = true;
  
  for (i = 0; i < str.length; ++i) {
    c = str.charAt(i);
    wd = isWordDelim(c);
    if (wd) {
      cap = true;
    }  
    if (cap && !wd) {
      c = c.toUpperCase();
      cap = false;
    }
    s += c;
  }
  return s;

  function isWordDelim(c)
  {
    // add other word delimiters as needed
    // (for example '-' and other punctuation)
    return c == ' ' || c == '\n' || c == '\t';
  }
}
