/*
© Copyright 2006-2007 Apple Inc.  All rights reserved.

IMPORTANT:  This Apple software ("Apple Software") is supplied to you in consideration of your agreement to the following terms. Your use, installation and/or redistribution of this Apple Software constitutes acceptance of these terms. If you do not agree with these terms, please do not use, install, or redistribute this Apple Software.

Provided you comply with all of the following terms, Apple grants you a personal, non-exclusive license, under Apple’s copyrights in the Apple Software, to use, reproduce, and redistribute the Apple Software for the sole purpose of creating Dashboard widgets for Mac OS X. If you redistribute the Apple Software, you must retain this entire notice in all such redistributions.

You may not use the name, trademarks, service marks or logos of Apple to endorse or promote products that include the Apple Software without the prior written permission of Apple. Except as expressly stated in this notice, no other rights or licenses, express or implied, are granted by Apple herein, including but not limited to any patent rights that may be infringed by your products that incorporate the Apple Software or by other works in which the Apple Software may be incorporated.

The Apple Software is provided on an "AS IS" basis.  APPLE MAKES NO WARRANTIES, EXPRESS OR IMPLIED, INCLUDING WITHOUT LIMITATION THE IMPLIED WARRANTIES OF NON-INFRINGEMENT, MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE, REGARDING THE APPPLE SOFTWARE OR ITS USE AND OPERATION ALONE OR IN COMBINATION WITH YOUR PRODUCTS.

IN NO EVENT SHALL APPLE BE LIABLE FOR ANY SPECIAL, INDIRECT, INCIDENTAL OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) ARISING IN ANY WAY OUT OF THE USE, REPRODUCTION, AND/OR DISTRIBUTION OF THE APPLE SOFTWARE, HOWEVER CAUSED AND WHETHER UNDER THEORY OF CONTRACT, TORT (INCLUDING NEGLIGENCE), STRICT LIABILITY OR OTHERWISE, EVEN IF APPLE HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

function CreateGauge(gaugeID, spec)
{
	var gaugeElement = document.getElementById(gaugeID);
	if (!gaugeElement.loaded) 
	{
		gaugeElement.loaded = true;
		var onchanged = spec.onchange || null;
		try { onchanged = eval(onchanged); } catch (e) { onchanged = null; }
		gaugeElement.object = new Gauge(gaugeElement, spec.value || 0, spec.minValue || 0, spec.maxValue || 0, spec.onValue || 0, spec.warningValue || 0, spec.criticalValue || 0, spec.startAngle || 0, spec.stopAngle || 0, spec.pivotOffsetX || 0, spec.pivotOffsetY || 0, spec.pointerReach || 0, spec.interactive || false, spec.continuous || false, spec.imagePointer || null, spec.imageOff || null, spec.imageOn || null, spec.imageWarning || null, spec.imageCritical || null, onchanged);
		return gaugeElement.object;
	}
}

function Gauge(gaugeElement, value, minValue, maxValue, onValue, warningValue, criticalValue, startAngle, stopAngle, pivotOffsetX, pivotOffsetY, pointerReach, interactive, continuous, imagePointer, imageOff, imageOn, imageWarning, imageCritical, onchanged)
{
	/* public properties */
	// These are read-write. Set them as needed.
	this.onchanged = onchanged;
	this.continuous = continuous; // Fire onchanged live, as opposed to onmouseup
	
	// These are read-only. Use the setter functions to set them.
	this.value = value;
	
	/* Internal objects */
	var element = document.createElement("canvas");
	gaugeElement.appendChild(element);
	this._canvas = element;
		
	this._init(value, minValue, maxValue, onValue, warningValue, criticalValue, startAngle, stopAngle, pivotOffsetX, pivotOffsetY, pointerReach, interactive, imagePointer, imageOff, imageOn, imageWarning, imageCritical);
}

Gauge.prototype._init = function(value, minValue, maxValue, onValue, warningValue, criticalValue, startAngle, stopAngle, pivotOffsetX, pivotOffsetY, pointerReach, interactive, imagePointer, imageOff, imageOn, imageWarning, imageCritical)
{
	// For JavaScript event handlers
	var _self = this;
	
	this.minValue = minValue;
	this.maxValue = maxValue;
	this.onValue = onValue;
	this.warningValue = warningValue;
	this.criticalValue = criticalValue;
	this.pivotOffsetX = pivotOffsetX;
	this.pivotOffsetY = pivotOffsetY;
	this.pointerReach = pointerReach;
	this.interactive = interactive;
	
	if (startAngle == null)
		startAngle = 0;
	if (stopAngle == null)
		stopAngle = 360;
	this.startAngle = startAngle;
	this.startAngleRad = (2 * Math.PI) * startAngle / 360;
	this.stopAngle = stopAngle;
	this.stopAngleRad = (2 * Math.PI) * stopAngle / 360;

	this._captureEventHandler = function(event) { _self._captureEvent(event); };
	this._mousedownCanvasHandler = function(event) { _self._mousedownCanvas(event); };
	this._mousemoveCanvasHandler = function(event) { _self._mousemoveCanvas(event); };
	this._mouseupCanvasHandler = function(event) { _self._mouseupCanvas(event); };
	
	var style = null;

	this._imageOnPath = imageOn == null ? "Images/GaugeOn.png" : imageOn;
	this._imageOffPath = imageOff == null ? "Images/GaugeOff.png" : imageOff;
	this._imageWarningPath = imageWarning == null ? "Images/GaugeWarning.png" : imageWarning;
	this._imageCriticalPath = imageCritical == null ? "Images/GaugeCritical.png" : imageCritical;
	this._imagePointerPath = imagePointer == null ? "Images/GaugePointer.png" : imagePointer;

	// Add event listeners
	if (this.interactive)
	{
		this._canvas.addEventListener("mousedown", this._mousedownCanvasHandler, true);
		this._canvas.style.appleDashboardRegion = "dashboard-region(control rectangle)";
	}

	this._onPreLoadComplete = function () { _self.refresh(); };
	this.preLoadImages();
}

Gauge.prototype.preLoadComplete = function()
{
	if (--this._imagesToPreLoad == 0 && this._onPreLoadComplete)
		this._onPreLoadComplete();
}

Gauge.prototype.preLoadAnImage = function(imagePath, propertyKey)
{
	var _self = this;

	var imageObject = new Image();
	imageObject.onload = function () { _self.preLoadComplete(); };
	imageObject.src = imagePath;
	this[propertyKey] = imageObject;
}

Gauge.prototype.preLoadImages = function()
{
	this._imagesToPreLoad = 5;
	
	this.preLoadAnImage(this._imageOnPath,       "_imageOn");
	this.preLoadAnImage(this._imageOffPath,      "_imageOff");
	this.preLoadAnImage(this._imageWarningPath,  "_imageWarning");
	this.preLoadAnImage(this._imageCriticalPath, "_imageCritical");
	this.preLoadAnImage(this._imagePointerPath,  "_imagePointer");
}

Gauge.prototype.remove = function()
{
	var parent = this._canvas.parentNode;
	parent.removeChild(this._canvas);
}

/*
 * refresh() member function
 * Refresh the current guage position.
 * Call this to make the gauge appear after the widget has loaded and 
 * the Gauge object has been instantiated.
 */
Gauge.prototype.refresh = function()
{	
	this._setValueTo(this.value);
}

Gauge.prototype._setValueTo = function(newValue)
{	
	this.value = newValue;
	
	if (!this._imagesToPreLoad) {

		// Clear the existing canvas
		var canvas = this._canvas;
		var context = canvas.getContext("2d");
		var w = canvas.clientWidth;
		var h = canvas.clientHeight;
		if (w > 0 && h > 0) {
			context.clearRect(0, 0, w, h);
		}

		this._layoutElements();
	}

	if (this.continuous && this.onchanged != null)
		this.onchanged(this.value);
}

// Capture events that we don't handle but also don't want getting through
Gauge.prototype._captureEvent = function(event)
{
	event.stopPropagation();
	event.preventDefault();
}

Gauge.prototype._mousedownCanvas = function(event)
{	
	// temporary event listeners
	document.addEventListener("mousemove", this._mousemoveCanvasHandler, true);
	document.addEventListener("mouseup", this._mouseupCanvasHandler, true);
	document.addEventListener("mouseover", this._captureEventHandler, true);
	document.addEventListener("mouseout", this._captureEventHandler, true);
		
	this._setValueTo(this._computeValueFromMouseEvent(event));
} 

Gauge.prototype._mousemoveCanvas = function(event)
{	
	this._setValueTo(this._computeValueFromMouseEvent(event));
} 

Gauge.prototype._mouseupCanvas = function(event)
{	
	document.removeEventListener("mousemove", this._mousemoveCanvasHandler, true);
	document.removeEventListener("mouseup", this._mouseupCanvasHandler, true);
	document.removeEventListener("mouseover", this._captureEventHandler, true);
	document.removeEventListener("mouseout", this._captureEventHandler, true);

	// Fire our onchanged event now if they have discontinuous event firing
	if (!this.continuous && this.onchanged != null)
		this.onchanged(this.value);
} 

Gauge.prototype.setValue = function(newValue)
{
	this.value = newValue;
	this.refresh();
}

Gauge.prototype.setMinValue = function(newValue)
{
	this.minValue = newValue;
	this.refresh();
}

Gauge.prototype.setMaxValue = function(newValue)
{
	this.maxValue = newValue;
	this.refresh();
}

Gauge.prototype.setStartAngle = function(newValue)
{
	this.startAngle = newValue;
	this.startAngleRad = (2 * Math.PI) * newValue / 360;
	this.refresh();
}

Gauge.prototype.setStopAngle = function(newValue)
{
	this.stopAngle = newValue;
	this.stopAngleRad = (2 * Math.PI) * newValue / 360;
	this.refresh();
}

Gauge.prototype.setOnValue = function(newValue)
{
	this.onValue = newValue;
	this.refresh();
}

Gauge.prototype.setWarningValue = function(newValue)
{
	this.warningValue = newValue;
	this.refresh();
}

Gauge.prototype.setCriticalValue = function(newValue)
{
	this.criticalValue = newValue;
	this.refresh();
}

Gauge.prototype.setPivotOffsetX = function(newValue)
{
	this.pivotOffsetX = newValue;
	this.refresh();
}

Gauge.prototype.setPivotOffsetY = function(newValue)
{
	this.pivotOffsetY = newValue;
	this.refresh();
}

Gauge.prototype.setPointerReach = function(newValue)
{
	this.pointerReach = newValue;
	this.refresh();
}

Gauge.prototype.setInteractive = function(newValue)
{
	this.interactive = newValue;

	document.removeEventListener("mousedown", this._mousedownCanvasHandler, true);

	if (this.interactive)
	{
		this._canvas.addEventListener("mousedown", this._mousedownCanvasHandler, true);
		this._canvas.style.appleDashboardRegion = "dashboard-region(control rectangle)";
	}
	else
	{
		this._canvas.style.appleDashboardRegion = "none";
	}
		
	this.refresh();
}

Gauge.prototype._computeValueFromMouseEvent = function(event)
{
	var canvas = this._canvas;
	var context = canvas.getContext("2d");
	var w = canvas.clientWidth;
	var h = canvas.clientHeight;
	var cx = 0;
	var cy = 0;
	
	var node = canvas;
	while (node.offsetParent) 
	{
		cx += node.offsetLeft;
		cy += node.offsetTop;
		node = node.offsetParent;
	}

	// Get mouse coordinates relative to the center of the gauge
	var mx = event.x - cx - (w / 2);
	var my = event.y - cy - (h / 2);
	var theta = 0;
	if (mx < 0) 
	{
		theta = Math.atan(my / mx) + (Math.PI / 2);
	}
	else if (mx > 0)
	{
		theta = Math.atan(my / mx) + Math.PI + (Math.PI / 2);
	}
	else if (my < 0) 
	{
		theta = Math.PI;
	}
	
	var distance = theta - this.startAngleRad;
	var range = this.stopAngleRad - this.startAngleRad;
	
	var newValue = this.minValue + (((this.maxValue - this.minValue) * distance) / range);
		
	if (newValue < this.minValue)
		newValue = this.minValue;
	else if (newValue > this.maxValue)
		newValue = this.maxValue;
		
	return newValue;
}

Gauge.prototype._layoutElements = function()
{
	var canvas = this._canvas;
	
	var style = document.defaultView.getComputedStyle(canvas, null);
	if (!style) {
		// On Tiger, this means the canvas object is going to be displayed. No need to draw to prevent widget from crashing.
		return;
	}

	// Resize the canvas based on the part div size
	var parent = canvas.parentNode;
	var size = getElementSize(parent);
	canvas.style.width = size.width + "px";
	canvas.style.height = size.height + "px";
	canvas.width = size.width;
	canvas.height = size.height;

	var context = canvas.getContext("2d");
	var w = canvas.width;
	var h = canvas.height;

	// Draw the gauge face
	var gaugeFace = this._imageOff;
	if (this.value != null) 
	{
		if (this.value >= this.criticalValue) 
		{
			gaugeFace = this._imageCritical;
		}
		else if (this.value >= this.warningValue) 
		{
			gaugeFace = this._imageWarning;
		}
		else if (this.value >= this.onValue)
		{
			gaugeFace = this._imageOn;
		}
	}
	context.drawImage(gaugeFace, 0, 0, w, h);

	// How far up from the bottom is the pivot point? (pixels)
	var pivotOffsetY = 25;
	// How far in from the left is the pivot point? (pixels)
	var pivotOffsetX = 28;

	var pointer = this._imagePointer;

	// Calculate scaling factor for pointer
	var ratio = ((w + h) / 4) * (this.pointerReach / 100) / (pointer.height - this.pivotOffsetY);

	// Compute position and size of pointer
	var pw = pointer.width * ratio;
	var ph = pointer.height * ratio;
	var px = -(this.pivotOffsetX * ratio);
	var py = -ph + this.pivotOffsetY * ratio;

	var percent = (this.value - this.minValue) / (this.maxValue - this.minValue);
	var theta = this.startAngleRad + (this.stopAngleRad - this.startAngleRad) * percent - Math.PI;

	// Draw the pointer
	context.save();
	context.translate(w / 2, h / 2);
	context.rotate(theta);
	context.drawImage(this._imagePointer, px, py, pw, ph);
	context.restore();
}
