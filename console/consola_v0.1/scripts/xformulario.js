//<!--// Pandora - the Free monitoring system
// ====================================
// Copyright (c) Jonathan Barajas, jonathan.barajas[AT]gmail[DOT]com
// Copyright (c) INDISEG S.L, contacto[AT]indiseg[DOT]net www.indiseg.net

// This program is free software; you can redistribute it and/or
// modify it under the terms of the GNU General Public License
// as published by the Free Software Foundation; either version 2
// of the License, or (at your option) any later version.
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.-->

function FormSetup()
{

var cBtn = xGetElementById('formCerrBtn');
var xForm = xGetElementById('xForm');

posLeft = 0;
posTop = 0;

if (xClientWidth()>xWidth(xForm))
{
posLeft = xClientWidth()/2 -xWidth(xForm)/2;
}

if (xClientHeight()>xHeight(xForm))
{
posTop = xClientHeight()/2 -xHeight(xForm)/2
}

  xMoveTo('xForm',posLeft ,posTop);
formPaint();
  xEnableDrag('xFormBar', formOnDragStart, formOnDrag, null);
  xZIndex('xForm', highZ++);
 cBtn.onclick = cBtnOnClick;
  xShow('xForm');
}
function formPaint()
{
  var xForm = xGetElementById('xForm');
  var cBtn = xGetElementById('formCerrBtn');
  xMoveTo(cBtn, xWidth(xForm) - xWidth(cBtn), 0);

}
function formOnDragStart(ele, mx, my)
{
  xZIndex('xForm', highZ++);
}
function formOnDrag(ele, mdx, mdy)
{
  xMoveTo('xForm', xLeft('xForm') + mdx, xTop('xForm') + mdy);
}
function cBtnOnClick()
{
var cBtn = xGetElementById('formCerrBtn');
var xForm = xGetElementById('xForm');
xHide(xForm);

}
