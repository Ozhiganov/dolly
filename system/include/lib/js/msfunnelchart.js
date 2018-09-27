function MSFunnelChart(name, o)
{
	this.GetName = function(){return name;};
	this.GetId = function(){return 'msfunnelchart_' + name;};
	var data, xmlns = "http://www.w3.org/2000/svg", svg = document.createElementNS(xmlns, "svg"), legend = document.createElement("div"), row_h = 60, txt_v_offset = row_h / 3, row_max_w = 400, row_half_max_w = row_max_w / 2,
	colors = ['#ff3030', '#ff8800', '#00b6f7', '#95d900', '#ffea00', '#bb7bf1', '#8080ff', '#ff8888', '#457F51', 'darkblue', 'darkorange', 'purple', "#468966", "#FFF0A5", "#FFB03B", "#B64926", "#8E2800"],
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
	create_lbl = function(t)
	 {
		var l = document.createElementNS(xmlns, "text");
		l.setAttributeNS(null, "text-anchor", "middle");
		l.appendChild(document.createTextNode(t));
		return l;
	 },
	get_value = function(d){return parseFloat(typeof(d.value) == 'undefined' ? d[0] : d.value);},
	get_label = function(d){return typeof(d.label) == 'undefined' ? d[1] : d.label;},
	get_color = function(d, i){return typeof(d[i].color) == 'undefined' ? colors[i % colors.length] : d[i].color;};
	svg.setAttribute('id', this.GetId());
	svg.setAttribute('class', 'msfunnelchart__svg');
	this.Draw = function()
	 {
		svg.innerHTML = '';
		legend.innerHTML = '';
		var pol, value, l_pct, legend_row, sum = get_value(data[0]), inner_text = [];
		legend.className = 'msfunnelchart__legend';
		for(var i = 0; i < data.length; ++i)
		 {
			legend_row = document.createElement("div");
			legend_row.className = 'msfunnelchart__title';
			legend_row.innerHTML = '<span>' + get_label(data[i]) + '</span>';
			legend.appendChild(legend_row);
			pol = document.createElementNS(xmlns, "polygon");
			var k = get_value(data[i]) / sum, offset = (row_max_w - (row_max_w * k)) / 2, previous_offset = (row_max_w - (row_max_w * (get_value(data[(i > 0 ? i - 1 : 0)]) / sum))) / 2, v_off = txt_v_offset + i * row_h + 4;
			pol.setAttributeNS(null, "fill", get_color(data, i));
			// legend_row.style.borderColor = get_color(data, i);
			pol.setAttributeNS(null, "points", previous_offset + "," + i * row_h + " " + (row_max_w - previous_offset) + "," + i * row_h + " " + (row_max_w - offset) + "," + (i + 1) * row_h + " " + offset + "," + (i + 1) * row_h);
			value = create_lbl(get_value(data[i]));
			l_pct = create_lbl((k * 100).toFixed(2).replace(/(\.00)$/, '') + "%");
			inner_text[i] = [value, l_pct];
			set_coord(value, row_half_max_w, v_off);
			set_coord(l_pct, row_half_max_w, v_off + txt_v_offset + 2);
			svg.appendChild(pol);
			svg.appendChild(value);
			svg.appendChild(l_pct);
		 }
		set_size(svg, row_max_w, row_h * data.length);
		o.dest.appendChild(svg);
		o.dest.appendChild(legend);
		for(var i = 1; i < inner_text.length; ++i)
		 {
			var vt = get_value(data[i - 1]), vb = get_value(data[i]), tmp_w0t = inner_text[i][0].getBBox().width, tmp_w0b = inner_text[i][1].getBBox().width, tmp_w1t = (0.75 * vt + 0.25 * vb) * row_max_w / sum, tmp_w1b = (0.25 * vt + 0.75 * vb) * row_max_w / sum;
			if(tmp_w0t >= tmp_w1t || tmp_w0b >= tmp_w1b)
			 {
				var x = row_half_max_w - Math.max(tmp_w0t, tmp_w0b) / 2 - (vt + vb) * row_max_w * 0.5 / sum;
				inner_text[i][0].setAttributeNS(null, "x", x);
				inner_text[i][1].setAttributeNS(null, "x", x);
			 }
		 }
		inner_text = [];
	 };
	this.SetData = function(d)
	 {
		data = arguments.length > 1 ? arguments : d;
		return this;
	 };
	this.GetData = function(){return data;};
}