// xSelect, Copyright 2004-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xSelect(sId, fnSubOnChange)
{
  //// Properties
  
  this.ready = false;
  
  //// Constructor

  // Check for required browser objects
  var s0 = xGetElementById(sId);
  if (!s0 || !s0.firstChild || !s0.nodeName || !document.createElement || !s0.form || !s0.form.appendChild)
  {
    return;
  }
  
  // Create main category SELECT element
  var s1 = document.createElement('SELECT');
  s1.id = sId + '_main';
  s1.display = 'block'; // for opera bug?
  s1.style.position = 'absolute';
  s1.xSelObj = this;
  s1.xSelData = new Array();
  // append s1 to s0's form
  s0.form.appendChild(s1);

  // Iterate thru s0 and fill array.
  // For each OPTGROUP, a[og][0] == OPTGROUP label, and...
  // a[og][n] = innerHTML of OPTION n.
  var ig=0, io, op, og, a = s1.xSelData;
  og = s0.firstChild;
  while (og) {
    if (og.nodeName.toLowerCase() == 'optgroup') {
      io = 0;
      a[ig] = new Array();
      a[ig][io] = og.label;
      op = og.firstChild;
      while (op) {
        if (op.nodeName.toLowerCase() == 'option') {
          io++;
          a[ig][io] = op.innerHTML;
        }
        op = op.nextSibling;
      }
      ig++;
    }
    og = og.nextSibling;
  }

  // in s1 insert a new OPTION for each OPTGROUP in s0
  for (ig=0; ig<a.length; ++ig) {
    op = new Option(a[ig][0]);
    s1.options[ig] = op;
  }
  
  // Create sub-category SELECT element
  var s2 = document.createElement('SELECT');
  s2.id = sId + '_sub';
  s2.display = 'block'; // for opera bug?
  s2.style.position = 'absolute';
  s2.xSelMain = s1;
  s1.xSelSub = s2;
  // append s2 to s0's form
  s0.form.appendChild(s2);
  
  // Add event listeners
  s1.onchange = xSelectMain_OnChange;
  s2.onchange = fnSubOnChange;
  // Hide s0. Position and show s1 where s0 was.
  // Position and show s2 to the right of s1.
  xHide(s0);
//alert(s1.offsetParent.nodeName + '\n' + xPageX(s0) + ', ' + xPageY(s0) + '\n' + xOffsetLeft(s0) + ', ' + xOffsetTop(s0));//////////
  xMoveTo(s1, xOffsetLeft(s0), xOffsetTop(s0));
//  xMoveTo(s1, xOffsetLeft(s0), xPageY(s0));
//  xMoveTo(s1, s0.offsetLeft, xPageY(s0));
//  xMoveTo(s1, s0.offsetLeft, s0.offsetTop);
  xShow(s1);
  xMoveTo(s2, xOffsetLeft(s0) + xWidth(s1), xOffsetTop(s0));
//  xMoveTo(s2, s0.offsetLeft + xWidth(s1), xPageY(s0));
//  xMoveTo(s2, s0.offsetLeft + xWidth(s1), s0.offsetTop);
  xShow(s2);

  // Initialize s2
  s1.onchange();
  // Ready to rock!
  this.ready = true;
  
} // end xSelect object prototype

function xSelectMain_OnChange()
{
  var io, s2 = this.xSelSub;
  // clear existing
  for (io=0; io<s2.options.length; ++io) {
    s2.options[io] = null;
  }
  // insert new
  var a = this.xSelData, ig = this.selectedIndex;
  for (io=1; io<a[ig].length; ++io) {
    op = new Option(a[ig][io]);
    s2.options[io-1] = op;
  }
}
