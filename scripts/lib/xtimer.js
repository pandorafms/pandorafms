// xTimer, Copyright 2003-2005 Michael Foster (Cross-Browser.com)
// Part of X, a Cross-Browser Javascript Library, Distributed under the terms of the GNU LGPL

function xTimerMgr()
{
  this.timers = new Array();
}

// xTimerMgr Methods
xTimerMgr.prototype.set = function(type, obj, sMethod, uTime, data) // type: 'interval' or 'timeout'
{
  return (this.timers[this.timers.length] = new xTimerObj(type, obj, sMethod, uTime, data));
};
xTimerMgr.prototype.run = function()
{
  var i, t, d = new Date(), now = d.getTime();
  for (i = 0; i < this.timers.length; ++i) {
    t = this.timers[i];
    if (t && t.running) {
      t.elapsed = now - t.time0;
      if (t.elapsed >= t.preset) { // timer event on t
        t.obj[t.mthd](t); // pass listener this xTimerObj
        if (t.type.charAt(0) == 'i') { t.time0 = now; }
        else { t.stop(); }
      }  
    }
  }
};

// Object Prototype used only by xTimerMgr
function xTimerObj(type, obj, mthd, preset, data)
{
  // Public Properties
  this.data = data;
  // Read-only Properties
  this.type = type; // 'interval' or 'timeout'
  this.obj = obj;
  this.mthd = mthd; // string
  this.preset = preset;
  this.reset();
} // end xTimerObj constructor
// xTimerObj Methods
xTimerObj.prototype.stop = function() { this.running = false; };
xTimerObj.prototype.start = function() { this.running = true; }; // continue after a stop
xTimerObj.prototype.reset = function()
{
  var d = new Date();
  this.time0 = d.getTime();
  this.elapsed = 0;
  this.running = true;
};

var xTimer = new xTimerMgr(); // applications assume global name is 'xTimer'
setInterval('xTimer.run()', 250);
