function ge() {
  var ea;
  for (var i = 0; i < arguments.length; i++) {
    var e = arguments[i];
    if (typeof e == 'string')
      e = document.getElementById(e);
    if (arguments.length == 1)
      return e;
    if (!ea)
      ea = new Array();
    ea.push(e);
  }
  return ea;
}
sortable = {
	dragging: false,
	drag_elem: null,
	list: null,
	xOff: 0,
	yOff: 0,
	sortHelper: null,
	on_move_item: function(){},

	makeSortable: function(elem, drag) {
		var dragtargets = {nodes:[]};
		if(drag && drag.target){
			var nodes = drag.target.childNodes;
			for (var i in nodes) {
				if (nodes[i].nodeType == 1)  {
					dragtargets.nodes.push(nodes[i]);
				}
			}
			dragtargets.ondragover = drag.ondragover;
			dragtargets.ondragout = drag.ondragout;
			dragtargets.ondragend = drag.ondragend;
		}
		var nodes = elem.childNodes;
		for (var i in nodes) {
			if (nodes[i].nodeType == 1)  {
				nodes[i]._sortable = true;
				if(dragtargets.nodes.length>0)nodes[i]._dragtargets = dragtargets;
				nodes[i]._width = nodes[i].offsetWidth;
				nodes[i]._height = nodes[i].offsetHeight;
				var xy = getXY(nodes[i]);
				nodes[i]._x = xy[0] - nodes[i].offsetLeft;
				nodes[i]._y = xy[1] - nodes[i].offsetTop;
				nodes[i]._xright = nodes[i]._x + nodes[i]._width;
				nodes[i]._ybottom = nodes[i]._y + nodes[i]._height;
				addEvent(nodes[i], "mousedown", this.mousedown.bind(this));
				addEvent(nodes[i], "mousemove", this.mousemove.bind(this));
				addEvent(nodes[i], "mouseup", this.mouseup.bind(this));
			}
		}
	},

	findNode: function(elem) {
		do {
			if (elem._sortable)
				return elem;
			elem = elem.parentNode;
		} while(elem.parentNode != false);				
		return null;
	},

	updatePos: function(elem, event) {
		elem.style.left = event.pageX - this.xOff + "px";
		elem.style.top = event.pageY - this.yOff + "px";
	},

	mousedown: function(event) {
		if(this.dragging) return;
		var target = event.srcElement || event.target;
		if(target.tagName == "A" || target.tagName == "INPUT" || target.getAttribute("nosorthandle")) return;
		var elem = this.findNode(target);
		var xy = getXY(elem);
		this.xOff = event.pageX - xy[0];
		this.yOff = event.pageY - xy[1];
		css.pushStyles(elem, {"width": elem.style.width || (elem.offsetWidth + "px"), "zIndex": "10000", "left": elem.style.left || "", "top": elem.style.top || ""});
		elem._width = elem.offsetWidth;
		elem._height = elem.offsetHeight;
		this.updatePos(elem, event);
		var t = elem.ownerDocument.createElement("DIV");
		t.innerHTML = '&nbsp; ';
		t.style.height = elem._height + "px";
		t.style.width = elem._width + "px";
		t.style.cssFloat = elem.style.cssFloat || "";
		t.style.styleFloat = elem.style.styleFloat || "";
		this.sortHelper = t;
 		this.list = elem.parentNode;
   	if (this.list.onSortBegin){
        var allowBegin = (this.list.onSortBegin.bind(this))(elem);
        if(allowBegin !== undefined && !allowBegin)return;
       }
		this.dragging = true;
		this.drag_elem = elem;		
		addEvent(document, "mousemove", this.mousemove.bind(this));
		addEvent(document, "drag", this.mousemove.bind(this));
		addEvent(document, "mouseup", this.mouseup.bind(this));
		this.list.insertBefore(t, elem);
		css.pushStyles(elem, {"position": "absolute"});
		this.list.removeChild(elem);
		t.ownerDocument.body.appendChild(elem);

		var nodes = this.list.childNodes, n = nodes.length;
		var dir = 1, before = true;

		return cancelEvent(event);
	},

	mousemove: function(event, wheel) {
		if(!this.dragging) return;
		var elem = this.drag_elem;
		this.updatePos(elem, event);	
		var nodes = this.list.childNodes, n = nodes.length;
		var dir = 1, before = true;
		var mouseX = event.pageX, mouseY = event.pageY;
		if(elem._dragtargets){
			for(var i in elem._dragtargets.nodes){
				var dragtarget = elem._dragtargets.nodes[i];
				var xy = getXY(dragtarget);
				var p1 = {x:(mouseX - xy[0]), y:(mouseY - xy[1])};
				if(p1.x > 0 && p1.x < dragtarget.offsetWidth && p1.y > 0 && p1.y < dragtarget.offsetHeight){
					if(!dragtarget._dragover){
						if(elem._dragtargets.ondragover)elem._dragtargets.ondragover(elem, dragtarget);
						dragtarget._dragover = true;
						elem._dragtarget = dragtarget;
					}
				}else if(dragtarget._dragover){
					if(elem._dragtargets.ondragout)elem._dragtargets.ondragout(elem, dragtarget);
					dragtarget._dragover = false;
					elem._dragtarget = null;
				}
			}
		}
		var wBuffer = 0, hBuffer = 0;
		for(var i = 0; ; i += dir) {
			var itm = nodes[i];
			if (itm == this.sortHelper) {
				if (dir == 1) {
					i = n;
					dir = -1;
					before = false;
					continue;
				} else 
					break;
			}
			if(itm.nodeType == 3 || itm == elem) continue;
			if (before) {
				if (mouseX <= itm._xright + itm.offsetLeft && mouseY <= itm._ybottom + itm.offsetTop) {
					this.list.removeChild(this.sortHelper);
					this.list.insertBefore(this.sortHelper, itm);
					this.on_move_item();
					break;
				}
			} else {
				wBuffer = elem._width < itm._width ? itm._width - elem._width : 0;
				hBuffer = elem._height < itm._height ? itm._height - elem._height : 0;
				if (mouseX > itm._x + itm.offsetLeft + wBuffer && mouseY > itm._y + itm.offsetTop + hBuffer) {
					this.list.removeChild(this.sortHelper);
					this.list.insertBefore(this.sortHelper, itm.nextSibling);
					this.on_move_item();
					break;
				}
			}
		}
		if (!wheel)
			return cancelEvent(event);
		return true;
	},

	mouseup: function(event) {
		if (!this.dragging)
			return;				
		var elem = this.drag_elem;
		this.sortHelper.ownerDocument.body.removeChild(elem);
		this.list.insertBefore(elem, this.sortHelper);
		this.list.removeChild(this.sortHelper);
		this.sortHelper = null;
		css.popStyles(elem, ["width", "position", "zIndex", "left", "top"]);
		this.dragging = false;
		this.drag_elem = null;
		removeEvent(document, "mousemove");
		removeEvent(document, "drag");
		removeEvent(document, "mouseup");
		if (elem._dragtarget) {
			if(elem._dragtargets.ondragout)elem._dragtargets.ondragout(elem, elem._dragtarget);
			if(elem._dragtargets.ondragend)elem._dragtargets.ondragend(elem, elem._dragtarget);
		} else if (elem.parentNode.onSortEnd) {
			elem.parentNode.onSortEnd(elem);
		}
		return cancelEvent(event);
	}
}
function getXY(obj) {
 if (!obj || obj == undefined) return;
 var left = 0, top = 0;
 if (obj.offsetParent) {
  do {
   left += obj.offsetLeft;
   top += obj.offsetTop;
  } while (obj = obj.offsetParent);
 }
 return [left,top];
}
function addEvent(elem, types, handler) {
  elem = ge(elem);
  if (!elem || elem.nodeType == 3 || elem.nodeType == 8 )
    return;
  
  // For whatever reason, IE has trouble passing the window object
  // around, causing it to be cloned in the process
  if (elem.setInterval && elem != window)
    elem = window;
    
  var events = data(elem, "events") || data(elem, "events", []),    
      handle = data(elem, "handle") || data(elem, "handle", function(){
        _eventHandle.apply(arguments.callee.elem, arguments);
      });
  // Add elem as a property of the handle function
  // This is to prevent a memory leak with non-native
  // event in IE.
  handle.elem = elem;
  each(types.split(/\s+/), function(index, type) {
    var handlers = events[type];
    if (!handlers) {
      handlers = events[type] = new Array();
      
      if (elem.addEventListener) 
        elem.addEventListener(type, handle, false);
      else if (elem.attachEvent) 
        elem.attachEvent('on' + type, handle);
    }
    handlers.push(handler);
  });
  
  elem = null;
}
Function.prototype.bind = function(object) {
  var __method = this;
  return function() {
    return __method.apply(object, arguments);
  }
};
function data(elem, name, data) {
  var id = elem[ expand ], undefined;
  if ( !id )
    id = elem[ expand ] = ++vk_uuid;

  if (name && !vk_cache[id])
    vk_cache[id] = {};

  if (data !== undefined)
    vk_cache[id][name] = data;

  return name ?
    vk_cache[id][name] :
    id;
}
var expand = "VK" + now(), vk_uuid = 0, vk_cache = {};
function now() { return +new Date; }
function each(object, callback) {
  var name, i = 0, length = object.length;

  if ( length === undefined ) {
    for ( name in object )
      if ( callback.call( object[ name ], name, object[ name ] ) === false )
        break;
  } else
    for ( var value = object[0];
      i < length && callback.call( value, i, value ) !== false; value = object[++i] ){}

  return object;
};
function _eventHandle(event) {
  event = event || window.event;
  
  var originalEvent = event;
  event = clone(originalEvent);
  event.originalEvent = originalEvent;

  if (!event.target)
    event.target = event.srcElement || document; 

  // check if target is a textnode (safari)
  if ( event.target.nodeType == 3 )
    event.target = event.target.parentNode;

  if (!event.relatedTarget && event.fromElement)
    event.relatedTarget = event.fromElement == event.target 
    
  if ( event.pageX == null && event.clientX != null ) {
    var doc = document.documentElement, body = document.body;
    event.pageX = event.clientX + (doc && doc.scrollLeft || body && body.scrollLeft || 0) - (doc.clientLeft || 0);
    event.pageY = event.clientY + (doc && doc.scrollTop || body && body.scrollTop || 0) - (doc.clientTop || 0);
  }
  
  if ( !event.which && ((event.charCode || event.charCode === 0) ? event.charCode : event.keyCode) )
    event.which = event.charCode || event.keyCode;

  // Add metaKey to non-Mac browsers (use ctrl for PC's and Meta for Macs)
  if ( !event.metaKey && event.ctrlKey )
    event.metaKey = event.ctrlKey;
    
  // Add which for click: 1 == left; 2 == middle; 3 == right
  // Note: button is not normalized, so don't use it
  if ( !event.which && event.button )
    event.which = (event.button & 1 ? 1 : ( event.button & 2 ? 3 : ( event.button & 4 ? 2 : 0 ) ));

  var handlers = data(this, "events");
  if (!handlers || typeof(event.type) != 'string' || !handlers[event.type] || !handlers[event.type].length) { 
    return;
  }
  //try {
  //fixed: handlers[event.type] = undefined
  for (var i = 0; i < (handlers[event.type] || []).length; i++) {
    if (event.type == 'mouseover' || event.type == 'mouseout') {
      var parent = event.relatedElement;
      // Traverse up the tree
      while ( parent && parent != this )
        try { parent = parent.parentNode; }
        catch(e) { parent = this; }
      if (parent == this) {
        continue
      }
    }
    var ret = handlers[event.type][i].apply(this, arguments);
    if (ret === false) {
      cancelEvent(event);
    }
  }
  //} catch (e) {}
}
function clone(obj) {
  var newObj = {};
  for (var i in obj) {
    newObj[i] = obj[i];
  }
  return newObj;
}
css = {
	pushStyles: function(obj, styles) {
		for(i in styles) {
			obj["_style" + i] = getStyle(obj, i) || "";
			obj.style[i] = styles[i];
		}
	},

	popStyles: function(obj, styles) {
		for(i = 0; i < styles.length; i++)
			obj.style[styles[i]] = obj["_style"+ styles[i]];
	}
};
function getStyle(elem, name, force) {
  if (force === undefined) force = true;
  if (!force) {
    return elem.style[name];
  }
  if (name == "width" || name == "height") {
    return getSize(elem, true)[({'width':0, 'height':1})[name]] + 'px';
  }
  var ret, defaultView = document.defaultView || window;
  if (defaultView.getComputedStyle) {
    name = name.replace( /([A-Z])/g, "-$1" ).toLowerCase();
    var computedStyle = defaultView.getComputedStyle( elem, null );
      if (computedStyle)
        ret = computedStyle.getPropertyValue(name);
  } else if (elem.currentStyle) {
    if (name == 'opacity' && browser.msie) {
      var filter = elem.currentStyle['filter'];
      return filter && filter.indexOf("opacity=") >= 0 ?
        (parseFloat(filter.match(/opacity=([^)]*)/)[1] ) / 100) + '' : '1';
    }
    var camelCase = name.replace(/\-(\w)/g, function(all, letter){
      return letter.toUpperCase();
    });
    ret = elem.currentStyle[name] || elem.currentStyle[camelCase];
    // If we're not dealing with a regular pixel number
    // but a number that has a weird ending, we need to convert it to pixels
    if ( !/^\d+(px)?$/i.test( ret ) && /^\d/.test( ret ) ) {
      // Remember the original values
      var left = style.left, rsLeft = elem.runtimeStyle.left;

      // Put in the new values to get a computed value out
      elem.runtimeStyle.left = elem.currentStyle.left;
      style.left = ret || 0;
      ret = style.pixelLeft + "px";

      // Revert the changed values
      style.left = left;
      elem.runtimeStyle.left = rsLeft;
    }
  }
  return ret;
}
function getSize(elem, woBounds) {
  var s = [0, 0];
  if (elem == document) {
    s =  [Math.max(
        document.documentElement["clientWidth"],
        document.body["scrollWidth"], document.documentElement["scrollWidth"],
        document.body["offsetWidth"], document.documentElement["offsetWidth"]
      ), Math.max(
        document.documentElement["clientHeight"],
        document.body["scrollHeight"], document.documentElement["scrollHeight"],
        document.body["offsetHeight"], document.documentElement["offsetHeight"]
      )];
  } else if (elem){
    function getWH() {
      s = [elem.offsetWidth, elem.offsetHeight];
      if (!woBounds) return;
      var padding = 0, border = 0;
      each(s, function(i, v) {
        var which = i ? ['Top', 'Bottom'] : ['Left', 'Right'];
        each(which, function(){
          s[i] -= parseFloat(getStyle(elem, "padding" + this)) || 0;
          s[i] -= parseFloat(getStyle(elem, "border" + this + "Width")) || 0;
        });
      });
      s = [Math.round(s[0]), Math.round(s[1])];
    }
    if (!isVisible(elem)) { 
      var props = {position: "absolute", visibility: "hidden", display:"block"};
      var old = {};
      each(props, function(i, val){
        old[i] = elem.style[i];
        elem.style[i] = val;
      });
      getWH();
      each(props, function(i, val){
        elem.style[i] = old[i];
      });
    } else getWH();
    
  } 
  return s;
}
function isVisible(elem) {
 elem = ge(elem);
 return getStyle(elem, 'display') != 'none' && getStyle(elem, 'visibility') != 'hidden';
}
function cancelEvent(event) {
  var e = event.originalEvent || event;
  if (e.preventDefault)
      e.preventDefault();
  if (e.stopPropagation) 
      e.stopPropagation(); 
  e.cancelBubble = true;
  e.returnValue = false;
  return false;
}
function removeEvent(elem, type, handler) {
  elem = ge(elem);
  if (!elem) return;
  var events = data(elem, "events");
  if (events) {
    if (typeof(type) == 'string' && isArray(events[type])) {
      if (isFunction(handler)) {
        for (var i = 0; i < events[type].length; i++) {
          if (events[type][i] == handler) {
            delete events[type][i];
            break;
          }
        }
      } else {
        for (var i = 0; i < events[type].length; i++) {
          delete events[type][i];
        }
      }
    } else {
      for (var i in events) {
        removeEvent(elem, i);
      }
      return;
    }
    for (var ret in events[type]) break;
    if (!ret && data(elem, "handle")) {
      
      if (elem.removeEventListener)
        elem.removeEventListener(type, data(elem, "handle"), false);
      else if (elem.detachEvent)
        elem.detachEvent("on" + type, data(elem, "handle"));
    }
    ret = null;
    delete events[type];
  }
}
function isArray(obj) { return Object.prototype.toString.call(obj) === "[object Array]"; }
function isFunction(obj) {return Object.prototype.toString.call(obj) === "[object Function]"; }