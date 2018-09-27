function MSBarChart(name, o)
{
	this.GetName = function(){return name;};
	this.GetId = function(){return 'msbarchart_' + name;};
	var data, xmlns = "http://www.w3.org/2000/svg", create = function(tag, attrs)
	 {
		var n = document.createElementNS(xmlns, tag);
		if(attrs) for(var a in attrs) n.setAttribute(a, attrs[a]);
		return n;
	 },
	svg = create("svg"), w = 600, h = 400, bar_width = 40, bar_spacing = 10, tooltip, labels_y = document.createElement('div'), labels_x = document.createElement('div'),
	set_size = function(n, w, h)
	 {
		n.setAttributeNS(null, "width", w);
		n.setAttributeNS(null, "height", h);
		return n;
	 },
	draw_line = function(x1, y1, x2, y2, c){return create("line", {x1:x1, x2:x2, y1:y1, y2:y2, 'class':c, 'stroke-width':1});},
	get_value = function(d){return parseFloat(typeof(d.value) == 'undefined' ? d[0] : d.value);},
	get_label = function(d){return typeof(d.label) == 'undefined' ? d[1] : d.label;},
	get_title = function(d){return typeof(d.title) == 'undefined' ? d[2] : d.title;},
	get_color = function(d, i){return typeof(d[i].color) == 'undefined' ? 'orange' : d[i].color;},
	barmouseover = function()
	 {
		if(tooltip) tooltip.removeAttribute('data-state');
		else
		 {
			tooltip = create("g", {'class':'msbarchart__tooltip'});
			tooltip.appendChild(create('rect', {rx:3, ry:3, fill:'white', width:115, height:48, 'fill-opacity':0.85, stroke:'#c8b0fa', 'stroke-width':1}));
			var text = tooltip.appendChild(create('text', {x:7, y:20}));
			text.appendChild(create('tspan'), {x:7});
			text.appendChild(create('tspan', {x:7, dy:18}));
			text.childNodes[0].appendChild(document.createTextNode(''));
			text.childNodes[1].appendChild(document.createTextNode(''));
			tooltip.SetLabel = function(label, value)
			 {
				text.childNodes[0].firstChild.nodeValue = label;
				text.childNodes[1].firstChild.nodeValue = value;
				return this;
			 };
			svg.appendChild(tooltip);
		 }
		var ttw = parseInt(tooltip.firstChild.getAttribute('width')), bw = parseInt(this.getAttribute('width')), ttx = parseInt(this.getAttribute('x')), tth = parseInt(tooltip.firstChild.getAttribute('height')), tty = parseInt(this.getAttribute('y'));
		tooltip.SetLabel(get_title(this._data) || get_label(this._data), get_value(this._data)).setAttribute('transform', 'translate(' + (ttx + bw + ttw > parseInt(svg.getAttribute('width')) ? ttx - ttw : ttx + bw) + ', ' + (tty + tth > parseInt(svg.getAttribute('height')) ? tty - tth : tty) + ')');
		tooltip.firstChild.setAttribute('stroke', this.getAttribute('fill'));
		this.setAttribute('data-state', 'active');
	 },
	barmouseout = function()
	 {
		if(tooltip) tooltip.setAttribute('data-state', 'hidden');
		this.removeAttribute('data-state');
	 },
	intersects = function(r1, r2) { return ((r1.bottom() >= r2.top() && r2.top() >= r1.top()) || (r1.bottom() >= r2.bottom() && r2.bottom() >= r1.top())) && ((r1.right() >= r2.left() && r2.left() >= r1.left()) || (r1.right() >= r2.right() && r2.right() >= r1.left())) || ((r2.bottom() >= r1.top() && r1.top() >= r2.top()) || (r2.bottom() >= r1.bottom() && r1.bottom() >= r2.top())) && ((r2.right() >= r1.left() && r1.left() >= r2.left()) || (r2.right() >= r1.right() && r1.right() >= r2.left())); },
	Pos = function(left, top, width, height)
	 {
		this.IncTop = function(val){top += val;};
		this.left = function(){return left;};
		this.top = function(){return top;};
		this.right = function(){return left + width;};
		this.bottom = function(){return top + height;};
	 };
	svg.setAttribute('id', this.GetId());
	set_size(svg, w, h);
	labels_y.className = 'msbarchart__labels _y';
	labels_x.className = 'msbarchart__labels _x';
	labels_y.style.height = h + 'px';
	o.dest.appendChild(labels_y);
	o.dest.appendChild(svg);
	o.dest.appendChild(labels_x);
	this.Draw = function()
	 {
		labels_y.innerHTML = labels_x.innerHTML = '';
		for(var n = svg.getElementsByTagName('line'), i = n.length - 1; i >= 0; --i) if('msbarchart__grid' == n[i].getAttribute('class')) n[i].parentNode.removeChild(n[i]);
		for(var n = svg.getElementsByTagName('rect'), i = n.length - 1; i >= 0; --i) if('msbarchart__bar' == n[i].getAttribute('class')) n[i].parentNode.removeChild(n[i]);
		var max = 0, lbl_max_w = 0, factor = w / ((bar_width + bar_spacing) * data.length + 3 * bar_spacing), bar_w = factor < 1 ? Math.ceil(bar_width * factor) : bar_width, bar_w2 = Math.round(bar_w / 2), bar_sp = factor < 1 ? Math.ceil(bar_spacing * factor) : bar_spacing, max_px = h * 0.9;
		for(var i = 0; i < data.length; ++i) max = Math.max(max, get_value(data[i]));
		var sw = (bar_sp + (bar_w + bar_sp) * (data.length - 1)) + 2 * bar_sp + bar_w, step = max / 6, ppu = max_px / max, x = 0;
		while(step > 100)
		 {
			step /= 10;
			++x;
		 }
		if(step >= 75) step = 100;
		else if(step >= 37.5) step = 50;
		else if(step >= 12.5) step = 25;
		else if(step >= 7.5) step = 10;
		else if(step >= 2.5) step = 5;
		else step = 1;
		step *= Math.pow(10, x);
		if(sw < 300) sw = 300;
		for(var n = step, px; n < max * 1.1; n += step)
		 {
			svg.appendChild(draw_line(0, px = Math.round(h - n * ppu), sw, px, 'msbarchart__grid'));
			var label = document.createElement('span');
			label.className = 'msbarchart__label';
			label.appendChild(document.createTextNode(n));
			label.style.top = px + 'px';
			labels_y.appendChild(label);
			lbl_max_w = Math.max(lbl_max_w, label.offsetWidth);
		 }
		labels_y.style.width = lbl_max_w + 'px';
		svg.parentNode.style.paddingLeft = lbl_max_w + 4 + 'px';
		var prev_pos = false, lxh = 0, show_label = factor < 1 ? (new function(){
			var x = Math.round(1 / factor);
			if(x > 10) x = 10;
			console.log(factor, x);
			this.Run = function(i){return !(i % x);};
		}).Run : function(){return true;};
		for(var i = 0; i < data.length; ++i)
		 {
			var bar = create("rect", {'class':'msbarchart__bar'}), bar_h = Math.round(get_value(data[i]) / max * max_px), x = bar_sp + (bar_w + bar_sp) * i;
			if(bar_h < 1) bar_h = 1;
			bar._data = data[i];
			bar.onmouseover = barmouseover;
			bar.onmouseout = barmouseout;
			set_size(bar, bar_w, bar_h);
			bar.setAttribute('x', x);
			bar.setAttribute('y', h - bar_h);
			bar.setAttribute('fill', get_color(data, i));
			svg.appendChild(bar);
			if(show_label(i))
			 {
				var label = document.createElement('span'), tick = document.createElement('span');
				label.onmouseover = (new(function(bar){this.Run = function(){barmouseover.call(bar);};})(bar)).Run;
				label.onmouseout = (new(function(bar){this.Run = function(){barmouseout.call(bar);};})(bar)).Run;
				label.className = 'msbarchart__label';
				label.appendChild(document.createTextNode(get_label(data[i])));
				labels_x.appendChild(label);
				tick.className = 'msbarchart__tick';
				labels_x.appendChild(tick);
				var pos = new Pos(x + (bar_w - label.offsetWidth) / 2, 4, label.offsetWidth, label.offsetHeight);
				if(prev_pos && intersects(pos, prev_pos)) pos.IncTop(label.offsetHeight * 1.25);
				label.style.left = pos.left() + 'px';
				tick.style.left = x + bar_w2 + 'px';
				label.style.top = pos.top() + 'px';
				lxh = Math.max(lxh, pos.bottom());
				prev_pos = pos;
			 }
		 }
		labels_x.style.height = lxh + 'px';
		svg.setAttribute('width', sw);
		if(tooltip) svg.appendChild(tooltip);
	 };
	if(o.dest.className)
	 {
		if(!(/\bmsbarchart\b/).test(o.dest.className)) o.dest.className += ' msbarchart';
	 }
	else o.dest.className = 'msbarchart';
	// o.dest.setAttribute('data-type', typeof(o.vertical) == 'undefined' || o.vertical ? 'vertical' : 'horizontal');
	this.SetData = function(d)
	 {
		data = arguments.length > 1 ? arguments : d;
		return this;
	 };
	this.GetData = function(){return data;};
}