

function FormSetup()
{

var cBtn = xGetElementById('formCerrBtn');

  xMoveTo('xForm', parseInt(menuLeft),parseInt(menuTop));
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
