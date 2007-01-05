<!--// Pandora - the Free monitoring system
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


// object-oriented version - see drag1.php for a procedural version

function xFenster(eleId, iniX, iniY, barId, resBtnId, maxBtnId) // object prototype
{
  // Private Properties
  var me = this;
  var ele = xGetElementById(eleId);
  var rBtn = xGetElementById(resBtnId);
  var mBtn = xGetElementById(maxBtnId);
  var x, y, w, h, maximized = false;
  // Public Methods
  this.onunload = function()
  {
    if (xIE4Up) { // clear cir refs
      xDisableDrag(barId);
      xDisableDrag(rBtn);
      mBtn.onclick = ele.onmousedown = null;
      me = ele = rBtn = mBtn = null;
    }
  }
  this.paint = function()
  {
    xMoveTo(rBtn, xWidth(ele) - xWidth(rBtn), xHeight(ele) - xHeight(rBtn));
    xMoveTo(mBtn, xWidth(ele) - xWidth(rBtn), 0);
  }
  // Private Event Listeners
  function barOnDrag(e, mdx, mdy)
  {

    xMoveTo(ele, xLeft(ele) + mdx, xTop(ele) + mdy);
  }
//   function resOnDrag(e, mdx, mdy)
//   {
//     xResizeTo(ele, xWidth(ele) + mdx, xHeight(ele) + mdy);
//     me.paint();
//   }
  function fenOnDrag(e, mdx, mdy)
  {
    xMoveTo(ele, xLeft(ele) + mdx, xTop(ele) + mdy);

 	var params=eleId.split("_");
        var idObjetoMover=params[2];
	for (var key in aRelacionesObjetos)
	{
 		var objetos=key.split("_");
 		var objeto1=objetos[2];
 		var objeto2=objetos[3];
		
 		if (objeto1 == idObjetoMover )
 		{	
// 			alert(objetos[0]+objetos[1]+objetos[2]+objetos[3]);	
			ele2=xGetElementById('fen_'+objetos[1]+'_'+objeto2);
			
 			aRelacionesObjetos[key].clear();
			aRelacionesObjetos[key].drawLine(xLeft(ele) + mdx, xTop(ele) + mdy,xLeft(ele2),xTop(ele2));
			aRelacionesObjetos[key].paint();
	
 		} else if (objeto2 == idObjetoMover )
		{
			ele1=xGetElementById('fen_'+objetos[1]+'_'+objeto1);
			
 			aRelacionesObjetos[key].clear();
			aRelacionesObjetos[key].drawLine(xLeft(ele1),xTop(ele1),xLeft(ele) + mdx, xTop(ele) + mdy);
			aRelacionesObjetos[key].paint();
		}
		
	}
	
//             			var pos = params[i].indexOf("=");
//             			var name = params[i].substring(0, pos);
//             			var value = params[i].substring(pos + 1);
// jg.clear();
// jg.setColor('#ff0000'); // red
//   jg.drawLine(10, 113, xLeft(ele) + mdx, xTop(ele) + mdy); // co-ordinates related to 'myCanvas'
// jg.paint();

  }
  function fenOnMousedown()
  {
    xZIndex(ele, xFenster.z++);
  }

function fenOnMouseup()
  {
<!--     alert("Subelooo!!"); -->
  }
//   function maxOnClick()
//   {
//     if (maximized) {
//       maximized = false;
//       xResizeTo(ele, w, h);
//       xMoveTo(ele, x, y);
//     }
//     else {
//       w = xWidth(ele);
//       h = xHeight(ele);
//       x = xLeft(ele);
//       y = xTop(ele);
//       xMoveTo(ele, xScrollLeft(), xScrollTop());
//       maximized = true;
//       xResizeTo(ele, xClientWidth(), xClientHeight());
//     }
//     me.paint();
//   }

  this.dameX=function()
  {
	return xLeft(ele);
  }
  this.dameY=function()
  {
	return xTop(ele);
  }


  this.dameCentroX=function()
	{
	posicion = xLeft(ele) + (xWidth(ele)/2);
	return posicion ; // xWidth siempre da 0 con lo que hace imposible calcular el centro .. es un problema que hay que solucionar
	}
  this.dameCentroY=function()
	{
	posicion = xTop(ele) + (xHeight(ele)/2);
	return posicion; // xHeight siempre da 0 con lo que hace imposible calcular el centro.. es un problema que hay que solucionar
	}

  // Constructor Code
  xFenster.z++;
  xMoveTo(ele, iniX, iniY);
  this.paint();
  xEnableDrag(barId, null, barOnDrag, null);
//   xEnableDrag(rBtn, null, resOnDrag, null);
  xEnableDrag(ele, null, fenOnDrag, null);
//   mBtn.onclick = maxOnClick;
  ele.onmousedown = fenOnMousedown;
  ele.onmouseup = fenOnMouseup;
  xShow(ele);
} // end xFenster object prototype

xFenster.z = 0; // xFenster static property

