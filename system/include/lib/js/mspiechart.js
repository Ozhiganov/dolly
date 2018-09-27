function MSPieChart(name, o)
{
	this.GetName = function(){return name;};
	this.GetId = function(){return 'mspiechart_' + name;};
	var data, xmlns = "http://www.w3.org/2000/svg", svg = document.createElementNS(xmlns, "svg"), w = 500, h = 400, r = 150, center = {x: Math.floor(w / 2), y: Math.floor(h / 2)}, sum, angle_offset = 0.5 * Math.PI, legend = document.createElement("div"),
		calc_arc_coords = function(center, r, angle) { return {x: center.x + r * Math.cos(angle), y: center.y + r * Math.sin(angle)}; },
		colors = ['#ff3030', '#ff8800', '#00b6f7', '#95d900', '#ffea00', '#bb7bf1', '#8080ff', '#ff8888', '#457F51', 'darkblue', 'darkorange', 'purple', "#468966", "#FFF0A5", "#FFB03B", "#B64926", "#8E2800"],
		sector = function(path, row, l_angle)
		 {
			var c = calc_arc_coords(center, 0.05 * r, l_angle);
			row.onmouseover = function(){path.setAttributeNS(null, 'transform', 'translate(' + (c.x - center.x) + ', ' + (c.y - center.y) + ')');};
			row.onmouseout = function(){path.removeAttributeNS(null, 'transform');};
		 },
		set_size = function(n, w, h)
		 {
			n.setAttributeNS(null, "width", w);
			n.setAttributeNS(null, "height", h);
			return n;
		 },
		set_coord = function(n, x, y)
		 {
			n.setAttributeNS(null, "x", x);
			n.setAttributeNS(null, "y", y);
			return n;
		 },
		create_label = function(pt, abs, label, fill)
		 {
			var legend_color = document.createElement("span"), legend_row = document.createElement("div"), legend_pct = document.createElement("span"), legend_abs = document.createElement("span"), legend_values = document.createElement('span');
			legend_pct.className = 'mspiechart__pct';
			legend_pct.innerHTML = pt + '%';
			legend_abs.className = 'mspiechart__abs';
			legend_abs.innerHTML = abs;
			legend_color.style.backgroundColor = fill;
			legend_color.setAttribute('class', 'mspiechart__color');
			legend_row.setAttribute('class', 'mspiechart__title');
			legend_row.appendChild(legend_color);
			legend_row.appendChild(document.createTextNode(label));
			legend_values.setAttribute('class', 'mspiechart__values');
			legend_values.appendChild(legend_pct);
			legend_values.appendChild(legend_abs);
			legend_row.appendChild(legend_values);
			legend.appendChild(legend_row);
			return legend_row;
		 },
		draw_sector = function(value, prev, label, fill)
		 {
			var start_angle = 2 * Math.PI * prev / sum - angle_offset, angle = 2 * Math.PI * value / sum, c1 = calc_arc_coords(center, r, start_angle), c2 = calc_arc_coords(center, r, start_angle + angle), g = document.createElementNS(xmlns, "g"), path = document.createElementNS(xmlns, "path"), text = document.createElementNS(xmlns, "text"), p = value / sum, pt = (p * 100).toFixed(2), l_angle = start_angle + angle / 2, lo_angle = l_angle + angle_offset, cl = calc_arc_coords(center, 1.07 * r, l_angle), h_align = 'middle', v_align = 'auto';
			if((/\.0+$/).test(pt)) pt = pt.replace(/\.0+$/, '');
			else if((/(\.[1-9]+)0+$/).test(pt)) pt = pt.replace(/(\.[1-9]+)0+$/, '$1');
			path.setAttributeNS(null, 'stroke', 'none');
			path.setAttributeNS(null, 'stroke-width', 0);
			path.setAttributeNS(null, 'd', ['M', center.x, center.y, 'L', c1.x, c1.y, 'A', r, r, 0, angle > Math.PI ? 1 : 0, 1, c2.x, c2.y, 'Z'].join(' '));
			path.setAttributeNS(null, 'fill', fill);
			g.appendChild(path);
			set_coord(text, cl.x, cl.y);
			if(0.25 < lo_angle && lo_angle < 2.9) h_align = 'start';
			else if(3.39 < lo_angle && lo_angle < 6.04) h_align = 'end';
			if((1.32 <= lo_angle && lo_angle <= 1.82) || (4.46 <= lo_angle && lo_angle <= 4.96)) v_align = 'middle';
			else if(1.82 < lo_angle && lo_angle < 4.46) v_align = 'hanging';
			text.setAttributeNS(null, 'text-anchor', h_align);
			text.setAttributeNS(null, 'alignment-baseline', v_align);
			text.appendChild(document.createTextNode(pt + '%'));
			text.setAttribute('class', 'mspiechart__label');
			g.appendChild(text);
			g.setAttribute('class', 'mspiechart__sector');
			svg.appendChild(g);
			new sector(path, create_label(pt, value, label, fill), l_angle);
		 },
		get_value = function(d){return parseFloat(typeof(d.value) == 'undefined' ? d[0] : d.value);},
		get_label = function(d){return typeof(d.label) == 'undefined' ? d[1] : d.label;},
		get_color = function(d, i){return typeof(d[i].color) == 'undefined' ? colors[i % colors.length] : d[i].color;};
	svg.setAttribute('id', this.GetId());
	set_size(svg, w, h);
	this.Draw = function()
	 {
		legend.innerHTML = '';
		for(var i = svg.childNodes.length - 1; i >= 0; --i)
		 switch(svg.childNodes[i].getAttribute('class'))
		  {
			case 'mspiechart__sector': svg.removeChild(svg.childNodes[i]); break;
		  }
		sum = 0;
		if(1 === data.length)
		 {
			var g = document.createElementNS(xmlns, "g"), text = document.createElementNS(xmlns, "text"), circle = document.createElementNS(xmlns, "circle"), fill = get_color(data, 0);
			circle.setAttributeNS(null, 'fill', fill);
			circle.setAttributeNS(null, 'cx', center.x);
			circle.setAttributeNS(null, 'cy', center.y);
			circle.setAttributeNS(null, 'r', r);
			set_coord(text, center.x + r * 0.7, center.y - r * 0.95);
			text.setAttributeNS(null, 'text-anchor', 'middle');
			text.setAttributeNS(null, 'alignment-baseline', 'auto');
			text.appendChild(document.createTextNode('100%'));
			text.setAttribute('class', 'mspiechart__label');
			create_label(100, get_value(data[0]), get_label(data[0]), fill);
			g.appendChild(text);
			g.appendChild(circle);
			g.setAttribute('class', 'mspiechart__sector');
			svg.appendChild(g);
		 }
		else
		 {
			for(var i = 0; i < data.length; ++i) sum += get_value(data[i]);
			for(var i = 0, s = 0, v; i < data.length; ++i, s += v) draw_sector(v = get_value(data[i]), s, get_label(data[i]), get_color(data, i));
		 }
		return this;
	 };
	legend.setAttribute('class', 'mspiechart__legend');
	legend.onclick = function()
	 {
		var a = 'data-state';
		if(this.hasAttribute(a)) this.removeAttribute(a);
		else this.setAttribute(a, 'abs');
	 };
	o.dest.appendChild(svg);
	o.dest.appendChild(legend);
	if(o.dest.className)
	 {
		if(!(/\bmspiechart\b/).test(o.dest.className)) o.dest.className += ' mspiechart';
	 }
	else o.dest.className = 'mspiechart';
	o.dest.setAttribute('data-type', typeof(o.vertical) == 'undefined' || o.vertical ? 'vertical' : 'horizontal');
	this.SetData = function(d)
	 {
		data = arguments.length > 1 ? arguments : d;
		return this;
	 };
	this.GetData = function(){return data;};
}