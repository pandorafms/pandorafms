// xInclude, Copyright 2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xInclude(url1, url2, etc)
{
  if (document.getElementById || document.all || document.layers) { // minimum dhtml support required
    var h, f, i, j, a, n, inc;

    for (var i=0; i<arguments.length; ++i) { // loop thru all the url arguments

      h = ''; // html (script or link element) to be written into the document
      f = arguments[i].toLowerCase(); // f is current url in lowercase
      inc = false; // if true the file has already been included

      // Extract the filename from the url

      // Should I extract the file name? What if there are two files with the same name 
      // but in different directories? If I don't extract it what about: '../foo.js' and '../../foo.js' ?

      a = f.split('/');
      n = a[a.length-1]; // the file name

      // loop thru the list to see if this file has already been included
      for (j = 0; j < xIncludeList.length; ++j) {
        if (n == xIncludeList[j]) { // should I use '==' or a string cmp func?
          inc = true;
          break;
        }
      }

      if (!inc) { // if the file has not yet been included

        xIncludeList[xIncludeList.length] = n; // add it to the list of included files

        // is it a .js file?
        if (f.indexOf('.js') != -1) {
          if (xNN4) { // if nn4 use nn4 versions of certain lib files
            var c='x_core', e='x_event', d='x_dom', n='_n4';
            if (f.indexOf(c) != -1) { f = f.replace(c, c+n); }
            else if (f.indexOf(e) != -1) { f = f.replace(e, e+n); }
            else if (f.indexOf(d) != -1) { f = f.replace(d, d+n); }
          }
          h = "<script type='text/javascript' src='" + f + "'></script>";
        }

        // else is it a .css file?
        else if (f.indexOf('.css') != -1) { // CSS file
          h = "<link rel='stylesheet' type='text/css' href='" + f + "'>";
        }    
        
        // write the link or script element into the document
        if (h.length) { document.writeln(h); }

      } // end if (!inc)
    } // end outer for
    return true;
  } // end if (min dhtml support)
  return false;
}
