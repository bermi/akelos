// Copyright (c) 2006 Sébastien Gruhier (http://xilinus.com, http://itseb.com)
// 
// Permission is hereby granted, free of charge, to any person obtaining
// a copy of this software and associated documentation files (the
// "Software"), to deal in the Software without restriction, including
// without limitation the rights to use, copy, modify, merge, publish,
// distribute, sublicense, and/or sell copies of the Software, and to
// permit persons to whom the Software is furnished to do so, subject to
// the following conditions:
// 
// The above copyright notice and this permission notice shall be
// included in all copies or substantial portions of the Software.
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
// EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
// MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
// NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
// LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
// OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

var Window = Class.create();
Window.prototype = {
	// Constructor
	// Available parameters : minWidth, minHeight, maxWidth, maxHeight, width, height, top, left, resizable, zIndex, opacity, hideEffect, showEffect, url
	initialize: function(id, parameters) {
		this.hasEffectLib = String.prototype.parseColor != null
		this.minWidth = parameters.minWidth || 100;
		this.minHeight = parameters.minHeight || 100;
		this.maxWidth = parameters.maxWidth;
		this.maxHeight = parameters.maxHeight;
		this.showEffect = parameters.showEffect || (this.hasEffectLib ? Effect.Appear : Element.show)
		this.hideEffect = parameters.hideEffect || (this.hasEffectLib ? Effect.Fade : Element.hide)
		
		var resizable = parameters.resizable != null ? parameters.resizable : true;
		var className = parameters.className != null ? parameters.className : "dialog";
		
			
		this.element = this.createWindow(id, className, resizable, parameters.title, parameters.url);
		this.isIFrame = parameters.url != null;
		
		// Bind event listener
	    this.eventMouseDown = this.initDrag.bindAsEventListener(this);
      	this.eventMouseUp   = this.endDrag.bindAsEventListener(this);
      	this.eventMouseMove = this.updateDrag.bindAsEventListener(this);

		this.topbar = $(this.element.id + "_top");
    	Event.observe(this.topbar, "mousedown", this.eventMouseDown);
	
		if (resizable) {
			this.sizer = $(this.element.id + "_sizer");
	    	Event.observe(this.sizer, "mousedown", this.eventMouseDown);
	    }
	
		var top = parseFloat(parameters.top) || 10;
		var width = parseFloat(parameters.width) || 200;
		var height = parseFloat(parameters.height) || 200;

		if (parameters.left != null)
			Element.setStyle(this.element,{left: parseFloat(parameters.left) + 'px'});

		if (parameters.right != null)
			Element.setStyle(this.element,{right: parseFloat(parameters.right) + 'px'});

		if (parameters.top != null)
			Element.setStyle(this.element,{top: parameters.top + 'px'});

		if (parameters.bottom != null)
			Element.setStyle(this.element,{bottom: parameters.bottom + 'px'});

		this.setSize(width, height);
		if (parameters.opacity)
			this.setOpacity(parameters.opacity);
		if (parameters.zIndex) {
			Element.setStyle(this.element,{zIndex: parameters.zIndex});
		}
		Windows.register(this);	    
  	},
 
	// Destructor
 	destroy: function() {
    	Event.stopObserving(this.topbar, "mousedown", this.eventMouseDown);
		if (this.sizer)
    		Event.stopObserving(this.sizer, "mousedown", this.eventMouseDown);

		var objBody = document.getElementsByTagName("body").item(0);
		objBody.removeChild(this.element);

		Windows.unregister(this);	    
	},
  	
	// Get window content
	getContent: function () {
		return $(this.element.id + "_content");
	},
	
	// Get window ID
	getId: function() {
		return this.element.id;
	},
	
	// Init drag callback
	initDrag: function(event) {
		// Get pointer X,Y
       	this.pointer = [Event.pointerX(event), Event.pointerY(event)];
		this.doResize = false;
		
		// Check if click on close button, 
		var closeButton = $(this.getId() + '_close');
		if (closeButton && Position.within(closeButton, this.pointer[0], this.pointer[1])) {
			return;
		}
		// Check if click on sizer
		if (this.sizer && Position.within(this.sizer, this.pointer[0], this.pointer[1])) {
			this.doResize = true;
		}
		
		// Register global event to capture mouseUp and mouseMove
		Event.observe(document, "mouseup", this.eventMouseUp);
      	Event.observe(document, "mousemove", this.eventMouseMove);
		
		// Add an invisible div to keep catching mouse event over the iframe
		if (this.isIFrame) {
			var objBody = document.getElementsByTagName("body").item(0);
			var div = document.createElement("div");
			div.style.position = "absolute";
			div.style.top = "0px";
			div.style.bottom = "0px";
			div.style.zIndex = "10000";			
			div.style.width = (this.width + 100) + "px";
			div.style.height = (this.height + 100) + "px";
			this.element.appendChild(div);
			
			this.tmpDiv = div;			
		}
		this.toFront();
      	Event.stop(event);
  	},

	// Drag callback
  	updateDrag: function(event) {
	   	var pointer = [Event.pointerX(event), Event.pointerY(event)];    

		var dx = pointer[0] - this.pointer[0];
		var dy = pointer[1] - this.pointer[1];

		this.pointer = pointer;

		// Resize case, update width/height
		if (this.doResize) {
			var width = parseFloat(Element.getStyle(this.element, 'width'));
			var height = parseFloat(Element.getStyle(this.element, 'height'));
			
			width += dx;
			height += dy;
			// Check if it's a right position, update it to keep upper-left corner at the same position
			var right = Element.getStyle(this.element, 'right');
			if (right != null) 
				Element.setStyle(this.element,{right: (parseFloat(right) -dx) + 'px'});

			// Check if it's a bottom position, update it to keep upper-left corner at the same position
			var bottom = Element.getStyle(this.element, 'bottom');
			if (bottom != null) 
				Element.setStyle(this.element,{bottom: (parseFloat(bottom) -dy) + 'px'});
			
			this.setSize(width, height)
		}
		// Move case, update top/left
		else {
			var top = Element.getStyle(this.element, 'top');
			var left = Element.getStyle(this.element, 'left');
			
			if (left != null) {
				left = parseFloat(left) + dx;
				Element.setStyle(this.element,{left: left + 'px'});
			}
			else {
				var right = Element.getStyle(this.element, 'right');
				right = parseFloat(right) - dx;
				Element.setStyle(this.element,{right: right + 'px'});
			}
			
			if (top != null) {
				top = parseFloat(top) + dy;
				Element.setStyle(this.element,{top: top + 'px'});
			} else {
				var bottom = Element.getStyle(this.element, 'bottom');
				bottom = parseFloat(bottom) - dy;
				Element.setStyle(this.element,{bottom: bottom + 'px'});
				
			}
		}
      	Event.stop(event);
  	},

	// End drag callback
  	endDrag: function(event) {
		// Release event observing
		Event.stopObserving(document, "mouseup", this.eventMouseUp);
      	Event.stopObserving(document, "mousemove", this.eventMouseMove);

		// Remove temporary div
		if (this.isIFrame) {
			this.tmpDiv.parentNode.removeChild(this.tmpDiv);
			this.tmpDiv = null;
		}
      	Event.stop(event);
  	},

	createWindow: function(id, className, resizable, title, url) {
		var objBody = document.getElementsByTagName("body").item(0);
		win = document.createElement("div");
		win.setAttribute('id', id);
		win.className = "dialog";
	 	if (!title)
			title = "&nbsp;";

		var content;
		if (url)
			content= "<IFRAME id=\"" + id + "_content\" SRC=\"" + url + "\" > </IFRAME>";
		else
			content ="<DIV id=\"" + id + "_content\" class=\"" +className + "_content\"> content</DIV>";
			
		win.innerHTML = "\
		<DIV class=\"" +className + "_close\" id=\""+ id + "_close\" onclick=\"Windows.close('"+ id + "')\"> </DIV>\
		<TABLE background='#F00' border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\""+ id + "_header\">\
			<TR id=\""+ id + "_row1\">\
				<TD> \
					<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\""+ id + "_top\">\
						<TR>\
							<TD id=\""+ id + "_nw\"  class=\"" +className + "_nw\"> </TD>\
							<TD class=\"" +className + "_n\"  valign=\"middle\">\
								<DIV class=\"" +className + "_title\">" + title + " </DIV>						\
							</TD>\
							<TD class=\"" +className + "_ne\"> </TD>\
						</TR>\
					</TABLE>\
				</TD>\
			</TR>\
			<TR id=\""+ id + "_row2\">\
				<TD> \
					<TABLE class=\"" +className + "der=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\">\
						<TR>\
							<TD class=\"" +className + "_w\"><DIV class=\"" +className + "_w\"> </TD> \
							<TD class=\"" +className + "_content\">" + content + "</TD>\
							<TD class=\"" +className + "_e\"><DIV class=\"" +className + "_e\"> </TD>\
						</TR>\
					</TABLE>\
				</TD>\
			</TR>\
			<TR id=\""+ id + "_row3\">\
				<TD> \
					<TABLE border=\"0\" cellpadding=\"0\" cellspacing=\"0\" width=\"100%\" id=\""+ id + "_bottom\">\
						<TR>\
							<TD class=\"" +className + "_sw\" id=\""+ id + "_sw\"  > </TD>\
							<TD class=\"" +className + "_s\" valign=\"middle\">&nbsp;</TD>\
							<TD class=\"" +className + "_se\"> " + (resizable  ? "<DIV id=\""+ id + "_sizer\" class=\"" +className + "_sizer\"></DIV>" : "") + "</TD>\
						</TR>\
					</TABLE>\
				</TD>\
			</TR>\
		</TABLE>\
		";
		Element.hide(win);
		objBody.insertBefore(win, objBody.firstChild);
		
		return win;
	},
	
	// Update window location
	setLocation: function(top, left) {
		Element.setStyle(this.element,{top: top + 'px'});
		Element.setStyle(this.element,{left: left + 'px'});
	},
	
	// Update window size
	setSize: function(width, height) {
		// Check min and max size
		if (width < this.minWidth)
			width = this.minWidth;

		if (height < this.minHeight)
			height = this.minHeight;
			
		if (this.maxHeight && height > this.maxHeight)
			height = this.maxHeight;

		if (this.minHeight && height < this.minHeight)
			height = this.minHeight;

		this.width = width;
		this.height = height;
		
		Element.setStyle(this.element,{width: width + 'px'});
		Element.setStyle(this.element,{height: height + 'px'});

		// Update content height
		var content = $(this.element.id + '_content')
		Element.setStyle(content,{height: height  + 'px'});
		Element.setStyle(content,{width: width  + 'px'});
	},
	
	// Bring to front
	toFront: function() {
		windows = document.getElementsByClassName("dialog");
		var maxIndex= 0;
		for (i = 0; i<windows.length; i++){
			if (maxIndex < parseFloat(windows[i].style.zIndex))
				maxIndex = windows[i].style.zIndex;
		}
		this.element.style.zIndex = parseFloat(maxIndex) +1;
	},
	
	show: function() {
		this.setSize(this.width, this.height);
		this.showEffect(this.element);		
	},
	
	showCenter: function() {
		this.setSize(this.width, this.height);
		
		var arrayPageSize = getPageSize();
		var arrayPageScroll = getPageScroll();

		this.element.style.top = (arrayPageScroll[1] + ((arrayPageSize[3] - this.height) / 2) + 'px');
		this.element.style.left = (((arrayPageSize[0] - this.width) / 2) + 'px');
		
		this.showEffect(this.element);		
	},
	
	hide: function() {
		// To avoid bug on scrolling bar
		Element.setStyle(this.getContent(), {overflow: "hidden"});
		this.hideEffect(this.element);		
	},
	
	setOpacity: function(opacity) {
		if (Element.setOpacity)
			Element.setOpacity(this.element, opacity);
	}	
};

// Windows containers, register all page windows
var Windows = {
  windows: [],
  
  // Find window from its id
  getWindow: function(id) {
	return this.windows.detect(function(d) { return d.getId() ==id });
  },

  // Register a new window (called by Windows constructor)
  register: function(win) {
    this.windows.push(win);
  },
  
  // Unregister a window (called by Windows destructor)
  unregister: function(win) {
    this.windows = this.windows.reject(function(d) { return d==win });
  }, 

  // Close a window with its id
  close: function(id) {
	win = this.getWindow(id);
    if (win)
		win.hide();
  }
};

/*
	Based on Lightbox JS: Fullsize Image Overlays 
	by Lokesh Dhakar - http://www.huddletogether.com

	For more information on this script, visit:
	http://huddletogether.com/projects/lightbox/

	Licensed under the Creative Commons Attribution 2.5 License - http://creativecommons.org/licenses/by/2.5/
	(basically, do anything you want, just leave my name and link)
*/
//
// getPageScroll()
// Returns array with x,y page scroll values.
// Core code from - quirksmode.org
//
function getPageScroll(){
	var yScroll;

	if (self.pageYOffset) {
		yScroll = self.pageYOffset;
	} else if (document.documentElement && document.documentElement.scrollTop){	 // Explorer 6 Strict
		yScroll = document.documentElement.scrollTop;
	} else if (document.body) {// all other Explorers
		yScroll = document.body.scrollTop;
	}

	arrayPageScroll = new Array('',yScroll) 
	return arrayPageScroll;
}

//
// getPageSize()
// Returns array with page width, height and window width, height
// Core code from - quirksmode.org
// Edit for Firefox by pHaez
//
function getPageSize(){
	var xScroll, yScroll;
	
	if (window.innerHeight && window.scrollMaxY) {	
		xScroll = document.body.scrollWidth;
		yScroll = window.innerHeight + window.scrollMaxY;
	} else if (document.body.scrollHeight > document.body.offsetHeight){ // all but Explorer Mac
		xScroll = document.body.scrollWidth;
		yScroll = document.body.scrollHeight;
	} else { // Explorer Mac...would also work in Explorer 6 Strict, Mozilla and Safari
		xScroll = document.body.offsetWidth;
		yScroll = document.body.offsetHeight;
	}
	
	var windowWidth, windowHeight;
	if (self.innerHeight) {	// all except Explorer
		windowWidth = self.innerWidth;
		windowHeight = self.innerHeight;
	} else if (document.documentElement && document.documentElement.clientHeight) { // Explorer 6 Strict Mode
		windowWidth = document.documentElement.clientWidth;
		windowHeight = document.documentElement.clientHeight;
	} else if (document.body) { // other Explorers
		windowWidth = document.body.clientWidth;
		windowHeight = document.body.clientHeight;
	}	
	
	// for small pages with total height less then height of the viewport
	if(yScroll < windowHeight){
		pageHeight = windowHeight;
	} else { 
		pageHeight = yScroll;
	}

	// for small pages with total width less then width of the viewport
	if(xScroll < windowWidth){	
		pageWidth = windowWidth;
	} else {
		pageWidth = xScroll;
	}

	arrayPageSize = new Array(pageWidth,pageHeight,windowWidth,windowHeight) 
	return arrayPageSize;
}
