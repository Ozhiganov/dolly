function GetPosition(e)/*deprecated*/
{
	var left = 0, top = 0;
	while(e.offsetParent)
	 {
		left += e.offsetLeft;
		top += e.offsetTop;
		e = e.offsetParent;
	 }
	left += e.offsetLeft;
	top += e.offsetTop;
	return {x:left, y:top};
}
function FixEvent(e)/*deprecated*/
{
	e = e || window.event;
	if(null == e.pageX && null != e.clientX)
	 {
		var html = document.documentElement, body = document.body;
		e.pageX = e.clientX + (html && html.scrollLeft || body && body.scrollLeft || 0) - (html.clientLeft || 0);
		e.pageY = e.clientY + (html && html.scrollTop || body && body.scrollTop || 0) - (html.clientTop || 0);
	 }
	if(!e.which && e.button) e.which = e.button & 1 ? 1 : (e.button & 2 ? 3 : (e.button & 4 ? 2 : 0));
	return e;
}
MSDragNDropManager = new (function()
{
	var mouseOffset, dragable_items = [],
	DragItem = new (function()
	 {
		var drag_item;
		this.Set = function(item){drag_item = item;};
		this.Get = function(){return drag_item;};
	 }),
	Release = function()
	 {
		var item = DragItem.Get();
		if(!item) return;
		var dests = [];
		for(var dest = item.FirstTarget(); dest; dest = item.NextTarget())
		 {
			if(dest.__intersect) dests.push(dest);
			dest.__intersect = false;
		 }
		if(item.OnEndDrag) item.OnEndDrag(dests);
		DragItem.Set(null);
		document.ondragstart = document.body.onselectstart = function(){return true;};
	 },
	intersects = function(drag, dest, px, py)
	 {
		if(drag.FullIntersection())
		 {
			var drag_r = drag.GetRect(), dest_r = dest.GetRect();
			return (Math.abs((drag_r.tx + drag.offsetWidth / 2) - (dest_r.tx + dest.offsetWidth / 2)) <= (drag.offsetWidth + dest.offsetWidth) / 2) && (Math.abs((drag_r.ty + drag.offsetHeight / 2) - (dest_r.ty + dest.offsetHeight / 2)) <= (drag.offsetHeight + dest.offsetHeight) / 2);
		 }
		else
		 {
			var r = dest.GetRect();
			return (dest.IgnoreX() || (px >= r.tx && px <= r.bx)) && (dest.IgnoreY() || (py >= r.ty && py <= r.by));
		 }
	 },
	MouseMove = function(evt)
	 {
		evt = FixEvent(evt);
		var item = DragItem.Get();
		if(item)
		 {
			if(item.OnDrag) item.OnDrag(evt);
			item.SetPosition(evt.pageX - mouseOffset.x, evt.pageY - mouseOffset.y, mouseOffset.x, mouseOffset.y, evt.pageX, evt.pageY);
			var drag_overs = [], drag_outs = [];
			for(var dest = item.FirstTarget(); dest; dest = item.NextTarget())
			 if(intersects(item, dest, evt.pageX, evt.pageY))
			  {
				drag_overs.push(dest);
				if(dest.OnDragOver) dest.OnDragOver(item);
				dest.__intersect = true;
			  }
			 else if(dest.__intersect)
			  {
				drag_outs.push(dest);
				if(dest.OnDragOut) dest.OnDragOut(item);
				dest.__intersect = false;
			  }
			if(item.OnDragOver && drag_overs.length) item.OnDragOver(drag_overs);
			if(item.OnDragOut && drag_outs.length) item.OnDragOut(drag_outs);
			return false;
		 }
	 },
	Capture = function(evt)
	 {
		document.ondragstart = document.body.onselectstart = FalseFunc;
		evt = FixEvent(evt);
		var pos = GetPosition(this);
		mouseOffset = {x: evt.pageX - pos.x, y: evt.pageY - pos.y};
		DragItem.Set(this);
		if(this.OnStartDrag) this.OnStartDrag(pos.x, pos.y, mouseOffset.x, mouseOffset.y, evt.pageX, evt.pageY);
		evt.cancelBubble = true;
		return false;
	 },
	GetRect = function()
	 {
		var p = GetPosition(this);
		return {tx:p.x, ty:p.y, bx:p.x + this.offsetWidth, by:p.y + this.offsetHeight};
	 };
	this.InitItem = function(item)
	 {
		dragable_items[dragable_items.length] = item;
		item.onmousedown = Capture;
		var dests = [], index = 0, GetTarget = function(index){return index < dests.length ? dests[index] : null;};
		item.AttachTarget = function(item)
		 {
			item.GetRect = GetRect;
			if(!item.IgnoreX) item.IgnoreX = FalseFunc;
			if(!item.IgnoreY) item.IgnoreY = FalseFunc;
			return dests.push(item) - 1;
		 };
		item.FirstTarget = function(){return GetTarget(index = 0);};
		item.NextTarget = function(){return GetTarget(++index);};
		item.GetRect = GetRect;
		if(!item.FullIntersection) item.FullIntersection = FalseFunc;
		if(!item.SetPosition) item.SetPosition = EmptyFunc;
		return item;
	 };
	this.Init = function()
	 {
		document.onmouseup = Release;
		document.onmousemove = MouseMove;
	 };
});