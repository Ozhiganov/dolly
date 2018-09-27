function MSDBTable(id, row_opts, cell_opts, input_group_opts, btn_opts, sort)
{
	var stop = function(evt){evt.stopPropagation();}, boxes = $('#' + id + ' tbody input[type="checkbox"], #' + id + ' tbody input[type="radio"]').bind('change click', stop), get_id = function(){return this.id.slice(id.length + 5);}, rows = $('#' + id + ' tbody>tr').each(function(){this.GetId = get_id;}),
	get_url_tpl = function(tpl)
	 {
		if(tpl.indexOf('{id}') == -1) tpl = (tpl ? tpl + ('/' == tpl[tpl.length - 1] ? '' : '/') : '') + '?id={id}';
		return tpl;
	 };
	$('#' + id + ' tfoot tr:last input').each(function(){
		if('submit' == this.type)
		 {
			this.__onsubmit = btn_opts && btn_opts[this.name] ? btn_opts[this.name] : function(){return true;};
			$(this).click(function(){this.form.onsubmit = this.__onsubmit;});
		 }
		else if(btn_opts && btn_opts[this.name]) $(this).click(btn_opts[this.name]);
	 });
	if(row_opts)
	 {
		var row_click_input = function(name){this.Run = function(evt){$(this).find('input[name="' + name + '"]').click();};};
		if(row_opts.inputs) rows.click((new row_click_input(row_opts.inputs)).Run);
		if('undefined' != typeof(row_opts.click))
		 {
			if(typeof(row_opts.click) == 'string')
			 {
				var wrap = function(i){return cell_opts && cell_opts[i] || this.getElementsByTagName('a').length ? false : '<a class="cell_wr" href="' + get_url_tpl(row_opts.click).replace('{id}', this.parentNode.GetId()) + '" />';},
				get_class = function(i){return cell_opts && cell_opts[i] || this.getElementsByTagName('a').length ? false : 'wr';};
				rows.each(function(){$(this.cells).addClass(get_class).wrapInner(wrap);});
			 }
			else rows.click(row_opts.click);
		 }
	 }
	if(cell_opts)
	 {
		var checkbox_click = function(name){this.Run = function(evt){$(this.parentNode).find('input[name="' + name + '"]').click();};};
		for(var i in cell_opts) if(cell_opts[i].inputs) cell_opts[i].input_click = (new checkbox_click(cell_opts[i].inputs)).Run;
		$('#' + id + ' tbody>tr').each(function(){
			for(var i in cell_opts)
			 {
				var c = $(this.cells[i]).click(stop);
				if(cell_opts[i].input_click) c.click(cell_opts[i].input_click);
				if('undefined' != typeof(cell_opts[i].click))
				 {
					if(typeof(cell_opts[i].click) == 'string') c.wrapInner(function(){return '<a class="cell_wr" href="' + get_url_tpl(cell_opts[i].click).replace('{id}', this.parentNode.GetId()) + '" />';}).addClass('wr');
					else c.click(cell_opts[i].click);
				 }
			 }
		});
	 }
	if(input_group_opts)
	 {
		var checkbox_click = function(names)
		 {
			this.Run = function(evt)
			 {
				var disabled = true, selectors = [];
				$('#' + id + ' tbody input[name="' + this.name + '"]:checkbox').each(function(){if(this.checked) return disabled = false;});
				for(var i = 0; i < names.length; ++i) selectors.push('#' + id + ' tfoot .btns [name="' + names[i] + '"]');
				$(selectors.join(', ')).prop('disabled', disabled);
			 };
		 },
		radio_click = function(name)
		 {
			this.Run = function(evt){};
		 };
		boxes.each(function(){
			if(input_group_opts[this.name])
			 {
				var o = input_group_opts[this.name], b = $(this);
				if(o.button)
				 {
					if('checkbox' == this.type) b.change((new checkbox_click(o.button)).Run);
					else if('radio' == this.type) b.change((new radio_click(o.button)).Run);
				 }
				if(o.click) b.change(o.click);
			 }
		 });
	 }
	$('#' + id + ' thead input[type="checkbox"].select_all').attr('title', 'Отметить все').each(function(){
		var index = 1, th = this.parentNode;
		th.onclick = (new (function(box){this.Run = function(){box.click();};})(this)).Run;
		while(th = th.previousSibling)
		 {
			var colspan = th.getAttribute('colspan');
			index += colspan ? parseInt(colspan) : 1;
		 }
		$(this).click((new (function(selector, boxes){
			boxes.change(function(){
				if(boxes.filter(':checked').length == boxes.length)
				 {
					selector.checked = true;
					selector.title = 'Снять отметку';
					selector.className = 'select_all';
				 }
				else if(boxes.filter(':not(:checked)').length == boxes.length)
				 {
					selector.checked = false;
					selector.title = 'Отметить все';
					selector.className = 'select_all';
				 }
				else selector.className = 'select_all grayed';
			});
			this.Run = function(evt)
			 {
				boxes.prop('checked', this.checked).change();
				this.title = this.checked ? 'Снять отметку' : 'Отметить все';
				this.className = 'select_all';
				evt.stopPropagation();
			 };
		})(this, $('#' + id + ' tbody td:nth-child(' + index + ') input[type="checkbox"]'))).Run);
	});
	if(sort)
	 {
		var IgnoreX = function(){return true;}, DragableItem = new (function(){
			var OnStartDrag = function(x, y){this.parentNode.className = 'captured';}, OnEndDrag = function(destiny){this.parentNode.removeAttribute('class');};
			this.Create = function(item)
			 {
				item.SetPosition = function(){};
				item.OnStartDrag = OnStartDrag;
				item.OnEndDrag = OnEndDrag;
				item.OnDragOver = function(dest)
				 {
					dest = dest[0];
					var tbody = dest.parentNode, row = this.parentNode, before;
					$(tbody.childNodes).each(function(){
						if(this == dest) { before = false; return false; }
						else if(this == row) { before = true; return false; }
					});
					if(before)
					 {
						if(dest.nextSibling) tbody.insertBefore(row, dest.nextSibling);
						else tbody.appendChild(row);
					 }
					else tbody.insertBefore(row, dest);
					document.getElementById(id + '_save_order').disabled = false;
				 };
				return item;
			 };
		});
		MSDragNDropManager.Init();
		var rows = $('#' + id + ' tbody>tr');
		rows.each(function(i){
			var drag = MSDragNDropManager.InitItem(DragableItem.Create(this.lastChild));
			this.IgnoreX = IgnoreX;
			rows.each(function(k){if(k != i) drag.AttachTarget(this);});
		});
	 }
}
MSDBTable.MarkForDeletion = function(){this.parentNode.parentNode.className = this.checked ? 'deleting' : '';};
MSDBTable.SaveOrder = function()
{
	var items = [], tbl = this.parentNode.parentNode.parentNode.parentNode.parentNode, rows = tbl.tBodies[0].rows, __this_obj = this;
	for(var i = 0; i < rows.length; ++i) items.push(rows[i].GetId());
	ms.post({order:items.join('|'), msdbtable_id:tbl.id}, function(){__this_obj.disabled = true;}, 'set_order');
};
MSDBTable.CheckBoxClick = function(){$(this).find('input[type="checkbox"]').click();};