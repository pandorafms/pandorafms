// xBar, Copyright 2003,2004,2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

// Bar-Graph Object

function xBar(dir,                // direction, 'ltr', 'rtl', 'ttb', or 'btt'
              conStyle, barStyle) // container and bar style class names
{
  //// Public Properties

  this.value = 0; // current value, read-only

  //// Public Methods

  // Update current value
  this.update = function(v)
  {
    if (v < 0) v = 0;
    else if (v > this.inMax) v = this.inMax;
    this.con.title = this.bar.title = this.value = v;
    switch(this.dir) {
      case 'ltr': // left to right
      v = this.scale(v, this.w);
      xLeft(this.bar, v - this.w);
      break;
      case 'rtl': // right to left
      v = this.scale(v, this.w);
      xLeft(this.bar, this.w - v);
      break;
      case 'btt': // bottom to top
      v = this.scale(v, this.h);
      xTop(this.bar, this.h - v);
      break;
      case 'ttb': // top to bottom
      v = this.scale(v, this.h);
      xTop(this.bar, v - this.h);
      break;
    }
  };

  // Change position and/or size
  this.paint = function(x, y, // container position
                        w, h) // container size
  {
    if (xNum(x)) this.x = x;
    if (xNum(y)) this.y = y;
    if (xNum(w)) this.w = w;
    if (xNum(h)) this.h = h;
    xResizeTo(this.con, this.w, this.h);
    xMoveTo(this.con, this.x, this.y);
    xResizeTo(this.bar, this.w, this.h);
    xMoveTo(this.bar, 0, 0);
  };

  // Change scale and/or start value
  this.reset = function(max, start) // non-scaled values
  {
    if (xNum(max)) this.inMax = max;
    if (xNum(start)) this.start = start;
    this.update(this.start);
  };

  //// Private Methods
  
  this.scale = function(v, outMax)
  {
    return Math.round(xLinearScale(v, 0, this.inMax, 0, outMax));
  };

  //// Private Properties

  this.dir = dir;
  this.x = 0;
  this.y = 0;
  this.w = 100;
  this.h = 100;
  this.inMax = 100;
  this.start = 0;
  this.conStyle = conStyle;
  this.barStyle = barStyle;

  //// Constructor

  // Create container
  this.con = document.createElement('DIV');
  this.con.className = this.conStyle;
  // Create bar
  this.bar = document.createElement('DIV');
  this.bar.className = this.barStyle;
  // Insert in object tree
  this.con.appendChild(this.bar);
  document.body.appendChild(this.con);

} // end xBar
