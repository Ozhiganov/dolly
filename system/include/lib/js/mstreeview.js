(function($){
$.fn.addStates = function(states, active)
 {
	var obj = this;
	return this.each(function(){
		if(!this.__msse_states) this.__msse_states = {};
		for(var state_name in states) this.__msse_states[state_name] = states[state_name];
		if(active) obj.setState(active);
	});
 };
$.fn.setState = function(name){return this.each(function(){this.__msse_states[name].call(this);});};
})(jQuery);

function MSTreeView(id, data_source, o)
{
	o = $.extend({}, {'read_only':false}, o);
	if(!MSTreeView.Items) MSTreeView.Items = {};
	if(MSTreeView.Items[id]) throw new Error(MSTreeView.Lang.duplicate_tree_id(id));
	MSTreeView.Items[id] = this;
	var tree_view = this, lang_pack = MSTreeView.Lang, stop_evt = function(evt){evt.stopPropagation();}, tvNodeList = function()
	 {
		var nodes = {}, length = 0;
		this.Add = function(id, node)
		 {
			if(typeof(nodes[id]) != 'undefined') throw new Error(lang_pack.duplicate_node_id(id));
			nodes[id] = node;
			++length;
		 };
		if(!o.read_only) this.Remove = function(id)
		 {
			if(typeof(nodes[id]) != 'undefined')
			 {
				delete(nodes[id]);
				--length;
			 }
		 };
		this.GetLength = function(){return length;};
		this.Get = function(id){return nodes[id];};
	 },
	all_nodes = new tvNodeList(),
	tvNode = function(id, title, has_children)
	 {
		all_nodes.Add(id, this);
		var dom_node, parent_node, child_nodes = new tvNodeList(), GetNodesContainer = function(){return dom_node.children().last().hasClass("ul") ? dom_node.children().last().children().last() : $("<ul />").appendTo($("<div />").addClass("ul").bind('onclick onmouseout onmouseover', stop_evt).appendTo(dom_node));},
		Open = function()
		 {
			this.Select().Expand();
			data_source.OnOpenNode(this);
		 },
		__this_node = this;
		this.GetTree = function(){return tree_view;};
		this.HasChildNodes = function(){return !!child_nodes.GetLength();};
		this.AddNode = function(node, before)
		 {
			if(!o.read_only)
			 {
				var p = node.GetParent();
				if(p) p.RemoveNode(this);
			 }
			node.SetParent(this);
			child_nodes.Add(node.GetId(), node);
			GetNodesContainer()[before ? 'prepend' : 'append'](node.GetDOMNode());
			this.Expand();
		 };
		this.CreateNode = function(id, title, has_children){this.AddNode(new tvNode(id, title, has_children));};
		if(!o.read_only) this.RemoveNode = function(node)
		 {
			child_nodes.Remove(node.GetId());
			if(!this.HasChildNodes()) this.HideBtn();
			return this;
		 };
		this.GetDOMNode = function(){return dom_node;};
		this.Select = function()
		 {
			if(tvNode.selected) tvNode.selected.GetDOMNode().children().first().removeAttr('class');
			dom_node.children().first().addClass("selected");
			if(!o.read_only)
			 {
				if(this.IsRoot())
				 {
					btns.edit_node.setState('st_disabled');
					btns.delete_node.setState('st_disabled');
				 }
				else
				 {
					btns.edit_node.setState('st_enabled');
					btns.delete_node.setState('st_enabled');
				 }
			 }
			tvNode.selected = this;
			return this;
		 };
		this.Open = function(){Open.call(__this_node);};
		if(typeof(id) != 'undefined')
		 {
			var plus_minus = $('<em />').addStates({st_expanded:function(){$(this).attr('class', 'expanded').click(function(evt){stop_evt(evt);__this_node.Collapse();});}, st_collapsed:function(){$(this).attr('class', 'collapsed').click(function(evt){stop_evt(evt);__this_node.Expand();});}, st_none:function(){$(this).attr('class', '').click(stop_evt);}}).setState(has_children ? 'st_collapsed' : 'st_none'),
			title_node = document.createTextNode(title);
			dom_node = $("<li />").append($("<h6 />").click(function(){Open.call(__this_node);}).append(plus_minus, $("<span />").append(title_node)));
			this.SetParent = function(node){parent_node = node;};
			this.GetParent = function(){return parent_node;};
			this.IsRoot = function(){return false;};
			this.Expand = function()
			 {
				if(this.HasChildNodes())
				 {
					plus_minus.setState('st_expanded');
					dom_node.children().last().css('display', 'block');
				 }
				else data_source.OnExpandNode(this);
				return this;
			 };
			this.Collapse = function()
			 {
				if(this.HasChildNodes())
				 {
					plus_minus.setState('st_collapsed');
					dom_node.children().last().css('display', 'none');
				 }
			 };
			this.HideBtn = function(){plus_minus.setState('st_none');};
			if(!o.read_only) this.Remove = function()
			 {
				var p = this.GetParent();
				p.Select().RemoveNode(this);
				dom_node.remove();
				all_nodes.Remove(this.GetId());
			 };
			this.GetId = function(){return id;};
			this.GetTitle = function(){return title;};
			this.SetTitle = function(val){title_node.nodeValue = title = val;};
		 }
		else
		 {
			dom_node = $('<div />').addClass("inner").append($('<h6 />').click(function(){Open.call(__this_node);}).append($('<span />').text(lang_pack.root_title())));
			this.IsRoot = function(){return true;};
			this.GetId = function(){return null;};
			this.Expand = function(){if(!this.HasChildNodes()) data_source.OnExpandNode(this);return this;};
			this.HideBtn = function(){};
			this.Select().Expand();
		 }
	 },
	tvRootNode;
	tvNode.selected = null;
	if(!data_source) data_source = new(function()
	 {
		this.ShowInsertForm = function()
		 {
			var title = prompt(lang_pack.new_sect_title(), "");
			if(title) this.OnCreateSuccess(all_nodes.GetLength(), title);
			else this.OnInsertCancel();
		 };
		this.ShowUpdateForm = function(node)
		 {
			var title = prompt(lang_pack.new_title(), node.GetTitle());
			if(title) this.OnUpdateSuccess(node.GetId(), title);
			else this.OnUpdateCancel();
		 };
		this.DeleteData = function(node){this.OnDeleteSuccess(node);};
		this.OnExpandNode = this.OnOpenNode = function(){};
	 });
	data_source.OnCreateSuccess = function(id, title)
	 {
		tvNode.selected.AddNode(new tvNode(id, title), true);
		tree_view.Enable();
	 };
	this.Disable = function(){tvRootNode.children().first().addClass('visible');};
	this.Enable = function(){tvRootNode.children().first().removeAttr('class');};
	this.GetNode = function(id){return all_nodes.Get(id);};
	this.GetDataSource = function(){return data_source;};
	data_source.OnInsertCancel = data_source.OnUpdateCancel = data_source.OnInsertError = data_source.OnUpdateError = data_source.OnDeleteError = tree_view.Enable;
	tvRootNode = $('<div />').addClass('mstreeview').attr('id', id).append($('<p />'));
	if(!o.read_only)
	 {
		data_source.OnUpdateSuccess = function(id, title)
		 {
			all_nodes.Get(id).SetTitle(title);
			tree_view.Enable();
		 };
		data_source.OnDeleteSuccess = function(node)
		 {
			node.Remove();
			tree_view.Enable();
		 };
		var DeleteNode = function()
		 {
			var node = tvNode.selected, title = node.GetTitle();
			while((node = node.GetParent()) && !node.IsRoot()) title = node.GetTitle() + ' → ' + title;
			if(!confirm(lang_pack.confirm_deletion(title))) return;
			tree_view.Disable();
			data_source.DeleteData(tvNode.selected);
		 },
		RenameNode = function()
		 {
			tree_view.Disable();
			data_source.ShowUpdateForm(tvNode.selected);
		 },
		btns =
		 {
			create_node : $('<em />').click(function(){tree_view.Disable();data_source.ShowInsertForm(tvNode.selected.GetId());}).attr({title:lang_pack.create_node(), 'class':'create'}),
			edit_node : $('<em />').addStates({st_enabled:function(){$(this).unbind('click').click(RenameNode).attr({title:lang_pack.edit_node(), 'class':'edit'});}, st_disabled:function(){$(this).unbind('click').attr({title:'', 'class':'edit disabled'});}}, 'st_disabled'),
			delete_node : $('<em />').addStates({st_enabled:function(){$(this).unbind('click').click(DeleteNode).attr({title:lang_pack.delete_node(), 'class':'delete'});}, st_disabled:function(){$(this).unbind('click').attr({title:'', 'class':'delete disabled'});}}, 'st_disabled')
		 };
		tvRootNode.append($('<div class="mstreeview__top" />').append(btns.delete_node, btns.edit_node, btns.create_node));
	 }
	$('#' + id).replaceWith(tvRootNode.append((new tvNode()).GetDOMNode()));
}

MSTreeView.Lang = 
{
	delete_node : function(){return "Удалить";},
	edit_node : function(){return "Переименовать";},
	create_node : function(){return "Создать";},
	root_title : function(){return "Корневой раздел";},
	empty_title_error : function(){return "Напишите название!";},
	new_title : function(){return "Новое название:";},
	renamed : function(){return "Раздел переименован";},
	deleted : function(){return "Раздел удалён";},
	confirm_deletion : function(title){return "Удалить раздел «" + title + "» и всё его содержимое?";},
	new_sect_title : function(){return "Название нового раздела:";},
	duplicate_node_id : function(id){return "Узел с идентификатором `" + id + "` уже существует";},
	duplicate_tree_id : function(id){return "Дерево с идентификатором `" + id + "` уже существует";}
};