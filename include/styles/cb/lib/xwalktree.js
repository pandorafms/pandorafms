// xWalkTree, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWalkTree(n, f)
{
  f(n);
  for (var c = n.firstChild; c; c = c.nextSibling) {
    if (c.nodeType == 1) xWalkTree(c, f);
  }
}

// original implementation:
// function xWalkTree(oNode, fnVisit)
// {
//   if (oNode) {
//     if (oNode.nodeType == 1) {fnVisit(oNode);}
//     for (var c = oNode.firstChild; c; c = c.nextSibling) {
//       xWalkTree(c, fnVisit);
//     }
//   }
// }
