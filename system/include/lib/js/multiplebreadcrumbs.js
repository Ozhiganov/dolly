function MultipleBreadCrumbs(block)
{
	var dd_change = function()
	 {
		var block = this.parents('.multiple_bread_crumbs__group').first(), crumbs = block.find('.dropdown_list'), name = crumbs.find('[type="hidden"][name]').attr('name'), src = this, index = this.index(), input = this.find('[type="hidden"]'), id = input.val(), prev = null, prev_id = null;
		crumbs.attr('data-disabled', 'true');
		if(index > 0) prev = crumbs.eq(index - 1);
		for(var i = index + 1; i < crumbs.length; ++i) crumbs.eq(i).remove();
		if(prev) prev_id = prev.find('[type="hidden"]').val();
		ms.xpost({parent_id: id ? id : prev_id, name: name}, function(x){
			var items = x.find('item');
			if(!id && prev_id);
			else if(items.length)
			 {
				var dd = new DropDownList($(DropDownList.CreateMarkup()).insertAfter(src), {onchange:dd_change});
				dd.AddOption('', '—', true);
				items.each(function(){
					var n = $(this);
					dd.AddOption(n.attr('value'), n.text());
				});
			 }
			var b = src.parents('.multiple_bread_crumbs').first(), groups = b.find('.multiple_bread_crumbs__group');
			if(!groups.filter(function(){return !$(this).find('.dropdown_list').first().find('[type="hidden"]').val();}).length)
			 {
				var n = groups.first().clone();
				n.find('.dropdown_list').each(function(){new DropDownList(this, {onchange:dd_change});}).removeAttr('data-disabled').find('.dropdown_list__item').first().click();
				n.find('.delete_block').MSUIDeleteBlock(MultipleBreadCrumbs.DelConf);
				b.append(n);
				if(typeof(MSDragNDropManager) != 'undefined')
				 {
					n = n.get(0);
					var drag = MSDragNDropManager.InitItem(MultipleBreadCrumbs.DragableItem.Create(n.lastChild));
					n.IgnoreX = MultipleBreadCrumbs.DragableItem.IgnoreX;
					groups.each(function(){drag.AttachTarget(this);this.lastChild.AttachTarget(n);});
				 }
			 }
			block.find('.dropdown_list').removeAttr('data-disabled').find('[type="hidden"][name]').removeAttr('name');
			input.attr('name', name);
		}, 'getmlselectdata');
	 };
	$(block).find('.dropdown_list').each(function(){new DropDownList(this, {onchange:dd_change});});
}

MultipleBreadCrumbs.DragableItem = new (function(){
	var OnStartDrag = function(x, y){$(this.parentNode).addClass('_captured');}, OnEndDrag = function(destiny){$(this.parentNode).removeClass('_captured');};
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
		 };
		return item;
	 };
	this.IgnoreX = function(){return true;};
});

MultipleBreadCrumbs.DelConf = {
	ondelete:function(){ this.find('.dropdown_list').attr('data-disabled', 'true'); this.find('input').prop('disabled', true);},
	onundo:function(){ this.find('.dropdown_list').removeAttr('data-disabled'); this.find('input').prop('disabled', false);}
};


$(function(){
	$('.multiple_bread_crumbs__group').each(function(){new MultipleBreadCrumbs(this);}).find('.delete_block').MSUIDeleteBlock(MultipleBreadCrumbs.DelConf);
	if(typeof(MSDragNDropManager) != 'undefined')
	 {
		MSDragNDropManager.Init();
		$('.multiple_bread_crumbs').each(function(){
			var rows = $(this).find('.multiple_bread_crumbs__group');
			rows.each(function(i){
				var drag = MSDragNDropManager.InitItem(MultipleBreadCrumbs.DragableItem.Create(this.lastChild));
				this.IgnoreX = MultipleBreadCrumbs.DragableItem.IgnoreX;
				rows.each(function(k){if(k != i) drag.AttachTarget(this);});
			});
		});
	 }
});