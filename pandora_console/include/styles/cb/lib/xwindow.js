// xWindow, Copyright 2001-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xWindow(name, w, h, x, y, loc, men, res, scr, sta, too)
{
  var e='',c=',',xf='left=',yf='top='; this.n = name;
  if (document.layers) {xf='screenX='; yf='screenY=';}
  this.f = (w?'width='+w+c:e)+(h?'height='+h+c:e)+(x>=0?xf+x+c:e)+
    (y>=0?yf+y+c:e)+'location='+loc+',menubar='+men+',resizable='+res+
    ',scrollbars='+scr+',status='+sta+',toolbar='+too;
  this.opened = function() {return this.w && !this.w.closed;};
  this.close = function() {if(this.opened()) this.w.close();};
  this.focus = function() {if(this.opened()) this.w.focus();};
  this.load = function(sUrl) {
    if (this.opened()) this.w.location.href = sUrl;
    else this.w = window.open(sUrl,this.n,this.f);
    this.focus();
    return false;
  };
}

// Previous implementation:
// function xWindow(name, w, h, x, y, loc, men, res, scr, sta, too)
// {
//   var f = '';
//   if (w && h) {
//     if (document.layers) f = 'screenX=' + x + ',screenY=' + y;
//     else f = 'left=' + x + ',top=' + y;
//     f += ',width=' + w + ',height=' + h + ',';
//   }
//   f += ('location='+loc+',menubar='+men+',resizable='+res
//     +',scrollbars='+scr+',status='+sta+',toolbar='+too);
//   this.features = f;
//   this.name = name;
//   this.load = function(sUrl) {
//     if (this.wnd && !this.wnd.closed) this.wnd.location.href = sUrl;
//     else this.wnd = window.open(sUrl, this.name, this.features);
//     this.wnd.focus();
//     return false;
//   }
// }
