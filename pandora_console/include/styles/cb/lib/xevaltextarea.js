// xEvalTextarea, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xEvalTextarea()
{
  var f = document.createElement('FORM');
  f.onsubmit = 'return false';
  var t = document.createElement('TEXTAREA');
  t.id='xDebugTA';
  t.name='xDebugTA';
  t.rows='20';
  t.cols='60';
  var b = document.createElement('INPUT');
  b.type = 'button';
  b.value = 'Evaluate';
  b.onclick = function() {eval(this.form.xDebugTA.value);};
  f.appendChild(t);
  f.appendChild(b);
  document.body.appendChild(f);
}
